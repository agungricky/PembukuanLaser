<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pesanan;
use App\Models\Toko;
use Carbon\Carbon;

class PesananBatalController extends Controller
{
    public function index(Request $request)
    {
        // opsi per halaman
        $allowedPerPage = [10, 20, 50, 100];
        $perPage = (int) $request->input('per_page', 20);
        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 20;
        }

        // status yang diizinkan untuk modul ini
        $allowedStatus = ['batal', 'pengembalian', 'pengiriman gagal'];

        $query = Pesanan::with(['toko'])
            ->whereIn('status', $allowedStatus)

            // search no_pesanan / no_resi
            ->when($request->filled('no_pesanan'), function ($q) use ($request) {
                $keyword = trim((string) $request->input('no_pesanan'));
                $q->where(function ($sub) use ($keyword) {
                    $sub->where('no_pesanan', 'like', "%{$keyword}%")
                        ->orWhere('no_resi', 'like', "%{$keyword}%");
                });
            })

            // filter toko
            ->when($request->filled('id_toko'), function ($q) use ($request) {
                $q->where('id_toko', (int) $request->input('id_toko'));
            })

            // filter status spesifik (dropdown)
            ->when($request->filled('status'), function ($q) use ($request, $allowedStatus) {
                $st = (string) $request->input('status');
                if (in_array($st, $allowedStatus, true)) {
                    $q->where('status', $st);
                }
            });

        // filter tanggal (YYYY-MM-DD atau "YYYY-MM-DD s.d YYYY-MM-DD")
        if ($request->filled('tanggal')) {
            $raw = trim((string) $request->input('tanggal'));
            $start = $end = null;

            if (str_contains($raw, ' s.d ')) {
                [$sRaw, $eRaw] = explode(' s.d ', $raw, 2);
                try {
                    $start = Carbon::createFromFormat('Y-m-d', trim($sRaw))->startOfDay();
                    $end   = Carbon::createFromFormat('Y-m-d', trim($eRaw))->endOfDay();
                } catch (\Throwable $e) {
                    // ignore invalid input
                }
            } else {
                try {
                    $start = Carbon::createFromFormat('Y-m-d', $raw)->startOfDay();
                    $end   = Carbon::createFromFormat('Y-m-d', $raw)->endOfDay();
                } catch (\Throwable $e) {
                    // ignore invalid input
                }
            }

            if ($start && $end) {
                if ($end->lt($start)) {
                    [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
                }
                $query->whereBetween('tanggal', [$start, $end]);
            }
        } else {
            // default: bulan ini
            $start = Carbon::now()->startOfMonth();
            $end   = Carbon::now()->endOfMonth();
            $query->whereBetween('tanggal', [$start, $end]);
        }

        /**
         * SORTING KERUGIAN
         * kerugian = pencairan - total_hpp
         * dir: asc / desc (default: desc = kerugian terbesar di atas)
         */
        $dir = strtolower((string) $request->query('dir', 'desc'));
        $dir = in_array($dir, ['asc', 'desc'], true) ? $dir : 'desc';

        $query->orderByRaw('(COALESCE(pencairan,0) - COALESCE(total_hpp,0)) ' . strtoupper($dir)); // orderByRaw supported [web:36][web:169]

        // pagination + keep query string filters
        $pesanan = $query->paginate($perPage)->withQueryString();

        // hitung field turunan per baris (hanya untuk halaman ini)
        foreach ($pesanan as $p) {
            $totalHpp  = (float) ($p->total_hpp ?? 0);
            $pencairan = (float) ($p->pencairan ?? 0);
            $p->keuntungan = $pencairan - $totalHpp; // kerugian/keuntungan bisa negatif
        }

        // agregat berdasarkan data di paginator
        $jumlahPesanan = $pesanan->total();
        $totalKerugian = (float) $pesanan->sum('keuntungan'); // halaman ini

        $daftarToko = Toko::select('id_toko', 'nama_toko', 'marketplace')
            ->orderBy('nama_toko')
            ->get();

        return view('pesanan.return', [
            'pesanan'        => $pesanan,
            'jumlahPesanan'  => $jumlahPesanan,
            'totalKerugian'  => $totalKerugian,
            'daftarToko'     => $daftarToko,
            'perPage'        => $perPage,
            'allowedPerPage' => $allowedPerPage,
            'dir'            => $dir, // biar gampang dipakai di blade
        ]);
    }
}