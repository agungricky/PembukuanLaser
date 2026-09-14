<?php

namespace App\Services\Gudang;
use App\Models\PesananPerProduk;
use App\Models\Produk;
use Carbon\Carbon;

class CustomService
{
    public function produkcustom()
    {
        $pesanan_perproduk = PesananPerProduk::with('pesanan')
            ->where('custom', 1)
            ->whereHas('pesanan')
            ->get();

        $data = $pesanan_perproduk
            ->groupBy(function ($item) {
                $tanggal = Carbon::parse($item->pesanan->tanggal);

                return $item->sku.'-'.$tanggal->format('Y-m');
            })
            ->map(function ($items) {
                $firstItem = $items->first();
                $produk = Produk::where('sku', $firstItem->sku)->first();
                $tanggal = Carbon::parse($firstItem->pesanan->tanggal);

                return [
                    'sku' => $firstItem->sku,
                    'nama_produk' => $produk?->nama_produk ?? $firstItem->nama_produk,
                    'variasi' => $produk?->variasi ?? $firstItem->variasi,
                    'qty' => $items->sum('jumlah'),
                    'diproses' => $items
                        ->filter(fn ($item) => $item->pesanan->status === 'proses')
                        ->sum('jumlah'),
                    'kirim' => $items
                        ->filter(fn ($item) => $item->pesanan->status === 'kirim')
                        ->sum('jumlah'),
                    'retur' => $items
                        ->filter(fn ($item) => $item->pesanan->status === 'pengiriman gagal' && $item->pesanan->status === 'pengembalian')
                        ->sum('jumlah'),
                    'selesai' => $items
                        ->filter(fn ($item) => $item->pesanan->status === 'selesai')
                        ->sum('jumlah'),
                    'bulan' => $tanggal->month,
                    'tahun' => $tanggal->year,
                    'tanggal_awal' => $tanggal->copy()->startOfMonth(),
                    'tanggal_akhir' => $tanggal->copy()->endOfMonth(),
                    'data' => $items->values(),
                ];
            })
            ->values();

        return view('gudang.produk_custom', compact('data'));
    }
}
