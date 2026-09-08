<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\UserFcmToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    // Ambil daftar notifikasi milik user yang sedang login
    public function index()
    {
        $notifications = Notification::where('user_id', Auth::id())
            ->latest()
            ->get();

        return response()->json([
            'status' => 'success',
            'data'   => $notifications,
        ], 200);
    }

    // Tandai semua atau spesifik notifikasi sebagai sudah dibaca
    public function markAsRead()
    {
        Notification::where('user_id', Auth::id())
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Notifikasi ditandai sudah dibaca',
        ], 200);
    }

    // Simpan/perbarui FCM token milik device yang sedang dipakai user
    public function updateFcmToken(Request $request)
    {
        $request->validate([
            'fcm_token' => 'required|string',
            'device_id' => 'nullable|string',
            'platform'  => 'nullable|string|in:android,ios,web',
        ]);

        $user = Auth::user();

        // 🟢 DIPERBAIKI: kunci upsert sekarang berdasarkan device
        // (user_id + device_id) kalau device_id dikirim, BUKAN lagi
        // berdasarkan token semata.
        //
        // Alasan: Firebase SDK kadang mengeluarkan token BARU untuk
        // browser/device fisik yang SAMA (misal tiap reload halaman
        // web). Kalau kuncinya cuma 'token', tiap token baru dianggap
        // "belum pernah ada" -> selalu bikin baris baru -> satu device
        // bisa numpuk puluhan baris token basi seiring waktu.
        //
        // Dengan kunci (user_id, device_id): begitu device yang sama
        // dapat token baru, baris LAMA milik device itu di-UPDATE
        // (token-nya diganti ke yang baru), bukan bikin baris baru.
        //
        // Fallback ke kunci 'token' kalau device_id tidak dikirim (mis.
        // dari client lama yang belum update, atau kasus edge lainnya)
        // -- tetap aman karena token sendiri unique secara global.
        $matchKey = $request->filled('device_id')
            ? ['user_id' => $user->id, 'device_id' => $request->device_id]
            : ['token' => $request->fcm_token];

        UserFcmToken::updateOrCreate(
            $matchKey,
            [
                'user_id'      => $user->id,
                'token'        => $request->fcm_token,
                'device_id'    => $request->device_id,
                'platform'     => $request->platform,
                'last_used_at' => now(),
            ]
        );

        return response()->json([
            'status'  => 'success',
            'message' => 'FCM token berhasil disimpan',
        ], 200);
    }

    // Hapus FCM token milik device tertentu (dipanggil saat logout)
    public function removeFcmToken(Request $request)
    {
        $request->validate([
            'fcm_token' => 'required|string',
        ]);

        UserFcmToken::where('user_id', Auth::id())
            ->where('token', $request->fcm_token)
            ->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'FCM token berhasil dihapus',
        ], 200);
    }

    // Hapus satu notifikasi spesifik berdasarkan ID
    public function destroy($id)
    {
        $notification = Notification::where('id', $id)
            ->where('user_id', Auth::id())
            ->first();

        if (!$notification) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Notifikasi tidak ditemukan atau Anda tidak memiliki akses',
            ], 404);
        }

        $notification->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Notifikasi berhasil dihapus',
        ], 200);
    }

    // Hapus semua notifikasi milik user yang sedang login
    public function clearAll()
    {
        Notification::where('user_id', Auth::id())->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Semua notifikasi berhasil dihapus',
        ], 200);
    }
}