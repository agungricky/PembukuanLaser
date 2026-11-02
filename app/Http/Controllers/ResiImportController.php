<?php

namespace App\Http\Controllers;

use App\Models\Pesanan;
use App\Models\ResiImport;
use App\Models\ResiPage;
use App\Models\Toko;
use App\Services\ImportResi\DeadlineUpdater;
use App\Services\ImportResi\ShopeeService;
use App\Services\ImportResi\TikTokService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Smalot\PdfParser\Parser;

class ResiImportController extends Controller
{
    protected ShopeeService $shopeeService;

    protected TikTokService $tiktokService;

    public function __construct(
        ShopeeService $shopeeService,
        TikTokService $tiktokService
    ) {
        $this->shopeeService = $shopeeService;
        $this->tiktokService = $tiktokService;
    }

    public function index()
    {
        $toko = Toko::orderBy('marketplace')
            ->orderBy('nama_toko')
            ->get();

        return view('resi.import', compact('toko'));
    }

    public function preview(Request $request)
    {
        $request->validate([
            'marketplace' => 'required|in:Shopee,Tiktok',
            'id_toko' => 'required|exists:toko,id_toko',
            'file_resi' => 'required|file|mimes:pdf|max:51200',
        ]);

        $tokoDipilih = Toko::where('id_toko', $request->id_toko)
            ->where('marketplace', $request->marketplace)
            ->first();

        if (! $tokoDipilih) {
            return back()
                ->withInput()
                ->with(
                    'error',
                    'Toko tidak sesuai dengan marketplace.'
                );
        }

        // Hapus preview lama kalau ada & hapus file temporary lama
        $previewLama = session('resi_preview');
        if ($previewLama && ! empty($previewLama['temp_path']) && File::exists($previewLama['temp_path'])) {
            File::delete($previewLama['temp_path']);
        }

        session()->forget('resi_preview');

        // pindahkan pdf sementara ke Temp directory
        $tempDirectory = storage_path('app/private/resi_temp');
        File::ensureDirectoryExists($tempDirectory);
        $tempName = Str::uuid() . '.pdf';
        $request->file('file_resi')->move($tempDirectory, $tempName);
        $tempPath =
            $tempDirectory .
            DIRECTORY_SEPARATOR .
            $tempName;

        // Parse PDF / Pengenalan halaman
        try {
            $parser = new Parser;
            $pdf = $parser->parseFile($tempPath);
            $pages = $pdf->getPages();
        } catch (\Throwable $e) {
            File::delete($tempPath);

            return back()
                ->withInput()
                ->with(
                    'error',
                    'PDF gagal dibaca: ' .
                        $e->getMessage()
                );
        }

        // Pastikan ada halaman
        if (empty($pages)) {
            File::delete($tempPath);

            return back()
                ->withInput()
                ->with(
                    'error',
                    'PDF tidak memiliki halaman yang dapat dibaca.'
                );
        }

        // Pilih Service berdasarkan marketplace
        $service = match ($request->marketplace) {
            'Shopee' => $this->shopeeService,
            'Tiktok' => $this->tiktokService,
        };

        // Ambil data toko untuk service TikTok
        if ($request->marketplace === 'Tiktok') {
            $this->tiktokService
                ->prepareContext(
                    (int) $request->id_toko
                );
        }

        // Membaca halaman
        $preview = [];
        foreach ($pages as $index => $page) {
            try {
                $text = $page->getText();
            } catch (\Throwable $e) {
                $text = '';
            }

            $hasil = $service->detectPage($text, (int) $request->id_toko);
            if (empty($hasil['batas_kirim_at'])) {

                if (preg_match(
                    '/Batas\s*Kirim\s*:\s*(?:-\s*)?(\d{1,2}[-\/]\d{1,2}[-\/]\d{4})/iu',
                    $text,
                    $matches
                )) {
                    $raw = $matches[1];

                    try {
                        $tanggal = \Carbon\Carbon::createFromFormat(
                            '!d-m-Y',
                            str_replace('/', '-', $raw)
                        );

                        if ($tanggal->format('d-m-Y') === str_replace('/', '-', $raw)) {
                            $hasil['batas_kirim_at'] = $tanggal->format('Y-m-d');
                            $hasil['batas_kirim_source'] = 'resi';
                            $hasil['batas_kirim_raw'] = $raw;
                        }
                    } catch (\Throwable $e) {
                    }
                }
            }

            $preview[] = [
                'halaman' => $index + 1,
                'no_pesanan' => $hasil['no_pesanan'] ?? '',
                'no_resi' => $hasil['no_resi'] ?? '',
                'status' => $hasil['status'] ?? 'unreadable',
                'batas_kirim_at' => $hasil['batas_kirim_at'] ?? null,
                'batas_kirim_source' => $hasil['batas_kirim_source'] ?? null,
                'batas_kirim_raw' => $hasil['batas_kirim_raw'] ?? null,
            ];
        }

        session([
            'resi_preview' => [
                'temp_name' => $tempName,
                'temp_path' => $tempPath,
                'original_name' => $request->file('file_resi')
                    ->getClientOriginalName(),
                'marketplace' => $request->marketplace,
                'id_toko' => (int) $request->id_toko,
                'jumlah_halaman' => count($pages),
                'detected_pages' => $preview,
            ],
        ]);

        $toko = Toko::orderBy('marketplace')
            ->orderBy('nama_toko')
            ->get();

        return view(
            'resi.import',
            [
                'toko' => $toko,
                'preview' => $preview,
                'selectedMarketplace' => $request->marketplace,
                'selectedToko' => (int) $request->id_toko,
            ]
        );
    }

