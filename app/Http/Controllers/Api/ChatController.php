<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\Message;
use App\Models\User;
use App\Models\Notification;
use App\Events\MessageSent;
use App\Events\MessageDeleted;
use App\Events\NotificationCreated;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    /**
     * Ambil daftar pesan berdasarkan chat_id (bisa berupa angka ID asli atau string unik room).
     */
    public function getMessages($chatId, Request $request)
    {
        // Cari chat berdasarkan id (jika angka) atau buat/cari room berdasarkan string identifier
        $chat = Chat::where('id', $chatId)
            ->orWhere('room_identifier', $chatId)
            ->first();

        if (!$chat) {
            return response()->json([
                'status' => 'success',
                'data' => []
            ], 200);
        }

        $messages = Message::where('chat_id', $chat->id)
            ->with('sender:id,name,avatar')
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $messages,
        ], 200);
    }

    /**
     * Resolve chatId (bisa berupa id numeric ATAU room_identifier
     * string custom) menjadi id numeric ASLI dari tabel chats.
     *
     * Kenapa endpoint ini dibutuhkan:
     * Event MessageSent selalu di-broadcast ke channel "chat.{id}" dengan
     * $message->chat_id, yang NILAINYA SELALU numeric id asli (lihat
     * sendMessage() di bawah -> $realChatId = $chat->id). Kalau klien
     * (Flutter) subscribe ke channel Reverb menggunakan chatId dalam
     * bentuk string custom (bukan id numeric asli), nama channel yang
     * di-subscribe klien ("private-chat.ROOM_STRING") akan BEDA dengan
     * channel yang sungguhan di-broadcast server ("private-chat.5"),
     * sehingga klien tidak akan pernah menerima event walau proses
     * auth-nya sendiri sukses. Endpoint ini dipanggil klien SEBELUM
     * subscribe ke Reverb, supaya nama channel yang dipakai subscribe
     * selalu konsisten dengan yang dipakai broadcast.
     *
     * Response chat_id bisa null kalau room memang belum pernah dibuat
     * sama sekali (belum ada pesan pertama yang dikirim lewat
     * sendMessage(), yang baru men-trigger firstOrCreate()).
     */
    public function resolveChatId($chatId, Request $request)
    {
        $chat = Chat::where('id', $chatId)
            ->orWhere('room_identifier', $chatId)
            ->first();

        return response()->json([
            'status' => 'success',
            'chat_id' => $chat?->id,
        ], 200);
    }

    /**
     * Kirim pesan baru dan otomatis buat room jika belum terdaftar di database.
     */
    public function sendMessage(Request $request)
    {
        $request->validate([
            'chat_id' => 'required|string',
            'receiver_id' => 'required|exists:users,id',
            'text' => 'required|string',
        ]);

        $senderId = $request->user()->id;
        $chatIdParam = $request->chat_id;

        // 1. Cari atau buat room chat di database berdasarkan string unik atau ID angka
        $chat = Chat::firstOrCreate(
            is_numeric($chatIdParam)
                ? ['id' => $chatIdParam]
                : ['room_identifier' => $chatIdParam],
            [
                'last_message' => $request->text,
                'last_sender_id' => $senderId,
            ]
        );

        $realChatId = $chat->id;

        // 2. Simpan pesan ke tabel messages
        $message = Message::create([
            'chat_id' => $realChatId,
            'sender_id' => $senderId,
            'text' => $request->text,
        ]);

        // 3. Update data room chat terakhir
        $chat->update([
            'last_message' => $request->text,
            'last_sender_id' => $senderId,
            'updated_at' => now(),
        ]);

        // 4. Broadcast real-time event via Laravel Reverb ke klien lain
        //    (ini yang bikin bubble chat langsung muncul di layar penerima
        //    KALAU dia sedang membuka halaman chat room ini)
        broadcast(new MessageSent($message))->toOthers();

        // 5. 🟢 Buat record Notification + fire NotificationCreated,
        // menggantikan kode FCM manual yang lama.
        //
        // a) Realtime badge & popup di NotifikasiPage otomatis nyala lewat
        //    channel "notifications.{receiver_id}" (channel yang SAMA
        //    dipakai notifikasi booking/payment, sudah didengarkan oleh
        //    NotificationSocketService di Flutter -- TIDAK PERLU ubah
        //    kode Flutter sama sekali).
        // b) Push notification FCM otomatis terkirim lewat listener
        //    SendPushNotification yang sudah ada (satu jalur push untuk
        //    SEMUA jenis notifikasi, bukan kode FCM terpisah khusus chat).
        //
        // Kenapa PERLU tetap dibuat walau MessageSent sudah broadcast?
        // Karena MessageSent cuma didengar KALAU penerima sedang membuka
        // halaman chat room itu. Kalau dia sedang di halaman lain (dashboard,
        // booking, dst) atau app di background, dia TIDAK subscribe ke
        // channel "chat.{id}" itu -- makanya tetap butuh notifikasi umum
        // lewat channel "notifications.{userId}" yang selalu didengarkan
        // sepanjang sesi app (lihat NotificationSocketService.connect()).
        $sender = $request->user();
        $snippet = mb_strlen($request->text) > 80
            ? mb_substr($request->text, 0, 80) . '...'
            : $request->text;

        $notification = Notification::create([
            'user_id' => $request->receiver_id,
            'title'   => $sender->name,
            'message' => $snippet,
            'type'    => 'chat_message',
            'is_read' => false,
        ]);

        event(new NotificationCreated($notification));

        return response()->json([
            'status' => 'success',
            'message' => 'Pesan terkirim',
            'data' => $message->load('sender:id,name,avatar'),
        ], 201);
    }

    /**
     * Hapus pesan secara permanen dari database (hard delete).
     *
     * Sengaja hanya sender pesan itu sendiri yang boleh menghapus (bukan
     * penerima), supaya konsisten dengan pola "hapus untuk semua" di WA.
     * Setelah row dihapus, kita broadcast MessageDeleted ke channel
     * "chat.{chat_id}" dan "admin-chat-list" supaya klien lain yang
     * sedang membuka room yang sama (Flutter maupun Filament) langsung
     * menghilangkan pesan itu dari layar tanpa perlu refresh manual.
     */
    public function deleteMessage($messageId, Request $request)
    {
        $message = Message::find($messageId);

        if (!$message) {
            return response()->json([
                'status' => 'error',
                'message' => 'Pesan tidak ditemukan',
            ], 404);
        }

        if ($message->sender_id !== $request->user()->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kamu tidak bisa menghapus pesan orang lain',
            ], 403);
        }

        $chatId = $message->chat_id;

        // Hard delete: row-nya beneran hilang dari tabel messages.
        $message->delete();

        broadcast(new MessageDeleted($messageId, $chatId))->toOthers();

        return response()->json([
            'status' => 'success',
            'message' => 'Pesan berhasil dihapus',
        ], 200);
    }
}