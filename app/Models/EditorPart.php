<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EditorPart extends Model
{
    protected $table = 'editor_parts';

    public const SESI_PAGI = 'pagi';
    public const SESI_SIANG = 'siang';
    public const SESI_MALAM = 'malam';

    protected $fillable = [
        'tanggal_part',
        'sesi',
        'marketplace',
        'nomor_part',
        'kode_part',
        'kapasitas_per_kelompok',
        'status',
        'created_by',
        'downloaded_by',
        'downloaded_at',
        'uploaded_by',
        'uploaded_at',
    ];

    protected $casts = [
        'tanggal_part' => 'date',
        'kapasitas_per_kelompok' => 'integer',
        'downloaded_at' => 'datetime',
        'uploaded_at' => 'datetime',
    ];

    public function items()
    {
        return $this->hasMany(
            EditorPartItem::class,
            'editor_part_id',
            'id'
        )->orderBy('urutan');
    }

    public function creator()
    {
        return $this->belongsTo(
            User::class,
            'created_by',
            'id'
        );
    }

    public function downloader()
    {
        return $this->belongsTo(
            User::class,
            'downloaded_by',
            'id'
        );
    }

    public function uploader()
    {
        return $this->belongsTo(
            User::class,
            'uploaded_by',
            'id'
        );
    }

    public function editorRequests()
    {
        return $this->hasMany(
            EditorRequest::class,
            'editor_part_id',
            'id'
        );
    }
}
