<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Location\WilayahIndo;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\ConnectionException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class WilayahController extends Controller
{
    public function __construct(
        private WilayahIndo $wilayahIndo
    ) {}

    private function success(array $data): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function provinsi(): JsonResponse
    {
        return $this->success(
            $this->wilayahIndo->provinsi()
        );
    }

    public function kabupatenKota(string $provinsiId): JsonResponse
    {
        return $this->success(
            $this->wilayahIndo->kabupatenKota($provinsiId)
        );
    }

    public function kecamatan(string $kabupatenId): JsonResponse
    {
        return $this->success(
            $this->wilayahIndo->kecamatan($kabupatenId)
        );
    }

    public function desa(string $kecamatanId): JsonResponse
    {
        return $this->success(
            $this->wilayahIndo->desa($kecamatanId)
        );
    }


    public function alamatLengkap(string $desaId): JsonResponse
    {
        try {
            $alamat = $this->wilayahIndo->alamatLengkap($desaId);

            return response()->json([
                'success' => true,
                'data' => $alamat,
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Format kode desa tidak valid.',
            ], 422);
        } catch (NotFoundHttpException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Alamat tidak ditemukan.',
            ], 404);
        } catch (RequestException $e) {
            $status = $e->response->status();

            return response()->json([
                'success' => false,
                'message' => $status === 404
                    ? 'Alamat tidak ditemukan.'
                    : 'Gagal mengambil data wilayah.',
            ], $status === 404 ? 404 : 502);
        } catch (ConnectionException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Server wilayah tidak dapat dihubungi.',
            ], 503);
        }
    }
}
