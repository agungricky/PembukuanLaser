<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PengajuanHapusPesanan extends Model
{
    protected $table = 'pengajuan_hapus_pesanans';

    protected $fillable = [
        'no_pesanan',
        'keterangan',
    ];

    public function pesanan()
    {
        return $this->belongsTo(
            Pesanan::class,
            'no_pesanan',
            'no_pesanan'
        );
    }
}
