<?php

namespace App\Services\Ringkasan;

use App\Models\Pesanan;
use Illuminate\Support\Facades\Auth;

class CsService
{
    public function cs()
    {
        $pesanan = Pesanan::where('id_user', Auth::id())
            ->where('platform', 'meta')
            ->whereMonth('input_at', now()->month)
            ->whereYear('input_at', now()->year)
            ->get();

        $pesanan_valid = $pesanan->whereNotIn('status', [
            'batal',
            'affiliate'
        ]);

        return (object) [
            'totalPesanan' => $pesanan->count(),
            'pesananValid' => $pesanan_valid->count(),
            'pesananBatal' => $pesanan->where('status', 'batal')->count(),
            'omzet' => $pesanan_valid->sum('total_harga'),
            'distribusiPesanan' => [
                'proses' => $pesanan->where('status', 'proses')->count(),
                'packing' => $pesanan->where('status', 'packing')->count(),
                'dikirim' => $pesanan->where('status', 'kirim')->count(),
                'selesai' => $pesanan->where('status', 'selesai')->count(),
                'batal' => $pesanan->where('status', 'batal')->count(),
            ],
        ];
    }
}
