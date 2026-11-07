<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Services\Pesanan\PesananService;
use Illuminate\Http\Request;

class pesananController extends Controller
{
    public function index(PesananService $pesananService)
    {
        $pesanan = $pesananService->index();

        return response()->json([
            'success' => true,
            'data' => $pesanan
        ]);
    }
}
