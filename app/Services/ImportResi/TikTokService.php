<?php

namespace App\Services\ImportResi;

use App\Models\Pesanan;
use App\Models\ResiPage;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class TikTokService
{
    protected Collection $pesananByOrder;

    protected Collection $pesananByResi;

    protected Collection $existingResiPages;

    protected ?int $preparedIdToko = null;

    public function prepareContext(int $idToko): void
    {
        if ($this->preparedIdToko === $idToko) {
            return;
        }

        $pesananToko = Pesanan::where('id_toko', $idToko)
            ->select(['no_pesanan', 'no_resi'])
            ->get();

        $this->pesananByOrder =
            $pesananToko->keyBy(
                fn ($item) => (string) $item->no_pesanan
            );

        $this->pesananByResi =
            $pesananToko->filter(fn ($item) => trim((string) $item->no_resi) !== '')
                ->keyBy(fn ($item) => strtoupper(
                    trim((string) $item->no_resi)
                ));

        // Ambil pesanan yang sudah mempunyai PDF resi
        $orderNumbers = 
            $this->pesananByOrder
                ->keys()
                ->values()
                ->all();

        if (empty($orderNumbers)) {
            $this->existingResiPages = collect();
        } else {
            $this->existingResiPages =
                ResiPage::whereIn('no_pesanan', $orderNumbers)
                    ->pluck('no_pesanan')
                    ->map(fn ($value) => (string) $value)
                    ->unique()
                    ->flip();
        }

        $this->preparedIdToko = $idToko;
    }

    // Detect Page
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

        if ($this->preparedIdToko !== $idToko) {
            $this->prepareContext($idToko);
        }

        $hasil = $this->detectTikTokPage($text);

        return array_merge(
            $hasil,
            $this->extractTikTokDeadline($text)
        );
    }

    // Detect TikTok Page
    private function detectTikTokPage(string $text): array {
        $orderCandidates = $this->extractTikTokOrderCandidates($text);
        $trackingCandidates = $this->extractTikTokTrackingCandidates($text);
        $pesanan = null;
        $noPesanan = '';
        $noResi = '';

        foreach ($orderCandidates as $candidate) {
            $candidate = trim((string) $candidate);

            if ($this->pesananByOrder->has($candidate)) {
                $pesanan = $this->pesananByOrder->get($candidate);
                $noPesanan = (string) $pesanan->no_pesanan;

                break;
            }
        }

        if (! $pesanan) {
            foreach ($trackingCandidates as $candidate) {
                $key = strtoupper(trim((string) $candidate));

                if ($this->pesananByResi->has($key)) {
                    $pesanan = $this->pesananByResi->get($key);

                    $noPesanan = (string) $pesanan->no_pesanan;
                    $noResi = (string) $pesanan->no_resi;

                    break;
                }
            }
        }

        if (! $pesanan) {
            return [
                'no_pesanan' => $orderCandidates[0] ?? '',
                'no_resi' => $this->preferredTikTokTracking($trackingCandidates),
                'status' => 'not_found',
            ];
        }

        if ($noResi === '') {
            $noResi = $this->findMatchingTracking($trackingCandidates, (string) $pesanan->no_resi);

            if ($noResi === '') {
                $noResi = (string) $pesanan->no_resi;
            }
        }

        $sudahAda = $this->existingResiPages->has((string) $pesanan->no_pesanan);

        return [
            'no_pesanan' => (string) $pesanan->no_pesanan,
            'no_resi' => $noResi,
            'status' => $sudahAda ? 'existing' : 'matched',
        ];
    }

    private function extractTikTokOrderCandidates(string $text): array {
        $candidates = [];
        $patterns = [
            '/Order\s*ID\s*[:#]?\s*(\d{15,25})/i',
            '/Order\s*Id\s*[:#]?\s*(\d{15,25})/i',
            '/(\d{15,25})[\s\S]{0,100}?Order\s*ID\s*[:#]?/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $text, $matches)) {
                foreach ($matches[1] ?? [] as $value) {
                    $candidates[] = trim($value);
                }
            }
        }

        if (preg_match_all('/(?<!\d)(\d{15,25})(?!\d)/', $text, $matches)) {
            foreach ($matches[1] as $value) {
                $candidates[] = trim($value);
            }
        }

        return collect($candidates)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function extractTikTokTrackingCandidates(string $text): array {
        $candidates = [];

        $patterns = [
            '/No\.?\s*Resi\s*[:#]?\s*([A-Z0-9\-]{8,100})/i',
            '/Nomor\s*Resi\s*[:#]?\s*([A-Z0-9\-]{8,100})/i',
            '/Tracking\s*ID\s*[:#]?\s*([A-Z0-9\-]{8,100})/i',
            '/Tracking\s*(?:No|Number)\.?\s*[:#]?\s*([A-Z0-9\-]{8,100})/i',
            '/\b(JY[A-Z0-9\-]{6,40})\b/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $text, $matches)) {
                foreach ($matches[1] ?? [] as $value) {
                    $value = strtoupper(trim($value));

                    if ($value !== '') {
                        $candidates[] = $value;
                    }
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
            foreach ($matches[0] as $value) {
                $value = strtoupper(trim($value));

                if (! preg_match('/[A-Z]/', $value)) {
                    continue;
                }

                if (! preg_match('/\d/', $value)) {
                    continue;
                }

                $candidates[] = $value;
            }
        }

        return collect($candidates)
            ->filter()
            ->unique()
            ->values()
            ->take(100)
            ->all();
    }

    private function findMatchingTracking(array $candidates, string $databaseTracking): string {
        $databaseTracking = strtoupper(trim($databaseTracking));

        if ($databaseTracking === '') {
            return '';
        }

        foreach ($candidates as $candidate) {
            if (strtoupper(trim($candidate)) === $databaseTracking) {
                return $candidate;
            }
        }

        return '';
    }

    private function preferredTikTokTracking(array $candidates): string {
        foreach ($candidates as $candidate) {
            if (preg_match('/^JY/i', $candidate)) {
                return $candidate;
            }
        }

        return $candidates[0] ?? '';
    }

    private function extractTikTokDeadline(string $text): array {
        $normalized = str_replace(["\r\n", "\r",], "\n", $text);

        $patterns = [
            '/In\s*transit\s*by\s*[:\-]?\s*([^\n]{3,80})/i',
            '/Ship\s*by\s*[:\-]?\s*([^\n]{3,80})/i',
        ];

        $raw = '';

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $normalized, $match)) {
                $raw = trim(preg_replace('/\s+/', ' ', $match[1]));

                break;
            }
        }

        if ($raw === '') {
            return $this->emptyDeadlinePayload();
        }

        $raw = preg_split('/\s{2,}|\s+Order\s*ID\b|\s+Order\s*Id\b|\s+Tracking\b|\s+Seller\b/i', $raw, 2)[0] ?? $raw;
        $raw = trim($raw," \t\n\r\0\x0B|,;");

        $parsed = $this->parseDeadlineValue($raw);

        return [
            'batas_kirim_at' => $parsed ? $parsed->format('Y-m-d H:i:s') : null,
            'batas_kirim_source' => 'tiktok_in_transit_by',
            'batas_kirim_raw' => $raw,
        ];
    }

    private function parseDeadlineValue(?string $value): ?Carbon {
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

            'Y-m-d H:i:s',
            'Y-m-d H:i',
            'Y-m-d',
        ];

        foreach ($formats as $format) {
            try {$date = Carbon::createFromFormat(
                        '!'.$format,
                        $value,
                        $timezone
                    );

                if ($date === false) {
                    continue;
                }

                $errors = Carbon::getLastErrors();

                if (is_array($errors) && (($errors['warning_count'] ?? 0) > 0 || ($errors['error_count'] ?? 0) > 0)) {
                    continue;
                }

                if (! preg_match('/\d{1,2}:\d{2}/', $value)) {
                    $date->endOfDay();
                }

                return $date;
            } catch (\Throwable $e) {
                //
            }
        }

        try {
            $date = Carbon::parse($value, $timezone);

            if (! preg_match('/\d{1,2}:\d{2}/', $value)) {
                $date->endOfDay();
            }

            return $date;

        } catch (\Throwable $e) {
            return null;
        }
    }

    private function emptyDeadlinePayload(): array
    {
        return [
            'batas_kirim_at' => null,
            'batas_kirim_source' => null,
            'batas_kirim_raw' => null,
        ];
    }
}
