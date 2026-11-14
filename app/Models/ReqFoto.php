<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReqFoto extends Model
{
    protected $table = 'req_fotos';

    protected $fillable = [
        'per_produk_id',
        'foto',
    ];

    public function pesananPerProduk(): BelongsTo
    {
        return $this->belongsTo(PesananPerProduk::class, 'per_produk_id', 'id');
    }
}
