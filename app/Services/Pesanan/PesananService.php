<?php

namespace App\Services\Pesanan;

use App\Models\Pesanan;

class PesananService
{
    public function index(){
        return Pesanan::where('platform', 'meta')->get();
    }
}
