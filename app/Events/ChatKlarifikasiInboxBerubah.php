<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** Hanya sinyal refresh; tanpa identitas pembeli, isi pesan, atau nomor pesanan. */
class ChatKlarifikasiInboxBerubah implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public int $chatId, public array $recipientUserIds) {}

    public function broadcastOn(): array
    {
        return array_map(
            fn (int $id) => new PrivateChannel('chat-inbox-user.'.$id),
            $this->recipientUserIds
        );
    }

    public function broadcastAs(): string
    {
        return 'chat.inbox.berubah';
    }

    public function broadcastWith(): array
    {
        return ['chat_id' => $this->chatId];
    }
}
