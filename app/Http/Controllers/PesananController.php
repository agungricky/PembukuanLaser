<?php

namespace App\Http\Controllers;

use App\Imports\PesananExcelService;
use App\Models\Pesanan;
use App\Models\Toko;
use App\Services\EditorPartService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

class PesananController extends Controller
{
    public function index(Request $request)
    {
        $allowed = [10, 20, 25, 50, 100];

        $perPage = (int) $request->input('per_page', 20);

        if (! in_array($perPage, $allowed, true)) {
            $perPage = 20;
        }

        $keyword = trim((string) $request->input('no_pesanan', ''));
        $range = trim((string) $request->input('tanggal', ''));

        // Default bulan berjalan
        $startStr = now()->startOfMonth()->toDateString();
        $endStr = now()->toDateString();

        // Jika user memilih tanggal
        if ($range !== '') {

            try {

                if (str_contains($range, ' s.d ')) {

                    [$s, $e] = array_map('trim', explode(' s.d ', $range, 2));

                    $start = Carbon::createFromFormat('Y-m-d', $s);
                    $end = Carbon::createFromFormat('Y-m-d', $e);

                } else {

                    $start = Carbon::createFromFormat('Y-m-d', $range);
                    $end = Carbon::createFromFormat('Y-m-d', $range);

                }

                if ($end->lt($start)) {
                    [$start, $end] = [$end, $start];
                }

                $startStr = $start->toDateString();
                $endStr = $end->toDateString();

            } catch (\Throwable $e) {
                // gunakan default bulan berjalan
            }

        }

        $pesanan = Pesanan::select([
            'no_pesanan',
            'tanggal',
            'nama_pembeli',
            'kurir',
            'status',
            'status_cek',
            'no_resi',
            'total_harga',
        ])
            ->with([
                'produk' => function ($q) {
                    $q->select(
                        'no_pesanan',
                        'nama_produk',
                        'variasi',
                        'jumlah',
                        'harga',
                        'hpp',
                        'sku'
                    );
                },
            ])

            ->when($keyword !== '', function ($q) use ($keyword) {

                $q->where(function ($sub) use ($keyword) {

                    $sub->where('no_pesanan', 'like', "%{$keyword}%")
                        ->orWhere('no_resi', 'like', "%{$keyword}%");

                });

            })

            // Hanya gunakan filter tanggal jika tidak sedang mencari keyword
            ->when($keyword === '', function ($q) use ($startStr, $endStr) {

                $q->whereBetween('tanggal', [$startStr, $endStr]);

            })

            ->orderByDesc('tanggal')
            ->orderByDesc('no_pesanan')
            ->paginate($perPage)
            ->withQueryString();

        $totalItems = DB::table('pesanan_per_produk as pp')
            ->join('pesanan as p', 'pp.no_pesanan', '=', 'p.no_pesanan')

            ->when($keyword !== '', function ($q) use ($keyword) {

                $q->where(function ($sub) use ($keyword) {

                    $sub->where('p.no_pesanan', 'like', "%{$keyword}%")
                        ->orWhere('p.no_resi', 'like', "%{$keyword}%");

                });

            })

            ->when($keyword === '', function ($q) use ($startStr, $endStr) {

                $q->whereBetween('p.tanggal', [$startStr, $endStr]);

            })

            ->sum('pp.jumlah');

        return view('pesanan.pesanan', [
            'pesanan' => $pesanan,
            'total' => (int) $totalItems,
            'perPage' => $perPage,
            'allowed' => $allowed,
            'keyword' => $keyword,
            'tanggal' => $range,
        ]);
    }

    public function pesananDetail($id)
    {
        $pesanan = Pesanan::where('no_pesanan', $id)->with('toko')->first();

        return response()->json($pesanan);
    }

    public function importPage()
    {
        $daftarToko = Toko::select(
            'id_toko',
            'nama_toko',
            'marketplace'
        )
            ->orderBy('nama_toko')
            ->get();

        return view('pesanan.import', [
            'daftarToko' => $daftarToko,
        ]);
    }

    public function getTokoByMarketplace($market)
    {
        return Toko::where('marketplace', $market)
            ->orderBy('nama_toko')
            ->get();
    }

