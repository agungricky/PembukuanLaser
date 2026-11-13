<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Ringkasan\CsService;
use Illuminate\Http\Request;

class RingkasanController extends Controller
{
    public function cs(CsService $csService)
    {
        $data = $csService->cs();
        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }
}
