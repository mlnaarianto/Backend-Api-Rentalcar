<?php

namespace App\Observers;

use App\Events\NotificationCreated;
use App\Models\Notification;

class NotificationObserver
{
    public function created(Notification $notification): void
{
    // Ganti dari broadcast() -> dispatch event biasa.
    // Karena NotificationCreated implements ShouldBroadcast,
    // ini otomatis: (1) jalankan SendPushNotification listener,
    // (2) broadcast ke Reverb. Dua-duanya sekaligus, satu baris.
    NotificationCreated::dispatch($notification);
}
}