<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Pesanan;
use Carbon\Carbon;

class PembeliController extends Controller
{
    public function index(Request $request)
    {
        $startDate = $request->input('start_date', now()->toDateString());
        $endDate = $request->input('end_date', $startDate);
        $storeId = $request->input('store_id'); // id_toko (nullable)

        // Pastikan urutan tanggal benar, gunakan date-string karena kolom 'tanggal' bertipe DATE
        $start = Carbon::parse($startDate)->toDateString();
        $end = Carbon::parse($endDate)->toDateString();
        if ($end < $start) {
            $end = $start;
            $endDate = $startDate;
        }

        // Dropdown toko: key=id_toko, value=nama_toko
        $stores = DB::table('toko')
            ->select('id_toko', 'nama_toko', 'marketplace')
            ->orderBy('nama_toko')
            ->get()
            ->keyBy('id_toko');

        // ===== Hitung pembeli baru/lama
        $buyerCounts = DB::table('pesanan')
            ->whereBetween('tanggal', [$start, $end])
            ->when($storeId, fn($q) => $q->where('id_toko', $storeId))
            ->whereNotNull('username')
            ->select('username', DB::raw('COUNT(*) AS cnt'))
            ->groupBy('username');

        $totalUnique = DB::query()->fromSub($buyerCounts, 't')->count();
        $pembeliBaru = DB::query()->fromSub($buyerCounts, 't')->where('cnt', 1)->count();
        $pembeliLama = DB::query()->fromSub($buyerCounts, 't')->where('cnt', '>', 1)->count();

        $repeatRate = $totalUnique > 0 ? round(($pembeliLama / $totalUnique) * 100, 2) : 0.0;

        // ===== Tabel pembeli (pagination) + filter toko
        $buyersQuery = Pesanan::whereBetween('tanggal', [$start, $end])
            ->when($storeId, fn($q) => $q->where('id_toko', $storeId))
            ->whereNotNull('username')
            ->selectRaw('username, COUNT(*) as total_pesanan, SUM(COALESCE(total_harga,0)) as total_penjualan, MAX(tanggal) as last_order')
            ->groupBy('username');

        $buyers = $buyersQuery
            ->orderByDesc(DB::raw('SUM(COALESCE(total_harga,0))'))
            ->limit(5)
            ->get();

        $chart = [
            'labels' => ['Pembeli Baru', 'Pembeli Lama'],
            'values' => [(int) $pembeliBaru, (int) $pembeliLama],
        ];

        return view('laporan.pembeli', [
            'summary' => [
                'pembeli_baru' => $pembeliBaru,
                'pembeli_lama' => $pembeliLama,
                'repeat_rate' => $repeatRate,
                'total_pembeli' => $totalUnique,
            ],
            'buyers' => $buyers,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'stores' => $stores,
            'storeId' => $storeId ? (string) $storeId : '',
            'chart' => $chart,
        ]);
    }
}