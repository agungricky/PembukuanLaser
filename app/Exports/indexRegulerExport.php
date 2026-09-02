<?php

namespace App\Exports;

use App\Models\PesananPerProduk;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class indexRegulerExport implements WithMultipleSheets
{
    protected $tabone;

    protected $tabtwo;

    public function __construct()
    {
        $dataAwal = PesananPerProduk::with(['pesanan', 'produk.stok_produk'])
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
            ->map(function ($item) {
                return [
                    'id_per_produk' => $item->id_per_produk,
                    'jumlah' => $item->jumlah,
                    'sku' => $item->produk?->sku ?? '-',
                    'nama_produk' => $item->produk?->nama_produk ?? '-',
                    'variasi' => $item->produk?->variasi ?? '-',
                    'stok_id' => $item->produk?->stok_produk?->id,
                    'jumlah_stok' => $item->produk?->stok_produk?->jumlah_tersedia,
                ];
            })
            ->values();

        $this->tabone = $dataFilter
            ->groupBy('sku')
            ->map(function ($items, $sku) {
                $first = $items->first();

                $totalPesanan = (int) $items->sum('jumlah');
                $jumlahStok = (int) ($first['jumlah_stok'] ?? 0);

                $kebutuhanProduksi = max(
                    $totalPesanan - $jumlahStok,
                    0
                );

                return [
                    'sku' => $sku,
                    'nama_produk' => $first['nama_produk'] ?? '-',
                    'variasi' => $first['variasi'] ?? '-',
                    'total_pesanan' => $totalPesanan,
                    'stok_id' => $first['stok_id'] ?? null,
                    'jumlah_stok' => $jumlahStok,
                    'kebutuhan_produksi' => $kebutuhanProduksi,
                ];
            })
            ->filter(fn ($item) => $item['kebutuhan_produksi'] > 0)
            ->sortByDesc('kebutuhan_produksi')
            ->values();

        $skuTabOne = $this->tabone
            ->pluck('sku');

        $this->tabtwo = $dataFilter
            ->whereIn('sku', $skuTabOne)
            ->groupBy('sku')
            ->map(function ($items) {
                return $items->values();
            });

        // testing
        // dd(
        //     $this->tabtwo
        //         ->take(-10)
        //         ->toArray()
        // );
    }

    public function sheets(): array
    {
        return [
            new produkRegulerExport($this->tabone),
            new perprodukRegulerExport($this->tabtwo),
        ];
    }
}
