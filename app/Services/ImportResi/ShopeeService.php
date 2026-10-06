<?php

namespace App\Services\ImportResi;

use App\Models\Pesanan;
use App\Models\ResiPage;
use Carbon\Carbon;

class ShopeeService
{
    public function detectPage(string $text, int $idToko): array {
        $text = trim($text);

        if ($text === '') {
            return [
                'no_pesanan' => '',
                'no_resi' => '',
                'status' => 'unreadable',
                ...$this->emptyDeadlinePayload(),
            ];
        }

        $hasil = $this->detectShopeePage($text, $idToko);

        return array_merge(
            $hasil,
            $this->extractShopeeDeadline($text)
        );
    }

    /**
     * Mengambil No Pesanan dan No Resi
     * kemudian mencocokkan ke database.
     */
    private function detectShopeePage(string $text, int $idToko): array {
        $noPesanan = $this->extractShopeeOrderNumber($text);
        $noResi = $this->extractShopeeTrackingNumber($text);
        
        return $this->resolvePage(
            $noPesanan,
            $noResi,
            $idToko
        );
    }

    /**
     * Mencocokkan hasil pembacaan PDF
     * dengan pesanan di database.
     */
    private function resolvePage(string $noPesanan, string $noResi, int $idToko): array {
        $noPesanan = strtoupper(trim($noPesanan));
        $noResi = strtoupper(trim($noResi));

        $pesanan = null;
        if ($noPesanan !== '') {
            $pesanan = Pesanan::where('no_pesanan', $noPesanan)
                    ->where('id_toko', $idToko)
                    ->first();
        }

        if (! $pesanan && $noPesanan !== '' && strlen($noPesanan) >= 10) {
            $kandidatPesanan = Pesanan::where('id_toko', $idToko)
                    ->where('no_pesanan', 'like', $noPesanan.'%')
                    ->limit(2)
                    ->get();

            if ($kandidatPesanan->count() === 1) {
                $pesanan = $kandidatPesanan->first();
                $noPesanan = (string) $pesanan->no_pesanan;
            }
        }

        // Jika nomor pesanan gagal, cari menggunakan resi
        if (! $pesanan && $noResi !== '') {
            $pesanan = Pesanan::where('no_resi', $noResi)
                    ->where('id_toko', $idToko)
                    ->first();

            if ($pesanan) {
                $noPesanan = (string) $pesanan->no_pesanan;
            }
        }

        if (! $pesanan) {
            return [
                'no_pesanan' => $noPesanan,
                'no_resi' => $noResi,
                'status' => 'not_found',
            ];
        }

        if ($noResi === '') {
            $noResi = strtoupper(
                    trim((string) $pesanan->no_resi)
                );
        }

        $sudahAda = ResiPage::where('no_pesanan', $pesanan->no_pesanan)->exists();
        return [
            'no_pesanan' => (string) $pesanan->no_pesanan,
            'no_resi' => $noResi,
            'status' => $sudahAda ? 'existing' : 'matched',
        ];
    }

    //  Mengambil batas kirim dari PDF Shopee.
    private function extractShopeeDeadline(string $text): array {
        $normalized = str_replace(["\r\n", "\r"], "\n", $text);
        $raw = '';

        if (
            preg_match(
                '/Batas\s*Kirim\s*:\s*(\d{1,2}-\d{1,2}-\d{4})/i',
                $normalized,
                $match
            )
        ) {
            $raw = trim($match[1]);
        }

        if ($raw === '') {
            return $this->emptyDeadlinePayload();
        }

        try {
            $parsed = Carbon::createFromFormat('d-m-Y', $raw)->endOfDay();
        } catch (\Throwable $e) {
            $parsed = null;
        }

        return [
            'batas_kirim_at' => $parsed ? $parsed->format('Y-m-d H:i:s') : null,
            'batas_kirim_source' => 'shopee_batas_kirim',
            'batas_kirim_raw' => $raw,
        ];
    }

    // Mengambil nomor pesanan Shopee.
    private function extractShopeeOrderNumber(string $text): string {
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

    // Mengambil nomor resi Shopee.
    private function extractShopeeTrackingNumber(string $text): string {
        $patterns = [
            '/No\.?\s*Resi\s*[:#]?\s*([A-Z0-9\-]{8,100})/i',
            '/Nomor\s*Resi\s*[:#]?\s*([A-Z0-9\-]{8,100})/i',
            '/\bResi\s*[:#]?\s*([A-Z0-9\-]{8,100})/i',
            '/Tracking\s*ID\s*[:#]?\s*([A-Z0-9\-]{8,100})/i',
            '/Tracking\s*(?:No|Number)\.?\s*[:#]?\s*([A-Z0-9\-]{8,100})/i',
            '/\b(SPXID[A-Z0-9\-]{8,100})\b/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $match)) {
                return strtoupper(trim($match[1]));
            }
        }

        return '';
    }

    // Payload batas kirim kosong.
    private function emptyDeadlinePayload(): array
    {
        return [
            'batas_kirim_at' => null,
            'batas_kirim_source' => null,
            'batas_kirim_raw' => null,
        ];
    }
}