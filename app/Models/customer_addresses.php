<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class customer_addresses extends Model
{
    protected $fillable = [
        'no_pesanan',
        'provinsi',
        'kabupaten_kota',
        'kecamatan',
        'desa',
        'alamat_lengkap',
        'phone'
    ];

    public function pesanan(){
        return $this->belongsTo(Pesanan::class, 'no_pesanan', 'no_pesanan');
    }
}
