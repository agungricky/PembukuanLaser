<?php

namespace App\Services\Pesanan;

use App\Models\Pesanan;

class PesananService
{
    public function index(){
        $data = Pesanan::where('platform', 'meta')->get();
        
        dd($data);
    }
}
