<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class mutasi_stok extends Model
{
    protected $fillable = [
        'stok_produk_id',
        'gudang_id',
        'produksi_id', 	
        'adm_penjualan_id', 
        'jenis_mutasi',
        'keterangan',
        'jumlah'
    ];

    public function stok_produk(){
        return $this->belongsTo(stok_produk::class);
    }


    public function gudang(){
        return $this->belongsTo(User::class, 'gudang_id', 'id');
    }

    public function ambil_barang(){
        return $this->belongsTo(User::class, 'adm_penjualan_id', 'id');
    }

    public function pesanan_per_produk(){
        return $this->hasMany(PesananPerProduk::class, 'mutasi_stok_id', 'id');
    }
}
