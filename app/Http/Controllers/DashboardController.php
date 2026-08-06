<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pesanan;
use Illuminate\Support\Facades\DB;
use Carbon\CarbonImmutable;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date'   => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
        ]);

        $startDate = $validated['start_date'] ?? now()->toDateString();
        $endDate   = $validated['end_date']   ?? $startDate;

        $start = CarbonImmutable::createFromFormat('Y-m-d', $startDate)->startOfDay();
        $end   = CarbonImmutable::createFromFormat('Y-m-d', $endDate)->endOfDay();

        if ($end->lt($start)) {
            $end     = $start->endOfDay();
            $endDate = $startDate;
        }

        $startStr = $start->toDateString();
        $endStr   = $end->toDateString();

        // Panjang previous = selisih hari (TANPA +1), di-anchorkan ke AWAL periode
        // Contoh: 04-09 s/d 06-09  => diff=2  => prev: 02-09 s/d 03-09
        $diffDays = $start->diffInDays($end);     // 0 utk single day
        $prevSpan = max(1, $diffDays);            // kalau single day, pakai 1 hari ke belakang

        $prevStart = $start->subDays($prevSpan)->toDateString();
        $prevEnd   = $start->subDay()->toDateString();

        $changePct = function (float $current, ?float $previous): ?float {
            if (is_null($previous) || $previous <= 0) return null;
            return round((($current - $previous) / $previous) * 100, 2);
        };

        // ================= Agregat periode sekarang =================
        $currAgg = Pesanan::query()
            ->whereBetween('tanggal', [$startStr, $endStr])
            ->selectRaw('COALESCE(SUM(total_harga),0) as penjualan')
            ->selectRaw('COUNT(*) as total_pesanan')
            ->selectRaw("
                SUM(
                    CASE 
                        WHEN status IN ('batal','pengiriman gagal','pengembalian') THEN 1 
                        ELSE 0 
                    END
                ) as pesanan_batal
            ")
            ->first();

        $penjualan         = (float) ($currAgg->penjualan ?? 0);
        $totalPesanan      = (int) ($currAgg->total_pesanan ?? 0);
        $pesananBatal      = (int) ($currAgg->pesanan_batal ?? 0);
        $penjualanPerOrder = $totalPesanan > 0 ? $penjualan / $totalPesanan : 0.0;

        // ================= Agregat periode previous (mengikuti logic di atas) =================
        $prevAgg = Pesanan::query()
            ->whereBetween('tanggal', [$prevStart, $prevEnd])
            ->selectRaw('COALESCE(SUM(total_harga),0) as penjualan')
            ->selectRaw('COUNT(*) as total_pesanan')
            ->selectRaw('SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as pesanan_batal', ['batal'])
            ->first();

        $prevPenjualan         = (float) ($prevAgg->penjualan ?? 0);
        $prevTotalPesanan      = (int) ($prevAgg->total_pesanan ?? 0);
        $prevPesananBatal      = (int) ($prevAgg->pesanan_batal ?? 0);
        $prevPenjualanPerOrder = $prevTotalPesanan > 0 ? $prevPenjualan / $prevTotalPesanan : 0.0;

        // ================= Buyer metrics =================
        $buyerCountsCurr = Pesanan::query()
            ->whereBetween('tanggal', [$startStr, $endStr])
            ->whereNotNull('username')
            ->select('username', DB::raw('COUNT(*) AS cnt'))
            ->groupBy('username');

        $totalPembeliUnik = DB::query()->fromSub($buyerCountsCurr, 't')->count();
        $pembeliBaru      = DB::query()->fromSub($buyerCountsCurr, 't')->where('cnt', 1)->count();
        $pembeliLama      = DB::query()->fromSub($buyerCountsCurr, 't')->where('cnt', '>', 1)->count();
        $repeatRate       = $totalPembeliUnik > 0 ? ($pembeliLama / $totalPembeliUnik) * 100 : 0.0;

        $buyerCountsPrev = Pesanan::query()
            ->whereBetween('tanggal', [$prevStart, $prevEnd])
            ->whereNotNull('username')
            ->select('username', DB::raw('COUNT(*) AS cnt'))
            ->groupBy('username');

        $prevTotalPembeliUnik = DB::query()->fromSub($buyerCountsPrev, 't')->count();
        $prevPembeliBaru      = DB::query()->fromSub($buyerCountsPrev, 't')->where('cnt', 1)->count();
        $prevPembeliLama      = DB::query()->fromSub($buyerCountsPrev, 't')->where('cnt', '>', 1)->count();
        $prevRepeatRate       = $prevTotalPembeliUnik > 0 ? ($prevPembeliLama / $prevTotalPembeliUnik) * 100 : 0.0;

        // ================= Ranking =================
        $limit = 5;

        $rankingPenjualan = DB::table('pesanan_per_produk as pp')
            ->join('pesanan as p', 'pp.no_pesanan', '=', 'p.no_pesanan')
            ->whereBetween('p.tanggal', [$startStr, $endStr])
            ->select(
                'pp.nama_produk',
                DB::raw('MAX(pp.harga) as harga_satuan'),
                DB::raw('SUM(pp.harga * pp.jumlah) as total_penjualan')
            )
            ->groupBy('pp.nama_produk')
            ->orderByDesc('total_penjualan')
            ->limit($limit)
            ->get();

        $rankingProduk = DB::table('pesanan_per_produk as pp')
            ->join('pesanan as p', 'pp.no_pesanan', '=', 'p.no_pesanan')
            ->whereBetween('p.tanggal', [$startStr, $endStr])
            ->select(
                'pp.nama_produk',
                DB::raw('MAX(pp.harga) as harga_satuan'),
                DB::raw('SUM(pp.jumlah) as total_terjual')
            )
            ->groupBy('pp.nama_produk')
            ->orderByDesc('total_terjual')
            ->limit($limit)
            ->get();

        return view('dashboard', [
            'metrics' => [
                [
                    'label'  => 'Penjualan',
                    'type'   => 'currency',
                    'value'  => $penjualan,
                    'change' => $changePct($penjualan, $prevPenjualan),
                ],
                [
                    'label'  => 'Total Pesanan',
                    'type'   => 'number',
                    'value'  => $totalPesanan,
                    'change' => $changePct($totalPesanan, $prevTotalPesanan),
                ],
                [
                    'label'  => 'Pesanan Batal',
                    'type'   => 'number',
                    'value'  => $pesananBatal,
                    'change' => $changePct($pesananBatal, $prevPesananBatal),
                    'good'   => 'down',
                ],
                [
                    'label'  => 'Avg/Pesanan',
                    'type'   => 'currency',
                    'value'  => $penjualanPerOrder,
                    'change' => $changePct($penjualanPerOrder, $prevPenjualanPerOrder),
                ],
            ],
            'buyer' => [
                'baru'       => $pembeliBaru,
                'lama'       => $pembeliLama,
                'total'      => $totalPembeliUnik,
                'persenBaru' => $totalPembeliUnik > 0 ? round(($pembeliBaru / $totalPembeliUnik) * 100, 2) : 0,
                'persenLama' => $totalPembeliUnik > 0 ? round(($pembeliLama / $totalPembeliUnik) * 100, 2) : 0,
                'repeatRate' => round($repeatRate, 2),
                'changes'    => [
                    'baru'       => $changePct($pembeliBaru, $prevPembeliBaru),
                    'lama'       => $changePct($pembeliLama, $prevPembeliLama),
                    'repeatRate' => $changePct($repeatRate, $prevRepeatRate),
                ],
            ],
            'rankingPenjualan' => $rankingPenjualan,
            'rankingProduk'    => $rankingProduk,
            'startDate'        => $startStr,
            'endDate'          => $endStr,
        ]);
    }

    public function gudang(){
        return view('gudang.Dashboard');
    }
}
