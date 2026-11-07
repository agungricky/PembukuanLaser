<?php

namespace App\Services\Kategori;

use App\Models\kategori;

class KategoriService
{
    public function index()
    {
        return Kategori::all();
    }
}
