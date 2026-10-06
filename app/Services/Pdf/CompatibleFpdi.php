<?php

namespace App\Services\Pdf;

use Illuminate\Support\Facades\File;
use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfParser\CrossReference\CrossReferenceException;
use Symfony\Component\Process\Process;

class CompatibleFpdi extends Fpdi
{
    private array $compatibleSources = [];

    public function setSourceFile($file)
    {
        if (is_string($file) && isset($this->compatibleSources[$file])) {
            return parent::setSourceFile($this->compatibleSources[$file]);
        }

        try {
            return parent::setSourceFile($file);
        } catch (CrossReferenceException $exception) {
            if ($exception->getCode() !== CrossReferenceException::COMPRESSED_XREF || !is_string($file)) {
                throw $exception;
            }
        }

        $directory = storage_path('app/private/resi_pdf_cache');
        File::ensureDirectoryExists($directory);
        $hash = hash_file('sha256', $file);
        if ($hash === false) {
            throw new \RuntimeException('File PDF sumber gagal dibaca.');
        }
        $cachedPath = $directory.DIRECTORY_SEPARATOR.$hash.'.pdf';
        $lock = fopen($cachedPath.'.lock', 'c');
        if ($lock === false) {
            throw new \RuntimeException('Cache PDF tidak dapat dikunci.');
        }

        $temporaryPath = null;
        try {
            if (!flock($lock, LOCK_EX)) {
                throw new \RuntimeException('Cache PDF tidak dapat dikunci.');
            }
            if (!is_file($cachedPath)) {
                $temporaryPath = $cachedPath.'.'.bin2hex(random_bytes(8)).'.tmp';
                $process = new Process([
                    config('pdf.node_binary', 'node'),
                    base_path('scripts/pdf-compatible.cjs'),
                    $file,
                    $temporaryPath,
                ], base_path());
                $process->setTimeout(120);
                try {
                    $process->mustRun();
                } catch (\Throwable $error) {
                    throw new \RuntimeException(
                        'PDF terkompresi gagal diproses. Pastikan Node.js dan dependency pdf-lib tersedia; atur PDF_NODE_BINARY jika diperlukan.',
                        0,
                        $error
                    );
                }

                // Validate before publishing the cached copy. Keep the original intact.
                $validator = new Fpdi;
                $validator->setSourceFile($temporaryPath);
                $validator->cleanUp();
                File::move($temporaryPath, $cachedPath);
            }
        } finally {
            if ($temporaryPath !== null && is_file($temporaryPath)) {
                File::delete($temporaryPath);
            }
            flock($lock, LOCK_UN);
            fclose($lock);
        }

        $this->compatibleSources[$file] = $cachedPath;

        return parent::setSourceFile($cachedPath);
    }
}
