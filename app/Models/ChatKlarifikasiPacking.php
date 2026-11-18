<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatKlarifikasiPacking extends Model
{
    protected $table = 'chat_klarifikasi_packings';

    protected $primaryKey = 'id';

    protected $fillable = [
        'no_pesanan',
        'id_per_produk',
        'packing_user_id',
        'cs_user_id',
        'topik',
        'status',
        'pesan_terakhir_at',
    ];

    protected $casts = [
        'id_per_produk' => 'integer',
        'packing_user_id' => 'integer',
        'cs_user_id' => 'integer',
        'pesan_terakhir_at' => 'datetime',
    ];

    // Relasi ke pesanan
    public function pesanan(): BelongsTo
    {
        return $this->belongsTo(
            Pesanan::class,
            'no_pesanan',
            'no_pesanan'
        );
    }

    // Relasi ke produk dalam pesanan
    public function produkPesanan(): BelongsTo
    {
        return $this->belongsTo(
            PesananPerProduk::class,
            'id_per_produk',
            'id_per_produk'
        );
    }

    // Relasi ke akun Packing
    public function packing(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'packing_user_id',
            'id'
        );
    }

    // Relasi ke CS pemilik pesanan
    public function cs(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'cs_user_id',
            'id'
        );
    }

    // Relasi ke seluruh pesan dalam chat
    public function pesan(): HasMany
    {
        return $this->hasMany(
            PesanChatKlarifikasiPacking::class,
            'chat_id',
            'id'
        )->orderBy('id', 'asc');
    }

    // Relasi ke pesan terakhir
    public function pesanTerakhir(): HasMany
    {
        return $this->hasMany(
            PesanChatKlarifikasiPacking::class,
            'chat_id',
            'id'
        )->latest('id');
    }
}
