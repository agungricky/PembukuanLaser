<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class bukti_pembayaran extends Model
{
    protected $fillable = [
        'no_pesanan', 
        'jenis_transaksi', 
        'cod', 
        'non_cod',
        'dp',
        'bukti_bayar'
    ];

    public function pesanan(){
        return $this->belongsTo(Pesanan::class, 'no_pesanan', 'no_pesanan');
    }
}
