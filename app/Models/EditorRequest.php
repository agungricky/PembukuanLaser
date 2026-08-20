<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EditorRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_per_produk',
        'plat_lengkap',
        'nama',
        'tanggal_bulan_tahun',
        'jumlah_editor',
        'tanpa_heartbeat',
        'tanpa_korlantas',
        'request_search',
        'editor_imported_by',
        'editor_imported_at',
    ];

    protected $casts = [
        'jumlah_editor' =>
            'integer',

        'tanpa_heartbeat' =>
            'boolean',

        'tanpa_korlantas' =>
            'boolean',

        'editor_imported_at' =>
            'datetime',
    ];

    public function item()
    {
        return $this->belongsTo(
            PesananPerProduk::class,
            'id_per_produk',
            'id_per_produk'
        );
    }

    public function editor()
    {
        return $this->belongsTo(
            User::class,
            'editor_imported_by',
            'id'
        );
    }
}
