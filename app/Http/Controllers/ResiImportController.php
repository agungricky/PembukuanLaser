<?php

namespace App\Http\Controllers;

use App\Models\Pesanan;
use App\Models\ResiImport;
use App\Models\ResiPage;
use App\Models\Toko;
use Illuminate\Http\Request;
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

        $toko = Toko::where('id_toko', $request->id_toko)
            ->where('marketplace', $request->marketplace)
            ->first();

        if (!$toko) {
            return back()
                ->withInput()
                ->with('error', 'Toko tidak sesuai dengan marketplace.');
        }

        $tempDirectory = storage_path('app/private/resi_temp');

        File::ensureDirectoryExists($tempDirectory);

        $tempName = Str::uuid() . '.pdf';

        $request->file('file_resi')->move(
            $tempDirectory,
            $tempName
        );

        $tempPath = $tempDirectory . DIRECTORY_SEPARATOR . $tempName;

        try {
            $parser = new Parser();
            $pdf = $parser->parseFile($tempPath);
            $pages = $pdf->getPages();
        } catch (\Throwable $e) {
            File::delete($tempPath);

            return back()
                ->withInput()
                ->with('error', 'PDF gagal dibaca: ' . $e->getMessage());
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
                (int) $request->id_toko
            );

            $preview[] = [
                'halaman' => $halaman,
                'no_pesanan' => $hasil['no_pesanan'],
                'no_resi' => $hasil['no_resi'],
                'status' => $hasil['status'],
            ];
        }

        session([
            'resi_preview' => [
                'temp_name' => $tempName,
                'temp_path' => $tempPath,
                'original_name' => $request->file('file_resi')->getClientOriginalName(),
                'marketplace' => $request->marketplace,
                'id_toko' => (int) $request->id_toko,
                'jumlah_halaman' => count($pages),
            ]
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
        $dataPreview = session('resi_preview');

        if (!$dataPreview) {
            return redirect()
                ->route('resi.import')
                ->with('error', 'Preview sudah tidak tersedia. Upload PDF kembali.');
        }

        $request->validate([
            'pages' => 'required|array',
            'pages.*.halaman' => 'required|integer|min:1',
            'pages.*.no_pesanan' => 'nullable|string|max:50',
            'pages.*.no_resi' => 'nullable|string|max:100',
        ]);

        if (!File::exists($dataPreview['temp_path'])) {
            session()->forget('resi_preview');

            return redirect()
                ->route('resi.import')
                ->with('error', 'File PDF sementara tidak ditemukan. Upload kembali.');
        }

        $mappings = collect($request->pages)
            ->map(function ($page) {
                return [
                    'halaman' => (int) $page['halaman'],
                    'no_pesanan' => trim((string) ($page['no_pesanan'] ?? '')),
                    'no_resi' => trim((string) ($page['no_resi'] ?? '')),
                ];
            })
            ->filter(fn ($page) => $page['no_pesanan'] !== '')
            ->values();

        if ($mappings->isEmpty()) {
            return back()->with(
                'error',
                'Tidak ada halaman yang memiliki No Pesanan.'
            );
        }

        $orderNumbers = $mappings
            ->pluck('no_pesanan')
            ->unique()
            ->values();

        $pesanan = Pesanan::whereIn('no_pesanan', $orderNumbers)
            ->where('id_toko', $dataPreview['id_toko'])
            ->get()
            ->keyBy('no_pesanan');

        $existingMappedOrders = ResiPage::whereIn(
            'no_pesanan',
            $orderNumbers
        )
            ->pluck('no_pesanan')
            ->unique()
            ->flip();

        $validPages = [];
        $errors = [];
        $urutan = [];

        foreach ($mappings as $page) {
            $noPesanan = $page['no_pesanan'];

            if (!$pesanan->has($noPesanan)) {
                $errors[] =
                    "Halaman {$page['halaman']}: Pesanan {$noPesanan} tidak ditemukan pada toko ini.";

                continue;
            }

            if ($existingMappedOrders->has($noPesanan)) {
                $errors[] =
                    "Halaman {$page['halaman']}: Pesanan {$noPesanan} sudah memiliki PDF resi.";

                continue;
            }

            $order = $pesanan->get($noPesanan);

            $urutan[$noPesanan] =
                ($urutan[$noPesanan] ?? 0) + 1;

            $validPages[] = [
                'halaman' => $page['halaman'],
                'no_pesanan' => $noPesanan,
                'no_resi' => $page['no_resi'] !== ''
                    ? $page['no_resi']
                    : $order->no_resi,
                'urutan' => $urutan[$noPesanan],
            ];
        }

        if (empty($validPages)) {
            return back()
                ->with('error', 'Tidak ada halaman yang dapat disimpan.')
                ->with('import_errors', $errors);
        }

        $directory =
            'resi/' .
            now()->format('Y') .
            '/' .
            now()->format('m');

        $fullDirectory =
            storage_path('app/private/' . $directory);

        File::ensureDirectoryExists($fullDirectory);

        $newName = Str::uuid() . '.pdf';

        $relativePath =
            $directory . '/' . $newName;

        $fullPath =
            $fullDirectory . DIRECTORY_SEPARATOR . $newName;

        try {
            File::move(
                $dataPreview['temp_path'],
                $fullPath
            );

            DB::beginTransaction();

            $import = ResiImport::create([
                'nama_file' => $dataPreview['original_name'],
                'path_file' => $relativePath,
                'jumlah_halaman' => $dataPreview['jumlah_halaman'],
                'marketplace' => $dataPreview['marketplace'],
                'id_toko' => $dataPreview['id_toko'],
                'user_id' => Auth::id(),
            ]);

            foreach ($validPages as $page) {
                ResiPage::create([
                    'resi_import_id' => $import->id,
                    'no_pesanan' => $page['no_pesanan'],
                    'no_resi' => $page['no_resi'],
                    'halaman' => $page['halaman'],
                    'urutan' => $page['urutan'],
                ]);
            }

            DB::commit();

            session()->forget('resi_preview');

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

            File::delete($fullPath);

            report($e);

            return back()->with(
                'error',
                'Gagal menyimpan PDF resi: ' . $e->getMessage()
            );
        }
    }

    private function detectPage(string $text, int $idToko): array
    {
        $text = trim($text);

        if ($text === '') {
            return [
                'no_pesanan' => '',
                'no_resi' => '',
                'status' => 'unreadable',
            ];
        }

        $noPesanan = $this->extractOrderNumber($text);
        $noResi = $this->extractTrackingNumber($text);

        $pesanan = null;

        if ($noPesanan !== '') {
            $pesanan = Pesanan::where(
                'no_pesanan',
                $noPesanan
            )
                ->where('id_toko', $idToko)
                ->first();
        }

        if (!$pesanan && $noResi !== '') {
            $pesanan = Pesanan::where(
                'no_resi',
                $noResi
            )
                ->where('id_toko', $idToko)
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

        if ($noResi === '') {
            $noResi = (string) $pesanan->no_resi;
        }

        $sudahAda = ResiPage::where(
            'no_pesanan',
            $pesanan->no_pesanan
        )->exists();

        return [
            'no_pesanan' => (string) $pesanan->no_pesanan,
            'no_resi' => $noResi,
            'status' => $sudahAda
                ? 'existing'
                : 'matched',
        ];
    }

    private function extractOrderNumber(string $text): string
    {
        $patterns = [
            '/No\.?\s*Pesanan\s*[:#]?\s*([A-Z0-9\-]{8,50})/i',
            '/Nomor\s*Pesanan\s*[:#]?\s*([A-Z0-9\-]{8,50})/i',
            '/Order\s*ID\s*[:#]?\s*([A-Z0-9\-]{8,50})/i',
            '/Order\s*(?:No|Number)\.?\s*[:#]?\s*([A-Z0-9\-]{8,50})/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $match)) {
                return trim($match[1]);
            }
        }

        return '';
    }

    private function extractTrackingNumber(string $text): string
    {
        $patterns = [
            '/No\.?\s*Resi\s*[:#]?\s*([A-Z0-9\-]{8,100})/i',
            '/Nomor\s*Resi\s*[:#]?\s*([A-Z0-9\-]{8,100})/i',
            '/Tracking\s*ID\s*[:#]?\s*([A-Z0-9\-]{8,100})/i',
            '/Tracking\s*(?:No|Number)\.?\s*[:#]?\s*([A-Z0-9\-]{8,100})/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $match)) {
                return trim($match[1]);
            }
        }

        return '';
    }
}
