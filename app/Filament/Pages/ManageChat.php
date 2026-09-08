<?php

namespace App\Filament\Pages;

use BackedEnum;
use App\Models\Chat;
use App\Models\Message;
use App\Models\User;
use App\Models\Notification;
use App\Events\MessageSent;
use App\Events\MessageDeleted;
use App\Events\NotificationCreated;
use App\Enums\Role;
use Filament\Pages\Page;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;

class ManageChat extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static ?string $navigationLabel = 'Customer Chat';
    protected static ?string $slug = 'customer-chats';
    protected string $view = 'filament.pages.manage-chat';

    public ?int $activeChatId = null;
    public string $newMessageText = '';

    // 🟢 DIPERBAIKI: sebelumnya pakai string literal mentah
    // ('Super Admin', 'Perental'). Kebetulan sudah cocok persis dengan
    // Role::SuperAdmin->value & Role::Perental->value, jadi tidak bug --
    // tapi rawan typo kalau ada yang edit manual di masa depan tanpa sadar
    // formatnya (kapitalisasi & spasi) harus persis sama. Disamakan pakai
    // enum, konsisten dengan Gate::before() di AppServiceProvider.
    public static function canAccess(): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        return $user->hasRole(Role::SuperAdmin->value) || $user->hasRole(Role::Perental->value);
    }

    /**
     * Daftar semua room chat, sudah termasuk nama customer & pesan terakhir.
     * Dipanggil ulang tiap kali komponen di-refresh (dipicu event WebSocket
     * lewat refreshChatList() di bawah, BUKAN timer/polling).
     *
     * 🟢 DIPERBAIKI (N+1 fix): sebelumnya resolveCustomer() dipanggil SATU
     * KALI PER CHAT di dalam ->map(), dan resolveCustomer() sendiri
     * menjalankan query User::whereIn(...)->get() setiap kali dipanggil.
     * Jadi kalau ada 30 room chat, ini menghasilkan 30 query User terpisah
     * (N+1), dan itu ikut dieksekusi ulang SETIAP KALI Livewire re-render
     * komponen ini -- termasuk setiap kali sendMessage() selesai (karena
     * Livewire menghitung ulang semua computed property yang dipakai view).
     *
     * Sekarang: kumpulkan SEMUA participant id dari SEMUA chat lebih dulu,
     * lalu ambil semua User-nya dalam SATU query saja, baru dipetakan balik
     * ke tiap chat dari collection yang sudah di-memory (tanpa query lagi).
     */
    public function getChatsProperty(): Collection
    {
        $chats = Chat::query()
            ->orderByDesc('updated_at')
            ->get();

        // Kumpulkan semua participant id dari seluruh chat sekaligus.
        $allParticipantIds = $chats
            ->flatMap(fn (Chat $chat) => $chat->participantIds())
            ->unique()
            ->values();

        // SATU query untuk semua user yang relevan, di-index by id supaya
        // lookup-nya O(1) per chat tanpa query tambahan.
        $usersById = User::whereIn('id', $allParticipantIds)
            ->get()
            ->keyBy('id');

        return $chats->map(function (Chat $chat) use ($usersById) {
            $customer = $this->resolveCustomerFromMap($chat, $usersById);

            return [
                'id' => $chat->id,
                'display_name' => $customer?->name ?? "Chat #{$chat->id}",
                'avatar' => $customer?->avatar,
                'last_message' => $chat->last_message,
                'updated_at' => $chat->updated_at,
            ];
        });
    }

    /**
     * Isi pesan untuk chat yang lagi dibuka.
     */
    public function getMessagesProperty(): Collection
    {
        if (!$this->activeChatId) {
            return collect();
        }

        return Message::where('chat_id', $this->activeChatId)
            ->with('sender:id,name,avatar')
            ->orderBy('created_at')
            ->get();
    }

    public function getActiveChatTitleProperty(): string
    {
        if (!$this->activeChatId) {
            return 'Pilih chat di samping';
        }

        $chat = Chat::find($this->activeChatId);
        if (!$chat) {
            return 'Chat tidak ditemukan';
        }

        // Dipanggil sendiri-sendiri (bukan lewat getChatsProperty()), jadi
        // tetap pakai resolveCustomer() versi query langsung -- cuma untuk
        // SATU chat yang sedang aktif, jadi tidak N+1.
        return $this->resolveCustomer($chat)?->name ?? "Chat #{$chat->id}";
    }

    public function selectChat(int $chatId): void
    {
        $this->activeChatId = $chatId;
    }

    public function sendMessage(): void
    {
        $text = trim($this->newMessageText);

        if ($text === '' || !$this->activeChatId) {
            return;
        }

        $chat = Chat::findOrFail($this->activeChatId);

        $message = Message::create([
            'chat_id' => $chat->id,
            'sender_id' => Auth::id(),
            'text' => $text,
        ]);

        $chat->update([
            'last_message' => $text,
            'last_sender_id' => Auth::id(),
            'updated_at' => now(),
        ]);

        // Event yang sama dengan yang dipakai mobile (ChatController::sendMessage),
        // jadi kalau admin balas dari sini, Flutter customer langsung nerima
        // realtime lewat Reverb tanpa perlu app tambahan apa pun.
        broadcast(new MessageSent($message))->toOthers();

        // 🟢 BARU: sama seperti ChatController::sendMessage(), tambahkan
        // notifikasi umum ke customer supaya badge/popup/list NotifikasiPage
        // di Flutter ikut nyala walau customer tidak sedang membuka halaman
        // chat room ini. Tanpa ini, MessageSent di atas cuma nyampe kalau
        // customer KEBETULAN sedang buka room chat-nya -- kalau dia lagi di
        // halaman lain atau app background, dia gak akan tahu ada balasan
        // dari admin.
        //
        // CATATAN performa: event(new NotificationCreated(...)) ini memicu
        // SendPushNotification::handle(), yang melakukan panggilan HTTP ke
        // Firebase (FCM). Karena QUEUE_CONNECTION=sync di project ini,
        // panggilan HTTP itu dieksekusi LANGSUNG di request ini juga --
        // artinya delay/jitter jaringan ke Firebase akan ikut menunda
        // response Livewire ke browser admin (ini penyebab paling mungkin
        // dari keluhan "enter/kirim kadang delay" yang terpisah dari N+1
        // fix di atas). Di luar cakupan perubahan N+1 ini, tapi solusinya
        // kalau mau dibahas lagi nanti: jadikan SendPushNotification
        // implements ShouldQueue + pindah QUEUE_CONNECTION ke database/redis
        // + jalankan queue worker.
        $customer = $this->resolveCustomer($chat);

        if ($customer) {
            $admin = Auth::user();
            $snippet = mb_strlen($text) > 80
                ? mb_substr($text, 0, 80) . '...'
                : $text;

            $notification = Notification::create([
                'user_id' => $customer->id,
                'title'   => $admin->name,
                'message' => $snippet,
                'type'    => 'chat_message',
                'is_read' => false,
            ]);

            event(new NotificationCreated($notification));
        }

        $this->newMessageText = '';

        // Refresh langsung di sisi admin sendiri juga (yang ->toOthers() sengaja
        // skip), supaya bubble pesan yang baru dikirim langsung muncul tanpa
        // nunggu event WebSocket balik ke diri sendiri.
        $this->dispatch('refresh-chat-list');
    }

    /**
     * Hapus pesan secara permanen dari sisi admin Filament.
     *
     * Sengaja hanya sender pesan itu sendiri (admin yang mengetik pesan
     * itu) yang boleh menghapus, konsisten dengan aturan yang sama di
     * ChatController::deleteMessage() untuk sisi Flutter. Admin TIDAK
     * bisa menghapus pesan milik customer dari sini.
     *
     * Setelah dihapus, broadcast MessageDeleted ke KEDUA channel supaya:
     * - customer yang lagi buka room ini di Flutter langsung kehilangan
     *   bubble pesan itu dari layar,
     * - admin lain yang lagi buka Filament (kalau ada multi-admin) juga
     *   ikut ter-refresh daftarnya.
     * ->toOthers() dipakai karena refresh untuk diri sendiri sudah
     * dilakukan manual lewat $this->dispatch('refresh-chat-list') di bawah.
     */
    public function deleteMessage(int $messageId): void
    {
        $message = Message::find($messageId);

        if (!$message) {
            return;
        }

        if ($message->sender_id !== Auth::id()) {
            // Admin cuma boleh hapus pesan balasannya sendiri, bukan pesan
            // customer. Diamkan saja tanpa error mengganggu UI.
            return;
        }

        $chatId = $message->chat_id;

        $message->delete();

        broadcast(new MessageDeleted($messageId, $chatId))->toOthers();

        $this->dispatch('refresh-chat-list');
    }

    /**
     * Dipicu dari JS (Echo) tiap kali ada pesan baru masuk ATAU pesan
     * dihapus di channel admin-chat-list — mencakup SEMUA room, bukan
     * cuma yang lagi dibuka. Method ini sengaja kosong; efeknya cukup
     * dari re-render Livewire yang otomatis menghitung ulang
     * getChatsProperty() & getMessagesProperty().
     */
    #[On('refresh-chat-list')]
    public function refreshChatList(): void
    {
        //
    }

    /**
     * Tentukan siapa "customer" (Penyewa) di chat ini, dengan MELAKUKAN
     * query User sendiri. Dipakai untuk kasus SATU chat saja (bukan
     * daftar), yaitu di getActiveChatTitleProperty() dan sendMessage() --
     * di situ memang cuma perlu resolve satu chat, jadi satu query di sini
     * tidak masalah dan TIDAK N+1.
     *
     * Untuk daftar banyak chat sekaligus (getChatsProperty()), JANGAN
     * pakai method ini di dalam loop -- pakai resolveCustomerFromMap()
     * dengan user yang sudah di-batch di luar loop.
     */
    protected function resolveCustomer(Chat $chat): ?User
    {
        $senderIds = $chat->participantIds();

        if (empty($senderIds)) {
            return null;
        }

        $users = User::whereIn('id', $senderIds)->get();

        return $users->first(fn (User $u) => $u->hasRole(Role::Penyewa->value))
            ?? $users->firstWhere('id', '!=', Auth::id());
    }

    /**
     * 🟢 BARU: versi resolveCustomer() yang TIDAK melakukan query sendiri.
     * Menerima $usersById (Collection User yang sudah di-keyBy('id')) yang
     * sudah diambil sekali untuk SEMUA chat di getChatsProperty(), sehingga
     * memanggil method ini di dalam ->map() tidak menambah query sama
     * sekali -- murni operasi in-memory.
     */
    protected function resolveCustomerFromMap(Chat $chat, Collection $usersById): ?User
    {
        $participants = collect($chat->participantIds())
            ->map(fn ($id) => $usersById->get($id))
            ->filter();

        if ($participants->isEmpty()) {
            return null;
        }

        return $participants->first(fn (User $u) => $u->hasRole(Role::Penyewa->value))
            ?? $participants->firstWhere('id', '!=', Auth::id());
    }
}