<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class mutasi_stok extends Model
{
    protected $fillable = [
        'stok_produk_id',
        'user_id',
        'pesanan_perproduk_id',
        'jenis_mutasi',
        'keterangan',
        'jumlah'
    ];

    public function stok_produk(){
        return $this->belongsTo(stok_produk::class);
    }

    public function user()
    {
        return $this->belongsTo(
            User::class,
            'user_id',
            'id'
        );
    }
}
