<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EditorPartItem extends Model
{
    protected $table = 'editor_part_items';

    protected $fillable = [
        'editor_part_id',
        'id_per_produk',
        'sku',
        'kelompok_produksi',
        'jumlah_awal',
        'jumlah_final',
        'urutan',
        'status',
        'processed_at',
    ];

    protected $casts = [
        'jumlah_awal' => 'integer',
        'jumlah_final' => 'integer',
        'urutan' => 'integer',
        'processed_at' => 'datetime',
    ];

    public function part()
    {
        return $this->belongsTo(
            EditorPart::class,
            'editor_part_id',
            'id'
        );
    }

    public function item()
    {
        return $this->belongsTo(
            PesananPerProduk::class,
            'id_per_produk',
            'id_per_produk'
        );
    }
}
