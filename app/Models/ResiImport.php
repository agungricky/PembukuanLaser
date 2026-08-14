<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResiImport extends Model
{
    protected $table = 'resi_imports';

    protected $fillable = [
        'nama_file',
        'path_file',
        'jumlah_halaman',
        'marketplace',
        'id_toko',
        'user_id',
    ];

    protected $casts = [
        'jumlah_halaman' => 'integer',
    ];

    public function pages()
    {
        return $this->hasMany(
            ResiPage::class,
            'resi_import_id',
            'id'
        );
    }

    public function toko()
    {
        return $this->belongsTo(
            Toko::class,
            'id_toko',
            'id_toko'
        );
    }

    public function user()
    {
        return $this->belongsTo(
            User::class,
            'user_id',
            'id'
        )->withTrashed();
    }
}
