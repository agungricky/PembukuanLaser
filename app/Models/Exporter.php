<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Exporter extends Model
{
    protected $fillable = ['user_id','role','status', 'keterangan'];

    public function user(){
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function perproduk(){
        return $this->hasMany(PesananPerProduk::class, 'tracking', 'id');
    }

    public function exporter(){
        return $this->belongsTo(Exporter::class, 'exporter_id', 'id');
    }
}
