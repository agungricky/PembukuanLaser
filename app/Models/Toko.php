<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Toko extends Model
{
    protected $table = 'toko';
    protected $primaryKey = 'id_toko';
    public $timestamps = false;

    protected $fillable = [
        'nama_toko',
        'biaya_admin',
        'biaya_tambahan',
        'marketplace',
    ];

    protected $casts = [
        'biaya_admin'    => 'decimal:2',
        'biaya_tambahan' => 'decimal:2',
    ];

    public function iklan()
    {
        return $this->hasMany(Iklan::class, 'id_toko', 'id_toko');
    }

    public function pesanan()
    {
        return $this->hasMany(Pesanan::class, 'id_toko', 'id_toko');
    }
    public function resiImports()
    {
        return $this->hasMany(
            ResiImport::class,
            'id_toko',
            'id_toko'
        );
    }
}
