<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
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
        ]);

        $user = Auth::user();
        $user->update(['fcm_token' => $request->fcm_token]);

        return response()->json([
            'status'  => 'success',
            'message' => 'FCM token berhasil disimpan',
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