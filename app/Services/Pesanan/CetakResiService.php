<?php

namespace App\Services\Pesanan;

use App\Models\Exporter;
use App\Models\PesananPerProduk;
use App\Models\ResiPage;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use setasign\Fpdi\Fpdi;
use Smalot\PdfParser\Parser;

class CetakResiService
{
    private $resi;
    private $pdf;

    public function previewResi($token)
    {
        $folder = 'temp/resi/'.$token;
        if (! Storage::disk('local')->exists($folder)) {
            abort(404, 'Folder resi tidak ditemukan.');
        }

        $files = Storage::disk('local')->files($folder);
        if (empty($files)) {
            abort(404, 'PDF resi tidak ditemukan.');
        }

        $path = Storage::disk('local')->path(
            $files[0]
        );

        return response()->file($path, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="preview-resi.pdf"',
        ]);
    }

    private function viewResi($request)
    {
        $daftarPesanan = collect($request->pesanan);
        $pesananSudahImport = ResiPage::with('resi_imports')
            ->whereIn('no_pesanan', $daftarPesanan)
            ->get();

        $noPesananSudahImport = $pesananSudahImport
            ->pluck('no_pesanan');

        $noPesananBelumImport = $daftarPesanan
            ->diff($noPesananSudahImport)
            ->values();

        $cekImport = [
            'sudah_import' => $noPesananSudahImport->values(),
            'belum_import' => $noPesananBelumImport,
        ];

        if ($cekImport['belum_import']->isNotEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Terdapat Pesanan Belum di Import, Mohon Import Resi Terlebih Dahulu',
                'data' => $cekImport['belum_import'],
            ], 422);
        }

        $resi = ResiPage::with('resi_imports')
            ->whereIn(
                'no_pesanan',
                $cekImport['sudah_import']
            )
            ->get()
            ->map(function ($item) {

                return [
                    'id' => $item->id,
                    'no_pesanan' => $item->no_pesanan,
                    'resi_import_id' => $item->resi_import_id,
                    'halaman' => $item->halaman,
                    'urutan' => $item->urutan,
                    'path_file' => $item->resi_imports?->path_file,
                    'nama_file' => $item->resi_imports?->nama_file,
                ];
            });

        $token = (string) Str::uuid();
        $tempFolder = 'temp/resi/'.$token;
        Storage::disk('local')->makeDirectory(
            $tempFolder
        );

        $pdf = new Fpdi;
        $pdf->SetMargins(0, 2, 0);
        $pdf->SetAutoPageBreak(false, 0);

        // HALAMAN RESI
        $produkPerPesanan = PesananPerProduk::query()
            ->whereIn('no_pesanan', $cekImport['sudah_import'])
            ->get()
            ->groupBy('no_pesanan');

        // AMBIL SEMUA SKU
        $semuaSku = $produkPerPesanan
            ->flatten()
            ->pluck('sku')
            ->filter()
            ->unique()
            ->values();

        $this->resi = $resi;
        $this->pdf = $pdf;
        $this->produkPerPesanan = $produkPerPesanan;

        // Proses Pencarian Resi
        $this->prosesResi();

        // SIMPAN PDF
        $relativePath = $tempFolder.'/preview.pdf';
        $outputPath = Storage::disk('local')->path($relativePath);
        $pdf->Output('F', $outputPath);

        return response()->json([
            'success' => true,
            'message' => 'Resi berhasil diproses.',
            'preview_url' => route('transaksi.preview-resi', ['token' => $token]),
        ]);
    }

    private function prosesResi()
    {
        $resi = $this->resi;
        $pdf = $this->pdf;

        foreach ($resi as $item) {
            $sourcePath = Storage::disk('local')->path(
                $item['path_file']
            );

            $pageCount = $pdf->setSourceFile($sourcePath);

            if (
                $item['halaman'] < 1 ||
                $item['halaman'] > $pageCount
            ) {
                throw new Exception(
                    'Halaman '.$item['halaman'].
                    ' tidak ditemukan pada file '.
                    $item['nama_file']
                );
            }

            $templateId = $pdf->importPage(
                $item['halaman']
            );

            $size = $pdf->getTemplateSize(
                $templateId
            );

            $pdf->AddPage(
                $size['orientation'],
                [
                    $size['width'],
                    $size['height'],
                ]
            );

            $pdf->useTemplate($templateId);
        }
    }

    public function cetakResi(Request $request)
    {
        $request->validate([
            'pesanan' => 'required|array|min:1',
            'pesanan.*' => 'required|string',
            'alasan_export' => 'required|string',
        ]);

        DB::beginTransaction();
        try {
            $response = $this->viewResi($request);
            $exporter = Exporter::create([
                'user_id' => Auth::id(),
                'role' => 'gudang',
                'status' => 'proses',
                'keterangan' => $request->alasan_export,
            ]);

            PesananPerProduk::whereIn('no_pesanan', $request->pesanan)->update([
                'exporter_id' => $exporter->id,
            ]);

            DB::commit();

            return $response;
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
        }

    }
}
