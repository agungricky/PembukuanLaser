<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Iklan extends Model
{
    protected $table = 'iklan';
    protected $primaryKey = 'no_iklan';

    public $incrementing = false;
    public $timestamps = false;
    protected $keyType = 'string';

    protected $fillable = [
        'no_iklan',
        'tanggal',
        'id_toko',
        'jumlah_pembayaran',
        'saldo',
        'metode_pembayaran',
    ];

    protected $casts = [
        'tanggal'           => 'date',
        'jumlah_pembayaran' => 'decimal:2',
        'saldo'             => 'decimal:2',
    ];

    public function toko()
    {
        return $this->belongsTo(Toko::class, 'id_toko', 'id_toko');
    }
}