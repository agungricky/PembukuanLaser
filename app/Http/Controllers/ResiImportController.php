<?php

namespace App\Http\Controllers;

use App\Models\Pesanan;
use App\Models\ResiImport;
use App\Models\ResiPage;
use App\Models\Toko;
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
        $tempName = Str::uuid().'.pdf';
        $request->file('file_resi')->move($tempDirectory, $tempName);
        $tempPath =
            $tempDirectory.
            DIRECTORY_SEPARATOR.
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
                    'PDF gagal dibaca: '.
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

        return view('resi.import', [
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

        if (! $dataPreview) {
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
        if (empty($tempPath) || ! File::exists($tempPath)) {
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
                    'no_pesanan' => preg_replace('/\s+/', '', (string) ($page['no_pesanan'] ?? '')),
                    'no_resi' => trim((string) ($page['no_resi'] ?? '')),
                ];
            })
            ->filter(
                fn ($page) => $page['halaman'] > 0 &&
                    $page['no_pesanan'] !== ''
            )
            ->values();

        if ($mappings->isEmpty()) {
            return back()
                ->with(
                    'error',
                    'Tidak ada halaman yang memiliki No Pesanan.'
                );
        }

        $detectedPages = collect($dataPreview['detected_pages'] ?? [])->keyBy(
            fn ($item) => (int) ($item['halaman'] ?? 0)
        );

        $orderNumbers = $mappings
            ->pluck('no_pesanan')
            ->unique()
            ->values();

        $pesanan = Pesanan::query()
            ->where('id_toko', $dataPreview['id_toko'])
            ->whereIn('no_pesanan', $orderNumbers)
            ->get(['no_pesanan', 'no_resi',])
            ->keyBy(
                fn ($item) => (string) $item->no_pesanan
            );

        $existingMappedOrders = ResiPage::query()
            ->whereIn('no_pesanan', $orderNumbers)
            ->pluck('no_pesanan')
            ->map(
                fn ($value) => (string) $value
            )
            ->unique()
            ->flip();

        $validPages = [];
        $errors = [];
        $urutan = [];

        foreach ($mappings as $page) {
            $noPesanan = (string) $page['no_pesanan'];

            if (! $pesanan->has($noPesanan)) {
                $errors[] =
                    "Halaman {$page['halaman']}: "
                    ."Pesanan {$noPesanan} tidak ditemukan pada toko ini.";

                continue;
            }

            if ($existingMappedOrders->has($noPesanan)) {
                $errors[] = "Halaman {$page['halaman']}: " . "Pesanan {$noPesanan} sudah memiliki PDF resi.";

                continue;
            }

            $order = $pesanan->get($noPesanan);
            $urutan[$noPesanan] = ($urutan[$noPesanan] ?? 0) + 1;
            $detected = (array) ($detectedPages->get($page['halaman']) ?? []);
            $deadline = $this->normalizeDeadlinePayload($detected);

            $validPages[] = [
                'halaman' => $page['halaman'],
                'no_pesanan' => $noPesanan,
                'no_resi' => $page['no_resi'] !== '' ? $page['no_resi'] : (string) $order->no_resi,
                'urutan' => $urutan[$noPesanan],
                'batas_kirim_at' => $deadline['batas_kirim_at'] ?? null,
                'batas_kirim_source' => $deadline['batas_kirim_source'] ?? null,
                'batas_kirim_raw' => $deadline['batas_kirim_raw'] ?? null,
            ];
        }

        if (empty($validPages)) {
            return back()
                ->with(
                    'error',
                    'Tidak ada halaman yang dapat disimpan.'
                )
                ->with('import_errors', $errors);
        }

        $now = now();
        $directory = 'resi/'.$now->format('Y').'/'.$now->format('m');
        $fullDirectory = storage_path('app/private/'.$directory);
        File::ensureDirectoryExists($fullDirectory);
        $newName = Str::uuid().'.pdf';
        $relativePath = $directory.'/'.$newName;
        $fullPath = $fullDirectory.DIRECTORY_SEPARATOR.$newName;

        try {
            File::move($tempPath, $fullPath);

            DB::transaction(
                function () use ($dataPreview, $validPages, $relativePath, $now) {
                    $import =
                        ResiImport::create([
                            'nama_file' => $dataPreview['original_name'],
                            'path_file' => $relativePath,
                            'jumlah_halaman' => $dataPreview['jumlah_halaman'],
                            'marketplace' => $dataPreview['marketplace'],
                            'id_toko' => $dataPreview['id_toko'],
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

                    if (! empty($resiPages)) {
                        ResiPage::insert($resiPages);
                    }

                    $deadlineUpdates =
                        collect($validPages)
                            ->filter(
                                fn ($page) => ! empty($page['batas_kirim_at'])
                            )
                            ->keyBy('no_pesanan');

                    foreach ($deadlineUpdates as $noPesanan => $page) {
                        Pesanan::query()
                            ->where('id_toko', $dataPreview['id_toko'])
                            ->where('no_pesanan', $noPesanan)
                            ->update([
                                'batas_kirim_at' => $page['batas_kirim_at'],
                                'batas_kirim_source' => $page['batas_kirim_source'],
                                'batas_kirim_raw' => $page['batas_kirim_raw'],
                            ]);
                    }
                }
            );

            session()->forget('resi_preview');

            return redirect()
                ->route('resi.import')
                ->with('success', count($validPages).' halaman resi berhasil disimpan.')
                ->with('import_errors', $errors);
        } catch (\Throwable $e) {
            if (File::exists($fullPath)) {
                File::delete($fullPath);
            }

            report($e);

            return back()
                ->with(
                    'error',
                    'Gagal menyimpan PDF resi: '.$e->getMessage()
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
