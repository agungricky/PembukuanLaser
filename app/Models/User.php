<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    public function pesanan()
    {
        return $this->hasMany(
            Pesanan::class,
            'id_user',
            'id'
        );
    }

    public function pesananKirim()
    {
        return $this->hasMany(
            Pesanan::class,
            'id_user_kirim',
            'id'
        );
    }

    public function mutasi_stok(){
        return $this->belongsTo(mutasi_stok::class, 'stok_produk_id', 'id');
    }
}