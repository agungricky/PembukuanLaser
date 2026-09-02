<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PesananPerProduk extends Model
{
    protected $table = 'pesanan_per_produk';
    protected $primaryKey = 'id_per_produk';
    public $timestamps = true;
    
    protected $fillable = [
        'no_pesanan',
        'nama_produk',
        'variasi',
        'jumlah',
        'hpp',
        'harga',
        'sku',
        'status_pesanan',
        'mutasi_stok_id'
    ];

    protected $casts = [
        'hpp'    => 'decimal:2',
        'harga'  => 'decimal:2',
        'jumlah' => 'integer',
    ];

    public function pesanan()
    {
        return $this->belongsTo(
            Pesanan::class,
            'no_pesanan',
            'no_pesanan'
        );
    }

    public function produk()
    {
        return $this->belongsTo(
            Produk::class,
            'sku',
            'sku'
        );
    }

    public function editorRequests()
    {
        return $this->hasMany(
            \App\Models\EditorRequest::class,
            'id_per_produk',
            'id_per_produk'
        );
    }
    public function editorPartItems()
    {
        return $this->hasMany(
            EditorPartItem::class,
            'id_per_produk',
            'id_per_produk'
        );
    }

    public function mutasi(){
        return $this->belongsTo(mutasi_stok::class, 'mutasi_stok_id', 'id');
    }

    public function retur(){
        return $this->hasOne(retur::class, 'per_produk_id', 'id_per_produk');
    }
}
