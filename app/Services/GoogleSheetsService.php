<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;

class GoogleSheetsService
{
    public function simpanPesanan(array $pesanan): array
    {
        $config = config('services.google_sheets');

        if (
            empty($config['webhook_url']) ||
            empty($config['secret']) ||
            empty($config['spreadsheet_url'])
        ) {
            throw new RuntimeException(
                'Konfigurasi Google Sheets belum lengkap.'
            );
        }

        $response = Http::timeout(60)
            ->withOptions([
                'allow_redirects' => true,
            ])
            ->post(
                $config['webhook_url'],
                array_merge($pesanan, [
                    'secret' => $config['secret'],
                    'spreadsheet_url' => $config['spreadsheet_url'],
                    'sheet_name' => $config['tab'] ?? 'Sheet1',
                ])
            );

        if (!$response->successful()) {
            throw new RuntimeException(
                'Google Sheets gagal. HTTP ' . $response->status()
            );
        }

        $result = $response->json();

        if (
            !is_array($result) ||
            ($result['success'] ?? false) !== true
        ) {
            throw new RuntimeException(
                is_array($result)
                    ? ($result['message'] ?? 'Gagal menyimpan pesanan.')
                    : 'Respons Google Apps Script tidak valid.'
            );
        }

        return $result;
    }
}