    public function detectMarketplace(array $sheet): ?string
    {
        if (! isset($sheet[1])) {
            return null;
        }

        $A = strtolower(trim($sheet[1]['A'] ?? ''));
        $B = strtolower(trim($sheet[1]['B'] ?? ''));
        $AN = strtolower(trim($sheet[1]['AN'] ?? ''));

        if ($A === 'tracking_number' && $B === 'order_sn') {
            return 'Shopee';
        }

        if ($A === 'order id' && $AN === 'tracking id') {
            return 'TikTok';
        }

        return null;
    }

    public function uploadPreviewExcel(Request $request, PesananExcelService $excelService)
    {
        $request->validate([
            'file' => ['required', 'file', 'extensions:xlsx,xls', 'max:20480'],
            'marketplace' => ['required', 'in:Shopee,TikTok'],
        ]);

        $file = $request->file('file');
        $path = $file->getRealPath() ?: $file->getPathname();
        $marketplace = $request->marketplace;

        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);

        $detectedMarketplace = $this->detectMarketplace($sheet);

        if ($detectedMarketplace === null) {
            return response()->json([
                'status' => 'error',
                'message' => 'Format file tidak dikenali. Pastikan file sesuai format Shopee atau TikTok.',
            ], 422);
        }

        if ($marketplace !== $detectedMarketplace) {
            return response()->json([
                'status' => 'error',
                'message' => "File ini terdeteksi format {$detectedMarketplace}, bukan {$marketplace}.",
            ], 422);
        }

        $data = $marketplace === 'Shopee'
            ? $excelService->parseShopee($path)
            : $excelService->parseTikTok($path);

