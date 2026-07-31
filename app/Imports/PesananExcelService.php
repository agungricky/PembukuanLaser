<?php

namespace App\Imports;

use PhpOffice\PhpSpreadsheet\IOFactory;

class PesananExcelService
{
    public function parseShopee(string $uploadedPath): array
    {
        return $this->parseBaseShopee($uploadedPath);
    }

    private function parseBaseShopee(string $uploadedPath): array
    {
        $reader = IOFactory::createReaderForFile($uploadedPath);
        if (method_exists($reader, 'setReadDataOnly'))
            $reader->setReadDataOnly(true);

        $spreadsheet = $reader->load($uploadedPath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);

        if (count($rows) < 2)
            return [];

        $firstRow = array_shift($rows);
        $header = [];
        foreach ($firstRow as $cell) {
            $h = strtolower(str_replace([' ', '.', '-'], '_', trim((string) $cell)));
            $header[] = $h;
        }

        $data = [];

        foreach ($rows as $rowCells) {
            $values = array_values($rowCells);
            $assoc = array_combine($header, $values);

            foreach ($assoc as $k => $v) {
                if (is_string($v))
                    $assoc[$k] = trim($v);
            }

            $item = $assoc;

            $item['no_pesanan'] = $item['order_sn'] ?? '';
            $item['nama_pembeli'] = $item['order_receiver_name'] ?? '';
            $item['username'] = $item['buyer_user_name'] ?? '';
            $item['kurir'] = $item['shipping_method'] ?? '';
            $item['no_resi'] = $item['tracking_number'] ?? '';

            $item['produk_detail'] = !empty($item['product_info'])
                ? $this->parseProductInfo($item['product_info'])
                : [];

            $data[] = $item;
        }

        $this->cleanup($spreadsheet);
        return $data;
    }

    public function parseTikTok(string $uploadedPath): array
    {
        $reader = IOFactory::createReaderForFile($uploadedPath);
        if (method_exists($reader, 'setReadDataOnly'))
            $reader->setReadDataOnly(true);

        $spreadsheet = $reader->load($uploadedPath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);

        if (count($rows) < 3)
            return [];

        $data = [];

        for ($i = 3; $i <= count($rows); $i++) {
            $r = $rows[$i];

            if (empty($r['A']))
                continue;

            $subtotal = floatval(preg_replace('/[^0-9]/', '', $r['P'] ?? 0));
            $qty = max(1, intval($r['J'] ?? 1));

            $hargaSatuan = $qty > 0 ? round($subtotal / $qty) : $subtotal;

            $item = [
                'no_pesanan' => trim($r['A'] ?? ''),
                'username' => trim($r['AR'] ?? ''),
                'nama_pembeli' => trim($r['AS'] ?? ''),
                'no_resi' => trim($r['AN'] ?? ''),
                'kurir' => trim($r['AP'] ?? ''),
            ];

            $item['produk_detail'] = [
                [
                    'sku' => trim($r['G'] ?? ''),
                    'Nama Produk' => trim($r['H'] ?? ''),
                    'Nama Variasi' => trim($r['I'] ?? ''),
                    'Jumlah' => $qty,
                    'Harga' => $hargaSatuan,
                    'Subtotal' => $subtotal
                ]
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
            $pairs = explode(';', str_replace('▶', '', trim($chunk)));
            $detail = [];

            foreach ($pairs as $pair) {
                [$k, $v] = array_pad(explode(':', $pair, 2), 2, null);
                if ($k && $v)
                    $detail[trim($k)] = trim($v);
            }

            $sku = $this->firstNonEmpty($detail, ['Nomor Referensi SKU', 'SKU Induk', 'SKU', 'sku']);
            if ($sku) {
                $sku = trim($sku);
                $detail['sku'] = $detail['__sku'] = $sku;
            }

            $result[] = $detail;
        }
        return $result;
    }



    private function firstNonEmpty(array $arr, array $keys): ?string
    {
        foreach ($keys as $k) {
            if (isset($arr[$k]) && trim($arr[$k]) !== '')
                return trim($arr[$k]);
        }
        return null;
    }

    private function cleanup($sheet): void
    {
        if (method_exists($sheet, 'disconnectWorksheets'))
            $sheet->disconnectWorksheets();
        unset($sheet);
    }
}