<?php

namespace App\Events;

use App\Models\Notification;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotificationCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Notification $notification;

    public function __construct(Notification $notification)
    {
        $this->notification = $notification;
    }

    // Channel privat per-user, supaya notifikasi user A tidak bisa
    // "didengar" oleh user B.
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('notifications.' . $this->notification->user_id),
        ];
    }

    // Nama event yang didengarkan di FE, biar gak perlu prefix App\Events\...
    public function broadcastAs(): string
    {
        return 'notification.created';
    }

    // Data yang dikirim ke FE — bentuknya sama persis dengan yang
    // sudah dipakai Flutter & React sekarang (title, message, type, dll)
    public function broadcastWith(): array
    {
        return [
            'data' => $this->notification,
        ];
    }
}