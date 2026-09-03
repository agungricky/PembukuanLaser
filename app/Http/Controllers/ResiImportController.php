<?php

namespace App\Http\Controllers;

use App\Models\Pesanan;
use App\Models\ResiImport;
use App\Models\ResiPage;
use App\Models\Toko;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Smalot\PdfParser\Parser;

class ResiImportController extends Controller
{
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

        if (!$tokoDipilih) {
            return back()
                ->withInput()
                ->with('error', 'Toko tidak sesuai dengan marketplace.');
        }

        $previewLama = session('resi_preview');

        if (
            $previewLama &&
            !empty($previewLama['temp_path']) &&
            File::exists($previewLama['temp_path'])
        ) {
            File::delete($previewLama['temp_path']);
        }

        session()->forget('resi_preview');

        $tempDirectory = storage_path(
            'app/private/resi_temp'
        );

        File::ensureDirectoryExists(
            $tempDirectory
        );

        $tempName = Str::uuid() . '.pdf';

        $request->file('file_resi')->move(
            $tempDirectory,
            $tempName
        );

        $tempPath =
            $tempDirectory .
            DIRECTORY_SEPARATOR .
            $tempName;

        try {
            $parser = new Parser();

            $pdf = $parser->parseFile(
                $tempPath
            );

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

        if (empty($pages)) {
            File::delete($tempPath);

            return back()
                ->withInput()
                ->with(
                    'error',
                    'PDF tidak memiliki halaman yang dapat dibaca.'
                );
        }

        $preview = [];

        foreach ($pages as $index => $page) {
            $halaman = $index + 1;

            try {
                $text = $page->getText();
            } catch (\Throwable $e) {
                $text = '';
            }

            $hasil = $this->detectPage(
                $text,
                (int) $request->id_toko,
                $request->marketplace
            );

            $preview[] = [
                'halaman' => $halaman,
                'no_pesanan' => $hasil['no_pesanan'],
                'no_resi' => $hasil['no_resi'],
                'status' => $hasil['status'],
                'batas_kirim_at' => $hasil['batas_kirim_at'] ?? null,
                'batas_kirim_source' => $hasil['batas_kirim_source'] ?? null,
                'batas_kirim_raw' => $hasil['batas_kirim_raw'] ?? null,
            ];
        }

        if (strcasecmp((string) $request->marketplace, 'Tiktok') === 0) {
            try {
                $normalizedPath = $this->normalizeTikTokPdf(
                    $tempPath,
                    $tempDirectory
                );

                File::delete($tempPath);

                $tempPath = $normalizedPath;
                $tempName = basename($normalizedPath);
            } catch (\Throwable $e) {
                File::delete($tempPath);

                return back()
                    ->withInput()
                    ->with(
                        'error',
                        'PDF TikTok gagal dinormalisasi: ' .
                        $e->getMessage()
                    );
            }
        }

        session([
            'resi_preview' => [
                'temp_name' => $tempName,
                'temp_path' => $tempPath,
                'original_name' => $request
                    ->file('file_resi')
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
        ]);
    }

    public function store(Request $request)
    {
        $dataPreview = session(
            'resi_preview'
        );

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

        if (
            empty($dataPreview['temp_path']) ||
            !File::exists(
                $dataPreview['temp_path']
            )
        ) {
            session()->forget(
                'resi_preview'
            );

            return redirect()
                ->route('resi.import')
                ->with(
                    'error',
                    'File PDF sementara tidak ditemukan. Upload kembali.'
                );
        }

        $mappings = collect(
            $request->pages
        )
            ->map(function ($page) {
                return [
                    'halaman' => (int) (
                        $page['halaman'] ?? 0
                    ),

                    'no_pesanan' => trim(
                        (string) (
                            $page['no_pesanan'] ?? ''
                        )
                    ),

                    'no_resi' => trim(
                        (string) (
                            $page['no_resi'] ?? ''
                        )
                    ),
                ];
            })
            ->filter(
                fn ($page) =>
                    $page['no_pesanan'] !== ''
            )
            ->values();

        if ($mappings->isEmpty()) {
            return back()->with(
                'error',
                'Tidak ada halaman yang memiliki No Pesanan.'
            );
        }

        $detectedPages = collect(
            $dataPreview['detected_pages'] ?? []
        )->keyBy(
            fn ($item) => (int) ($item['halaman'] ?? 0)
        );

        $orderNumbers = $mappings
            ->pluck('no_pesanan')
            ->unique()
            ->values();

        $pesanan = Pesanan::whereIn(
                'no_pesanan',
                $orderNumbers
            )
            ->where(
                'id_toko',
                $dataPreview['id_toko']
            )
            ->get()
            ->keyBy(
                fn ($item) =>
                    (string) $item->no_pesanan
            );

        $existingMappedOrders =
            ResiPage::whereIn(
                'no_pesanan',
                $orderNumbers
            )
            ->pluck('no_pesanan')
            ->map(
                fn ($value) =>
                    (string) $value
            )
            ->unique()
            ->flip();

        $validPages = [];
        $errors = [];
        $urutan = [];

        foreach ($mappings as $page) {
            $noPesanan =
                (string) $page['no_pesanan'];

            if (!$pesanan->has($noPesanan)) {
                $errors[] =
                    "Halaman {$page['halaman']}: Pesanan {$noPesanan} tidak ditemukan pada toko ini.";

                continue;
            }

            if (
                $existingMappedOrders->has(
                    $noPesanan
                )
            ) {
                $errors[] =
                    "Halaman {$page['halaman']}: Pesanan {$noPesanan} sudah memiliki PDF resi.";

                continue;
            }

            $order = $pesanan->get(
                $noPesanan
            );

            $urutan[$noPesanan] =
                ($urutan[$noPesanan] ?? 0)
                + 1;

            $deadline = $dataPreview['marketplace'] === 'Shopee'
                ? $this->getShopeeDeadlineFromOrder($order)
                : $this->normalizeDeadlinePayload(
                    (array) ($detectedPages->get($page['halaman']) ?? [])
                );

            $validPages[] = [
                'halaman' =>
                    $page['halaman'],

                'no_pesanan' =>
                    $noPesanan,

                'no_resi' =>
                    $page['no_resi'] !== ''
                        ? $page['no_resi']
                        : (string) $order->no_resi,

                'urutan' =>
                    $urutan[$noPesanan],

                'batas_kirim_at' =>
                    $deadline['batas_kirim_at'],

                'batas_kirim_source' =>
                    $deadline['batas_kirim_source'],

                'batas_kirim_raw' =>
                    $deadline['batas_kirim_raw'],
            ];
        }

        if (empty($validPages)) {
            return back()
                ->with(
                    'error',
                    'Tidak ada halaman yang dapat disimpan.'
                )
                ->with(
                    'import_errors',
                    $errors
                );
        }

        $directory =
            'resi/' .
            now()->format('Y') .
            '/' .
            now()->format('m');

        $fullDirectory =
            storage_path(
                'app/private/' .
                $directory
            );

        File::ensureDirectoryExists(
            $fullDirectory
        );

        $newName =
            Str::uuid() .
            '.pdf';

        $relativePath =
            $directory .
            '/' .
            $newName;

        $fullPath =
            $fullDirectory .
            DIRECTORY_SEPARATOR .
            $newName;

        try {
            File::move(
                $dataPreview['temp_path'],
                $fullPath
            );

            DB::beginTransaction();

            $import = ResiImport::create([
                'nama_file' =>
                    $dataPreview['original_name'],

                'path_file' =>
                    $relativePath,

                'jumlah_halaman' =>
                    $dataPreview['jumlah_halaman'],

                'marketplace' =>
                    $dataPreview['marketplace'],

                'id_toko' =>
                    $dataPreview['id_toko'],

                'user_id' =>
                    Auth::id(),
            ]);

            foreach ($validPages as $page) {
                ResiPage::create([
                    'resi_import_id' =>
                        $import->id,

                    'no_pesanan' =>
                        $page['no_pesanan'],

                    'no_resi' =>
                        $page['no_resi'],

                    'halaman' =>
                        $page['halaman'],

                    'urutan' =>
                        $page['urutan'],
                ]);

                if (!empty($page['batas_kirim_at'])) {
                    Pesanan::where(
                        'no_pesanan',
                        $page['no_pesanan']
                    )
                        ->where(
                            'id_toko',
                            $dataPreview['id_toko']
                        )
                        ->update([
                            'batas_kirim_at' =>
                                $page['batas_kirim_at'],
                            'batas_kirim_source' =>
                                $page['batas_kirim_source'],
                            'batas_kirim_raw' =>
                                $page['batas_kirim_raw'],
                        ]);
                }
            }

            DB::commit();

            if (strcasecmp((string) $dataPreview['marketplace'], 'Tiktok') === 0) {
                @file_put_contents(
                    $fullPath . '.fpdi14',
                    now()->format('Y-m-d H:i:s')
                );
            }

            session()->forget(
                'resi_preview'
            );

            return redirect()
                ->route('resi.import')
                ->with(
                    'success',
                    count($validPages) .
                    ' halaman resi berhasil disimpan.'
                )
                ->with(
                    'import_errors',
                    $errors
                );

        } catch (\Throwable $e) {
            DB::rollBack();

            File::delete(
                $fullPath
            );

            report($e);

            return back()->with(
                'error',
                'Gagal menyimpan PDF resi: ' .
                $e->getMessage()
            );
        }
    }

    private function detectPage(
        string $text,
        int $idToko,
        string $marketplace
    ): array {
        $text = trim($text);

        if ($text === '') {
            return array_merge(
                [
                    'no_pesanan' => '',
                    'no_resi' => '',
                    'status' => 'unreadable',
                ],
                $this->emptyDeadlinePayload()
            );
        }

        if ($marketplace === 'Tiktok') {
            return array_merge(
                $this->detectTikTokPage(
                    $text,
                    $idToko
                ),
                $this->extractTikTokDeadline($text)
            );
        }

        $hasil = $this->detectShopeePage(
            $text,
            $idToko
        );

        if (!empty($hasil['no_pesanan'])) {
            $order = Pesanan::where(
                'no_pesanan',
                $hasil['no_pesanan']
            )
                ->where(
                    'id_toko',
                    $idToko
                )
                ->first();

            if ($order) {
                $hasil = array_merge(
                    $hasil,
                    $this->getShopeeDeadlineFromOrder($order)
                );
            }
        }

        return array_merge(
            $this->emptyDeadlinePayload(),
            $hasil
        );
    }

    private function detectShopeePage(
        string $text,
        int $idToko
    ): array {
        $noPesanan =
            $this->extractShopeeOrderNumber(
                $text
            );

        $noResi =
            $this->extractShopeeTrackingNumber(
                $text
            );

        return $this->resolvePage(
            $noPesanan,
            $noResi,
            $idToko
        );
    }

    private function detectTikTokPage(
        string $text,
        int $idToko
    ): array {
        $orderCandidates =
            $this->extractTikTokOrderCandidates(
                $text
            );

        $trackingCandidates =
            $this->extractTikTokTrackingCandidates(
                $text
            );

        $pesanan = null;
        $noPesanan = '';
        $noResi = '';

        if (!empty($orderCandidates)) {
            $matchedOrders =
                Pesanan::where(
                    'id_toko',
                    $idToko
                )
                ->whereIn(
                    'no_pesanan',
                    $orderCandidates
                )
                ->get()
                ->keyBy(
                    fn ($item) =>
                        (string) $item->no_pesanan
                );

            foreach (
                $orderCandidates
                as $candidate
            ) {
                if (
                    $matchedOrders->has(
                        $candidate
                    )
                ) {
                    $pesanan =
                        $matchedOrders->get(
                            $candidate
                        );

                    $noPesanan =
                        (string)
                        $pesanan->no_pesanan;

                    break;
                }
            }
        }

        if (
            !$pesanan &&
            !empty($trackingCandidates)
        ) {
            $matchedTracking =
                Pesanan::where(
                    'id_toko',
                    $idToko
                )
                ->whereIn(
                    'no_resi',
                    $trackingCandidates
                )
                ->get()
                ->keyBy(
                    fn ($item) =>
                        strtoupper(
                            trim(
                                (string)
                                $item->no_resi
                            )
                        )
                );

            foreach (
                $trackingCandidates
                as $candidate
            ) {
                $key = strtoupper(
                    trim($candidate)
                );

                if (
                    $matchedTracking->has(
                        $key
                    )
                ) {
                    $pesanan =
                        $matchedTracking->get(
                            $key
                        );

                    $noPesanan =
                        (string)
                        $pesanan->no_pesanan;

                    $noResi =
                        (string)
                        $pesanan->no_resi;

                    break;
                }
            }
        }

        if (!$pesanan) {
            $noPesanan =
                $orderCandidates[0] ?? '';

            $noResi =
                $this->preferredTikTokTracking(
                    $trackingCandidates
                );

            return [
                'no_pesanan' =>
                    $noPesanan,

                'no_resi' =>
                    $noResi,

                'status' =>
                    'not_found',
            ];
        }

        if ($noResi === '') {
            $noResi =
                $this->findMatchingTracking(
                    $trackingCandidates,
                    (string) $pesanan->no_resi
                );

            if ($noResi === '') {
                $noResi =
                    (string)
                    $pesanan->no_resi;
            }
        }

        $sudahAda =
            ResiPage::where(
                'no_pesanan',
                $pesanan->no_pesanan
            )
            ->exists();

        return [
            'no_pesanan' =>
                (string)
                $pesanan->no_pesanan,

            'no_resi' =>
                $noResi,

            'status' =>
                $sudahAda
                    ? 'existing'
                    : 'matched',
        ];
    }

    private function resolvePage(
        string $noPesanan,
        string $noResi,
        int $idToko
    ): array {
        $noPesanan = strtoupper(trim($noPesanan));
        $noResi = strtoupper(trim($noResi));

        $pesanan = null;

        // 1. Prioritas pertama: cocokkan No. Pesanan secara exact.
        if ($noPesanan !== '') {
            $pesanan = Pesanan::where(
                    'no_pesanan',
                    $noPesanan
                )
                ->where(
                    'id_toko',
                    $idToko
                )
                ->first();
        }

        // 2. Fallback Shopee PDF.
        // Kadang text layer PDF memotong 1 karakter terakhir No. Pesanan.
        // Prefix hanya dipakai jika hasilnya TEPAT satu pesanan agar aman.
        if (
            !$pesanan &&
            $noPesanan !== '' &&
            strlen($noPesanan) >= 10
        ) {
            $kandidatPesanan = Pesanan::where(
                    'id_toko',
                    $idToko
                )
                ->where(
                    'no_pesanan',
                    'like',
                    $noPesanan . '%'
                )
                ->limit(2)
                ->get();

            if ($kandidatPesanan->count() === 1) {
                $pesanan = $kandidatPesanan->first();
                $noPesanan = (string) $pesanan->no_pesanan;
            }
        }

        // 3. Jika No. Pesanan tidak cocok, cari dari No. Resi.
        if (
            !$pesanan &&
            $noResi !== ''
        ) {
            $pesanan = Pesanan::where(
                    'no_resi',
                    $noResi
                )
                ->where(
                    'id_toko',
                    $idToko
                )
                ->first();

            if ($pesanan) {
                $noPesanan = (string) $pesanan->no_pesanan;
            }
        }

        if (!$pesanan) {
            return [
                'no_pesanan' => $noPesanan,
                'no_resi' => $noResi,
                'status' => 'not_found',
            ];
        }

        // Gunakan data database sebagai fallback jika resi dari PDF kosong.
        if ($noResi === '') {
            $noResi = strtoupper(
                trim((string) $pesanan->no_resi)
            );
        }

        $sudahAda = ResiPage::where(
                'no_pesanan',
                $pesanan->no_pesanan
            )
            ->exists();

        return [
            'no_pesanan' => (string) $pesanan->no_pesanan,
            'no_resi' => $noResi,
            'status' => $sudahAda
                ? 'existing'
                : 'matched',
        ];
    }

    private function getShopeeDeadlineFromOrder(
        Pesanan $pesanan
    ): array {
        $raw = trim(
            (string) $pesanan->getAttribute(
                'estimated_ship_out_date'
            )
        );

        if ($raw === '') {
            return $this->emptyDeadlinePayload();
        }

        $parsed = $this->parseDeadlineValue($raw);

        if (!$parsed) {
            return [
                'batas_kirim_at' => null,
                'batas_kirim_source' =>
                    'shopee_estimated_ship_out_date',
                'batas_kirim_raw' => $raw,
            ];
        }

        return [
            'batas_kirim_at' =>
                $parsed->format('Y-m-d H:i:s'),
            'batas_kirim_source' =>
                'shopee_estimated_ship_out_date',
            'batas_kirim_raw' => $raw,
        ];
    }

    private function extractTikTokDeadline(
        string $text
    ): array {
        $normalized = str_replace(
            ["\r\n", "\r"],
            "\n",
            $text
        );

        $patterns = [
            '/In\s*transit\s*by\s*[:\-]?\s*([^\n]{3,80})/i',
            '/Ship\s*by\s*[:\-]?\s*([^\n]{3,80})/i',
        ];

        $raw = '';

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $normalized, $match)) {
                $raw = trim(
                    preg_replace(
                        '/\s+/',
                        ' ',
                        $match[1]
                    )
                );

                break;
            }
        }

        if ($raw === '') {
            return $this->emptyDeadlinePayload();
        }

        $raw = preg_split(
            '/\s{2,}|\s+Order\s*Id\b|\s+Tracking\b|\s+Seller\b/i',
            $raw,
            2
        )[0] ?? $raw;

        $raw = trim($raw, " \t\n\r\0\x0B|,;");
        $parsed = $this->parseDeadlineValue($raw);

        return [
            'batas_kirim_at' => $parsed
                ? $parsed->format('Y-m-d H:i:s')
                : null,
            'batas_kirim_source' =>
                'tiktok_in_transit_by',
            'batas_kirim_raw' => $raw,
        ];
    }

    private function normalizeTikTokPdf(
        string $sourcePath,
        string $directory
    ): string {
        $normalizedPath =
            $directory .
            DIRECTORY_SEPARATOR .
            'normalized_' .
            Str::uuid() .
            '.pdf';

        $ghostscript = env(
            'GHOSTSCRIPT_BIN',
            '/bin/gs'
        );

        if (
            !is_file($ghostscript) ||
            !is_executable($ghostscript)
        ) {
            throw new \Exception(
                'Ghostscript tidak tersedia di server.'
            );
        }

        $command =
            escapeshellarg($ghostscript) .
            ' -q' .
            ' -dNOPAUSE' .
            ' -dBATCH' .
            ' -sDEVICE=pdfwrite' .
            ' -dCompatibilityLevel=1.4' .
            ' -dAutoRotatePages=/None' .
            ' -sOutputFile=' .
            escapeshellarg($normalizedPath) .
            ' ' .
            escapeshellarg($sourcePath) .
            ' 2>&1';

        $output = [];
        $exitCode = 0;

        exec(
            $command,
            $output,
            $exitCode
        );

        if (
            $exitCode !== 0 ||
            !File::exists($normalizedPath) ||
            File::size($normalizedPath) <= 0
        ) {
            File::delete($normalizedPath);

            throw new \Exception(
                'Gagal memproses PDF TikTok dengan Ghostscript. ' .
                implode(' ', $output)
            );
        }

        return $normalizedPath;
    }

    private function parseDeadlineValue(
        ?string $value
    ): ?Carbon {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        $timezone = 'Asia/Jakarta';
        $value = preg_replace('/\s+/u', ' ', $value);

        $formats = [
            'd/m/Y H:i:s',
            'd/m/Y H:i',
            'd/m/Y',
            'd-m-Y H:i:s',
            'd-m-Y H:i',
            'd-m-Y',
        ];

        foreach ($formats as $format) {
            try {
                $date = Carbon::createFromFormat(
                    '!' . $format,
                    $value,
                    $timezone
                );

                if ($date === false) {
                    continue;
                }

                $errors = Carbon::getLastErrors();

                if (
                    is_array($errors) &&
                    (
                        ($errors['warning_count'] ?? 0) > 0 ||
                        ($errors['error_count'] ?? 0) > 0
                    )
                ) {
                    continue;
                }

                if (!preg_match('/\d{1,2}:\d{2}/', $value)) {
                    $date->endOfDay();
                }

                return $date;
            } catch (\Throwable $e) {
            }
        }

        try {
            $date = Carbon::parse(
                $value,
                $timezone
            );

            if (!preg_match('/\d{1,2}:\d{2}/', $value)) {
                $date->endOfDay();
            }

            return $date;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function normalizeDeadlinePayload(
        array $data
    ): array {
        return [
            'batas_kirim_at' =>
                $data['batas_kirim_at'] ?? null,
            'batas_kirim_source' =>
                $data['batas_kirim_source'] ?? null,
            'batas_kirim_raw' =>
                $data['batas_kirim_raw'] ?? null,
        ];
    }

    private function emptyDeadlinePayload(): array
    {
        return [
            'batas_kirim_at' => null,
            'batas_kirim_source' => null,
            'batas_kirim_raw' => null,
        ];
    }

    private function extractShopeeOrderNumber(
        string $text
    ): string {
        $patterns = [
            '/No\.?\s*Pesanan\s*[:#]?\s*([A-Z0-9\-]{8,50})/i',
            '/Nomor\s*Pesanan\s*[:#]?\s*([A-Z0-9\-]{8,50})/i',
            '/Order\s*ID\s*[:#]?\s*([A-Z0-9\-]{8,50})/i',
            '/Order\s*(?:No|Number)\.?\s*[:#]?\s*([A-Z0-9\-]{8,50})/i',
        ];

        foreach ($patterns as $pattern) {
            if (
                preg_match(
                    $pattern,
                    $text,
                    $match
                )
            ) {
                return trim(
                    $match[1]
                );
            }
        }

        return '';
    }

    private function extractShopeeTrackingNumber(
        string $text
    ): string {
        $patterns = [
            '/No\.?\s*Resi\s*[:#]?\s*([A-Z0-9\-]{8,100})/i',
            '/Nomor\s*Resi\s*[:#]?\s*([A-Z0-9\-]{8,100})/i',

            // Beberapa label Shopee hanya menulis: Resi: SPXID...
            '/\bResi\s*[:#]?\s*([A-Z0-9\-]{8,100})/i',

            '/Tracking\s*ID\s*[:#]?\s*([A-Z0-9\-]{8,100})/i',
            '/Tracking\s*(?:No|Number)\.?\s*[:#]?\s*([A-Z0-9\-]{8,100})/i',

            // Fallback khusus format SPX yang muncul tanpa label Resi.
            '/\b(SPXID[A-Z0-9\-]{8,100})\b/i',
        ];

        foreach ($patterns as $pattern) {
            if (
                preg_match(
                    $pattern,
                    $text,
                    $match
                )
            ) {
                return strtoupper(
                    trim($match[1])
                );
            }
        }

        return '';
    }

    private function extractTikTokOrderCandidates(
        string $text
    ): array {
        $candidates = [];

        $patterns = [
            '/Order\s*Id\s*[:#]?\s*(\d{15,25})/i',

            '/(\d{15,25})[\s\S]{0,100}?Order\s*Id\s*[:#]?/i',
        ];

        foreach ($patterns as $pattern) {
            if (
                preg_match(
                    $pattern,
                    $text,
                    $match
                )
            ) {
                $candidates[] =
                    trim($match[1]);
            }
        }

        if (
            preg_match_all(
                '/(?<!\d)(\d{15,25})(?!\d)/',
                $text,
                $matches
            )
        ) {
            foreach (
                $matches[1]
                as $value
            ) {
                $candidates[] =
                    trim($value);
            }
        }

        return collect($candidates)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function extractTikTokTrackingCandidates(
        string $text
    ): array {
        $candidates = [];

        $patterns = [
            '/No\.?\s*Resi\s*[:#]?\s*([A-Z0-9\-]{8,100})/i',

            '/Nomor\s*Resi\s*[:#]?\s*([A-Z0-9\-]{8,100})/i',

            '/Tracking\s*ID\s*[:#]?\s*([A-Z0-9\-]{8,100})/i',

            '/\b(JY[A-Z0-9\-]{6,40})\b/i',
        ];

        foreach ($patterns as $pattern) {
            if (
                preg_match_all(
                    $pattern,
                    $text,
                    $matches
                )
            ) {
                foreach (
                    $matches[1] ?? []
                    as $value
                ) {
                    $candidates[] =
                        strtoupper(
                            trim($value)
                        );
                }
            }
        }

        if (
            preg_match_all(
                '/\b[A-Z0-9][A-Z0-9\-]{9,49}\b/i',
                strtoupper($text),
                $matches
            )
        ) {
            foreach (
                $matches[0]
                as $value
            ) {
                $value =
                    strtoupper(
                        trim($value)
                    );

                if (
                    !preg_match(
                        '/[A-Z]/',
                        $value
                    ) ||
                    !preg_match(
                        '/\d/',
                        $value
                    )
                ) {
                    continue;
                }

                $candidates[] =
                    $value;
            }
        }

        return collect($candidates)
            ->filter()
            ->unique()
            ->values()
            ->take(100)
            ->all();
    }

    private function findMatchingTracking(
        array $candidates,
        string $databaseTracking
    ): string {
        $databaseTracking =
            strtoupper(
                trim($databaseTracking)
            );

        if ($databaseTracking === '') {
            return '';
        }

        foreach (
            $candidates
            as $candidate
        ) {
            if (
                strtoupper(
                    trim($candidate)
                ) ===
                $databaseTracking
            ) {
                return $candidate;
            }
        }

        return '';
    }

    private function preferredTikTokTracking(
        array $candidates
    ): string {
        foreach (
            $candidates
            as $candidate
        ) {
            if (
                preg_match(
                    '/^JY/i',
                    $candidate
                )
            ) {
                return $candidate;
            }
        }

        return $candidates[0] ?? '';
    }
}
