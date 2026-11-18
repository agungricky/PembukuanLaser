<?php

namespace App\Events;

use App\Models\PesanChatKlarifikasiPacking;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatKlarifikasiPesanDikirim implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public array $data;

    public function __construct(
        PesanChatKlarifikasiPacking $pesan
    ) {
        $pesan->loadMissing('pengirim');

        $this->data = [
            'id' => (int) $pesan->id,
            'chat_id' => (int) $pesan->chat_id,
            'pengirim_user_id' => (int) $pesan->pengirim_user_id,
            'pengirim_nama' => $pesan->pengirim?->name,
            'pesan' => $pesan->pesan,
            'dibaca_at' => $pesan->dibaca_at?->toDateTimeString(),
            'created_at' => $pesan->created_at?->toDateTimeString(),
        ];
    }

    /**
     * Channel privat untuk satu percakapan.
     */
    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel(
            'packing-chat.' . $this->data['chat_id']
        );
    }

    /**
     * Nama event yang diterima Flutter.
     */
    public function broadcastAs(): string
    {
        return 'chat.pesan.baru';
    }

    /**
     * Data yang dikirim melalui WebSocket.
     */
    public function broadcastWith(): array
    {
        return $this->data;
    }
}
