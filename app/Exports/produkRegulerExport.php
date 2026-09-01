<?php

namespace App\Exports;

use App\Models\PesananPerProduk;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;

class produkRegulerExport implements FromArray, WithEvents, WithTitle
{
    private $data;

    public function __construct()
    {
        $dataAwal = PesananPerProduk::with(['pesanan', 'produk'])
            ->where('created_at', '>=', now()->subMonths(3))
            ->get();

        $dataFilter = $dataAwal
            ->where('custom', 0)
            ->where('status_pesanan', '0')
            ->where('status_produksi', false)
            ->whereNull('mutasi_stok_id')
            ->filter(function ($item) {
                return $item->pesanan?->status === 'proses';
            })
            ->take(30)
            ->map(function ($item) {
                return $item->only([
                    'sku',
                    'produk.nama_produk',
                    'produk.variasi',
                    'produk_id',
                ]);
            })
            ->values()
            ->toArray();

        // $produk = Produk::with('stok_produk')
        //     ->whereIn('sku', $skuList)
        //     ->get()
        //     ->keyBy('sku');

        // $stokProdukIds = $produk
        //     ->pluck('stok_produk.id')
        //     ->filter();

        // $mutasiKeluar = mutasi_stok::whereIn(
        //     'stok_produk_id',
        //     $stokProdukIds
        // )
        //     ->where('jenis_mutasi', 'keluar')
        //     ->where('created_at', '>=', now()->subDays(7))
        //     ->select('stok_produk_id')
        //     ->selectRaw('SUM(jumlah) AS total')
        //     ->groupBy('stok_produk_id')
        //     ->pluck('total', 'stok_produk_id');

        dd($dataFilter);
    }

    public function array(): array
    {
        $row = [
            ['', '', '', '', ''],
            ['', '', '', '', ''],
            ['Tanggal Export : ', now()->format('d/m/Y'), '', '', '', ''],
            ['SKU', 'Nama Produk', 'Variasi', 'Pesanan', 'Kebutuhan Produksi'],
        ];
        foreach ($this->data as $item) {
            $row[] = [
                'SKU' => $item->sku,
                'Nama Produk' => $item->produk->nama_produk ?? '-',
                'Variasi' => $item->produk->variasi ?? '-',
                'Pesanan' => $item->pesanan->jumlah ?? 0,
                'Kebutuhan Produksi' => $item->kebutuhan_produksi ?? 0,
            ];
        }

        return $row;
    }

    public function registerEvents(): array
    {
        return [];
    }

    public function title(): string
    {
        return 'Order Reguler';
    }
}
