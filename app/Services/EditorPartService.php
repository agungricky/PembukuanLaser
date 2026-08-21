<?php

namespace App\Services;

use App\Models\EditorPart;
use App\Models\EditorPartItem;
use Illuminate\Support\Facades\DB;

class EditorPartService
{
    private const KAPASITAS = 52;

    public function sinkronkanPekerjaanTersedia(
        ?int $userId = null
    ): array {
        $ids = DB::table('pesanan_per_produk as pp')
            ->join(
                'pesanan as p',
                'p.no_pesanan',
                '=',
                'pp.no_pesanan'
            )
            ->whereNotNull('pp.sku')
            ->whereRaw("UPPER(pp.sku) LIKE 'PLT%'")
            ->where('p.status', 'proses')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('editor_part_items as epi')
                    ->whereColumn(
                        'epi.id_per_produk',
                        'pp.id_per_produk'
                    );
            })
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('editor_requests as er')
                    ->whereColumn(
                        'er.id_per_produk',
                        'pp.id_per_produk'
                    );
            })
            ->orderByRaw(
                'CASE WHEN p.batas_kirim_at IS NULL THEN 1 ELSE 0 END'
            )
            ->orderBy('p.batas_kirim_at')
            ->orderBy('p.tanggal')
            ->orderBy('pp.id_per_produk')
            ->pluck('pp.id_per_produk')
            ->map(
                fn ($id) => (int) $id
            )
            ->all();

        return $this->alokasikanItemBaru(
            $ids,
            $userId
        );
    }

    public function alokasikanItemBaru(
        array $idPerProduk,
        ?int $userId = null
    ): array {
        $idPerProduk = collect(
            $idPerProduk
        )
            ->map(
                fn ($id) => (int) $id
            )
            ->filter(
                fn ($id) => $id > 0
            )
            ->unique()
            ->values()
            ->all();

        if (empty($idPerProduk)) {
            return [
                'items' => 0,
                'parts_baru' => 0,
                'oversize' => [],
                'ignored' => [],
            ];
        }

        return DB::transaction(
            function () use (
                $idPerProduk,
                $userId
            ) {
                return $this->prosesAlokasi(
                    $idPerProduk,
                    $userId
                );
            }
        );
    }

    private function prosesAlokasi(
        array $idPerProduk,
        ?int $userId
    ): array {
        $tanggalPart =
            now()->toDateString();

        $items = DB::table(
            'pesanan_per_produk as pp'
        )
            ->join(
                'pesanan as p',
                'p.no_pesanan',
                '=',
                'pp.no_pesanan'
            )
            ->leftJoin(
                'produk as pr',
                'pr.sku',
                '=',
                'pp.sku'
            )
            ->whereIn(
                'pp.id_per_produk',
                $idPerProduk
            )
            ->whereNotNull('pp.sku')
            ->whereRaw(
                "UPPER(pp.sku) LIKE 'PLT%'"
            )
            ->where(
                'p.status',
                'proses'
            )
            ->select([
                'pp.id_per_produk',
                'pp.no_pesanan',
                'pp.sku',
                'pp.jumlah',
                'pp.nama_produk as item_nama_produk',
                'pp.variasi as item_variasi',
                'pr.nama_produk as master_nama_produk',
                'pr.variasi as master_variasi',
                'p.tanggal',
                'p.batas_kirim_at',
            ])
            ->orderByRaw(
                'CASE WHEN p.batas_kirim_at IS NULL THEN 1 ELSE 0 END'
            )
            ->orderBy(
                'p.batas_kirim_at'
            )
            ->orderBy(
                'p.tanggal'
            )
            ->orderBy(
                'pp.id_per_produk'
            )
            ->get();

        if ($items->isEmpty()) {
            return [
                'items' => 0,
                'parts_baru' => 0,
                'oversize' => [],
                'ignored' => $idPerProduk,
            ];
        }

        $partsHariIni =
            EditorPart::where(
                'tanggal_part',
                $tanggalPart
            )
                ->orderBy(
                    'nomor_part'
                )
                ->lockForUpdate()
                ->get();

        $parts = $partsHariIni
            ->where(
                'status',
                'open'
            )
            ->values();

        $nomorTerakhir =
            (int) (
                $partsHariIni
                    ->max(
                        'nomor_part'
                    )
                ?? 0
            );

        $usage = [];
        $urutan = [];

        if ($parts->isNotEmpty()) {
            $partItems =
                EditorPartItem::whereIn(
                    'editor_part_id',
                    $parts->pluck('id')
                )
                    ->get();

            foreach (
                $partItems
                as $partItem
            ) {
                $partId =
                    (int)
                    $partItem
                        ->editor_part_id;

                $urutan[$partId] =
                    max(
                        $urutan[$partId]
                        ?? 0,
                        (int)
                        $partItem->urutan
                    );

                if (
                    $partItem->status ===
                    'skipped'
                ) {
                    continue;
                }

                $kelompok =
                    (string)
                    $partItem
                        ->kelompok_produksi;

                $usage[
                    $partId
                ][
                    $kelompok
                ] =
                    (
                        $usage[
                            $partId
                        ][
                            $kelompok
                        ]
                        ?? 0
                    )
                    +
                    (int)
                    $partItem
                        ->jumlah_awal;
            }
        }

        $assigned = 0;
        $partsBaru = 0;
        $oversize = [];
        $ignored = [];

        foreach (
            $items
            as $item
        ) {
            $idItem =
                (int)
                $item
                    ->id_per_produk;

            $jumlah =
                (int)
                $item->jumlah;

            if ($jumlah < 1) {
                $ignored[] =
                    $idItem;

                continue;
            }

            if (
                $jumlah >
                self::KAPASITAS
            ) {
                $oversize[] = [
                    'id_per_produk' =>
                        $idItem,

                    'no_pesanan' =>
                        (string)
                        $item
                            ->no_pesanan,

                    'sku' =>
                        (string)
                        $item->sku,

                    'jumlah' =>
                        $jumlah,
                ];

                continue;
            }

            $sedangDialokasikan =
                EditorPartItem::where(
                    'id_per_produk',
                    $idItem
                )
                    ->whereIn(
                        'status',
                        [
                            'pending',
                            'locked',
                        ]
                    )
                    ->exists();

            if ($sedangDialokasikan) {
                $ignored[] =
                    $idItem;

                continue;
            }

            $sudahLocked =
                DB::table(
                    'editor_requests'
                )
                    ->where(
                        'id_per_produk',
                        $idItem
                    )
                    ->whereNotNull(
                        'locked_at'
                    )
                    ->exists();

            if ($sudahLocked) {
                $ignored[] =
                    $idItem;

                continue;
            }

            $namaProduk =
                $item
                    ->master_nama_produk
                ?: $item
                    ->item_nama_produk;

            $variasi =
                $item
                    ->master_variasi
                ?: $item
                    ->item_variasi;

            $kelompok =
                $this
                    ->buatKelompokProduksi(
                        $namaProduk,
                        $variasi
                    );

            $partTerpilih =
                null;

            foreach (
                $parts
                as $part
            ) {
                $partId =
                    (int)
                    $part->id;

                $kapasitas =
                    (int) (
                        $part
                            ->kapasitas_per_kelompok
                        ?: self::KAPASITAS
                    );

                $terisi =
                    (int) (
                        $usage[
                            $partId
                        ][
                            $kelompok
                        ]
                        ?? 0
                    );

                if (
                    $terisi +
                    $jumlah
                    <=
                    $kapasitas
                ) {
                    $partTerpilih =
                        $part;

                    break;
                }
            }

            if (!$partTerpilih) {
                $nomorTerakhir++;

                $partTerpilih =
                    EditorPart::create([
                        'tanggal_part' =>
                            $tanggalPart,

                        'nomor_part' =>
                            $nomorTerakhir,

                        'kode_part' =>
                            $this
                                ->buatKodePart(
                                    $tanggalPart,
                                    $nomorTerakhir
                                ),

                        'kapasitas_per_kelompok' =>
                            self::KAPASITAS,

                        'status' =>
                            'open',

                        'created_by' =>
                            $userId,
                    ]);

                $parts->push(
                    $partTerpilih
                );

                $usage[
                    $partTerpilih
                        ->id
                ] = [];

                $urutan[
                    $partTerpilih
                        ->id
                ] = 0;

                $partsBaru++;
            }

            $partId =
                (int)
                $partTerpilih
                    ->id;

            $urutan[
                $partId
            ] =
                (
                    $urutan[
                        $partId
                    ]
                    ?? 0
                )
                + 1;

            EditorPartItem::create([
                'editor_part_id' =>
                    $partId,

                'id_per_produk' =>
                    $idItem,

                'sku' =>
                    mb_strtoupper(
                        trim(
                            (string)
                            $item->sku
                        )
                    ),

                'kelompok_produksi' =>
                    $kelompok,

                'jumlah_awal' =>
                    $jumlah,

                'jumlah_final' =>
                    null,

                'urutan' =>
                    $urutan[
                        $partId
                    ],

                'status' =>
                    'pending',

                'processed_at' =>
                    null,
            ]);

            $usage[
                $partId
            ][
                $kelompok
            ] =
                (
                    $usage[
                        $partId
                    ][
                        $kelompok
                    ]
                    ?? 0
                )
                +
                $jumlah;

            $assigned++;
        }

        return [
            'items' =>
                $assigned,

            'parts_baru' =>
                $partsBaru,

            'oversize' =>
                $oversize,

            'ignored' =>
                array_values(
                    array_unique(
                        $ignored
                    )
                ),
        ];
    }

    private function buatKelompokProduksi(
        ?string $namaProduk,
        ?string $variasi
    ): string {
        $namaProduk =
            preg_replace(
                '/\s+/u',
                ' ',
                trim(
                    (string)
                    $namaProduk
                )
            );

        $variasi =
            preg_replace(
                '/\s+/u',
                ' ',
                trim(
                    (string)
                    $variasi
                )
            );

        $variasi =
            preg_replace(
                '/\s*,?\s*\d+\s*PCS\s*$/iu',
                '',
                $variasi
            );

        $namaProduk =
            mb_strtoupper(
                trim(
                    $namaProduk
                )
            );

        $variasi =
            mb_strtoupper(
                trim(
                    $variasi
                )
            );

        if (
            $namaProduk === '' &&
            $variasi === ''
        ) {
            return 'TANPA KELOMPOK';
        }

        if ($variasi === '') {
            return mb_substr(
                $namaProduk,
                0,
                150
            );
        }

        if ($namaProduk === '') {
            return mb_substr(
                $variasi,
                0,
                150
            );
        }

        return mb_substr(
            $namaProduk .
            '|' .
            $variasi,
            0,
            150
        );
    }

    private function buatKodePart(
        string $tanggal,
        int $nomor
    ): string {
        return str_replace(
            '-',
            '',
            $tanggal
        )
            .
            '-PART-'
            .
            str_pad(
                $nomor,
                3,
                '0',
                STR_PAD_LEFT
            );
    }
}
