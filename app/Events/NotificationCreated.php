<?php

namespace App\Events;

use App\Models\Notification;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotificationCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Notification $notification;

    public function __construct(Notification $notification)
    {
        $this->notification = $notification;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('notifications.' . $this->notification->user_id),
        ];
    }

    // Wajib diisi — kalau tidak, Laravel pakai nama class penuh
    // (App\Events\NotificationCreated), tidak match dengan Flutter
    // yang expect event 'notification.created'.
    public function broadcastAs(): string
    {
        return 'notification.created';
    }

    // Wajib diisi supaya bentuk payload-nya {"data": {...}}
    // sesuai yang Flutter parse: payload['data']
    public function broadcastWith(): array
    {
        return [
            'data' => [
                'id'      => $this->notification->id,
                'title'   => $this->notification->title,
                'message' => $this->notification->message,
                'type'    => $this->notification->type,
                'is_read' => $this->notification->is_read,
            ],
        ];
    }
}