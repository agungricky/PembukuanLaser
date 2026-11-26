<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PengajuanEditPesanan extends Model
{
    protected $table = 'pengajuan_edit_pesanans';

    protected $fillable = [
        'no_pesanan',
        'tanggal_order',
        'user_id',
        'nama_pembeli',
        'total_hpp',
        'total_harga',
        'total_admin',
        'chat_masuk_at',
        'notes',
        'provinsi',
        'kabupaten_kota',
        'kecamatan',
        'desa',
        'alamat_lengkap',
        'phone',
        'jenis_transaksi',
        'cod',
        'non_cod',
        'dp',
        'por',
        'bukti_bayar',
        'status',
    ];

    protected $casts = [
        'pesanan_perproduk' => 'array',
        'tanggal_order' => 'datetime',
        'chat_masuk_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'cod' => 'decimal:2',
        'non_cod' => 'decimal:2',
        'dp' => 'decimal:2',
    ];

    public function pesanan()
    {
        return $this->belongsTo(
            Pesanan::class,
            'no_pesanan',
            'no_pesanan'
        );
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
