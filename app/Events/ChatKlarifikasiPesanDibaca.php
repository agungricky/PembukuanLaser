<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Memberi tahu kedua peserta bahwa seorang peserta telah membaca
 * pesan hingga ID tertentu dalam satu percakapan.
 *
 * Event dikirim setelah update database berhasil (di luar transaksi).
 */
class ChatKlarifikasiPesanDibaca implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $chatId,
        public int $pembacaUserId,
        public int $lastMessageId,
        public int $jumlahDitandai,
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('packing-chat.' . $this->chatId);
    }

    public function broadcastAs(): string
    {
        return 'chat.pesan.dibaca';
    }

    public function broadcastWith(): array
    {
        return [
            'chat_id' => $this->chatId,
            'pembaca_user_id' => $this->pembacaUserId,
            'last_message_id' => $this->lastMessageId,
            'jumlah_ditandai' => $this->jumlahDitandai,
        ];
    }
}
