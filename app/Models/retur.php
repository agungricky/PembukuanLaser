<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class retur extends Model
{
    protected $fillable = ['per_produk_id', 'diterima'];

    public function pesanan_per_produk(){
        return $this->belongsTo(PesananPerProduk::class, 'per_produk_id', 'id_per_produk');
    }
}
