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

    private function parseBaseShopee(
        string $uploadedPath
    ): array {
        $reader = IOFactory::createReaderForFile(
            $uploadedPath
        );

        if (
            method_exists(
                $reader,
                'setReadDataOnly'
            )
        ) {
            $reader->setReadDataOnly(
                true
            );
        }

        $spreadsheet = $reader->load(
            $uploadedPath
        );

        $sheet =
            $spreadsheet->getActiveSheet();

        $rows = $sheet->toArray(
            null,
            true,
            true,
            true
        );

        if (count($rows) < 2) {
            $this->cleanup(
                $spreadsheet
            );

            return [];
        }

        $firstRow =
            array_shift($rows);

        $header = [];

        foreach ($firstRow as $cell) {
            $h = strtolower(
                trim(
                    (string) $cell
                )
            );

            $h = preg_replace(
                '/[\s.\-]+/u',
                '_',
                $h
            );

            $header[] = $h;
        }

        $data = [];

        foreach ($rows as $rowCells) {
            $values =
                array_values($rowCells);

            if (
                count($header) !==
                count($values)
            ) {
                continue;
            }

            $assoc =
                array_combine(
                    $header,
                    $values
                );

            if (!is_array($assoc)) {
                continue;
            }

            foreach ($assoc as $k => $v) {
                if (is_string($v)) {
                    $assoc[$k] =
                        trim($v);
                }
            }

            $item = $assoc;

            $item['no_pesanan'] =
                trim(
                    (string) (
                        $item['order_sn']
                        ?? ''
                    )
                );

            if (
                $item['no_pesanan'] === ''
            ) {
                continue;
            }

            $item['nama_pembeli'] =
                trim(
                    (string) (
                        $item[
                            'order_receiver_name'
                        ]
                        ?? ''
                    )
                );

            $item['username'] =
                trim(
                    (string) (
                        $item[
                            'buyer_user_name'
                        ]
                        ?? ''
                    )
                );

            $item['kurir'] =
                trim(
                    (string) (
                        $item[
                            'shipping_method'
                        ]
                        ?? ''
                    )
                );

            $item['no_resi'] =
                trim(
                    (string) (
                        $item[
                            'tracking_number'
                        ]
                        ?? ''
                    )
                );

            $batasKirimRaw =
                $item[
                    'estimated_ship_out_date'
                ]
                ?? null;

            $batasKirimRawText =
                $batasKirimRaw !== null
                    ? trim(
                        (string)
                        $batasKirimRaw
                    )
                    : null;

            $item['batas_kirim_raw'] =
                $batasKirimRawText !== ''
                    ? $batasKirimRawText
                    : null;

            $item['batas_kirim_at'] =
                $this->normalizeDateTime(
                    $batasKirimRaw
                );

            $item[
                'batas_kirim_source'
            ] =
                $item[
                    'batas_kirim_raw'
                ]
                    ? 'shopee_estimated_ship_out_date'
                    : null;

            $item['produk_detail'] =
                !empty(
                    $item[
                        'product_info'
                    ]
                )
                    ? $this->parseProductInfo(
                        $item[
                            'product_info'
                        ]
                    )
                    : [];

            foreach (
                $item['produk_detail']
                as &$produk
            ) {
                $skuRaw = trim(
                    (string) (
                        $produk['sku']
                        ?? $produk['__sku']
                        ?? ''
                    )
                );

                $sku =
                    $this->normalizeSku(
                        $skuRaw
                    );

                $produk[
                    'sku_original'
                ] = $skuRaw;

                $produk['custom'] =
                    $this->isCustomSku(
                        $skuRaw
                    );

                $produk['sku'] =
                    $sku ?? '';

                $produk['__sku'] =
                    $sku ?? '';
            }

            unset($produk);

            $data[] = $item;
        }

        $this->cleanup(
            $spreadsheet
        );

        return $data;
    }

    public function parseTikTok(
        string $uploadedPath
    ): array {
        $reader =
            IOFactory::createReaderForFile(
                $uploadedPath
            );

        if (
            method_exists(
                $reader,
                'setReadDataOnly'
            )
        ) {
            $reader->setReadDataOnly(
                true
            );
        }

        $spreadsheet =
            $reader->load(
                $uploadedPath
            );

        $sheet =
            $spreadsheet
                ->getActiveSheet();

        $rows =
            $sheet->toArray(
                null,
                true,
                true,
                true
            );

        if (count($rows) < 3) {
            $this->cleanup(
                $spreadsheet
            );

            return [];
        }

        $data = [];

        for (
            $i = 3;
            $i <= count($rows);
            $i++
        ) {
            if (!isset($rows[$i])) {
                continue;
            }

            $r = $rows[$i];

            if (empty($r['A'])) {
                continue;
            }

            $subtotal =
                (float) preg_replace(
                    '/[^0-9]/',
                    '',
                    (string) (
                        $r['P']
                        ?? 0
                    )
                );

            $qty = max(
                1,
                (int) (
                    $r['J']
                    ?? 1
                )
            );

            $hargaSatuan =
                $qty > 0
                    ? round(
                        $subtotal / $qty
                    )
                    : $subtotal;

            $skuRaw = trim(
                (string) (
                    $r['G']
                    ?? ''
                )
            );

            $sku =
                $this->normalizeSku(
                    $skuRaw
                );

            $custom =
                $this->isCustomSku(
                    $skuRaw
                );

            $item = [
                'no_pesanan' =>
                    trim(
                        (string) (
                            $r['A']
                            ?? ''
                        )
                    ),

                'username' =>
                    trim(
                        (string) (
                            $r['AR']
                            ?? ''
                        )
                    ),

                'nama_pembeli' =>
                    trim(
                        (string) (
                            $r['AS']
                            ?? ''
                        )
                    ),

                'no_resi' =>
                    trim(
                        (string) (
                            $r['AN']
                            ?? ''
                        )
                    ),

                'kurir' =>
                    trim(
                        (string) (
                            $r['AP']
                            ?? ''
                        )
                    ),

                'batas_kirim_at' =>
                    null,

                'batas_kirim_raw' =>
                    null,

                'batas_kirim_source' =>
                    null,
            ];

            $item['produk_detail'] = [
                [
                    'sku_original' =>
                        $skuRaw,

                    'sku' =>
                        $sku ?? '',

                    '__sku' =>
                        $sku ?? '',

                    'Nama Produk' =>
                        trim(
                            (string) (
                                $r['H']
                                ?? ''
                            )
                        ),

                    'Nama Variasi' =>
                        trim(
                            (string) (
                                $r['I']
                                ?? ''
                            )
                        ),

                    'Jumlah' =>
                        $qty,

                    'Harga' =>
                        $hargaSatuan,

                    'Subtotal' =>
                        $subtotal,

                    'custom' =>
                        $custom,
                ],
            ];

            $item['sku'] =
                $sku ?? '';

            $item['__sku'] =
                $sku ?? '';

            $data[] = $item;
        }

        $this->cleanup(
            $spreadsheet
        );

        return $data;
    }

    private function parseProductInfo(
        string $info
    ): array {
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
                str_replace(
                    '▶',
                    '',
                    trim($chunk)
                )
            );

            $detail = [];

            foreach ($pairs as $pair) {
                [$k, $v] =
                    array_pad(
                        explode(
                            ':',
                            $pair,
                            2
                        ),
                        2,
                        null
                    );

                if (
                    $k !== null &&
                    $v !== null &&
                    trim($k) !== ''
                ) {
                    $detail[
                        trim($k)
                    ] = trim($v);
                }
            }

            $sku =
                $this->firstNonEmpty(
                    $detail,
                    [
                        'Nomor Referensi SKU',
                        'SKU Induk',
                        'SKU',
                        'sku',
                    ]
                );

            if ($sku !== null) {
                $detail['sku'] =
                    trim($sku);

                $detail['__sku'] =
                    trim($sku);
            }

            $result[] = $detail;
        }

        return $result;
    }

    public function normalizeSku(
        ?string $sku
    ): ?string {
        $sku = strtoupper(
            trim(
                (string) $sku
            )
        );

        $sku = preg_replace(
            '/\s+/u',
            '',
            $sku
        );

        if (
            $sku === null ||
            $sku === ''
        ) {
            return null;
        }

        if (
            preg_match(
                '/^(.+C)X([12])$/i',
                $sku,
                $match
            )
        ) {
            return strtoupper(
                $match[1]
            );
        }

        return $sku;
    }

    public function isCustomSku(
        ?string $sku
    ): int {
        $sku = strtoupper(
            trim(
                (string) $sku
            )
        );

        $sku = preg_replace(
            '/\s+/u',
            '',
            $sku
        );

        if (
            $sku === null ||
            $sku === ''
        ) {
            return 0;
        }

        return preg_match(
            '/CX[12]$/i',
            $sku
        )
            ? 1
            : 0;
    }

    private function normalizeDateTime(
        mixed $value
    ): ?string {
        if (
            $value === null ||
            $value === ''
        ) {
            return null;
        }

        if (is_numeric($value)) {
            try {
                return Carbon::instance(
                    ExcelDate::excelToDateTimeObject(
                        (float) $value
                    )
                )
                    ->format(
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

        foreach (
            $formatsDenganJam
            as $format
        ) {
            try {
                $date =
                    Carbon::createFromFormat(
                        $format,
                        $value
                    );

                if ($date) {
                    return $date->format(
                        'Y-m-d H:i:s'
                    );
                }
            } catch (\Throwable $e) {
            }
        }

        $formatsTanggal = [
            'Y-m-d',
            'd/m/Y',
            'd-m-Y',
        ];

        foreach (
            $formatsTanggal
            as $format
        ) {
            try {
                $date =
                    Carbon::createFromFormat(
                        $format,
                        $value
                    );

                if ($date) {
                    return $date
                        ->endOfDay()
                        ->format(
                            'Y-m-d H:i:s'
                        );
                }
            } catch (\Throwable $e) {
            }
        }

        try {
            $date =
                Carbon::parse(
                    $value
                );

            if (
                !preg_match(
                    '/\d{1,2}:\d{2}/',
                    $value
                )
            ) {
                $date->endOfDay();
            }

            return $date->format(
                'Y-m-d H:i:s'
            );

        } catch (\Throwable $e) {
            return null;
        }
    }

    private function firstNonEmpty(
        array $arr,
        array $keys
    ): ?string {
        foreach ($keys as $k) {
            if (
                isset($arr[$k]) &&
                trim(
                    (string) $arr[$k]
                ) !== ''
            ) {
                return trim(
                    (string) $arr[$k]
                );
            }
        }

        return null;
    }

    private function cleanup(
        $spreadsheet
    ): void {
        if (
            $spreadsheet &&
            method_exists(
                $spreadsheet,
                'disconnectWorksheets'
            )
        ) {
            $spreadsheet
                ->disconnectWorksheets();
        }

        unset($spreadsheet);
    }
}