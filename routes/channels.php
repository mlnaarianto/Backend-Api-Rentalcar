<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\Chat;

// Channel privat per-user untuk notifikasi.
Broadcast::channel('notifications.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

// Channel privat untuk real-time chat per room
Broadcast::channel('chat.{chatId}', function ($user, $chatId) {
    // 🟢 DIPERBAIKI: sebelumnya pakai Chat::find($chatId) yang HANYA
    // mencocokkan primary key "id". Padahal ChatController (getMessages
    // & sendMessage) mendukung $chatId berupa id numerik ATAUPUN string
    // "room_identifier" custom. Kalau Flutter mengirim chatId dalam
    // bentuk room_identifier (bukan id numerik asli), find() akan selalu
    // return null -> closure ini return false -> Reverb balas 403 ke
    // endpoint /broadcasting/auth, dan Flutter gagal subscribe channel
    // ini walau chat-nya sebenarnya valid & bisa diakses lewat REST API.
    //
    // Disamakan persis dengan logic pencarian yang sudah dipakai di
    // ChatController::getMessages() supaya konsisten.
    $chat = Chat::where('id', $chatId)
        ->orWhere('room_identifier', $chatId)
        ->first();

    if (!$chat) {
        return false;
    }

    // TODO (opsional, untuk keamanan lebih ketat ke depannya):
    // pastikan $user memang salah satu partisipan chat ini, bukan cuma
    // "chat-nya ada di database". Sesuaikan dengan struktur kolom tabel
    // chats Anda (mis. user_one_id / user_two_id, atau relasi
    // participants), lalu tambahkan pengecekan role admin/perental juga
    // supaya admin tetap bisa subscribe ke room manapun dari Filament:
    //
    // return $chat->user_one_id === $user->id
    //     || $chat->user_two_id === $user->id
    //     || $user->hasRole('Super Admin')
    //     || $user->hasRole('Perental');

    // Untuk sekarang, tetap permissive seperti kode awal (asal chat-nya
    // ada & user sudah terautentikasi):
    return true;
});

// Channel bersama khusus admin panel (Filament).
// Semua pesan dari SELURUH room chat dipancarkan ke sini juga (lihat
// App\Events\MessageSent::broadcastOn()), supaya inbox admin bisa realtime
// tanpa perlu subscribe satu per satu ke tiap room & tanpa polling.
// Hanya role Super Admin / Perental yang boleh dengar channel ini.
Broadcast::channel('admin-chat-list', function ($user) {
    return $user->hasRole('Super Admin') || $user->hasRole('Perental');
});