        if (empty($data)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tidak ada data dalam file.',
            ], 400);
        }

        $nos = array_unique(array_filter(array_column($data, 'no_pesanan')));
        $dupes = [];
        if (! empty($nos)) {
            $dupes = DB::table('pesanan')
                ->whereIn('no_pesanan', $nos)
                ->pluck('no_pesanan')
                ->toArray();
        }

        if (! empty($dupes)) {
            $data = array_values(array_filter($data, function ($row) use ($dupes) {
                $no = $row['no_pesanan'] ?? null;

                return $no && ! in_array($no, $dupes, true);
            }));
        }

        if (empty($data)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Semua nomor pesanan di file sudah ada di database, tidak ada yang bisa di-import.',
            ], 400);
        }

        Session::put('preview_pesanan', $data);

        return response()->json([
            'status' => 'success',
            'data' => $data,
            'skipped' => $dupes,
        ]);
    }

    public function getPreviewData(PesananExcelService $excelService)
    {
        $raw = Session::get('preview_pesanan', []);

        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $data = is_array($decoded) ? $decoded : [];
        } elseif (is_array($raw)) {
            $data = $raw;
        } else {
            $data = [];
        }

        $skuList = [];

        foreach ($data as $orderIndex => $order) {
            if (! is_array($order)) {
                $data[$orderIndex] = ['produk_detail' => []];
                continue;
            }

            $produkDetail = $order['produk_detail'] ?? [];

            if (! is_array($produkDetail)) {
                $data[$orderIndex]['produk_detail'] = [];
                continue;
            }

            foreach ($produkDetail as $produkIndex => $produk) {
                $p = is_array($produk) ? $produk : [];

                $skuOriginal = $this->firstNonEmpty(
                    $p,
                    [
                        'sku_original',
                        'sku_asli',
                        '__sku',
                        'sku',
                        'SKU Induk',
                        'SKU',
                    ]
                );

                $skuOriginal = trim($skuOriginal);
                $sku = $excelService->normalizeSku($skuOriginal) ?? '';
                $multiplier = $excelService->getSkuMultiplier($skuOriginal);
                $custom = $excelService->isCustomSku($skuOriginal);

                if (
                    $custom === 0 &&
                    isset($p['custom']) &&
                    (int) $p['custom'] === 1
                ) {
                    $custom = 1;
                }

                $jumlahPesanan = (int) (
                    $p['jumlah_pesanan']
                    ?? $p['Jumlah_original']
                    ?? 0
                );

                if ($jumlahPesanan < 1) {
                    $jumlahRaw = (int) (
                        $p['Jumlah']
                        ?? $p['jumlah']
                        ?? 1
                    );

                    $jumlahPesanan = max(1, $jumlahRaw);
                }

                $jumlahReal = $jumlahPesanan * $multiplier;

                $hargaMarketplace = $this->toNumber(
                    $p['harga_original']
                    ?? $p['Harga_original']
                    ?? $p['Harga']
                    ?? $p['harga']
                    ?? 0
                );

                $hargaJualReal = round(
                    $hargaMarketplace / max(1, $multiplier),
                    2
                );

                $subtotal = round(
                    $hargaJualReal * $jumlahReal,
                    2
                );

                $p['sku_original'] = $skuOriginal;
                $p['sku_asli'] = $skuOriginal;
                $p['__sku'] = $skuOriginal;
                $p['sku'] = $sku;
                $p['custom'] = $custom;
                $p['multiplier'] = $multiplier;
                $p['jumlah_pesanan'] = $jumlahPesanan;
                $p['jumlah'] = $jumlahReal;
                $p['Jumlah'] = $jumlahReal;
                $p['harga_original'] = $hargaMarketplace;
                $p['Harga_original'] = $hargaMarketplace;
                $p['harga'] = $hargaJualReal;
                $p['Harga'] = $hargaJualReal;
                $p['subtotal'] = $subtotal;
                $p['Subtotal'] = $subtotal;

                if ($sku !== '') {
                    $skuList[] = $sku;
                }

                $data[$orderIndex]['produk_detail'][$produkIndex] = $p;
            }
        }

        $skuList = array_values(
            array_unique(
                array_filter(
                    array_map(
                        fn ($sku) => Str::upper(trim((string) $sku)),
                        $skuList
                    )
                )
            )
        );

        $skuToHpp = [];

        if (! empty($skuList)) {
            $rows = DB::table('produk')
                ->whereIn('sku', $skuList)
                ->select('sku', 'hpp')
                ->get();

            foreach ($rows as $row) {
                $key = Str::upper(trim((string) $row->sku));
                $skuToHpp[$key] = (float) $row->hpp;
            }
        }

        foreach ($data as $orderIndex => $order) {
            foreach ($order['produk_detail'] ?? [] as $produkIndex => $produk) {
                $p = is_array($produk) ? $produk : [];
                $key = Str::upper(trim((string) ($p['sku'] ?? '')));

                $p['HPP'] = $key !== '' && isset($skuToHpp[$key])
                    ? (float) $skuToHpp[$key]
                    : 0.0;

                $p['hpp'] = $p['HPP'];

                $data[$orderIndex]['produk_detail'][$produkIndex] = $p;
            }
        }

        Session::put('preview_pesanan', $data);

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    private function firstNonEmpty(array $arr, array $keys): string
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

        return '';
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

    public function simpanImport(
        Request $request,
        EditorPartService $partService,
        PesananExcelService $excelService
    ) {
        $request->validate([
            'pesanan' => ['required', 'array', 'min:1'],
            'tanggal_import' => ['required', 'date'],
            'id_toko' => ['required', 'integer', 'exists:toko,id_toko'],
            'nama_user' => ['nullable', 'string'],
        ]);

        $payload = $request->input('pesanan', []);
        $tanggal = Carbon::parse($request->input('tanggal_import'))->startOfDay();
        $idUser = auth()->id();
        $idToko = (int) $request->input('id_toko');

        $toko = Toko::select(
            'id_toko',
            'marketplace',
            'biaya_admin',
            'biaya_tambahan'
        )->find($idToko);

        if (! $toko) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data toko tidak ditemukan.',
            ], 422);
        }

        $previewRaw = Session::get('preview_pesanan', []);

        if (is_string($previewRaw)) {
            $previewRaw = json_decode($previewRaw, true);

            if (! is_array($previewRaw)) {
                $previewRaw = [];
            }
        }

        $previewByOrder = collect($previewRaw)
            ->filter(
                fn ($row) =>
                    is_array($row) &&
                    ! empty($row['no_pesanan'])
            )
            ->keyBy(
                fn ($row) => trim((string) $row['no_pesanan'])
            );

        $biayaAdmin = (float) ($toko->biaya_admin ?? 0);
        $biayaTambahan = (float) ($toko->biaya_tambahan ?? 0);
        $imported = 0;
        $skipped = [];
        $platItemIds = [];
        $skuList = [];

        foreach ($payload as $item) {
            $noPesanan = trim((string) ($item['no_pesanan'] ?? ''));
            $previewItem = $previewByOrder->get($noPesanan, []);
            $previewProduk = collect($previewItem['produk_detail'] ?? [])->values();

            foreach ($item['produk'] ?? [] as $index => $prd) {
                $preview = $previewProduk->get($index, []);
                $preview = is_array($preview) ? $preview : [];

                $skuOriginal = $this->firstNonEmpty(
                    $prd,
                    ['sku_original', 'sku_asli', '__sku']
                );

                if ($skuOriginal === '') {
                    $skuOriginal = $this->firstNonEmpty(
                        $preview,
                        ['sku_original', 'sku_asli', '__sku', 'sku']
                    );
                }

                if ($skuOriginal === '') {
                    $skuOriginal = $this->firstNonEmpty(
                        $prd,
                        ['sku', 'SKU Induk', 'SKU']
                    );
                }

                $sku = $excelService->normalizeSku($skuOriginal);

                if ($sku !== null && $sku !== '') {
                    $skuList[] = Str::upper(trim($sku));
                }
            }
        }

        $skuList = array_values(array_unique($skuList));
        $produkMaster = collect();

        if (! empty($skuList)) {
            $produkMaster = DB::table('produk')
                ->whereIn('sku', $skuList)
                ->select('sku', 'hpp')
                ->get()
                ->keyBy(
                    fn ($row) => Str::upper(trim((string) $row->sku))
                );

            $skuTidakAda = array_values(
                array_diff(
                    $skuList,
                    $produkMaster->keys()->all()
                )
            );

            if (! empty($skuTidakAda)) {
                return response()->json([
                    'status' => 'error',
                    'message' =>
                        'Terdapat PRODUK dengan SKU yang tidak terdaftar: '.
                        implode(', ', $skuTidakAda),
                ], 422);
            }
        }

        DB::beginTransaction();

        try {
            foreach ($payload as $item) {
                $noPesanan = trim((string) ($item['no_pesanan'] ?? ''));

                if ($noPesanan === '') {
                    throw new \Exception('Nomor pesanan tidak boleh kosong.');
                }

                $sudahAda = DB::table('pesanan')
                    ->where('no_pesanan', $noPesanan)
                    ->exists();

                if ($sudahAda) {
                    $skipped[] = $noPesanan;
                    continue;
                }

                $previewItem = $previewByOrder->get($noPesanan, []);
                $batasKirimAt = $previewItem['batas_kirim_at']
                    ?? $item['batas_kirim_at']
                    ?? null;
                $batasKirimRaw = $previewItem['batas_kirim_raw']
                    ?? $item['batas_kirim_raw']
                    ?? null;
                $batasKirimSource = $previewItem['batas_kirim_source']
                    ?? $item['batas_kirim_source']
                    ?? null;

                DB::table('pesanan')->insert([
                    'no_pesanan' => $noPesanan,
                    'tanggal' => $tanggal,
                    'no_resi' => ! empty($item['no_resi'])
                        ? trim((string) $item['no_resi'])
                        : null,
                    'id_toko' => $idToko,
                    'id_user' => $idUser,
                    'nama_pembeli' => trim((string) ($item['nama_pembeli'] ?? '')),
                    'username' => trim((string) ($item['username'] ?? '')),
                    'kurir' => trim((string) ($item['kurir'] ?? '')),
                    'status' => 'proses',
                    'total_hpp' => 0,
                    'total_harga' => 0,
                    'total_admin' => 0,
                    'pencairan' => null,
                    'notes' => null,
                    'batas_kirim_at' => $batasKirimAt,
                    'batas_kirim_raw' => $batasKirimRaw,
                    'batas_kirim_source' => $batasKirimSource,
                ]);

                $totalHpp = 0;
                $totalHarga = 0;
                $previewProduk = collect(
                    $previewItem['produk_detail'] ?? []
                )->values();

                foreach ($item['produk'] ?? [] as $produkIndex => $prd) {
                    $preview = $previewProduk->get($produkIndex, []);
                    $preview = is_array($preview) ? $preview : [];

                    $skuOriginal = $this->firstNonEmpty(
                        $prd,
                        ['sku_original', 'sku_asli', '__sku']
                    );

                    if ($skuOriginal === '') {
                        $skuOriginal = $this->firstNonEmpty(
                            $preview,
                            ['sku_original', 'sku_asli', '__sku', 'sku']
                        );
                    }

                    if ($skuOriginal === '') {
                        $skuOriginal = $this->firstNonEmpty(
                            $prd,
                            ['sku', 'SKU Induk', 'SKU']
                        );
                    }

                    $sku = $excelService->normalizeSku($skuOriginal);
                    $multiplier = $excelService->getSkuMultiplier($skuOriginal);
                    $custom = $excelService->isCustomSku($skuOriginal);

                    if (
                        $custom === 0 &&
                        (
                            (int) ($prd['custom'] ?? 0) === 1 ||
                            (int) ($preview['custom'] ?? 0) === 1
                        )
                    ) {
                        $custom = 1;
                    }

                    $jumlahPesanan = (int) (
                        $prd['jumlah_pesanan']
                        ?? $preview['jumlah_pesanan']
                        ?? 0
                    );

                    if ($jumlahPesanan < 1) {
                        $jumlahRealPayload = max(
                            1,
                            (int) (
                                $prd['jumlah']
                                ?? $prd['Jumlah']
                                ?? $preview['jumlah']
                                ?? $preview['Jumlah']
                                ?? 1
                            )
                        );

                        $jumlahPesanan = max(
                            1,
                            (int) ceil(
                                $jumlahRealPayload / max(1, $multiplier)
                            )
                        );
                    }

                    $jumlahReal = $jumlahPesanan * $multiplier;

                    $hargaMarketplace = $this->toNumber(
                        $prd['harga_original']
                        ?? $prd['Harga_original']
                        ?? $preview['harga_original']
                        ?? $preview['Harga_original']
                        ?? null
                    );

                    if ($hargaMarketplace <= 0) {
                        $hargaRealPayload = $this->toNumber(
                            $prd['harga']
                            ?? $prd['Harga']
                            ?? $preview['harga']
                            ?? $preview['Harga']
                            ?? 0
                        );

                        $hargaMarketplace = $hargaRealPayload * $multiplier;
                    }

                    $hargaJualReal = round(
                        $hargaMarketplace / max(1, $multiplier),
                        2
                    );

                    $namaProduk = trim((string) (
                        $prd['nama_produk']
                        ?? $prd['Nama Produk']
                        ?? $preview['nama_produk']
                        ?? $preview['Nama Produk']
                        ?? ''
                    ));

                    $variasi = trim((string) (
                        $prd['variasi']
                        ?? $prd['Nama Variasi']
                        ?? $preview['variasi']
                        ?? $preview['Nama Variasi']
                        ?? ''
                    ));

                    $hpp = 0.0;

                    if ($sku !== null && $sku !== '') {
                        $keySku = Str::upper(trim($sku));
                        $master = $produkMaster->get($keySku);

                        if (! $master) {
                            throw new \Exception(
                                "SKU '{$sku}' tidak ditemukan di tabel produk."
                            );
                        }

                        $hpp = (float) ($master->hpp ?? 0);
                    }

                    $idPerProduk = DB::table('pesanan_per_produk')
                        ->insertGetId([
                            'no_pesanan' => $noPesanan,
                            'nama_produk' => $namaProduk,
                            'variasi' => $variasi,
                            'jumlah' => $jumlahReal,
                            'hpp' => $hpp,
                            'harga' => $hargaJualReal,
                            'sku' => $sku,
                            'custom' => $custom,
                        ], 'id_per_produk');

                    if ($sku && str_starts_with($sku, 'PLT')) {
                        $platItemIds[] = (int) $idPerProduk;
                    }

                    $totalHpp += $hpp * $jumlahReal;
                    $totalHarga += $hargaJualReal * $jumlahReal;
                }

                if ($biayaAdmin > 1 && $biayaAdmin <= 100) {
                    $totalAdmin =
                        ($totalHarga * ($biayaAdmin / 100)) +
                        $biayaTambahan;
                } elseif ($biayaAdmin > 0 && $biayaAdmin <= 1) {
                    $totalAdmin =
                        ($totalHarga * $biayaAdmin) +
                        $biayaTambahan;
                } else {
                    $totalAdmin = $biayaTambahan;
                }

                DB::table('pesanan')
                    ->where('no_pesanan', $noPesanan)
                    ->update([
                        'total_hpp' => $totalHpp,
                        'total_harga' => $totalHarga,
                        'total_admin' => $totalAdmin,
                    ]);

                $imported++;
            }

            $hasilPart = $partService->alokasikanItemBaru(
                $platItemIds,
                $idUser
            );

            DB::commit();
            Session::forget('preview_pesanan');

            return response()->json([
                'status' => 'success',
                'message' => 'Import selesai.',
                'imported' => $imported,
                'skipped_count' => count($skipped),
                'skipped' => $skipped,
                'part_items' => $hasilPart['items'],
                'parts_baru' => $hasilPart['parts_baru'],
                'part_oversize' => $hasilPart['oversize'],
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