    public function store(Request $request)
    {
        $dataPreview = session('resi_preview');

        if (!$dataPreview) {
            return redirect()
                ->route('resi.import')
                ->with(
                    'error',
                    'Preview sudah tidak tersedia. Upload PDF kembali.'
                );
        }

        $request->validate([
            'pages' => 'required|array',
            'pages.*.halaman' => 'required|integer|min:1',
            'pages.*.no_pesanan' => 'nullable|string|max:50',
            'pages.*.no_resi' => 'nullable|string|max:100',
        ]);

        $tempPath = $dataPreview['temp_path'] ?? null;
        $idToko = (int) $dataPreview['id_toko'];
        if (empty($tempPath) || !File::exists($tempPath)) {
            session()->forget('resi_preview');

            return redirect()
                ->route('resi.import')
                ->with(
                    'error',
                    'File PDF sementara tidak ditemukan. Upload kembali.'
                );
        }

        $mappings = collect($request->pages)
            ->map(function ($page) {
                return [
                    'halaman' => (int) ($page['halaman'] ?? 0),
                    'no_pesanan' => preg_replace(
                        '/\s+/',
                        '',
                        (string) ($page['no_pesanan'] ?? '')
                    ),
                    'no_resi' => trim(
                        (string) ($page['no_resi'] ?? '')
                    ),
                ];
            })
            ->filter(
                fn($page) =>
                $page['halaman'] > 0 &&
                    $page['no_pesanan'] !== ''
            )
            ->values();

        if ($mappings->isEmpty()) {
            return redirect()
                ->route('resi.import')
                ->with('error', 'Tidak ada halaman yang memiliki No Pesanan.');
        }

        $detectedPages = collect($dataPreview['detected_pages'] ?? [])->keyBy(
            fn($item) => (int) ($item['halaman'] ?? 0)
        );

        $orderNumbers = $mappings
            ->pluck('no_pesanan')
            ->unique()
            ->values();

        $pesanan = collect();
        $existingMappedOrders = collect();
        foreach ($orderNumbers->chunk(500) as $batch) {
            $pesanan = $pesanan->concat(
                Pesanan::query()
                    ->where('id_toko', $idToko)
                    ->whereIn('no_pesanan', $batch)
                    ->get(['no_pesanan', 'no_resi', 'batas_kirim_at'])
            );

            $existingMappedOrders = $existingMappedOrders->concat(
                ResiPage::query()
                    ->whereIn('no_pesanan', $batch)
                    ->pluck('no_pesanan')
            );
        }

        $pesanan = $pesanan->keyBy(
            fn($item) => (string) $item->no_pesanan
        );

        $existingMappedOrders = $existingMappedOrders
            ->map(fn($value) => (string) $value)
            ->unique()
            ->flip();

        $validPages = [];
        $deadlineOnlyPages = [];
        $errors = [];
        $urutan = [];
        $processedExistingDeadlines = [];

        foreach ($mappings as $page) {
            $noPesanan = (string) $page['no_pesanan'];
            $halaman = $page['halaman'];

            if (!$pesanan->has($noPesanan)) {
                $errors[] =
                    "Halaman {$halaman}: Pesanan {$noPesanan} "
                    . "tidak ditemukan pada toko ini.";

                continue;
            }

            $order = $pesanan->get($noPesanan);
            $detected = (array) (
                $detectedPages->get($halaman) ?? []
            );

            $deadline = $this->normalizeDeadlinePayload($detected);
            $batasKirimAt = $deadline['batas_kirim_at'] ?? null;
            $noResi = $page['no_resi'] !== ''
                ? $page['no_resi']
                : (string) $order->no_resi;

            if ($existingMappedOrders->has($noPesanan)) {
                if (!is_null($order->batas_kirim_at)) {
                    $errors[] =
                        "Halaman {$halaman}: Pesanan {$noPesanan} "
                        . "sudah memiliki PDF resi dan batas kirim.";

                    continue;
                }

                if (empty($batasKirimAt)) {
                    $errors[] =
                        "Halaman {$halaman}: Pesanan {$noPesanan} "
                        . "sudah memiliki PDF resi, tetapi "
                        . "tanggal batas kirim tidak terbaca.";

                    continue;
                }

                if (isset($processedExistingDeadlines[$noPesanan])) {
                    continue;
                }

                $processedExistingDeadlines[$noPesanan] = true;
                $deadlineOnlyPages[] = [
                    'halaman' => $halaman,
                    'no_pesanan' => $noPesanan,
                    'no_resi' => $noResi,
                    'batas_kirim_at' => $batasKirimAt,
                    'batas_kirim_source' => $deadline['batas_kirim_source'] ?? null,
                    'batas_kirim_raw' => $deadline['batas_kirim_raw'] ?? null,
                ];

                continue;
            }

            $urutan[$noPesanan] = ($urutan[$noPesanan] ?? 0) + 1;
            $validPages[] = [
                'halaman' => $halaman,
                'no_pesanan' => $noPesanan,
                'no_resi' => $noResi,
                'urutan' => $urutan[$noPesanan],
                'batas_kirim_at' => $batasKirimAt,
                'batas_kirim_source' => $deadline['batas_kirim_source'] ?? null,
                'batas_kirim_raw' => $deadline['batas_kirim_raw'] ?? null,
            ];
        }

        if (empty($validPages) && empty($deadlineOnlyPages)) {
            return redirect()
                ->route('resi.import')
                ->with(
                    'error',
                    'Tidak ada resi baru atau batas kirim yang dapat diperbarui.'
                )
                ->with('import_errors', $errors);
        }

        $now = now();
        $directory = 'resi/'. $now->format('Y'). '/'. $now->format('m');
        $fullDirectory = storage_path('app/private/' . $directory);
        $newName = Str::uuid() . '.pdf';
        $relativePath = $directory . '/' . $newName;
        $fullPath = $fullDirectory
            . DIRECTORY_SEPARATOR
            . $newName;

        $fileMoved = false;
        $jumlahBatasKirimUpdated = 0;

        try {
            if (!empty($validPages)) {
                File::ensureDirectoryExists($fullDirectory);
                if (!File::move($tempPath, $fullPath)) {
                    throw new \RuntimeException(
                        'Gagal memindahkan PDF resi.'
                    );
                }

                $fileMoved = true;
            }

            DB::transaction(function () use (
                $dataPreview,
                $idToko,
                $validPages,
                $deadlineOnlyPages,
                $relativePath,
                $now,
                &$jumlahBatasKirimUpdated
            ) {
                if (!empty($validPages)) {
                    $import = ResiImport::create([
                        'nama_file' => $dataPreview['original_name'],
                        'path_file' => $relativePath,
                        'jumlah_halaman' => $dataPreview['jumlah_halaman'],
                        'marketplace' => $dataPreview['marketplace'],
                        'id_toko' => $idToko,
                        'user_id' => Auth::id(),
                    ]);

                    $resiPages = [];
                    foreach ($validPages as $page) {
                        $resiPages[] = [
                            'resi_import_id' => $import->id,
                            'no_pesanan' => $page['no_pesanan'],
                            'no_resi' => $page['no_resi'],
                            'halaman' => $page['halaman'],
                            'urutan' => $page['urutan'],
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }

                    foreach (array_chunk($resiPages, 500) as $batch) {
                        ResiPage::insert($batch);
                    }

                    app(DeadlineUpdater::class)->update(
                        $idToko,
                        $validPages
                    );
                }

                foreach ($deadlineOnlyPages as $page) {
                    if (empty($page['batas_kirim_at'])) {
                        continue;
                    }

                    $updated = Pesanan::query()
                        ->where('id_toko', $idToko)
                        ->where('no_pesanan', $page['no_pesanan'])
                        ->whereNull('batas_kirim_at')
                        ->update([
                            'batas_kirim_at' => $page['batas_kirim_at'],
                        ]);

                    $jumlahBatasKirimUpdated += $updated;
                }
            });

            if (!$fileMoved && File::exists($tempPath)) {
                File::delete($tempPath);
            }

            session()->forget('resi_preview');
            $pesan =
                count($validPages)
                . ' halaman resi baru berhasil disimpan. '
                . $jumlahBatasKirimUpdated
                . ' batas kirim resi lama berhasil diperbarui.';

            return redirect()
                ->route('resi.import')
                ->with('success', $pesan)
                ->with('import_errors', $errors);
        } catch (\Throwable $e) {
            if ($fileMoved && File::exists($fullPath)) {
                File::delete($fullPath);
            }

            report($e);

            return redirect()
                ->route('resi.import')
                ->with(
                    'error',
                    'Gagal menyimpan PDF resi. Periksa log aplikasi.'
                );
        }
    }

    private function normalizeDeadlinePayload(array $data): array
    {
        return [
            'batas_kirim_at' => $data['batas_kirim_at'] ?? null,
            'batas_kirim_source' => $data['batas_kirim_source'] ?? null,
            'batas_kirim_raw' => $data['batas_kirim_raw'] ?? null,
        ];
    }
}
