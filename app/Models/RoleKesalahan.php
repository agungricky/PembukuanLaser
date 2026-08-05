<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoleKesalahan extends Model
{
    protected $table = 'role_kesalahans';
    protected $fillable = ['devisi', 'jenis_kesalahan'];
}
