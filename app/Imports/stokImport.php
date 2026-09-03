<?php

namespace App\Imports;

use App\Models\stok_produk;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;

class stokImport implements ToCollection, WithStartRow
{
    protected array $dataBerubah = [];
    protected array $skuTidakDitemukan = [];

    public function startRow(): int
    {
        return 3;
    }

    public function collection(Collection $rows)
    {
        $skuExcel = $rows
            ->pluck(0)
            ->filter()
            ->map(function ($sku) {
                return strtoupper(trim($sku));
            })
            ->unique()
            ->values();

        $stokProduk = stok_produk::whereIn('sku_id', $skuExcel)
            ->get()
            ->keyBy('sku_id');

       
        foreach ($rows as $row) {
            $sku = strtoupper(trim($row[0] ?? ''));
            if ($sku === '') {
                continue;
            }

            $stokExcel = (int) ($row[3] ?? 0);
            if (!$stokProduk->has($sku)) {
                $this->skuTidakDitemukan[] = [
                    'sku' => $sku,
                    'stok_excel' => $stokExcel,
                ];

                continue;
            }

            $stok = $stokProduk->get($sku);
            $stokLama = (int) $stok->jumlah_tersedia;
            if ($stokLama !== $stokExcel) {
                $this->dataBerubah[] = [
                    'stok_produk_id' => $stok->id,
                    'sku' => $sku,
                    'stok_lama' => $stokLama,
                    'stok_baru' => $stokExcel,
                    'selisih' => $stokExcel - $stokLama,
                ];
            }
        }
    }

    public function getDataBerubah(): array
    {
        return $this->dataBerubah;
    }

    public function getSkuTidakDitemukan(): array
    {
        return $this->skuTidakDitemukan;
    }
}