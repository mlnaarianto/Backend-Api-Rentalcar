<?php

namespace App\Observers;

use App\Events\NotificationCreated;
use App\Models\Notification;

class NotificationObserver
{
    // Dipanggil OTOMATIS oleh Laravel setiap kali Notification::create()
    // (atau ->save() pada record baru) berhasil — di manapun itu dipanggil.
    public function created(Notification $notification): void
    {
        broadcast(new NotificationCreated($notification));
    }
}