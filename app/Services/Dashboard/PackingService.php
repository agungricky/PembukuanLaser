<?php

namespace App\Services\Dashboard;

use App\Models\Pesanan;
use App\Models\PesananPerProduk;
use Illuminate\Support\Facades\Auth;

class PackingService
{
    public function packing()
    {
        $pesanan = Pesanan::where('platform', userLogin()?->platform)
            ->whereMonth('input_at', now()->month)
            ->whereYear('input_at', now()->year)
            ->select('no_pesanan', 'status', 'foto_at', 'batas_kirim_at', 'no_resi')
            ->get();

        if (userLogin()?->platform == "marketplace") {
            $totalMenunggu = Pesanan::query()
                ->where('status', 'proses')
                ->whereNotNull('batas_kirim_at')
                ->whereNull('id_user_kirim')
                ->where('batas_kirim_at', '>=', now()->addDays(2)->startOfDay())
                ->whereNotIn(
                    'no_pesanan',
                    PesananPerProduk::query()
                        ->select('no_pesanan')
                        ->where('status_pesanan', "0")
                )
                ->whereIn(
                    'no_pesanan',
                    PesananPerProduk::query()
                        ->select('no_pesanan')
                        ->where('status_pesanan', "1")
                )
                ->select('no_pesanan', 'status', 'batas_kirim_at')
                ->count();

            $siapDikirim = Pesanan::query()
                ->where('status', 'proses')
                ->whereNotNull('batas_kirim_at')
                ->whereNull('id_user_kirim')
                ->whereDate('batas_kirim_at', today())
                ->whereNotIn(
                    'no_pesanan',
                    PesananPerProduk::query()
                        ->select('no_pesanan')
                        ->where('status_pesanan', "0")
                )
                ->whereIn(
                    'no_pesanan',
                    PesananPerProduk::query()
                        ->select('no_pesanan')
                        ->where('status_pesanan', "1")
                )
                ->count();

            $dikirim = $pesanan->where('status', 'kirim')
                ->whereNotNull('batas_kirim_at')
                ->count();
            $terlambat = $pesanan
                ->where('status', 'proses')
                ->whereNotNull('batas_kirim_at')
                ->filter(function ($item) {
                    return \Carbon\Carbon::parse($item->batas_kirim_at)
                        ->startOfDay()
                        ->lt(today());
                })
                ->count();
        } else {
            $totalMenunggu = $pesanan
                ->where('status', 'proses')
                ->whereNull('no_resi')
                ->whereNull('foto_at')
                ->count();

            $siapDikirim = $pesanan
                ->where('status', 'proses')
                ->whereNotNull('no_resi')
                ->whereNotNull('foto_at')
                ->count();

            $dikirim = $pesanan->where('status', 'kirim')
                ->whereNotNull('no_resi')
                ->whereNotNull('foto_at')->count();

            $terlambat = Pesanan::where('platform', userLogin()?->platform)
                ->where('status', 'proses')
                ->whereNotNull('batas_kirim_at')
                ->where('batas_kirim_at', '<', today())
                ->count();
        }
        
        return (object) [
            'totalMenunggu' => $totalMenunggu,
            'siapDikirim' => $siapDikirim,
            'dikirim' => $dikirim,
            'terlambat' => $terlambat,
        ];
    }
}
