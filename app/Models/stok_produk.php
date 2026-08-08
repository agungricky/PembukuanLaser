<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class stok_produk extends Model
{
    protected $fillable = [
        'sku_id',
        'jumlah_tersedia',
        'min_stok',
        'created_at',
        'updated_at'
    ];

    public function produk()
    {
        return $this->belongsTo(Produk::class, 'sku_id', 'sku');
    }

     public function mutasi_stok()
    {
        return $this->hasMany(mutasi_stok::class);
    }
}
