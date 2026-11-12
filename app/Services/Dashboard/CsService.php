<?php

namespace App\Services\Dashboard;

use App\Models\Pesanan;

class CsService
{
    public function cs($user_id)
    {
        $pesanan = Pesanan::where('platform', 'meta')
            ->where('id_user', $user_id)
            ->get();

        $totalPesanan = $pesanan->count();
        $dalamProses = $pesanan->where('status', 'proses')->count();
        $dikirim = $pesanan->where('status', 'kirim')->count();
        $omzet = $pesanan->sum('total_hpp');

        return [
            'total_pesanan' => $totalPesanan,
            'dalam_proses' => $dalamProses,
            'dikirim' => $dikirim,
            'omzet' => $omzet,
            'pesanan' => $pesanan,
        ];
    }
}
