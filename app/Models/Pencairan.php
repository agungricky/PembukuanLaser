<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pencairan extends Model
{
    protected $table = 'pencairans';
    protected $fillable = [
        'no_pesanan',
        'user_id',
        'tanggal',
        'nominal',
    ];

    protected $casts = [
        'tanggal' => 'datetime',
        'nominal' => 'decimal:2',
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
