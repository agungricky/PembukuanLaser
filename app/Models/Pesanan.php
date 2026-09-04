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
        'input_at',
        'no_resi',

        'resi_printed_at',
        'resi_last_printed_at',
        'resi_printed_by',
        'resi_print_count',

        'id_toko',
        'id_user',
        'id_user_kirim',
        'tanggal_kirim',

        'nama_pembeli',
        'username',
        'kurir',
        'status',
        'status_cek',

        'total_hpp',
        'total_harga',
        'total_admin',
        'pencairan',

        'notes',
        'tag',

        'batas_kirim_at',
        'batas_kirim_source',
        'batas_kirim_raw',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'input_at' => 'datetime',
        'tanggal_kirim' => 'datetime',

        'resi_printed_at' => 'datetime',
        'resi_last_printed_at' => 'datetime',
        'resi_print_count' => 'integer',

        'batas_kirim_at' => 'datetime',

        'status_cek' => 'boolean',

        'total_hpp' => 'decimal:2',
        'total_harga' => 'decimal:2',
        'total_admin' => 'decimal:2',
        'pencairan' => 'decimal:2',
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

    public function kesalahan()
    {
        return $this->belongsTo(
            kesalahan::class,
            'no_pesanan',
            'no_pesanan'
        );
    }

    public function pesanan_per_produk()
    {
        return $this->hasMany(
            PesananPerProduk::class,
            'no_pesanan',
            'no_pesanan'
        );
    }

    public function resiPages()
    {
        return $this->hasMany(
            ResiPage::class,
            'no_pesanan',
            'no_pesanan'
        )
            ->orderBy('urutan')
            ->orderBy('halaman');
    }

    public function resiPrinter()
    {
        return $this->belongsTo(
            User::class,
            'resi_printed_by',
            'id'
        )->withTrashed();
    }
}
