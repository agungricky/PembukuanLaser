<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class FotoMeta extends Model
{
     protected $table = 'foto_metas';

    protected $fillable = [
        'perproduk_id',
        'foto',
        'user_id',
    ];

    public function pesanan(): BelongsTo
    {
        return $this->belongsTo(Pesanan::class, 'no_pesanan', 'no_pesanan');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function pesanan_per_produk(){
        return $this->belongsTo(PesananPerProduk::class, 'perproduk_id', 'id_per_produk');
    }
}
