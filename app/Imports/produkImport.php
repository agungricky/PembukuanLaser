<?php

namespace App\Imports;

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
        foreach ($rows as $row) {

            $sku = $row[0] ?? null;
            $hppBaru = $row[3] ?? null;

            if (!$sku || $hppBaru === null) {
                continue;
            }

            $produk = Produk::where('sku', $sku)->first();

            if (!$produk) {
                continue;
            }

            $hppLama = round((float) $produk->hpp, 2);
            $hppBaru = round((float) $hppBaru, 2);

            // Kalau sama, skip
            if ($hppLama === $hppBaru) {
                continue;
            }

            $this->perubahan[] = [
                'sku' => $produk->sku,
                'nama_produk' => $produk->nama_produk,
                'hpp_lama' => $hppLama,
                'hpp_baru' => $hppBaru,
            ];
        }
    }
}