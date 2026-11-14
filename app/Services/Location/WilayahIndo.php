<?php

namespace App\Services\Location;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class WilayahIndo
{
    private function wilayah(string $endpoint): array
    {
        return Cache::remember("wilayah_{$endpoint}", now()->addDay(), function () use ($endpoint) {
            return Http::timeout(10)
                ->get("https://map.dahakreasidigital.my.id/v2/{$endpoint}.json")
                ->throw()
                ->json('data') ?? [];
        });
    }

    public function provinsi()
    {
        return $this->wilayah('provinces');
    }

    public function kabupatenKota(string $provinsiId)
    {
        return $this->wilayah("regencies/{$provinsiId}");
    }

    public function kecamatan(string $kabupatenId)
    {
        return $this->wilayah("districts/{$kabupatenId}");
    }

    public function desa(string $kecamatanId)
    {
        return $this->wilayah("villages/{$kecamatanId}");
    }


    public function alamatLengkap(string $desaId): object
    {
        if (!preg_match('/^\d{2}\.\d{2}\.\d{2}\.\d{4}$/', $desaId)) {
            throw ValidationException::withMessages([
                'desaId' => 'Format kode desa tidak valid.',
            ]);
        }

        $data = $this->wilayah("villages/{$desaId}");

        if (
            empty($data) ||
            !isset(
                $data['province'],
                $data['regency'],
                $data['district'],
                $data['id'],
                $data['name']
            )
        ) {
            throw new NotFoundHttpException('Alamat tidak ditemukan.');
        }

        return (object) [
            'provinsi' => (object) $data['province'],
            'kabupaten_kota' => (object) $data['regency'],
            'kecamatan' => (object) $data['district'],
            'desa' => (object) [
                'id' => $data['id'],
                'name' => $data['name'],
            ],
            'kode_pos' => $data['postal_code'] ?? null,
        ];
    }
}
