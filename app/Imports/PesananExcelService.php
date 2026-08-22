<?php

namespace App\Imports;

use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class PesananExcelService
{
    public function parseShopee(string $uploadedPath): array
    {
        return $this->parseBaseShopee(
            $uploadedPath
        );
    }

    private function parseBaseShopee(string $uploadedPath): array
    {
        $reader = IOFactory::createReaderForFile($uploadedPath);
        if (method_exists($reader, 'setReadDataOnly')) {
            $reader->setReadDataOnly(
                true
            );
        }

        $spreadsheet = $reader->load($uploadedPath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);

        if (count($rows) < 2) {
            return [];
        }

        $firstRow = array_shift($rows);
        $header = [];

        foreach ($firstRow as $cell) {
            $h = strtolower(str_replace([' ', '.', '-'], '_',
                trim(
                    (string) $cell
                )
            )
            );

            $header[] = $h;
        }

        $data = [];
        foreach ($rows as $rowCells) {
            $values = array_values($rowCells);
            $assoc = array_combine($header, $values);

            foreach ($assoc as $k => $v) {
                if (is_string($v)) {
                    $assoc[$k] = trim($v);
                }
            }

            $item = $assoc;
            $item['no_pesanan'] = $item['order_sn'] ?? '';
            $item['nama_pembeli'] = $item['order_receiver_name'] ?? '';
            $item['username'] = $item['buyer_user_name'] ?? '';
            $item['kurir'] = $item['shipping_method'] ?? '';
            $item['no_resi'] = $item['tracking_number'] ?? '';
            $batasKirimRaw = $item['estimated_ship_out_date'] ?? null;
            $item['batas_kirim_raw'] = $batasKirimRaw !== null ? trim((string) $batasKirimRaw) : null;
            $item['batas_kirim_at'] = $this->normalizeDateTime($batasKirimRaw);
            $item['batas_kirim_source'] = $item['batas_kirim_at'] ? 'shopee_excel' : null;
            $item['produk_detail'] = ! empty($item['product_info']) ? $this->parseProductInfo($item['product_info']) : [];
            foreach ($item['produk_detail'] as &$produk) {
                $sku = strtoupper(trim($produk['sku'] ?? ''));
                $produk['custom'] = substr($sku, -3, 2) === 'CX' ? 1 : 0;
            }

            unset($produk);
            $data[] = $item;
        }

        $this->cleanup(
            $spreadsheet
        );

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
            return [];
        }

        $data = [];
        for ($i = 3; $i <= count($rows); $i++) {
            $r = $rows[$i];

            if (empty($r['A'])) {
                continue;
            }

            $subtotal = floatval(preg_replace('/[^0-9]/', '', $r['P'] ?? 0));
            $qty = max(1, intval($r['J'] ?? 1));
            $hargaSatuan = $qty > 0 ? round($subtotal / $qty) : $subtotal;

            $sku = trim($r['G'] ?? '');
            $custom = strtoupper(substr($sku, -3, 2)) === 'CX' ? 1 : 0;
            $item = [
                'no_pesanan' => trim($r['A'] ?? ''),
                'username' => trim($r['AR'] ?? ''),
                'nama_pembeli' => trim($r['AS'] ?? ''),
                'no_resi' => trim($r['AN'] ?? ''),
                'kurir' => trim($r['AP'] ?? ''),
                'batas_kirim_at' => null,
                'batas_kirim_raw' => null,
                'batas_kirim_source' => null,
            ];

            $item['produk_detail'] = [
                [
                    'sku' => trim($r['G'] ?? ''),
                    'Nama Produk' => trim($r['H'] ?? ''),
                    'Nama Variasi' => trim($r['I'] ?? ''),
                    'Jumlah' => $qty,
                    'Harga' => $hargaSatuan,
                    'Subtotal' => $subtotal,
                    'custom' => $custom,
                ],
            ];

            $item['sku'] = $item['produk_detail'][0]['sku'];
            $item['__sku'] = $item['sku'];
            $data[] = $item;
        }

        $this->cleanup($spreadsheet);

        return $data;
    }

    private function parseProductInfo(string $info): array
    {
        $chunks = preg_split('/\[\d+\]/u', $info, -1, PREG_SPLIT_NO_EMPTY);
        $result = [];

        foreach ($chunks as $chunk) {
            $pairs = explode(';',
                str_replace(
                    '▶',
                    '',
                    trim($chunk)
                )
            );

            $detail = [];
            foreach ($pairs as $pair) {
                [$k, $v] = array_pad(explode(':', $pair, 2), 2, null);
                if ($k && $v) {
                    $detail[trim($k)] = trim($v);
                }
            }

            $sku = $this->firstNonEmpty(
                $detail,
                [
                    'Nomor Referensi SKU',
                    'SKU Induk',
                    'SKU',
                    'sku',
                ]
            );

            if ($sku) {
                $sku = trim($sku);
                $detail['sku'] = $sku;
                $detail['__sku'] = $sku;
            }

            $result[] = $detail;
        }

        return $result;
    }

    private function normalizeDateTime(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            try {
                return Carbon::instance(
                    ExcelDate::excelToDateTimeObject(
                        (float) $value
                    )
                )->format(
                    'Y-m-d H:i:s'
                );
            } catch (\Throwable $e) {
            }
        }

        $value = trim(
            (string) $value
        );

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
                    return $date->endOfDay()
                        ->format(
                            'Y-m-d H:i:s'
                        );
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
            if (isset($arr[$k]) && trim((string) $arr[$k]) !== '') {
                return trim(
                    (string) $arr[$k]
                );
            }
        }

        return null;
    }

    private function cleanup($spreadsheet): void
    {
        if (method_exists($spreadsheet, 'disconnectWorksheets')) {
            $spreadsheet->disconnectWorksheets();
        }

        unset($spreadsheet);
    }
}
