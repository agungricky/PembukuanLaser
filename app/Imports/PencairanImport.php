<?php

namespace App\Imports;

use App\Models\Pesanan;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Concerns\WithStartRow;
use Maatwebsite\Excel\Row;

class PencairanImport implements OnEachRow, WithStartRow
{
    protected string $marketplace;
    protected int $updated = 0;
    protected int $skipped = 0;

    private const SHOPEE_COL_NO_PESANAN = 3;
    private const SHOPEE_COL_JUMLAH     = 5;

    private const TIKTOK_COL_NO_PESANAN = 0;
    private const TIKTOK_COL_JUMLAH     = 5;

    public function __construct(string $marketplace)
    {
        $this->marketplace = $marketplace;
    }

    public function startRow(): int
    {
        return match ($this->marketplace) {
            'Shopee' => 19,
            'TikTok' => 2,
            default  => 2,
        };
    }

    public function onRow(Row $row): void
    {
        $r = $row->toArray();

        if ($this->marketplace === 'Shopee') {
            $noRaw  = $r[self::SHOPEE_COL_NO_PESANAN] ?? '';
            $amount = $r[self::SHOPEE_COL_JUMLAH]     ?? 0;
        } else { // TikTok
            $noRaw  = $r[self::TIKTOK_COL_NO_PESANAN] ?? '';
            $amount = $r[self::TIKTOK_COL_JUMLAH]     ?? 0;
        }

        $noPesanan = trim((string) $noRaw);

        if ($noPesanan === '') {
            $this->skipped++;
            return;
        }

        if (preg_match('/^\d+(\.0+)?$/', $noPesanan)) {
            $noPesanan = preg_replace('/\.0+$/', '', $noPesanan);
        }

        $jumlah = $this->parseAmount($amount);

        $pesanan = Pesanan::where('no_pesanan', $noPesanan)->first();

        if ($pesanan) {
            $pesanan->pencairan = $jumlah;
            $pesanan->status    = 'selesai';
            $pesanan->save();
            $this->updated++;
        } else {
            $this->skipped++;
        }
    }

    private function parseAmount($value): float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $s = preg_replace('/[^0-9,.\-]/', '', (string) $value);

        if (strpos($s, ',') !== false && strpos($s, '.') !== false) {
            $s = str_replace('.', '', $s);
            $s = str_replace(',', '.', $s);
        } elseif (strpos($s, ',') === false && substr_count($s, '.') >= 1) {
            $s = str_replace('.', '', $s);
        } elseif (strpos($s, ',') !== false) {
            $s = str_replace(',', '.', $s);
        }

        return is_numeric($s) ? (float) $s : 0.0;
    }

    public function updatedCount(): int
    {
        return $this->updated;
    }

    public function skippedCount(): int
    {
        return $this->skipped;
    }
}