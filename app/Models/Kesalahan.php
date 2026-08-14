<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kesalahan extends Model
{
    protected $table = 'kesalahans';
    protected $fillable = ['no_pesanan', 'kesalahan_id', 'keterangan'];

    public function pesanan(){
        return $this->hasMany(Pesanan::class, 'no_pesanan', 'no_pesanan');
    }

    public function rolekesalahan(){
        return $this->hasMany(RoleKesalahan::class);
    }
}
