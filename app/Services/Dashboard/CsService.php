<?php

namespace App\Services\Dashboard;

use App\Models\Pesanan;
use Illuminate\Support\Facades\Auth;

class CsService
{
    public function cs()
    {
        $pesanan = Pesanan::with([
            'pesanan_per_produk' => function ($query) {
                $query->select('no_pesanan', 'id_per_produk', 'sku');
            },
            'pesanan_per_produk.produk' => function ($query) {
                $query->select('sku', 'nama_produk');
            }
        ])
            ->where('platform', 'meta')
            ->where('id_user', Auth::id())
            ->whereMonth('input_at', now()->month)
            ->whereYear('input_at', now()->year)
            ->select('no_pesanan', 'status', 'total_harga')
            ->get();

        $totalPesanan = $pesanan->count();
        $dalamProses = $pesanan->where('status', 'proses')->count();
        $dikirim = $pesanan->where('status', 'kirim')->count();
        $selesai = $pesanan->where('status', 'selesai')->count();
        $omzet = $pesanan->sum('total_harga');

        return (object) [
            'totalPesanan' => $totalPesanan,
            'proses' => $dalamProses,
            'dikirim' => $dikirim,
            'selesai' => $selesai,
            'omzet' => $omzet,
            'pesanan' => $pesanan,
        ];
    }
}
