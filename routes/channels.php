<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\Chat;
use App\Enums\Role;

// Channel privat per-user untuk notifikasi.
Broadcast::channel('notifications.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});

// Channel privat untuk real-time chat per room
Broadcast::channel('chat.{chatId}', function ($user, $chatId) {
    $chat = Chat::where('id', $chatId)
        ->orWhere('room_identifier', $chatId)
        ->first();

    if (!$chat) {
        return false;
    }

    return true;
});

// Channel bersama khusus admin panel (Filament).
// Hanya Super Admin yang boleh dengar channel ini — Perental sengaja
// dihapus, karena akses admin panel murni untuk Super Admin saja,
// konsisten dengan Gate::before() di AppServiceProvider yang otomatis
// meloloskan Super Admin di seluruh authorization check aplikasi.
Broadcast::channel('admin-chat-list', function ($user) {
    return $user->hasRole(Role::SuperAdmin->value);
});