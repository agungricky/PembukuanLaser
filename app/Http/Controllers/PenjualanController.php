<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pesanan;
use App\Models\Toko;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PenjualanSimpleExport;

class PenjualanController extends Controller
{
    public function performance(Request $request)
    {
        $startDate = $request->input('start_date', now()->toDateString());
        $endDate   = $request->input('end_date',   $startDate);

        // id_toko bisa: "", "all_shopee", "all_tiktok", atau id numerik
        $rawToko = $request->input('id_toko');

        $tokoId      = is_numeric($rawToko) ? (int) $rawToko : null;
        $marketplace = null;

        if ($rawToko === 'all_shopee') {
            $marketplace = 'Shopee';
        } elseif ($rawToko === 'all_tiktok') {
            $marketplace = 'TikTok';
        }

        $start = Carbon::parse($startDate)->startOfDay();
        $end   = Carbon::parse($endDate)->endOfDay();

        if ($end->lt($start)) {
            $end     = $start->copy()->endOfDay();
            $endDate = $startDate;
        }

        // Periode previous: mundur sepanjang span aktif (tanpa +1)
        $diffDays  = $start->diffInDays($end);
        $prevSpan  = max(1, $diffDays);
        $prevStart = $start->copy()->subDays($prevSpan)->startOfDay();
        $prevEnd   = $start->copy()->subDay()->endOfDay();

        // Helper filter marketplace (Pesanan via relasi toko)
        $filterMarketplace = function ($q) use ($marketplace) {
            if ($marketplace) {
                $q->whereHas('toko', fn ($t) => $t->where('marketplace', $marketplace));
            }
        };

        // ===== Periode aktif =====
        $base = Pesanan::whereBetween('tanggal', [$start, $end])
            ->when($tokoId, fn ($q) => $q->where('id_toko', $tokoId))
            ->when(true, $filterMarketplace);

        $penjualan     = (clone $base)->sum('total_harga');
        $pencairan     = (clone $base)->sum('pencairan');
        $totalPesanan  = (clone $base)->count();
        $totalHPP      = (clone $base)->sum('total_hpp');
        $totalAdmin    = (clone $base)->sum('total_admin');
        
        $baseSelesai = (clone $base)
            ->where('status', 'selesai');
        
        $biayaLainLain = (clone $baseSelesai)->sum('pencairan')
            - (
                (clone $baseSelesai)->sum('total_harga')
                - (clone $baseSelesai)->sum('total_admin')
            );

        // Biaya iklan (aktif)
        $biayaIklan = DB::table('iklan')
            ->whereBetween('tanggal', [$start, $end])
            ->when($tokoId, fn ($q) => $q->where('id_toko', $tokoId))
            ->when($marketplace, function ($q) use ($marketplace) {
                $q->whereExists(function ($sub) use ($marketplace) {
                    $sub->from('toko')
                        ->whereColumn('toko.id_toko', 'iklan.id_toko')
                        ->where('toko.marketplace', $marketplace);
                });
            })
            ->sum('jumlah_pembayaran');

        // Keuntungan = pencairan - HPP - iklan
        $keuntungan = $pencairan - $totalHPP - $biayaIklan;

        // Metrik tambahan
        $selisih       = $pencairan - ($penjualan - $totalAdmin);
        $totalTarik    = $penjualan - $totalAdmin;
        $pesananBatal  = (clone $base)
            ->whereIn('status', ['batal', 'pengiriman gagal', 'pengembalian'])
            ->count();
        $avgPesanan    = $totalPesanan > 0 ? $penjualan / $totalPesanan : 0.0;
        $roas          = $biayaIklan > 0 ? $penjualan / $biayaIklan : 0.0;

        // ===== 3 kolom baru (aktif) =====
        // 1) Estimasi keuntungan (berdasarkan penjualan, bukan pencairan)
        $estimasiKeuntungan = $penjualan - $totalHPP - $totalAdmin - $biayaIklan;

        // 2) Pengembalian = SUM(pencairan) + (SUM(total_hpp) * -1) khusus status pengembalian
        $pengembalianAgg = Pesanan::query()
            ->whereBetween('tanggal', [$start, $end])
            ->when($tokoId, fn ($q) => $q->where('id_toko', $tokoId))
            ->when(true, $filterMarketplace)
            ->where('status', 'pengembalian')
            ->selectRaw('SUM(pencairan) AS sum_pencairan, SUM(total_hpp) AS sum_total_hpp')
            ->first();
        
        $pengembalian = (float) ($pengembalianAgg->sum_pencairan ?? 0)
                      + ((float) ($pengembalianAgg->sum_total_hpp ?? 0) * -1);
        
        // 3) Pengiriman gagal = SUM(pencairan) + (SUM(total_hpp) * -1) khusus status pengiriman gagal
        $pengirimanGagalAgg = Pesanan::query()
            ->whereBetween('tanggal', [$start, $end])
            ->when($tokoId, fn ($q) => $q->where('id_toko', $tokoId))
            ->when(true, $filterMarketplace)
            ->where('status', 'pengiriman gagal')
            ->selectRaw('SUM(pencairan) AS sum_pencairan, SUM(total_hpp) AS sum_total_hpp')
            ->first();
        
        $pengirimanGagal = (float) ($pengirimanGagalAgg->sum_pencairan ?? 0) + ((float) ($pengirimanGagalAgg->sum_total_hpp ?? 0) * -1);
        
        $pesananPengembalian = (clone $base)
            ->where('status', 'pengembalian')
            ->count();
        
        $pesananAffiliate = (clone $base)
            ->where('status', 'affiliate')
            ->count();
        
        $affiliateAgg = Pesanan::query()
            ->whereBetween('tanggal', [$start, $end])
            ->when($tokoId, fn ($q) => $q->where('id_toko', $tokoId))
            ->when(true, $filterMarketplace)
            ->where('status', 'affiliate')
            ->selectRaw('SUM(pencairan) AS sum_pencairan, SUM(total_hpp) AS sum_total_hpp')
            ->first();
        
        $affiliate = (float) ($affiliateAgg->sum_pencairan ?? 0)
                   + ((float) ($affiliateAgg->sum_total_hpp ?? 0) * -1);

        // ===== Periode previous =====
        $prevBase = Pesanan::whereBetween('tanggal', [$prevStart, $prevEnd])
            ->when($tokoId, fn ($q) => $q->where('id_toko', $tokoId))
            ->when(true, $filterMarketplace);

        $prevPenjualan     = (clone $prevBase)->sum('total_harga');
        $prevPencairan     = (clone $prevBase)->sum('pencairan');
        $prevTotalPesanan  = (clone $prevBase)->count();
        $prevTotalHPP      = (clone $prevBase)->sum('total_hpp');
        $prevTotalAdmin    = (clone $prevBase)->sum('total_admin');
        
        $prevBaseSelesai = (clone $prevBase)
            ->where('status', 'selesai');
        
        $prevBiayaLainLain = (clone $prevBaseSelesai)->sum('pencairan')
            - (
                (clone $prevBaseSelesai)->sum('total_harga')
                - (clone $prevBaseSelesai)->sum('total_admin')
            );

        $prevBiayaIklan = DB::table('iklan')
            ->whereBetween('tanggal', [$prevStart, $prevEnd])
            ->when($tokoId, fn ($q) => $q->where('id_toko', $tokoId))
            ->when($marketplace, function ($q) use ($marketplace) {
                $q->whereExists(function ($sub) use ($marketplace) {
                    $sub->from('toko')
                        ->whereColumn('toko.id_toko', 'iklan.id_toko')
                        ->where('toko.marketplace', $marketplace);
                });
            })
            ->sum('jumlah_pembayaran');

        $prevKeuntungan   = $prevPencairan - $prevTotalHPP - $prevBiayaIklan;
        $prevSelisih      = $prevPencairan - ($prevPenjualan - $prevTotalAdmin);
        $prevTotalTarik   = $prevPenjualan - $prevTotalAdmin;
        $prevPesananBatal = (clone $prevBase)
            ->whereIn('status', ['batal', 'pengiriman gagal', 'pengembalian'])
            ->count();
        $prevAvgPesanan   = $prevTotalPesanan > 0 ? $prevPenjualan / $prevTotalPesanan : 0.0;
        $prevRoas         = $prevBiayaIklan > 0 ? $prevPenjualan / $prevBiayaIklan : 0.0;

        // ===== 3 kolom baru (previous) =====
        $prevEstimasiKeuntungan = $prevPenjualan - $prevTotalHPP - $prevTotalAdmin - $prevBiayaIklan;

        $prevPengembalianAgg = Pesanan::query()
            ->whereBetween('tanggal', [$prevStart, $prevEnd])
            ->when($tokoId, fn ($q) => $q->where('id_toko', $tokoId))
            ->when(true, $filterMarketplace)
            ->where('status', 'pengembalian')
            ->selectRaw('SUM(pencairan) AS sum_pencairan, SUM(total_hpp) AS sum_total_hpp')
            ->first();
        
        $prevPengembalian = (float) ($prevPengembalianAgg->sum_pencairan ?? 0)
                          + ((float) ($prevPengembalianAgg->sum_total_hpp ?? 0) * -1);
        
        $prevPengirimanGagalAgg = Pesanan::query()
            ->whereBetween('tanggal', [$prevStart, $prevEnd])
            ->when($tokoId, fn ($q) => $q->where('id_toko', $tokoId))
            ->when(true, $filterMarketplace)
            ->where('status', 'pengiriman gagal')
            ->selectRaw('SUM(pencairan) AS sum_pencairan, SUM(total_hpp) AS sum_total_hpp')
            ->first();
        
        $prevPengirimanGagal = (float) ($prevPengirimanGagalAgg->sum_pencairan ?? 0)
                             + ((float) ($prevPengirimanGagalAgg->sum_total_hpp ?? 0) * -1);
                             
        $prevPesananPengembalian = (clone $prevBase)
            ->where('status', 'pengembalian')
            ->count();
        
        $prevPesananAffiliate = (clone $prevBase)
            ->where('status', 'affiliate')
            ->count();
        
        $prevAffiliateAgg = Pesanan::query()
            ->whereBetween('tanggal', [$prevStart, $prevEnd])
            ->when($tokoId, fn ($q) => $q->where('id_toko', $tokoId))
            ->when(true, $filterMarketplace)
            ->where('status', 'affiliate')
            ->selectRaw('SUM(pencairan) AS sum_pencairan, SUM(total_hpp) AS sum_total_hpp')
            ->first();
        
        $prevAffiliate = (float) ($prevAffiliateAgg->sum_pencairan ?? 0)
                       + ((float) ($prevAffiliateAgg->sum_total_hpp ?? 0) * -1);

        $change = function (float $cur, float $prev): ?float {
            if ($prev <= 0) return null;
            return round((($cur - $prev) / $prev) * 100, 2);
        };

        // ===== Agregasi harian (untuk chart & export) =====
        $aggOrders = Pesanan::query()
            ->whereBetween('tanggal', [$start, $end])
            ->when($tokoId, fn ($q) => $q->where('id_toko', $tokoId))
            ->when(true, $filterMarketplace)
            ->selectRaw("
                DATE(tanggal) as d,
                SUM(total_harga) as penjualan,
                SUM(total_hpp)   as hpp,
                COUNT(*)         as pesanan,
                SUM(CASE WHEN status IN ('batal','pengiriman gagal','pengembalian') THEN 1 ELSE 0 END) as batal
            ")
            ->groupBy('d')
            ->orderBy('d')
            ->get()
            ->keyBy('d');

        $aggAds = DB::table('iklan')
            ->whereBetween('tanggal', [$start, $end])
            ->when($tokoId, fn ($q) => $q->where('id_toko', $tokoId))
            ->when($marketplace, function ($q) use ($marketplace) {
                $q->whereExists(function ($sub) use ($marketplace) {
                    $sub->from('toko')
                        ->whereColumn('toko.id_toko', 'iklan.id_toko')
                        ->where('toko.marketplace', $marketplace);
                });
            })
            ->selectRaw('DATE(tanggal) as d, SUM(jumlah_pembayaran) as biaya')
            ->groupBy('d')
            ->orderBy('d')
            ->get()
            ->keyBy('d');

        // Series chart
        $labels          = [];
        $seriesPenjualan = [];
        $seriesPesanan   = [];
        $seriesIklan     = [];

        $cursor = $start->copy();
        while ($cursor->lte($end)) {
            $key               = $cursor->toDateString();
            $labels[]          = $cursor->format('d M');
            $seriesPenjualan[] = isset($aggOrders[$key]) ? (float) $aggOrders[$key]->penjualan : 0.0;
            $seriesPesanan[]   = isset($aggOrders[$key]) ? (int)   $aggOrders[$key]->pesanan   : 0;
            $seriesIklan[]     = isset($aggAds[$key])    ? (float) $aggAds[$key]->biaya       : 0.0;
            $cursor->addDay();
        }

        // Baris harian untuk EXPORT
        $dailyRows = [];
        $cursor    = $start->copy();
        while ($cursor->lte($end)) {
            $key   = $cursor->toDateString();
            $sales = isset($aggOrders[$key]) ? (float) $aggOrders[$key]->penjualan : 0.0;
            $hpp   = isset($aggOrders[$key]) ? (float) $aggOrders[$key]->hpp       : 0.0;
            $ord   = isset($aggOrders[$key]) ? (int)   $aggOrders[$key]->pesanan   : 0;
            $btl   = isset($aggOrders[$key]) ? (int)   $aggOrders[$key]->batal     : 0;
            $dailyRows[] = [$key, $sales, $hpp, $ord, $btl];
            $cursor->addDay();
        }

        // ===== Export XLSX =====
        if (strtolower($request->query('download', '')) === 'xlsx') {
            if ($tokoId) {
                $tokoName = Toko::where('id_toko', $tokoId)->value('nama_toko') ?? 'Tidak diketahui';
            } elseif ($marketplace) {
                $tokoName = 'Semua Toko ' . $marketplace;
            } else {
                $tokoName = 'Semua Toko';
            }

            $file = 'penjualan_' . ($tokoId ? 'toko-' . $tokoId . '_' : '') .
                $start->format('Ymd') . '-' . $end->format('Ymd') . '.xlsx';

            $meta = [
                'period'                => $startDate . ' s.d ' . $endDate,
                'store'                 => $tokoName,
                'generated'             => now('Asia/Jakarta')->format('Y-m-d H:i:s') . ' WIB',
                'omset'                 => (float) $penjualan,
                'total_hpp'             => (float) $totalHPP,
                'total_admin'           => (float) $totalAdmin,
                'ad_spend'              => (float) $biayaIklan,
                'selisih'               => (float) $selisih,
                'net_profit'            => (float) $keuntungan,
                'estimasi_keuntungan'   => (float) $estimasiKeuntungan,
                'pengembalian'          => (float) $pengembalian,
                'pengiriman_gagal_hpp'  => (float) $pengirimanGagal,
                'affiliate'             => (float) $affiliate,
                'total_pesanan'         => (int) $totalPesanan,
                'pesanan_batal'         => (int) $pesananBatal,
                'pesanan_pengembalian'  => (int) $pesananPengembalian,
                'pesanan_affiliate'     => (int) $pesananAffiliate,
            ];

            return Excel::download(new PenjualanSimpleExport($meta, $dailyRows), $file);
        }

        // ===== Status agregasi (aktif) =====
        $statusAgg = Pesanan::query()
            ->whereBetween('tanggal', [$start, $end])
            ->when($tokoId, fn ($q) => $q->where('id_toko', $tokoId))
            ->when(true, $filterMarketplace)
            ->selectRaw("
                SUM(CASE WHEN status = 'proses' THEN 1 ELSE 0 END) AS diproses,
                SUM(CASE WHEN status = 'kirim' THEN 1 ELSE 0 END) AS dikirim,
                SUM(CASE WHEN status = 'selesai' THEN 1 ELSE 0 END) AS selesai,
                SUM(CASE WHEN status = 'affiliate' THEN 1 ELSE 0 END) AS affiliate,
                SUM(CASE WHEN status = 'pengiriman gagal' THEN 1 ELSE 0 END) AS pengiriman_gagal,
                SUM(CASE WHEN status = 'batal' THEN 1 ELSE 0 END) AS batal,
                SUM(CASE WHEN status = 'pengembalian' THEN 1 ELSE 0 END) AS pengembalian
            ")
            ->first();
        
        $statusLabels = [
            'Diproses',
            'Dikirim',
            'Selesai',
            'Affiliate',
            'Pengiriman Gagal',
            'Batal',
            'Pengembalian',
        ];
        
        $statusValues = [
            (int) ($statusAgg->diproses ?? 0),
            (int) ($statusAgg->dikirim ?? 0),
            (int) ($statusAgg->selesai ?? 0),
            (int) ($statusAgg->affiliate ?? 0),
            (int) ($statusAgg->pengiriman_gagal ?? 0),
            (int) ($statusAgg->batal ?? 0),
            (int) ($statusAgg->pengembalian ?? 0),
        ];
        
        $statusSummary = (clone $base)
            ->selectRaw("
                status,
                COUNT(*) as jumlah_pesanan,
                SUM(COALESCE(total_harga,0)) as penjualan,
                SUM(COALESCE(total_admin,0)) as admin,
                SUM(COALESCE(pencairan,0)) as pencairan,
                SUM(COALESCE(total_hpp,0)) as hpp
            ")
            ->groupBy('status')
            ->get();
        
        $totalStatusPesanan = $statusSummary->sum('jumlah_pesanan');
        
        $statusSummary = $statusSummary->map(function ($item) use ($totalStatusPesanan) {
        
            $item->persentase =
                $totalStatusPesanan > 0
                    ? round(($item->jumlah_pesanan / $totalStatusPesanan) * 100, 2)
                    : 0;
        
            $item->selisih =
                ($item->pencairan ?? 0)
                -
                (
                    ($item->penjualan ?? 0)
                    -
                    ($item->admin ?? 0)
                );
        
            return $item;
        
        });
        
        $totalStatus = [
            'jumlah_pesanan' => $statusSummary->sum('jumlah_pesanan'),
            'penjualan'      => $statusSummary->sum('penjualan'),
            'admin'          => $statusSummary->sum('admin'),
            'pencairan'      => $statusSummary->sum('pencairan'),
            'hpp'            => $statusSummary->sum('hpp'),
            'selisih'        => $statusSummary->sum('selisih'),
        ];

        // ===== View (dashboard) =====
        $daftarToko = Toko::select('id_toko', 'nama_toko', 'marketplace')
            ->orderBy('marketplace')
            ->orderBy('nama_toko')
            ->get();

        return view('laporan.penjualan', [
            'metrics' => [
                ['label' => 'Total Penjualan',       'value' => $penjualan,           'type' => 'currency', 'change' => $change($penjualan, $prevPenjualan)],
                ['label' => 'Total HPP',             'value' => $totalHPP,            'type' => 'currency', 'change' => $change($totalHPP, $prevTotalHPP)],
                ['label' => 'Biaya Admin',           'value' => $totalAdmin,          'type' => 'currency', 'change' => $change($totalAdmin, $prevTotalAdmin)],
                ['label' => 'Biaya Iklan',           'value' => $biayaIklan,          'type' => 'currency', 'change' => $change($biayaIklan, $prevBiayaIklan)],
                ['label' => 'Total Tarik',           'value' => $totalTarik,          'type' => 'currency', 'change' => $change($totalTarik, $prevTotalTarik)],
                ['label' => 'Pencairan',             'value' => $pencairan,           'type' => 'currency', 'change' => $change($pencairan, $prevPencairan)],
                ['label' => 'Selisih',               'value' => $selisih,             'type' => 'currency', 'change' => $change($selisih, $prevSelisih)],
                ['label' => 'Keuntungan',            'value' => $keuntungan,          'type' => 'currency', 'change' => $change($keuntungan, $prevKeuntungan)],
                ['label' => 'Total Pesanan',         'value' => $totalPesanan,        'type' => 'number',   'change' => $change($totalPesanan, $prevTotalPesanan)],
                ['label' => 'AVG/Pesanan',           'value' => $avgPesanan,          'type' => 'currency', 'change' => $change($avgPesanan, $prevAvgPesanan)],
                ['label' => 'ROAS',                  'value' => $roas,                'type' => 'number',   'decimals' => 2, 'change' => $change($roas, $prevRoas)],
                ['label' => 'Estimasi Keuntungan',   'value' => $estimasiKeuntungan,  'type' => 'currency', 'change' => $change($estimasiKeuntungan, $prevEstimasiKeuntungan)],
                ['label' => 'Pesanan Batal',         'value' => $pesananBatal,        'type' => 'number',   'change' => $change($pesananBatal, $prevPesananBatal), 'good' => 'down'],
                ['label' => 'Pengiriman Gagal',      'value' => $pengirimanGagal,     'type' => 'currency', 'change' => $change($pengirimanGagal, $prevPengirimanGagal), 'good' => 'down'],
                ['label' => 'Pesanan Pengembalian',  'value' => $pesananPengembalian, 'type' => 'number',   'change' => $change($pesananPengembalian, $prevPesananPengembalian), 'good' => 'down'],
                ['label' => 'Pengembalian',          'value' => $pengembalian,        'type' => 'currency', 'change' => $change($pengembalian, $prevPengembalian)],
                ['label' => 'Pesanan Affiliate',     'value' => $pesananAffiliate,    'type' => 'number',   'change' => $change($pesananAffiliate, $prevPesananAffiliate)],
                ['label' => 'Affiliate',             'value' => $affiliate,           'type' => 'currency', 'change' => $change($affiliate, $prevAffiliate)],
                ['label' => 'Biaya Lain-lain',       'value' => $biayaLainLain,       'type' => 'currency', 'change' => $change($biayaLainLain, $prevBiayaLainLain),],
            ],
            'startDate'   => $startDate,
            'endDate'     => $endDate,
            'chart'       => [
                'labels'        => $labels,
                'sales'         => $seriesPenjualan,
                'orders'        => $seriesPesanan,
                'adspend'       => $seriesIklan,
                'status_labels' => $statusLabels,
                'status_values' => $statusValues,
            ],
            'daftarToko'  => $daftarToko,
            'idToko'      => $tokoId,
            'marketplace' => $marketplace,
            'rawToko'     => $rawToko,
            'statusSummary' => $statusSummary,
            'totalStatusPesanan' => $totalStatusPesanan,
            'totalStatus' => $totalStatus,
        ]);
    }
}