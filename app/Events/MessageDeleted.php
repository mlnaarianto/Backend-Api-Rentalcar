<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageDeleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $messageId,
        public int $chatId,
    ) {}

    // 🟢 DIPERBAIKI: sekarang broadcast ke DUA channel, sama seperti
    // MessageSent -> "chat.{id}" didengar Flutter (Penyewa/Perental yang
    // lagi buka room ini), dan "admin-chat-list" didengar SEMUA admin
    // Filament yang lagi buka ManageChat, dari mana pun pesan itu dihapus
    // (baik dihapus dari Flutter maupun dari Filament sendiri).
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chat.' . $this->chatId),
            new PrivateChannel('admin-chat-list'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.deleted';
    }

    public function broadcastWith(): array
    {
        return [
            'message_id' => $this->messageId,
            'chat_id' => $this->chatId,
        ];
    }
}