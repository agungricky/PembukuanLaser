<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PesananPerProduk extends Model
{
    protected $table = 'pesanan_per_produk';
    protected $primaryKey = 'id_per_produk';
    public $timestamps = false;

    protected $fillable = [
        'no_pesanan',
        'nama_produk',
        'variasi',
        'jumlah',
        'hpp',
        'harga',
        'sku',
    ];

    protected $casts = [
        'hpp'   => 'decimal:2',
        'harga' => 'decimal:2',
        'jumlah'=> 'integer',
    ];

    public function pesanan()
    {
        return $this->belongsTo(Pesanan::class, 'no_pesanan', 'no_pesanan');
    }

    public function produk(){
        return $this->belongsTo(Produk::class, 'sku', 'sku');
    }

    
}
