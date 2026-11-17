<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PesanChatKlarifikasiPacking extends Model
{
    protected $table = 'pesan_chat_klarifikasi_packings';

    protected $primaryKey = 'id';

    protected $fillable = [
        'chat_id',
        'pengirim_user_id',
        'pesan',
        'dibaca_at',
    ];

    protected $casts = [
        'chat_id' => 'integer',
        'pengirim_user_id' => 'integer',
        'dibaca_at' => 'datetime',
    ];

    /**
     * Relasi ke percakapan utama.
     */
    public function chat(): BelongsTo
    {
        return $this->belongsTo(
            ChatKlarifikasiPacking::class,
            'chat_id',
            'id'
        );
    }

    /**
     * Relasi ke pengguna yang mengirim pesan.
     */
    public function pengirim(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'pengirim_user_id',
            'id'
        );
    }

    /**
     * Mengetahui apakah pesan sudah dibaca.
     */
    public function getSudahDibacaAttribute(): bool
    {
        return $this->dibaca_at !== null;
    }
}
