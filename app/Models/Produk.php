<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Produk extends Model
{
    protected $table = 'produk';
    protected $primaryKey = 'sku';

    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'sku',
        'nama_produk',
        'variasi',
        'hpp',
        'status',
        'kategori_id',
    ];

    protected $casts = [
        'hpp' => 'decimal:2',
    ];


    public function stok_produk()
    {
        return $this->hasOne(stok_produk::class, 'sku_id', 'sku');
    }

    public function pesanan_per_produk(){
        return $this->hasMany(PesananPerProduk::class, 'sku', 'sku');
    }

    public function kategori()
    {
        return $this->belongsTo(
            kategori::class,
            'kategori_id',
            'id'
        );
    }
}
