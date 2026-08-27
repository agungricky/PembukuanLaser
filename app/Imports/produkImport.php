<?php

namespace App\Imports;

use App\Models\kategori;
use App\Models\Produk;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;

class produkImport implements ToCollection, WithStartRow
{
    public array $perubahan = [];

    public function startRow(): int
    {
        return 4;
    }

    public function collection(Collection $rows): void
    {
        // Looping Semua Data kategori
        $kategoriMap = kategori::pluck('id', 'nama_kategori')->toArray();

        foreach ($rows as $row) {
            $sku = $row[0] ?? null;
            $hppBaru = $row[3] ?? null;

            $kategoriBaru = trim((string) ($row[5] ?? null));
            if ($row[5] == "-") {
                 $kategoriBaru = null;
            }

            if (! $sku || $hppBaru === null) {
                continue;
            }

            $produk = Produk::with('kategori')->where('sku', $sku)->first();
            $kategoriLama = $produk->kategori?->nama_kategori ?? null;

            if (! $produk) {
                continue;
            }

            $hppLama = round((float) $produk->hpp, 2);
            $hppBaru = round((float) $hppBaru, 2);

            $hppBerubah = $hppLama !== $hppBaru;
            $kategoriBerubah = $kategoriLama !== $kategoriBaru;

            if (! $hppBerubah && ! $kategoriBerubah) {
                continue;
            }

            $this->perubahan[] = [
                'sku' => $produk->sku,
                'nama_produk' => $produk->nama_produk,
                'hpp_lama' => $hppLama,
                'hpp_baru' => $hppBaru,
                'kategori_lama' => $kategoriLama,
                'kategori_baru' => $kategoriBaru,
            ];
        }
    }
}
