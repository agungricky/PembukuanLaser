<?php

namespace App\Http\Controllers;

use App\Models\totalklik;

class rotatorController extends Controller
{
    public function redirect()
    {
        $today = now()->toDateString();
        $rotator = totalklik::whereDate('created_at', $today)->first();
        if ($rotator) {
            $rotator->increment('jumlah_click');
        } else {
            totalklik::create([
                'jumlah_click' => 1,
            ]);
        }

        return view('rotator.redirectPage');
    }

    public function index()
    {
        $today = now()->toDateString();

        // Total klik hari ini
        $totalKlikHariIni = totalklik::whereDate('created_at', $today)->value('jumlah_click') ?? 0;

        // Riwayat klik, terbaru di atas
        $riwayat = totalklik::orderBy('created_at', 'desc')->get();

        return view('rotator.index', compact(
            'totalKlikHariIni',
            'riwayat'
        ));
    }
}
