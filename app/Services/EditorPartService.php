<?php

namespace App\Services;

use App\Models\EditorPart;
use App\Models\EditorPartItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class EditorPartService
{
    private const MULAI_EDITOR = '2026-08-26';
    private const TIMEZONE = 'Asia/Jakarta';

    private const SESI_PAGI = 'pagi';
    private const SESI_SIANG = 'siang';
    private const SESI_MALAM = 'malam';

    private const MARKETPLACE_SHOPEE = 'Shopee';
    private const MARKETPLACE_TIKTOK = 'TikTok';

    private const SKU_DILEWATI = [
        'PLT028C',
    ];

    public function sinkronkanPekerjaanTersedia(?int $userId = null): array
    {
        return DB::transaction(function () use ($userId) {
            $partsBaru = 0;
            $skuDilewati = $this->bersihkanSkuDilewatiDariAntrianOpen();
            $dipindahMalam = $this->pindahkanMalamBelumDidownload(
                $userId,
                $partsBaru
            );

            $ids = DB::table('pesanan_per_produk as pp')
                ->join(
                    'pesanan as p',
                    'p.no_pesanan',
                    '=',
                    'pp.no_pesanan'
                )
                ->join(
                    'toko as t',
                    't.id_toko',
                    '=',
                    'p.id_toko'
                )
                ->whereNotNull('pp.sku')
                ->whereRaw("UPPER(TRIM(pp.sku)) LIKE 'PLT%'")
                ->whereRaw("UPPER(TRIM(pp.sku)) <> 'PLT028C'")
                ->whereRaw("LOWER(TRIM(t.marketplace)) IN ('shopee', 'tiktok')")
                ->where('p.status', 'proses')
                ->whereNotNull('p.input_at')
                ->where(
                    'p.input_at',
                    '>=',
                    self::MULAI_EDITOR . ' 00:00:00'
                )
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
                ->orderBy('p.input_at')
                ->orderBy('pp.id_per_produk')
                ->pluck('pp.id_per_produk')
                ->map(fn ($id) => (int) $id)
                ->all();

            $hasil = $this->prosesAlokasi(
                $ids,
                $userId,
                true,
                $partsBaru
            );

            $hasil['dipindah_malam'] = $dipindahMalam;
            $hasil['sku_dilewati'] = $skuDilewati;

            return $hasil;
        });
    }

    public function alokasikanItemBaru(
        array $idPerProduk,
        ?int $userId = null
    ): array {
        $idPerProduk = collect($idPerProduk)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        if (empty($idPerProduk)) {
            return $this->hasilKosong();
        }

        return DB::transaction(function () use ($idPerProduk, $userId) {
            $partsBaru = 0;
            $skuDilewati = $this->bersihkanSkuDilewatiDariAntrianOpen();
            $dipindahMalam = $this->pindahkanMalamBelumDidownload(
                $userId,
                $partsBaru
            );

            $hasil = $this->prosesAlokasi(
                $idPerProduk,
                $userId,
                true,
                $partsBaru
            );

            $hasil['dipindah_malam'] = $dipindahMalam;
            $hasil['sku_dilewati'] = $skuDilewati;

            return $hasil;
        });
    }

    public function alokasikanKeAntrianBerikutnya(
        array $idPerProduk,
        EditorPart $partAsal,
        ?int $userId = null
    ): array {
        $idPerProduk = collect($idPerProduk)
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        if (empty($idPerProduk)) {
            return $this->hasilKosong();
        }

        return DB::transaction(function () use (
            $idPerProduk,
            $partAsal,
            $userId
        ) {
            $partAsal = EditorPart::where('id', $partAsal->id)
                ->lockForUpdate()
                ->firstOrFail();

            $marketplace = $this->normalizeMarketplace(
                $partAsal->marketplace
            );

            if (
                $marketplace === null ||
                !in_array(
                    $partAsal->sesi,
                    [
                        self::SESI_PAGI,
                        self::SESI_SIANG,
                        self::SESI_MALAM,
                    ],
                    true
                )
            ) {
                throw new \RuntimeException(
                    'Antrian asal tidak memiliki sesi/marketplace yang valid.'
                );
            }

            $tanggalAsal = Carbon::parse(
                $partAsal->tanggal_part,
                $this->timezone()
            )->toDateString();

            [$tanggalTujuan, $sesiTujuan] = $this->antrianBerikutnya(
                $tanggalAsal,
                $partAsal->sesi
            );

            $partsBaru = 0;

            $hasil = $this->prosesAlokasi(
                $idPerProduk,
                $userId,
                false,
                $partsBaru,
                $tanggalTujuan,
                $sesiTujuan,
                $marketplace
            );

            $hasil['dipindah_malam'] = 0;
            $hasil['sku_dilewati'] = 0;

            return $hasil;
        });
    }

    private function prosesAlokasi(
        array $idPerProduk,
        ?int $userId,
        bool $gunakanWaktuMasuk,
        int &$partsBaru,
        ?string $tanggalPaksa = null,
        ?string $sesiPaksa = null,
        ?string $marketplacePaksa = null
    ): array {
        if (empty($idPerProduk)) {
            $hasil = $this->hasilKosong();
            $hasil['parts_baru'] = $partsBaru;

            return $hasil;
        }

        $items = DB::table('pesanan_per_produk as pp')
            ->join(
                'pesanan as p',
                'p.no_pesanan',
                '=',
                'pp.no_pesanan'
            )
            ->join(
                'toko as t',
                't.id_toko',
                '=',
                'p.id_toko'
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
            ->whereRaw("UPPER(TRIM(pp.sku)) LIKE 'PLT%'")
            ->whereRaw("UPPER(TRIM(pp.sku)) <> 'PLT028C'")
            ->whereRaw("LOWER(TRIM(t.marketplace)) IN ('shopee', 'tiktok')")
            ->where('p.status', 'proses')
            ->whereNotNull('p.input_at')
            ->where(
                'p.input_at',
                '>=',
                self::MULAI_EDITOR . ' 00:00:00'
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
                'p.input_at',
                'p.batas_kirim_at',
                't.marketplace',
            ])
            ->orderByRaw(
                'CASE WHEN p.batas_kirim_at IS NULL THEN 1 ELSE 0 END'
            )
            ->orderBy('p.batas_kirim_at')
            ->orderBy('p.input_at')
            ->orderBy('pp.id_per_produk')
            ->get();

        $ditemukan = $items
            ->pluck('id_per_produk')
            ->map(fn ($id) => (int) $id)
            ->all();

        $ignored = array_values(
            array_diff($idPerProduk, $ditemukan)
        );

        if ($items->isEmpty()) {
            return [
                'items' => 0,
                'parts_baru' => $partsBaru,
                'oversize' => [],
                'ignored' => $ignored,
            ];
        }

        $assigned = 0;
        $partCache = [];
        $urutanCache = [];

        foreach ($items as $item) {
            $idItem = (int) $item->id_per_produk;
            $jumlah = (int) $item->jumlah;
            $sku = mb_strtoupper(trim((string) $item->sku));
            $marketplaceItem = $this->normalizeMarketplace(
                $item->marketplace ?? null
            );

            $marketplace = $marketplacePaksa
                ? $this->normalizeMarketplace($marketplacePaksa)
                : $marketplaceItem;

            if (
                $jumlah < 1 ||
                $marketplace === null ||
                $marketplaceItem === null ||
                (
                    $marketplacePaksa !== null &&
                    $marketplaceItem !== $marketplace
                ) ||
                in_array($sku, self::SKU_DILEWATI, true)
            ) {
                $ignored[] = $idItem;
                continue;
            }

            $sedangDialokasikan = EditorPartItem::where(
                'id_per_produk',
                $idItem
            )
                ->whereIn(
                    'status',
                    ['pending', 'locked']
                )
                ->exists();

            if ($sedangDialokasikan) {
                $ignored[] = $idItem;
                continue;
            }

            $sudahLocked = DB::table('editor_requests')
                ->where('id_per_produk', $idItem)
                ->whereNotNull('locked_at')
                ->exists();

            if ($sudahLocked) {
                $ignored[] = $idItem;
                continue;
            }

            $namaProduk = $item->master_nama_produk
                ?: $item->item_nama_produk;

            $variasi = $item->master_variasi
                ?: $item->item_variasi;

            $kelompok = $this->buatKelompokProduksi(
                $namaProduk,
                $variasi
            );

            if (
                $tanggalPaksa !== null &&
                $sesiPaksa !== null
            ) {
                $tanggalPart = $tanggalPaksa;
                $sesi = $sesiPaksa;
            } else {
                $waktuDasar = $gunakanWaktuMasuk
                    ? $this->waktuMasukItem($item)
                    : $this->sekarangWib();

                [$tanggalPart, $sesi] = $this->tentukanAntrianDariWaktu(
                    $waktuDasar
                );

                if (
                    $gunakanWaktuMasuk &&
                    $sesi === self::SESI_MALAM &&
                    $tanggalPart < $this->sekarangWib()->toDateString()
                ) {
                    [$tanggalPart, $sesi] = $this->antrianBerikutnya(
                        $tanggalPart,
                        $sesi
                    );
                }
            }

            $part = $this->cariAntrianTersedia(
                $tanggalPart,
                $sesi,
                $marketplace,
                $userId,
                $partsBaru,
                $partCache,
                $urutanCache
            );

            $partId = (int) $part->id;

            if (!array_key_exists($partId, $urutanCache)) {
                $urutanCache[$partId] = (int) EditorPartItem::where(
                    'editor_part_id',
                    $partId
                )->max('urutan');
            }

            $urutanCache[$partId]++;

            EditorPartItem::create([
                'editor_part_id' => $partId,
                'id_per_produk' => $idItem,
                'sku' => $sku,
                'kelompok_produksi' => $kelompok,
                'jumlah_awal' => $jumlah,
                'jumlah_final' => null,
                'urutan' => $urutanCache[$partId],
                'status' => 'pending',
                'processed_at' => null,
            ]);

            $assigned++;
        }

        return [
            'items' => $assigned,
            'parts_baru' => $partsBaru,
            'oversize' => [],
            'ignored' => array_values(
                array_unique($ignored)
            ),
        ];
    }

    private function bersihkanSkuDilewatiDariAntrianOpen(): int
    {
        $partIds = EditorPart::where('status', 'open')
            ->pluck('id');

        if ($partIds->isEmpty()) {
            return 0;
        }

        $jumlah = EditorPartItem::whereIn(
            'editor_part_id',
            $partIds
        )
            ->where('status', 'pending')
            ->whereRaw("UPPER(TRIM(sku)) = 'PLT028C'")
            ->delete();

        EditorPart::where('status', 'open')
            ->whereDoesntHave('items')
            ->delete();

        return $jumlah;
    }

    private function pindahkanMalamBelumDidownload(
        ?int $userId,
        int &$partsBaru
    ): int {
        $hariIni = $this->sekarangWib()->toDateString();

        $partsMalam = EditorPart::where(
            'sesi',
            self::SESI_MALAM
        )
            ->whereIn(
                'marketplace',
                [
                    self::MARKETPLACE_SHOPEE,
                    self::MARKETPLACE_TIKTOK,
                ]
            )
            ->where('status', 'open')
            ->whereDate('tanggal_part', '<', $hariIni)
            ->orderBy('tanggal_part')
            ->lockForUpdate()
            ->get();

        if ($partsMalam->isEmpty()) {
            return 0;
        }

        $dipindah = 0;
        $partCache = [];
        $urutanCache = [];

        foreach ($partsMalam as $partMalam) {
            $marketplace = $this->normalizeMarketplace(
                $partMalam->marketplace
            );

            if ($marketplace === null) {
                continue;
            }

            $pendingItems = EditorPartItem::where(
                'editor_part_id',
                $partMalam->id
            )
                ->where('status', 'pending')
                ->whereRaw("UPPER(TRIM(sku)) <> 'PLT028C'")
                ->orderBy('urutan')
                ->lockForUpdate()
                ->get();

            if ($pendingItems->isEmpty()) {
                if (!$partMalam->items()->exists()) {
                    $partMalam->delete();
                }

                continue;
            }

            $tanggalPagi = Carbon::parse(
                $partMalam->tanggal_part,
                $this->timezone()
            )
                ->addDay()
                ->toDateString();

            $partTujuan = $this->cariAntrianTersedia(
                $tanggalPagi,
                self::SESI_PAGI,
                $marketplace,
                $userId,
                $partsBaru,
                $partCache,
                $urutanCache
            );

            $partTujuanId = (int) $partTujuan->id;

            if (!array_key_exists($partTujuanId, $urutanCache)) {
                $urutanCache[$partTujuanId] = (int) EditorPartItem::where(
                    'editor_part_id',
                    $partTujuanId
                )->max('urutan');
            }

            foreach ($pendingItems as $pendingItem) {
                $sudahAdaDiTujuan = EditorPartItem::where(
                    'editor_part_id',
                    $partTujuanId
                )
                    ->where(
                        'id_per_produk',
                        $pendingItem->id_per_produk
                    )
                    ->exists();

                if ($sudahAdaDiTujuan) {
                    $pendingItem->delete();
                    continue;
                }

                $urutanCache[$partTujuanId]++;

                $pendingItem->update([
                    'editor_part_id' => $partTujuanId,
                    'urutan' => $urutanCache[$partTujuanId],
                ]);

                $dipindah++;
            }

            if (!$partMalam->items()->exists()) {
                $partMalam->delete();
            }
        }

        return $dipindah;
    }

    private function cariAntrianTersedia(
        string $tanggal,
        string $sesi,
        string $marketplace,
        ?int $userId,
        int &$partsBaru,
        array &$partCache,
        array &$urutanCache
    ): EditorPart {
        for ($i = 0; $i < 370; $i++) {
            $key = $tanggal . '|' . $sesi . '|' . $marketplace;

            if (array_key_exists($key, $partCache)) {
                $part = $partCache[$key];
            } else {
                $part = EditorPart::whereDate(
                    'tanggal_part',
                    $tanggal
                )
                    ->where('sesi', $sesi)
                    ->where('marketplace', $marketplace)
                    ->lockForUpdate()
                    ->first();

                $partCache[$key] = $part;
            }

            if ($part) {
                if ($part->status === 'open') {
                    return $part;
                }

                [$tanggal, $sesi] = $this->antrianBerikutnya(
                    $tanggal,
                    $sesi
                );

                continue;
            }

            $part = EditorPart::create([
                'tanggal_part' => $tanggal,
                'sesi' => $sesi,
                'marketplace' => $marketplace,
                'nomor_part' => $this->nomorPartBerikutnya($tanggal),
                'kode_part' => $this->buatKodePart(
                    $tanggal,
                    $sesi,
                    $marketplace
                ),
                'kapasitas_per_kelompok' => 0,
                'status' => 'open',
                'created_by' => $userId,
            ]);

            $partCache[$key] = $part;
            $urutanCache[$part->id] = 0;
            $partsBaru++;

            return $part;
        }

        throw new \RuntimeException(
            'Tidak dapat menentukan antrian Editor yang tersedia.'
        );
    }

    private function tentukanAntrianDariWaktu(Carbon $waktu): array
    {
        $tanggal = $waktu->toDateString();
        $jamMenit = $waktu->format('H:i');

        if ($jamMenit <= '10:00') {
            return [
                $tanggal,
                self::SESI_PAGI,
            ];
        }

        if ($jamMenit <= '15:00') {
            return [
                $tanggal,
                self::SESI_SIANG,
            ];
        }

        return [
            $tanggal,
            self::SESI_MALAM,
        ];
    }

    private function antrianBerikutnya(
        string $tanggal,
        string $sesi
    ): array {
        if ($sesi === self::SESI_PAGI) {
            return [
                $tanggal,
                self::SESI_SIANG,
            ];
        }

        if ($sesi === self::SESI_SIANG) {
            return [
                $tanggal,
                self::SESI_MALAM,
            ];
        }

        $tanggalBerikutnya = Carbon::parse(
            $tanggal,
            $this->timezone()
        )
            ->addDay()
            ->toDateString();

        return [
            $tanggalBerikutnya,
            self::SESI_PAGI,
        ];
    }

    private function waktuMasukItem(object $item): Carbon
    {
        if (empty($item->input_at)) {
            return $this->sekarangWib();
        }

        try {
            return Carbon::parse(
                $item->input_at,
                $this->timezone()
            );
        } catch (\Throwable $e) {
            return $this->sekarangWib();
        }
    }

    private function normalizeMarketplace(?string $marketplace): ?string
    {
        $marketplace = mb_strtolower(
            trim((string) $marketplace)
        );

        if ($marketplace === 'shopee') {
            return self::MARKETPLACE_SHOPEE;
        }

        if ($marketplace === 'tiktok') {
            return self::MARKETPLACE_TIKTOK;
        }

        return null;
    }

    private function sekarangWib(): Carbon
    {
        return Carbon::now(
            $this->timezone()
        );
    }

    private function timezone(): string
    {
        return self::TIMEZONE;
    }

    private function nomorPartBerikutnya(string $tanggal): int
    {
        return ((int) EditorPart::whereDate(
            'tanggal_part',
            $tanggal
        )->max('nomor_part')) + 1;
    }

    private function buatKelompokProduksi(
        ?string $namaProduk,
        ?string $variasi
    ): string {
        $namaProduk = preg_replace(
            '/\s+/u',
            ' ',
            trim((string) $namaProduk)
        );

        $variasi = preg_replace(
            '/\s+/u',
            ' ',
            trim((string) $variasi)
        );

        $variasi = preg_replace(
            '/\s*,?\s*(?:1|2)\s*PCS\s*$/iu',
            '',
            $variasi
        );

        $namaProduk = mb_strtoupper(
            trim($namaProduk)
        );

        $variasi = mb_strtoupper(
            trim($variasi)
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
            $namaProduk . '|' . $variasi,
            0,
            150
        );
    }

    private function buatKodePart(
        string $tanggal,
        string $sesi,
        string $marketplace
    ): string {
        return str_replace(
            '-',
            '',
            $tanggal
        ) . '-' . mb_strtoupper($sesi) . '-' . mb_strtoupper($marketplace);
    }

    private function hasilKosong(): array
    {
        return [
            'items' => 0,
            'parts_baru' => 0,
            'oversize' => [],
            'ignored' => [],
            'dipindah_malam' => 0,
            'sku_dilewati' => 0,
        ];
    }
}
