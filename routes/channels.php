<?php

use Illuminate\Support\Facades\Broadcast;

// Channel privat per-user untuk notifikasi.
// Dicek: user yang subscribe channel "notifications.{userId}" harus
// punya id yang SAMA dengan {userId} di nama channel-nya.
// Kalau tidak sama, subscribe ditolak (user A tidak bisa dengar
// notifikasi milik user B).
Broadcast::channel('notifications.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});