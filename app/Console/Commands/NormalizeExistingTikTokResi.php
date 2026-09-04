<?php

namespace App\Console\Commands;

use App\Models\ResiImport;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class NormalizeExistingTikTokResi extends Command
{
    protected $signature = 'resi:normalize-tiktok {--force : Normalisasi ulang walaupun sudah memiliki marker}';

    protected $description = 'Normalisasi PDF resi TikTok lama ke PDF 1.4 agar cetak Packing cepat';

    public function handle(): int
    {
        $ghostscript = env('GHOSTSCRIPT_BIN', '/bin/gs');

        if (!is_file($ghostscript) || !is_executable($ghostscript)) {
            $this->error('Ghostscript tidak tersedia: ' . $ghostscript);
            return self::FAILURE;
        }

        $total = ResiImport::whereRaw('LOWER(marketplace) = ?', ['tiktok'])->count();

        if ($total === 0) {
            $this->info('Tidak ada PDF resi TikTok yang perlu diproses.');
            return self::SUCCESS;
        }

        $this->info("Ditemukan {$total} import resi TikTok.");

        $berhasil = 0;
        $dilewati = 0;
        $gagal = 0;

        ResiImport::whereRaw('LOWER(marketplace) = ?', ['tiktok'])
            ->orderBy('id')
            ->chunkById(20, function ($imports) use (
                $ghostscript,
                &$berhasil,
                &$dilewati,
                &$gagal
            ) {
                foreach ($imports as $import) {
                    $sourcePath = storage_path(
                        'app/private/' . ltrim((string) $import->path_file, '/')
                    );

                    $markerPath = $sourcePath . '.fpdi14';

                    if (!$this->option('force') && File::exists($markerPath)) {
                        $dilewati++;
                        $this->line("SKIP #{$import->id} sudah dinormalisasi");
                        continue;
                    }

                    if (!File::exists($sourcePath)) {
                        $gagal++;
                        $this->error("GAGAL #{$import->id} file tidak ditemukan: {$sourcePath}");
                        continue;
                    }

                    $normalizedPath =
                        dirname($sourcePath) .
                        DIRECTORY_SEPARATOR .
                        'normalize_' .
                        Str::uuid() .
                        '.pdf';

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

                    exec($command, $output, $exitCode);

                    if (
                        $exitCode !== 0 ||
                        !File::exists($normalizedPath) ||
                        File::size($normalizedPath) <= 0
                    ) {
                        File::delete($normalizedPath);
                        $gagal++;
                        $this->error(
                            "GAGAL #{$import->id}: " . implode(' ', $output)
                        );
                        continue;
                    }

                    $backupPath = $sourcePath . '.backup_' . Str::uuid();

                    try {
                        if (!rename($sourcePath, $backupPath)) {
                            throw new \RuntimeException('Gagal membuat backup file sumber.');
                        }

                        if (!rename($normalizedPath, $sourcePath)) {
                            @rename($backupPath, $sourcePath);
                            throw new \RuntimeException('Gagal mengganti file sumber dengan hasil normalisasi.');
                        }

                        File::delete($backupPath);
                        file_put_contents(
                            $markerPath,
                            now()->format('Y-m-d H:i:s')
                        );

                        $berhasil++;
                        $this->info("OK #{$import->id} {$import->nama_file}");
                    } catch (\Throwable $e) {
                        File::delete($normalizedPath);

                        if (
                            File::exists($backupPath) &&
                            !File::exists($sourcePath)
                        ) {
                            @rename($backupPath, $sourcePath);
                        }

                        $gagal++;
                        $this->error("GAGAL #{$import->id}: {$e->getMessage()}");
                    }
                }
            });

        $this->newLine();
        $this->info("Berhasil : {$berhasil}");
        $this->line("Dilewati : {$dilewati}");

        if ($gagal > 0) {
            $this->error("Gagal    : {$gagal}");
            return self::FAILURE;
        }

        $this->info('Normalisasi PDF TikTok selesai.');
        return self::SUCCESS;
    }
}
