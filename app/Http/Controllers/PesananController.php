<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pesanan;
use App\Models\Toko;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Carbon\Carbon;
use App\Imports\PesananExcelService;
use App\Services\EditorPartService;
use Illuminate\Support\Str;

class PesananController extends Controller
{
    public function index(Request $request)
    {
        $allowed = [10, 20, 25, 50, 100];

        $perPage = (int) $request->input('per_page', 20);

        if (!in_array($perPage, $allowed, true)) {
            $perPage = 20;
        }

        $keyword = trim((string) $request->input('no_pesanan', ''));
        $range   = trim((string) $request->input('tanggal', ''));

        // Default bulan berjalan
        $startStr = now()->startOfMonth()->toDateString();
        $endStr   = now()->toDateString();

        // Jika user memilih tanggal
        if ($range !== '') {

            try {

                if (str_contains($range, ' s.d ')) {

                    [$s, $e] = array_map('trim', explode(' s.d ', $range, 2));

                    $start = Carbon::createFromFormat('Y-m-d', $s);
                    $end   = Carbon::createFromFormat('Y-m-d', $e);

                } else {

                    $start = Carbon::createFromFormat('Y-m-d', $range);
                    $end   = Carbon::createFromFormat('Y-m-d', $range);

                }

                if ($end->lt($start)) {
                    [$start, $end] = [$end, $start];
                }

                $startStr = $start->toDateString();
                $endStr   = $end->toDateString();

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
                }
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
            'total'   => (int) $totalItems,
            'perPage' => $perPage,
            'allowed' => $allowed,
            'keyword' => $keyword,
            'tanggal' => $range,
        ]);
    }

    public function pesananDetail($id){
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
        if (!isset($sheet[1])) {
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

        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($path);
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

        if (!empty($nos)) {
            $dupes = DB::table('pesanan')
                ->whereIn('no_pesanan', $nos)
                ->pluck('no_pesanan')
                ->toArray();
        }

        if (!empty($dupes)) {
            $data = array_values(array_filter($data, function ($row) use ($dupes) {
                $no = $row['no_pesanan'] ?? null;

                return $no && !in_array($no, $dupes, true);
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

    public function getPreviewData()
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

        for ($i = 0; $i < count($data); $i++) {
            if (!isset($data[$i]) || !is_array($data[$i])) {
                $data[$i] = [];
            }

            if (!isset($data[$i]['produk_detail']) || !is_array($data[$i]['produk_detail'])) {
                $data[$i]['produk_detail'] = [];
                continue;
            }

            for ($j = 0; $j < count($data[$i]['produk_detail']); $j++) {
                $p = is_array($data[$i]['produk_detail'][$j])
                    ? $data[$i]['produk_detail'][$j]
                    : [];

                $sku = $this->firstNonEmpty($p, [
                    'sku',
                    '__sku',
                    'SKU Induk',
                    'SKU',
                ]);

                $sku = trim((string) $sku);

                $p['sku'] = $sku;
                $p['__sku'] = array_key_exists('__sku', $p) ? $p['__sku'] : $sku;

                if ($sku !== '') {
                    $skuList[] = $sku;
                }

                $data[$i]['produk_detail'][$j] = $p;
            }
        }

        $skuList = array_values(array_unique(array_filter(array_map(
            fn ($s) => trim((string) $s),
            $skuList
        ))));

        $skuToHpp = [];

        if (!empty($skuList)) {
            $rows = DB::table('produk')
                ->whereIn('sku', $skuList)
                ->select('sku', 'hpp')
                ->get();

            foreach ($rows as $r) {
                $key = Str::upper(trim((string) $r->sku));
                $skuToHpp[$key] = (float) $r->hpp;
            }
        }

        $changed = false;

        for ($i = 0; $i < count($data); $i++) {
            for ($j = 0; $j < count($data[$i]['produk_detail']); $j++) {
                $p = $data[$i]['produk_detail'][$j];

                $key = Str::upper(trim((string) ($p['sku'] ?? '')));

                $hpp = $key !== '' && isset($skuToHpp[$key])
                    ? (float) $skuToHpp[$key]
                    : 0.0;

                $currentHpp = isset($p['HPP'])
                    ? (float) $p['HPP']
                    : null;

                if ($currentHpp !== $hpp) {
                    $p['HPP'] = $hpp;
                    $changed = true;
                }

                $data[$i]['produk_detail'][$j] = $p;
            }
        }

        if ($changed) {
            Session::put('preview_pesanan', $data);
        }

        return response()->json([
            'status' => 'success',
            'data' => $data,
        ]);
    }

    private function firstNonEmpty(array $arr, array $keys): string
    {
        foreach ($keys as $k) {
            if (array_key_exists($k, $arr)) {
                $val = $arr[$k];

                if (is_string($val)) {
                    $val = trim($val);
                }

                if ($val !== null && $val !== '') {
                    return (string) $val;
                }
            }
        }

        return '';
    }

    public function simpanImport(
        Request $request,
        EditorPartService $partService
    ) {
        $request->validate([
            'pesanan' => [
                'required',
                'array',
                'min:1',
            ],

            'tanggal_import' => [
                'required',
                'date',
            ],

            'id_toko' => [
                'required',
                'integer',
                'exists:toko,id_toko',
            ],

            'nama_user' => [
                'nullable',
                'string',
            ],
        ]);

        $payload =
            $request->input(
                'pesanan',
                []
            );

        $tanggal =
            Carbon::parse(
                $request->input(
                    'tanggal_import'
                )
            )->startOfDay();

        $idUser =
            auth()->id();

        $idToko =
            (int) $request->input(
                'id_toko'
            );

        $toko =
            Toko::select(
                'id_toko',
                'marketplace',
                'biaya_admin',
                'biaya_tambahan'
            )->find(
                $idToko
            );

        if (!$toko) {
            return response()->json([
                'status' => 'error',
                'message' =>
                    'Data toko tidak ditemukan.',
            ], 422);
        }

        $previewRaw =
            Session::get(
                'preview_pesanan',
                []
            );

        if (is_string($previewRaw)) {
            $previewRaw =
                json_decode(
                    $previewRaw,
                    true
                );

            if (!is_array($previewRaw)) {
                $previewRaw = [];
            }
        }

        $previewByOrder =
            collect($previewRaw)
                ->filter(
                    fn ($row) =>
                        is_array($row) &&
                        !empty(
                            $row['no_pesanan']
                        )
                )
                ->keyBy(
                    fn ($row) =>
                        trim(
                            (string)
                            $row['no_pesanan']
                        )
                );

        $biayaAdmin =
            (float) (
                $toko->biaya_admin
                ?? 0
            );

        $biayaTambahan =
            (float) (
                $toko->biaya_tambahan
                ?? 0
            );

        $imported = 0;
        $skipped = [];
        $platItemIds = [];

        DB::beginTransaction();

        try {
            foreach ($payload as $item) {
                $noPesanan =
                    trim(
                        (string) (
                            $item[
                                'no_pesanan'
                            ]
                            ?? ''
                        )
                    );

                if ($noPesanan === '') {
                    throw new \Exception(
                        'Nomor pesanan tidak boleh kosong.'
                    );
                }

                $sudahAda =
                    DB::table(
                        'pesanan'
                    )
                        ->where(
                            'no_pesanan',
                            $noPesanan
                        )
                        ->exists();

                if ($sudahAda) {
                    $skipped[] =
                        $noPesanan;

                    continue;
                }

                $previewItem =
                    $previewByOrder->get(
                        $noPesanan,
                        []
                    );

                $batasKirimAt =
                    $previewItem[
                        'batas_kirim_at'
                    ]
                    ?? $item[
                        'batas_kirim_at'
                    ]
                    ?? null;

                $batasKirimRaw =
                    $previewItem[
                        'batas_kirim_raw'
                    ]
                    ?? $item[
                        'batas_kirim_raw'
                    ]
                    ?? null;

                $batasKirimSource =
                    $previewItem[
                        'batas_kirim_source'
                    ]
                    ?? $item[
                        'batas_kirim_source'
                    ]
                    ?? null;

                DB::table(
                    'pesanan'
                )->insert([
                    'no_pesanan' =>
                        $noPesanan,

                    'tanggal' =>
                        $tanggal,

                    'no_resi' =>
                        !empty(
                            $item['no_resi']
                        )
                        ? trim(
                            (string)
                            $item['no_resi']
                        )
                        : null,

                    'id_toko' =>
                        $idToko,

                    'id_user' =>
                        $idUser,

                    'nama_pembeli' =>
                        trim(
                            (string) (
                                $item[
                                    'nama_pembeli'
                                ]
                                ?? ''
                            )
                        ),

                    'username' =>
                        trim(
                            (string) (
                                $item['username']
                                ?? ''
                            )
                        ),

                    'kurir' =>
                        trim(
                            (string) (
                                $item['kurir']
                                ?? ''
                            )
                        ),

                    'status' =>
                        'proses',

                    'total_hpp' =>
                        0,

                    'total_harga' =>
                        0,

                    'total_admin' =>
                        0,

                    'pencairan' =>
                        null,

                    'notes' =>
                        null,

                    'batas_kirim_at' =>
                        $batasKirimAt,

                    'batas_kirim_raw' =>
                        $batasKirimRaw,

                    'batas_kirim_source' =>
                        $batasKirimSource,
                ]);

                $totalHpp = 0;
                $totalHarga = 0;

                foreach (
                    $item['produk'] ?? []
                    as $prd
                ) {
                    $namaProduk =
                        trim(
                            (string) (
                                $prd[
                                    'nama_produk'
                                ]
                                ?? ''
                            )
                        );

                    $variasi =
                        trim(
                            (string) (
                                $prd['variasi']
                                ?? ''
                            )
                        );

                    $jumlah =
                        max(
                            1,
                            (int) (
                                $prd['jumlah']
                                ?? 1
                            )
                        );

                    $harga =
                        (float) (
                            $prd['harga']
                            ?? 0
                        );

                    $hpp =
                        (float) (
                            $prd['hpp']
                            ?? 0
                        );

                    $sku =
                        !empty(
                            $prd['sku']
                        )
                        ? strtoupper(
                            trim(
                                (string)
                                $prd['sku']
                            )
                        )
                        : null;

                    $idPerProduk =
                        DB::table(
                            'pesanan_per_produk'
                        )->insertGetId(
                            [
                                'no_pesanan' =>
                                    $noPesanan,

                                'nama_produk' =>
                                    $namaProduk,

                                'variasi' =>
                                    $variasi,

                                'jumlah' =>
                                    $jumlah,

                                'hpp' =>
                                    $hpp,

                                'harga' =>
                                    $harga,

                                'sku' =>
                                    $sku,
                            ],
                            'id_per_produk'
                        );

                    if (
                        $sku &&
                        str_starts_with(
                            $sku,
                            'PLT'
                        )
                    ) {
                        $platItemIds[] =
                            (int)
                            $idPerProduk;
                    }

                    $totalHpp +=
                        $hpp * $jumlah;

                    $totalHarga +=
                        $harga * $jumlah;
                }

                if (
                    $biayaAdmin > 1 &&
                    $biayaAdmin <= 100
                ) {
                    $totalAdmin =
                        (
                            $totalHarga *
                            (
                                $biayaAdmin
                                / 100
                            )
                        )
                        + $biayaTambahan;
                } elseif (
                    $biayaAdmin > 0 &&
                    $biayaAdmin <= 1
                ) {
                    $totalAdmin =
                        (
                            $totalHarga *
                            $biayaAdmin
                        )
                        + $biayaTambahan;
                } else {
                    $totalAdmin =
                        $biayaTambahan;
                }

                DB::table(
                    'pesanan'
                )
                    ->where(
                        'no_pesanan',
                        $noPesanan
                    )
                    ->update([
                        'total_hpp' =>
                            $totalHpp,

                        'total_harga' =>
                            $totalHarga,

                        'total_admin' =>
                            $totalAdmin,
                    ]);

                $imported++;
            }

            $hasilPart =
                $partService
                    ->alokasikanItemBaru(
                        $platItemIds,
                        $idUser
                    );

            DB::commit();

            Session::forget(
                'preview_pesanan'
            );

            return response()->json([
                'status' =>
                    'success',

                'message' =>
                    'Import selesai.',

                'imported' =>
                    $imported,

                'skipped_count' =>
                    count($skipped),

                'skipped' =>
                    $skipped,

                'part_items' =>
                    $hasilPart['items'],

                'parts_baru' =>
                    $hasilPart['parts_baru'],

                'part_oversize' =>
                    $hasilPart['oversize'],
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();

            report($e);

            return response()->json([
                'status' =>
                    'error',

                'message' =>
                    $e->getMessage(),
            ], 500);
        }
    }
}
