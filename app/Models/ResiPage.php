<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResiPage extends Model
{
    protected $table = 'resi_pages';

    protected $fillable = [
        'resi_import_id',
        'no_pesanan',
        'no_resi',
        'halaman',
        'urutan',
    ];

    protected $casts = [
        'halaman' => 'integer',
        'urutan'  => 'integer',
    ];

    public function import()
    {
        return $this->belongsTo(
            ResiImport::class,
            'resi_import_id',
            'id'
        );
    }

    public function pesanan()
    {
        return $this->belongsTo(
            Pesanan::class,
            'no_pesanan',
            'no_pesanan'
        );
    }
}
