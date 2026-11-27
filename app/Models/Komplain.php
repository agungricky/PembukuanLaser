<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Komplain extends Model
{
    protected $table = 'komplains';
    protected $fillable = [
        'no_pesanan',
        'user_id',
        'path',
        'keterangan',
    ];

    public function pesanan()
    {
        return $this->belongsTo(Pesanan::class, 'no_pesanan', 'no_pesanan');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
