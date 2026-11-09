<?php

namespace App\Services\Pesanan;

use App\Models\Pesanan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class PesananService
{
    private function generateNoPesanan(): string
    {
        $tanggal = now()->format('ymd');
        $nomorTerakhir = Pesanan::where('no_pesanan', 'like', $tanggal . '%')
            ->whereRaw(
                'no_pesanan REGEXP ?',
                ['^' . $tanggal . '[0-9]{3,}[A-Za-z]$']
            )
            ->selectRaw(
                'MAX(CAST(SUBSTRING(no_pesanan, 7, CHAR_LENGTH(no_pesanan) - 7) AS UNSIGNED)) as nomor'
            )
            ->value('nomor');

        $nomorBerikutnya = ($nomorTerakhir ?? 0) + 1;
        $nomorUrut = str_pad($nomorBerikutnya, 3, '0', STR_PAD_LEFT);
        $random = Str::upper(Str::random(1));

        return $tanggal . $nomorUrut . $random;
    }

    private function provinsi(): array
    {
        return Cache::remember('wilayah_provinsi', now()->addDay(), function () {
            return Http::timeout(10)
                ->get('https://map.dahakreasidigital.my.id/v2/provinces.json')
                ->throw()
                ->json('data') ?? [];
        });
    }

    private function kabupenkota(): array
    {
        return Cache::remember('wilayah_provinsi', now()->addDay(), function () {
            return Http::timeout(10)
                ->get('https://map.dahakreasidigital.my.id/v2/provinces.json')
                ->throw()
                ->json('data') ?? [];
        });
    }

    public function index()
    {
        $noPesananBaru = $this->generateNoPesanan();
        $user = Auth::user();

        $wilayah = (object) [
            'provinsi' => $this->provinsi(),
        ];

        return (object) [
            'no_pesanan' => $noPesananBaru,
            'user' => $user,
            'wilayah' => $wilayah,
        ];
    }
}
