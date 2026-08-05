<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pesanan extends Model
{
    protected $table = 'pesanan';

    protected $primaryKey = 'no_pesanan';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'no_pesanan',
        'tanggal',
        'no_resi',
        'id_toko',
        'id_user',
        'nama_pembeli',
        'username',
        'kurir',
        'status',
        'total_hpp',
        'total_harga',
        'total_admin',
        'pencairan',
        'notes',
        'status_cek',
        'id_user_kirim',
        'tanggal_kirim',
        'tag',
    ];

    protected $casts = [
        'tanggal'        => 'date',
        'tanggal_kirim'  => 'datetime',

        'total_hpp'      => 'decimal:2',
        'total_harga'    => 'decimal:2',
        'total_admin'    => 'decimal:2',
        'pencairan'      => 'decimal:2',
    ];

    public function produk()
    {
        return $this->hasMany(
            PesananPerProduk::class,
            'no_pesanan',
            'no_pesanan'
        );
    }

    public function toko()
    {
        return $this->belongsTo(
            Toko::class,
            'id_toko',
            'id_toko'
        );
    }

    public function user()
    {
        return $this->belongsTo(
            User::class,
            'id_user',
            'id'
        )->withTrashed();
    }

    public function userKirim()
    {
        return $this->belongsTo(
            User::class,
            'id_user_kirim',
            'id'
        )->withTrashed();
    }

    public function kesalahan(){
        return $this->belongsTo(kesalahan::class, 'no_pesanan', 'no_pesanan');
    }
}