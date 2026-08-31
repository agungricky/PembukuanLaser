<?php

namespace App\Imports;

use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class PesananExcelService
{
    public function parseShopee(string $uploadedPath): array
    {
        return $this->parseBaseShopee($uploadedPath);
    }

    private function parseBaseShopee(string $uploadedPath): array
    {
        $reader = IOFactory::createReaderForFile($uploadedPath);

        if (method_exists($reader, 'setReadDataOnly')) {
            $reader->setReadDataOnly(true);
        }

        $spreadsheet = $reader->load($uploadedPath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);

        if (count($rows) < 2) {
            $this->cleanup($spreadsheet);

            return [];
        }

        $firstRow = array_shift($rows);
        $header = [];

        foreach ($firstRow as $cell) {
            $h = strtolower(trim((string) $cell));
            $h = preg_replace('/[\s.\-]+/u', '_', $h);
            $header[] = $h;
        }

        $data = [];

        foreach ($rows as $rowCells) {
            $values = array_values($rowCells);

            if (count($header) !== count($values)) {
                continue;
            }

            $assoc = array_combine($header, $values);

            if (! is_array($assoc)) {
                continue;
            }

            foreach ($assoc as $k => $v) {
                if (is_string($v)) {
                    $assoc[$k] = trim($v);
                }
            }

            $item = $assoc;
            $item['no_pesanan'] = trim((string) ($item['order_sn'] ?? ''));

            if ($item['no_pesanan'] === '') {
                continue;
            }

            $item['nama_pembeli'] = trim((string) ($item['order_receiver_name'] ?? ''));
            $item['username'] = trim((string) ($item['buyer_user_name'] ?? ''));
            $item['kurir'] = trim((string) ($item['shipping_method'] ?? ''));
            $item['no_resi'] = trim((string) ($item['tracking_number'] ?? ''));

            $batasKirimRaw = $item['estimated_ship_out_date'] ?? null;
            $batasKirimRawText = $batasKirimRaw !== null ? trim((string) $batasKirimRaw) : null;

            $item['batas_kirim_raw'] = $batasKirimRawText !== '' ? $batasKirimRawText : null;
            $item['batas_kirim_at'] = $this->normalizeDateTime($batasKirimRaw);
            $item['batas_kirim_source'] = $item['batas_kirim_raw']
                ? 'shopee_estimated_ship_out_date'
                : null;

            $item['produk_detail'] = ! empty($item['product_info'])
                ? $this->parseProductInfo((string) $item['product_info'])
                : [];

            $data[] = $item;
        }

        $this->cleanup($spreadsheet);

        return $data;
    }

    public function parseTikTok(string $uploadedPath): array
    {
        $reader = IOFactory::createReaderForFile($uploadedPath);

        if (method_exists($reader, 'setReadDataOnly')) {
            $reader->setReadDataOnly(true);
        }

        $spreadsheet = $reader->load($uploadedPath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);

        if (count($rows) < 3) {
            $this->cleanup($spreadsheet);

            return [];
        }

        $data = [];

        for ($i = 3; $i <= count($rows); $i++) {
            if (! isset($rows[$i])) {
                continue;
            }

            $r = $rows[$i];

            if (empty($r['A'])) {
                continue;
            }

            $jumlahPesanan = max(1, (int) ($r['J'] ?? 1));
            $subtotal = $this->toNumber($r['P'] ?? 0);
            $hargaMarketplace = $jumlahPesanan > 0
                ? round($subtotal / $jumlahPesanan, 2)
                : $subtotal;

            $skuOriginal = trim((string) ($r['G'] ?? ''));
            $sku = $this->normalizeSku($skuOriginal) ?? '';
            $multiplier = $this->getSkuMultiplier($skuOriginal);
            $jumlahReal = $jumlahPesanan * $multiplier;
            $hargaJualReal = round($hargaMarketplace / $multiplier, 2);
            $custom = $this->isCustomSku($skuOriginal);

            $item = [
                'no_pesanan' => trim((string) ($r['A'] ?? '')),
                'username' => trim((string) ($r['AR'] ?? '')),
                'nama_pembeli' => trim((string) ($r['AS'] ?? '')),
                'no_resi' => trim((string) ($r['AN'] ?? '')),
                'kurir' => trim((string) ($r['AP'] ?? '')),
                'batas_kirim_at' => null,
                'batas_kirim_raw' => null,
                'batas_kirim_source' => null,
            ];

            $item['produk_detail'] = [[
                'sku_original' => $skuOriginal,
                'sku_asli' => $skuOriginal,
                '__sku' => $skuOriginal,
                'sku' => $sku,
                'custom' => $custom,
                'multiplier' => $multiplier,
                'jumlah_pesanan' => $jumlahPesanan,
                'jumlah' => $jumlahReal,
                'Jumlah' => $jumlahReal,
                'harga_original' => $hargaMarketplace,
                'Harga_original' => $hargaMarketplace,
                'harga' => $hargaJualReal,
                'Harga' => $hargaJualReal,
                'subtotal' => $subtotal,
                'Subtotal' => $subtotal,
                'nama_produk' => trim((string) ($r['H'] ?? '')),
                'Nama Produk' => trim((string) ($r['H'] ?? '')),
                'variasi' => trim((string) ($r['I'] ?? '')),
                'Nama Variasi' => trim((string) ($r['I'] ?? '')),
            ]];

            $item['sku'] = $sku;
            $item['sku_original'] = $skuOriginal;
            $item['sku_asli'] = $skuOriginal;
            $item['__sku'] = $skuOriginal;

            $data[] = $item;
        }

        $this->cleanup($spreadsheet);

        return $data;
    }

    private function parseProductInfo(string $info): array
    {
        $chunks = preg_split(
            '/\[\d+\]/u',
            $info,
            -1,
            PREG_SPLIT_NO_EMPTY
        );

        $result = [];

        foreach ($chunks as $chunk) {
            $pairs = explode(
                ';',
                str_replace('▶', '', trim($chunk))
            );

            $detail = [];

            foreach ($pairs as $pair) {
                [$k, $v] = array_pad(
                    explode(':', $pair, 2),
                    2,
                    null
                );

                if ($k === null || $v === null) {
                    continue;
                }

                $k = trim((string) $k);
                $v = trim((string) $v);

                if ($k !== '') {
                    $detail[$k] = $v;
                }
            }

            $skuOriginal = $this->firstNonEmpty(
                $detail,
                [
                    'Nomor Referensi SKU',
                    'SKU Induk',
                    'SKU',
                    'sku',
                ]
            ) ?? '';

            $skuOriginal = trim($skuOriginal);
            $sku = $this->normalizeSku($skuOriginal) ?? '';
            $multiplier = $this->getSkuMultiplier($skuOriginal);
            $jumlahPesanan = $this->getProductQuantity($detail);
            $jumlahReal = $jumlahPesanan * $multiplier;
            $hargaMarketplace = $this->getProductPrice($detail);
            $hargaJualReal = round($hargaMarketplace / $multiplier, 2);
            $subtotal = round($hargaJualReal * $jumlahReal, 2);

            $detail['sku_original'] = $skuOriginal;
            $detail['sku_asli'] = $skuOriginal;
            $detail['__sku'] = $skuOriginal;
            $detail['sku'] = $sku;
            $detail['custom'] = $this->isCustomSku($skuOriginal);
            $detail['multiplier'] = $multiplier;
            $detail['jumlah_pesanan'] = $jumlahPesanan;
            $detail['jumlah'] = $jumlahReal;
            $detail['Jumlah'] = $jumlahReal;
            $detail['harga_original'] = $hargaMarketplace;
            $detail['Harga_original'] = $hargaMarketplace;
            $detail['harga'] = $hargaJualReal;
            $detail['Harga'] = $hargaJualReal;
            $detail['subtotal'] = $subtotal;
            $detail['Subtotal'] = $subtotal;

            if (isset($detail['Nama Produk'])) {
                $detail['nama_produk'] = trim((string) $detail['Nama Produk']);
            }

            if (isset($detail['Nama Variasi'])) {
                $detail['variasi'] = trim((string) $detail['Nama Variasi']);
            }

            $result[] = $detail;
        }

        return $result;
    }

    public function normalizeSku(?string $sku): ?string
    {
        $sku = strtoupper(trim((string) $sku));
        $sku = preg_replace('/\s+/u', '', $sku);

        if ($sku === null || $sku === '') {
            return null;
        }

        return preg_replace('/X[12]$/i', '', $sku);
    }

    public function getSkuMultiplier(?string $sku): int
    {
        $sku = strtoupper(trim((string) $sku));
        $sku = preg_replace('/\s+/u', '', $sku);

        if ($sku === null || $sku === '') {
            return 1;
        }

        if (preg_match('/X([12])$/i', $sku, $match)) {
            return max(1, (int) $match[1]);
        }

        return 1;
    }

    public function isCustomSku(?string $sku): int
    {
        $sku = strtoupper(trim((string) $sku));
        $sku = preg_replace('/\s+/u', '', $sku);

        if ($sku === null || $sku === '') {
            return 0;
        }

        return preg_match('/CX[12]$/i', $sku) ? 1 : 0;
    }

    private function getProductQuantity(array $detail): int
    {
        $jumlah = $this->firstNonEmpty(
            $detail,
            [
                'Jumlah',
                'jumlah',
                'Quantity',
                'quantity',
                'Qty',
                'qty',
                'Jumlah Produk',
                'Jumlah Produk Dibeli',
            ]
        );

        if ($jumlah === null || trim((string) $jumlah) === '') {
            return 1;
        }

        $angka = preg_replace('/[^0-9]/', '', (string) $jumlah);

        if ($angka === null || $angka === '') {
            return 1;
        }

        return max(1, (int) $angka);
    }

    private function getProductPrice(array $detail): float
    {
        $harga = $this->firstNonEmpty(
            $detail,
            [
                'Harga Setelah Diskon',
                'Harga',
                'Harga Produk',
                'harga',
            ]
        );

        return $this->toNumber($harga ?? 0);
    }

    private function normalizeDateTime(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            try {
                return Carbon::instance(
                    ExcelDate::excelToDateTimeObject((float) $value)
                )->format('Y-m-d H:i:s');
            } catch (\Throwable $e) {
            }
        }

        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        $formatsDenganJam = [
            'Y-m-d H:i:s',
            'Y-m-d H:i',
            'd/m/Y H:i:s',
            'd/m/Y H:i',
            'd-m-Y H:i:s',
            'd-m-Y H:i',
        ];

        foreach ($formatsDenganJam as $format) {
            try {
                $date = Carbon::createFromFormat($format, $value);

                if ($date) {
                    return $date->format('Y-m-d H:i:s');
                }
            } catch (\Throwable $e) {
            }
        }

        $formatsTanggal = [
            'Y-m-d',
            'd/m/Y',
            'd-m-Y',
        ];

        foreach ($formatsTanggal as $format) {
            try {
                $date = Carbon::createFromFormat($format, $value);

                if ($date) {
                    return $date->endOfDay()->format('Y-m-d H:i:s');
                }
            } catch (\Throwable $e) {
            }
        }

        try {
            $date = Carbon::parse($value);

            if (! preg_match('/\d{1,2}:\d{2}/', $value)) {
                $date->endOfDay();
            }

            return $date->format('Y-m-d H:i:s');
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function firstNonEmpty(array $arr, array $keys): ?string
    {
        foreach ($keys as $k) {
            if (! array_key_exists($k, $arr)) {
                continue;
            }

            $value = $arr[$k];

            if ($value === null) {
                continue;
            }

            $value = trim((string) $value);

            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function toNumber(mixed $value): float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $value = trim((string) $value);

        if ($value === '') {
            return 0;
        }

        $clean = preg_replace('/[^0-9\-]/', '', $value);

        if ($clean === null || $clean === '' || $clean === '-') {
            return 0;
        }

        return (float) $clean;
    }

    private function cleanup($spreadsheet): void
    {
        if (
            $spreadsheet &&
            method_exists($spreadsheet, 'disconnectWorksheets')
        ) {
            $spreadsheet->disconnectWorksheets();
        }

        unset($spreadsheet);
    }
}
