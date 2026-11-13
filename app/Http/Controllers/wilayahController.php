<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Dedoc\Scramble\Attributes\Group;

#[Group('Wilayah')]
class wilayahController extends Controller
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
        return response()->json([
            'success' => true,
            'data' => $this->wilayah('provinces'),
        ]);
    }

    public function kabupatenKota(string $provinsiId)
    {
        return response()->json([
            'success' => true,
            'data' => $this->wilayah("regencies/{$provinsiId}"),
        ]);
    }

    public function kecamatan(string $kabupatenId)
    {
        return response()->json([
            'success' => true,
            'data' => $this->wilayah("districts/{$kabupatenId}"),
        ]);
    }

    public function desa(string $kecamatanId)
    {
        return response()->json([
            'success' => true,
            'data' => $this->wilayah("villages/{$kecamatanId}"),
        ]);
    }

    public function alamatLengkap(string $desaId)
    {
        if (!preg_match('/^\d{2}\.\d{2}\.\d{2}\.\d{4}$/', $desaId)) {
            return response()->json([
                'success' => false,
                'message' => 'Format kode desa tidak valid.',
            ], 422);
        }

        try {
            $data = $this->wilayah("villages/{$desaId}");

            if (empty($data) || !isset($data['province'], $data['regency'], $data['district'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Alamat tidak ditemukan.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'provinsi' => $data['province'],
                    'kabupaten_kota' => $data['regency'],
                    'kecamatan' => $data['district'],
                    'desa' => [
                        'id' => $data['id'],
                        'name' => $data['name'],
                    ],
                    'kode_pos' => $data['postal_code'] ?? null,
                ],
            ]);
        } catch (\Illuminate\Http\Client\RequestException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->response->status() === 404
                    ? 'Alamat tidak ditemukan.'
                    : 'Gagal mengambil data wilayah.',
            ], $e->response->status() === 404 ? 404 : 502);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Server wilayah tidak dapat dihubungi.',
            ], 503);
        }
    }
}
