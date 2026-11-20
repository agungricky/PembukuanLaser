<?php

namespace App\Services\Pesanan;

use App\Models\Pesanan;
use App\Models\progres_pesanan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ScanPesananService
{
    public function scan($no_resi)
    {
        return Pesanan::with('toko')->where('no_resi', $no_resi)->first();
    }

    public function scanUpdate($no_pesanan)
    {
        return DB::transaction(function () use ($no_pesanan) {
            $pesanan = Pesanan::where('no_pesanan', $no_pesanan)
                ->lockForUpdate()
                ->firstOrFail();

            $pesanan->update([
                'status' => 'kirim',
                'id_user_kirim' => userLogin()?->id,
            ]);

            progres_pesanan::create([
                'no_pesanan' => $pesanan->no_pesanan,
                'role' => 'dikirim',
                'user_id' => userLogin()?->id,
            ]);

            return $pesanan;
        });
    }

    public function riwayat($request)
    {
        $pesanan = Pesanan::query()
            ->with('toko')
            ->where('id_user_kirim', Auth::id())
            ->where('status', 'kirim')
            ->where('batas_kirim_at', '>=', now()->subMonth())
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->input('search');

                $query->where(function ($q) use ($search) {
                    $q->where('no_resi', 'like', "%{$search}%")
                        ->orWhere('no_pesanan', 'like', "%{$search}%")
                        ->orWhere('nama_pembeli', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('tanggal'), function ($query) use ($request) {
                $query->whereDate(
                    'batas_kirim_at',
                    $request->input('tanggal')
                );
            })
            ->when($request->filled('marketplace'), function ($query) use ($request) {
                $query->whereHas('toko', function ($q) use ($request) {
                    $q->where(
                        'marketplace',
                        $request->input('marketplace')
                    );
                });
            })

            ->orderBy('batas_kirim_at', 'desc')
            ->get();

        return $pesanan;
    }
}
