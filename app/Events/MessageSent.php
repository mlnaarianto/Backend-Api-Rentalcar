<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    // Broadcast ke DUA channel sekaligus:
    // 1. chat.{id}      -> didengar Flutter (customer/driver yang lagi buka room ini)
    // 2. admin-chat-list -> didengar SEMUA admin/perental yang lagi buka Filament,
    //    dari mana pun pesan itu berasal. Ini yang bikin inbox Filament realtime
    //    tanpa perlu subscribe satu-satu ke tiap room & tanpa polling.
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chat.' . $this->message->chat_id),
            new PrivateChannel('admin-chat-list'),
        ];
    }

    // Nama event yang akan ditangkap di sisi client (Flutter & Filament)
    public function broadcastAs(): string
    {
        return 'message.sent';
    }
}