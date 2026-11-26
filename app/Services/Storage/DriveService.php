<?php

namespace App\Services\Storage;

use App\Services\Api\FileGatorService as ApiFileGatorService;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use RuntimeException;
use OutOfBoundsException;


class DriveService
{
    public function createFolder($filegator, $data): array
    {
        $name = $data['name'];
        $parent = $data['parent'] ?? '/';

        $filegator->login();
        $filegator->createFolder($name, $parent);

        return [
            'name' => $name,
            'path' => trim($parent, '/') === ''
                ? $name
                : trim($parent, '/') . '/' . $name,
        ];
    }

    public function uploadFiles($filegator, array $data): array
    {
        $file = $data['file'];
        $folder = $data['folder'];
        $filename = $file->getClientOriginalName();

        if (
            in_array($filename, ['.', '..'], true) ||
            str_contains($filename, '/') ||
            str_contains($filename, '\\')
        ) {
            return [
                'success' => false,
                'message' => 'Nama file tidak valid',
            ];
        }

        $filegator->login();
        $filegator->upload($file, $filename, $folder);
        return [
            'success' => true,
            'message' => 'File berhasil diupload',
            'data' => [
                'filename' => $filename,
                'folder' => trim($folder, '/'),
                'path' => trim($folder, '/') === ''
                    ? $filename
                    : trim($folder, '/') . '/' . $filename,
            ],
        ];
    }

    public function delete(array $paths): array
    {
        $items = [];
        $directories = [];

        // 01. PEMERIKSAAN PATH FILE
        foreach ($paths as $path) {
            $path = '/' . ltrim($path, '/');

            if (
                $path === '/' ||
                str_ends_with($path, '/') ||
                str_contains($path, '\\') ||
                str_contains($path, '%') ||
                str_contains($path, '//') ||
                preg_match('#(^|/)\.{1,2}(/|$)#', $path) ||
                preg_match('/[\x00-\x1F\x7F]/', $path)
            ) {
                throw new InvalidArgumentException(
                    'Path file tidak valid: ' . $path
                );
            }

            $items[$path] = [
                'type' => 'file',
                'path' => $path,
            ];
        }

        $items = array_values($items);
        if (empty($items) || count($items) > 50) {
            throw new InvalidArgumentException(
                'Jumlah file harus antara 1 sampai 50.'
            );
        }

        // 02. KONFIGURASI FILEGATOR LOGIN
        $url = rtrim(config('services.filegator.url'), '/');
        $cookies = new CookieJar();
        $client = Http::acceptJson()
            ->asJson()
            ->timeout(30)
            ->connectTimeout(10)
            ->withOptions([
                'cookies' => $cookies,
                'allow_redirects' => false,
                'force_ip_resolve' => 'v4',
            ]);

        // 03. AMBIL CSRF TOKEN
        $init = $client->get(
            $url . '/?r=/getconfig'
        );

        $init->throw();
        $csrfToken = $init->header('X-CSRF-Token');
        if (!$init->successful() || !$csrfToken) {
            throw new RuntimeException(
                'CSRF Token tidak ditemukan'
            );
        }

        // 04. LOGIN FILEGATOR
        $login = $client
            ->replaceHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'X-CSRF-Token' => $csrfToken,
            ])
            ->post($url . '/?r=/login', [
                'username' => config('services.filegator.username'),
                'password' => config('services.filegator.password'),
            ]);

        $login->throw();
        if (!$login->successful()) {
            throw new RuntimeException(
                'Login FileGator gagal'
            );
        }

        // 05. AMBIL SESSION
        $session = $client->get(
            $url . '/?r=/getuser'
        );

        $session->throw();
        $csrfToken = $session->header('X-CSRF-Token');
        if (!$session->successful() || !$csrfToken) {
            throw new RuntimeException(
                'Session CSRF tidak ditemukan'
            );
        }

        $client->replaceHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'X-CSRF-Token' => $csrfToken,
        ]);

        // 06. MEMASTIKAN TARGET ADALAH FILE
        foreach ($items as $item) {
            $path = $item['path'];
            $folder = dirname($path);
            if (!array_key_exists($folder, $directories)) {
                $listing = $client->post(
                    $url . '/?r=/changedir',
                    [
                        'to' => $folder,
                    ]
                );

                $listing->throw();
                $files = $listing->json('data.files');
                $location = $listing->json('data.location');
                if (
                    !$listing->successful() ||
                    !is_array($files) ||
                    $location !== $folder
                ) {
                    throw new RuntimeException(
                        'Gagal membaca direktori FileGator'
                    );
                }

                $directories[$folder] = $files;
            }

            // CARI TARGET BERDASARKAN PATH
            $target = collect($directories[$folder])
                ->first(function ($file) use ($path) {
                    return ($file['path'] ?? null) === $path;
                });

            // PASTIKAN FILE DITEMUKAN
            if (!$target) {
                throw new OutOfBoundsException(
                    'File tidak ditemukan: ' . $path
                );
            }

            // PASTIKAN TARGET BUKAN FOLDER
            if (($target['type'] ?? null) !== 'file') {
                throw new InvalidArgumentException(
                    'Penghapusan folder tidak diizinkan: ' . $path
                );
            }
        }

        // 07. HAPUS FILE
        $response = $client->post(
            $url . '/?r=/deleteitems',
            [
                'items' => $items,
            ]
        );

        $response->throw();
        $result = $response->json('data') ?? $response->json();
        if (!$response->successful() || $result !== 'Done') {
            throw new RuntimeException(
                'FileGator gagal mengonfirmasi penghapusan file'
            );
        }

        // 08. KEMBALIKAN HASIL
        return [
            'total' => count($items),
            'paths' => array_column($items, 'path'),
        ];
    }
}
