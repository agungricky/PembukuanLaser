<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pesanan;
use App\Models\Toko;
use Carbon\Carbon;

class PesananDiterimaController extends Controller
{
    public function index(Request $request)
    {
        // opsi per halaman
        $allowedPerPage = [10, 20, 50, 100];
        $perPage = (int) $request->input('per_page', 20);
        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 20;
        }

        $query = Pesanan::with(['produk', 'user', 'toko'])
            ->where('status', 'selesai')

            // search no_pesanan / no_resi
            ->when($request->filled('no_pesanan'), function ($qBuilder) use ($request) {
                $keyword = trim((string) $request->input('no_pesanan'));
                $qBuilder->where(function ($sub) use ($keyword) {
                    $sub->where('no_pesanan', 'like', "%{$keyword}%")
                        ->orWhere('no_resi', 'like', "%{$keyword}%");
                });
            })

            // filter toko
            ->when($request->filled('id_toko'), function ($qBuilder) use ($request) {
                $qBuilder->where('id_toko', (int) $request->input('id_toko'));
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
                } catch (\Throwable $e) {}
            } else {
                try {
                    $start = Carbon::createFromFormat('Y-m-d', $raw)->startOfDay();
                    $end   = Carbon::createFromFormat('Y-m-d', $raw)->endOfDay();
                } catch (\Throwable $e) {}
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

        // ===== SORTING (tanggal / selisih) =====
        $sort = (string) $request->query('sort', 'tanggal');          // tanggal | selisih
        $dir  = strtolower((string) $request->query('dir', 'desc'));  // asc | desc
        $dir  = in_array($dir, ['asc', 'desc'], true) ? $dir : 'desc';

        if ($sort === 'selisih') {
            // selisih = pencairan - (total_harga - total_admin)
            $query->orderByRaw(
                '(COALESCE(pencairan,0) - (COALESCE(total_harga,0) - COALESCE(total_admin,0))) ' . strtoupper($dir)
            );
        } else {
            // default: tanggal terbaru dulu
            $query->orderBy('tanggal', $dir);
        }

        // PAGINATION
        $pesanan = $query->paginate($perPage)->withQueryString();

        // hitung field turunan per baris (hanya untuk halaman ini)
        foreach ($pesanan as $p) {
            $totalHarga = (float) ($p->total_harga ?? 0);
            $totalAdmin = (float) ($p->total_admin ?? 0);
            $pencairan  = (float) ($p->pencairan   ?? 0);

            $totalHpp = isset($p->total_hpp)
                ? (float) $p->total_hpp
                : (float) $p->produk->sum(function ($row) {
                    return ((float) ($row->hpp ?? 0)) * ((int) ($row->jumlah ?? 0));
                });

            $p->total_hpp_calc = $totalHpp;
            $p->keuntungan     = $pencairan - $totalHpp;
            $p->selisih        = $pencairan - ($totalHarga - $totalAdmin);
        }

        // agregat berdasarkan data di paginator (halaman ini)
        $jumlahPesanan    = $pesanan->total();
        $totalKeuntungan  = (float) $pesanan->sum('keuntungan');
        $totalSelisih     = (float) $pesanan->sum('selisih');
        $totalHargaAll    = (float) $pesanan->sum(fn ($p) => (float) ($p->total_harga ?? 0));
        $totalAdminAll    = (float) $pesanan->sum(fn ($p) => (float) ($p->total_admin ?? 0));
        $totalHppAll      = (float) $pesanan->sum('total_hpp_calc');

        $daftarToko = Toko::select('id_toko', 'nama_toko', 'marketplace')
            ->orderBy('nama_toko')
            ->get();

        return view('pesanan.terima', [
            'pesanan'         => $pesanan,
            'jumlahPesanan'   => $jumlahPesanan,
            'totalKeuntungan' => $totalKeuntungan,
            'totalSelisih'    => $totalSelisih,
            'totalHargaAll'   => $totalHargaAll,
            'totalAdminAll'   => $totalAdminAll,
            'totalHppAll'     => $totalHppAll,
            'daftarToko'      => $daftarToko,
            'perPage'         => $perPage,
            'allowedPerPage'  => $allowedPerPage,
            'sort'            => $sort,
            'dir'             => $dir,
        ]);
    }
}