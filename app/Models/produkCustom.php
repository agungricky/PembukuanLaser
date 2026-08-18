<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class produkCustom extends Model
{
    protected $fillable = ['produk_id', 'harga_jual', 'nama_produk', 'jumlah', 'status', 'keterangan'];

    public function produk(){
        return $this->belongsTo(Produk::class, 'sku_id', 'sku');
    }
}
