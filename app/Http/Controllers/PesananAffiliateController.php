<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pesanan;
use App\Models\Toko;
use Carbon\Carbon;

class PesananAffiliateController extends Controller
{
    public function index(Request $request)
    {
        $allowedPerPage = [10, 20, 50, 100];
        $perPage = (int) $request->input('per_page', 20);

        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 20;
        }

        $query = Pesanan::with(['toko', 'produk', 'user'])
            ->where('status', 'affiliate')

            ->when($request->filled('q'), function ($qBuilder) use ($request) {
                $keyword = trim((string) $request->input('q'));

                $qBuilder->where(function ($sub) use ($keyword) {
                    $sub->where('no_pesanan', 'like', "%{$keyword}%")
                        ->orWhere('no_resi', 'like', "%{$keyword}%")
                        ->orWhere('nama_pembeli', 'like', "%{$keyword}%")
                        ->orWhere('username', 'like', "%{$keyword}%");
                });
            })

            ->when($request->filled('id_toko'), function ($qBuilder) use ($request) {
                $qBuilder->where('id_toko', (int) $request->input('id_toko'));
            });

        $min = $request->input('min_date');
        $max = $request->input('max_date');

        if ($min || $max) {
            try {
                $start = $min ? Carbon::createFromFormat('Y-m-d', $min)->startOfDay() : null;
                $end = $max ? Carbon::createFromFormat('Y-m-d', $max)->endOfDay() : null;

                if ($start && $end && $end->lt($start)) {
                    [$start, $end] = [
                        $end->copy()->startOfDay(),
                        $start->copy()->endOfDay(),
                    ];
                }

                if ($start && $end) {
                    $query->whereBetween('tanggal', [$start, $end]);
                } elseif ($start) {
                    $query->where('tanggal', '>=', $start);
                } elseif ($end) {
                    $query->where('tanggal', '<=', $end);
                }
            } catch (\Throwable $e) {
                //
            }
        }

        $query->orderByDesc('tanggal');

        $pesanan = $query->paginate($perPage)->withQueryString();

        foreach ($pesanan as $p) {
            $p->total_item = (int) $p->produk->sum('jumlah');
            $p->keuntungan = (float) ($p->pencairan ?? 0) - (float) ($p->total_hpp ?? 0);
        }

        $jumlahPesanan = $pesanan->total();
        $totalItem = (int) $pesanan->sum('total_item');
        $totalPencairan = (float) $pesanan->sum('pencairan');
        $totalKeuntungan = (float) $pesanan->sum('keuntungan');

        $daftarToko = Toko::select('id_toko', 'nama_toko', 'marketplace')
            ->orderBy('nama_toko')
            ->get();

        return view('pesanan.affiliate', [
            'pesanan' => $pesanan,
            'jumlahPesanan' => $jumlahPesanan,
            'totalItem' => $totalItem,
            'totalPencairan' => $totalPencairan,
            'totalKeuntungan' => $totalKeuntungan,
            'daftarToko' => $daftarToko,
            'perPage' => $perPage,
            'allowedPerPage' => $allowedPerPage,
        ]);
    }
}