<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class kategori extends Model
{
    use SoftDeletes;
    protected $fillable = ['nama_kategori'];

    public function produk(){
        return $this->hasMany(Produk::class);
    }
}
