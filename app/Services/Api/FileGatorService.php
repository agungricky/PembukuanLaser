<?php

namespace App\Services\Api;

use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Illuminate\Http\UploadedFile;

class FileGatorService
{
    private CookieJar $cookies;
    private string $csrf;
    private string $baseUrl;

    public function __construct()
    {
        $this->cookies = new CookieJar();

        $this->baseUrl = rtrim(
            (string) config('services.filegator.url'),
            '/'
        );
    }

    private function url(string $route): string
    {
        return $this->baseUrl . '/?r=/' . ltrim($route, '/');
    }

    private function client(): PendingRequest
    {
        return Http::withOptions([
            'cookies' => $this->cookies,
            'allow_redirects' => false,
        ])->timeout(60)->acceptJson();
    }

    private function post(string $route, array $data = []): mixed
    {
        $response = $this->client()
            ->withHeaders([
                'X-CSRF-Token' => $this->csrf,
            ])
            ->post($this->url($route), $data);

        if (!$response->successful()) {
            throw new RuntimeException(
                "FileGator {$route}: HTTP " . $response->status()
            );
        }

        return $response->json('data');
    }

    public function login(): bool
    {
        $response = $this->client()->get(
            $this->url('getuser')
        );

        if (!$response->successful()) {
            throw new RuntimeException(
                'Gagal mengambil sesi FileGator'
            );
        }

        $this->csrf = (string) $response->header(
            'X-CSRF-Token'
        );

        if ($this->csrf === '') {
            throw new RuntimeException(
                'Token CSRF FileGator tidak ditemukan'
            );
        }

        $result = $this->post('login', [
            'username' => config('services.filegator.username'),
            'password' => config('services.filegator.password'),
        ]);

        if (
            !is_array($result) ||
            ($result['username'] ?? null) !==
            config('services.filegator.username')
        ) {
            throw new RuntimeException(
                'Login akun sistem ke FileGator gagal'
            );
        }

        return true;
    }

    public function createFolder(
        string $name,
        string $parent = '/'
    ): void {
        $this->post('changedir', [
            'to' => $parent,
        ]);

        $result = $this->post('createnew', [
            'type' => 'dir',
            'name' => $name,
        ]);

        if ($result !== 'Done') {
            throw new RuntimeException(
                'FileGator gagal membuat folder'
            );
        }
    }


    public function upload(
        UploadedFile $file,
        string $filename,
        string $folder
    ): string {
        if (!$file->isValid()) {
            throw new RuntimeException('File upload Laravel tidak valid');
        }

        $path = $file->getPathname();

        if (!is_file($path) || !is_readable($path)) {
            throw new RuntimeException(
                'File sementara tidak ditemukan atau tidak bisa dibaca'
            );
        }

        $stream = fopen($path, 'rb');

        if ($stream === false) {
            throw new RuntimeException('Gagal membuka file sementara');
        }

        try {
            $response = $this->client()
                ->withHeaders([
                    'X-CSRF-Token' => $this->csrf,
                ])
                ->attach('file', $stream, $filename)
                ->post($this->url('upload'), [
                    'resumableFilename' => $filename,
                    'resumableRelativePath' => $folder,
                    'resumableChunkNumber' => 1,
                    'resumableTotalChunks' => 1,
                    'resumableTotalSize' => $file->getSize(),
                    'resumableIdentifier' => bin2hex(random_bytes(16)),
                ]);

            if (!$response->successful()) {
                throw new RuntimeException(
                    'Upload FileGator gagal: HTTP ' .
                        $response->status()
                );
            }

            $result = $response->json('data');

            if ($result !== 'Stored') {
                throw new RuntimeException(
                    'FileGator belum mengonfirmasi penyimpanan: ' .
                        json_encode($response->json())
                );
            }

            return rtrim($folder, '/') . '/' . ltrim($filename, '/');
        } finally {
            fclose($stream);
        }
    }
}
