<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class progres_pesanan extends Model
{
    protected $fillable = [
        'no_pesanan',
        'role',
        'user_id',
    ];

    public function users(){
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
