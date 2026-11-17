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

    /**
     * Daftar Provinsi
     *
     * Menampilkan seluruh daftar provinsi di Indonesia
     * untuk kebutuhan pemilihan alamat pelanggan.
     *
     * ### Header Request
     *
     * ```http
     * Accept: application/json
     * Authorization: Bearer {token}
     * ```
     *
     * ### Parameter Request
     *
     * Tidak memerlukan parameter.
     *
     * ### Contoh Request
     *
     * ```http
     * GET /api/wilayah/provinsi
     * ```
     *
     * ### Hasil Respon
     *
     * **200 - OK**
     *
     * ```json
     * {
     *   "success": true,
     *   "data": [
     *     {
     *       "id": "35",
     *       "name": "JAWA TIMUR"
     *     },
     *     {
     *       "id": "34",
     *       "name": "DI YOGYAKARTA"
     *     }
     *   ]
     * }
     * ```
     *
     * ### Ketentuan
     *
     * 1. Menggunakan metode GET tanpa parameter.
     * 2. Data provinsi digunakan untuk pemilihan wilayah alamat.
     * 3. Token wajib jika route menggunakan middleware auth:sanctum.
     */
    public function provinsi(): JsonResponse
    {
        return $this->success(
            $this->wilayahIndo->provinsi()
        );
    }

    /**
     * Daftar Kabupaten/Kota
     *
     * Menampilkan daftar kabupaten dan kota berdasarkan ID provinsi
     * untuk kebutuhan pemilihan alamat pelanggan.
     *
     * ### Header Request
     *
     * ```http
     * Accept: application/json
     * Authorization: Bearer {token}
     * ```
     *
     * ### Parameter Request
     *
     * | Parameter | Tipe | Wajib | Keterangan |
     * |---|---|---|---|
     * | provinsiId | string | Ya | ID provinsi yang dipilih |
     *
     * ### Contoh Request
     *
     * ```http
     * GET /api/wilayah/kabupaten/35
     * ```
     *
     * ### Hasil Respon
     *
     * **200 - OK**
     *
     * ```json
     * {
     *   "success": true,
     *   "data": [
     *     {
     *       "id": "35.01",
     *       "name": "KABUPATEN PACITAN"
     *     },
     *     {
     *       "id": "35.02",
     *       "name": "KABUPATEN PONOROGO"
     *     }
     *   ]
     * }
     * ```
     *
     * ### Ketentuan
     *
     * 1. Menggunakan metode GET.
     * 2. ID provinsi wajib diisi pada URL.
     * 3. Kabupaten/kota ditampilkan berdasarkan provinsi yang dipilih.
     * 4. Token wajib jika route menggunakan middleware auth:sanctum.
     */
    public function kabupatenKota(string $provinsiId): JsonResponse
    {
        return $this->success(
            $this->wilayahIndo->kabupatenKota($provinsiId)
        );
    }

    /**
     * Daftar Kecamatan
     *
     * Menampilkan daftar kecamatan berdasarkan ID kabupaten/kota
     *
     * ### Header Request
     *
     * ```http
     * Accept: application/json
     * Authorization: Bearer {token}
     * ```
     *
     * ### Parameter Request
     *
     * | Parameter | Tipe | Wajib | Keterangan |
     * |---|---|---|---|
     * | kabupatenId | string | Ya | ID kabupaten/kota yang dipilih|
     *
     * ### Contoh Request
     *
     * ```http
     * GET /api/wilayah/kecamatan/35.18
     * ```
     *
     * ### Hasil Respon
     *
     * **200 - OK**
     *
     * ```json
     * {
     *   "success": true,
     *   "data": [
     *     {
     *       "id": "35.18.01",
     *       "name": "SAWAHAN"
     *     },
     *     {
     *       "id": "35.18.02",
     *       "name": "NGETOS"
     *     }
     *   ]
     * }
     * ```
     *
     * ### Ketentuan
     *
     * 1. Menggunakan metode GET.
     * 2. ID kabupaten/kota wajib diisi pada URL.
     * 3. Kecamatan ditampilkan berdasarkan kabupaten/kota yang dipilih.
     * 4. Token wajib jika route menggunakan middleware auth:sanctum.
     */
    public function kecamatan(string $kabupatenId): JsonResponse
    {
        return $this->success(
            $this->wilayahIndo->kecamatan($kabupatenId)
        );
    }

    /**
     * Daftar Desa/Kelurahan
     *
     * Menampilkan daftar desa atau kelurahan berdasarkan ID kecamatan
     *
     * ### Header Request
     *
     * ```http
     * Accept: application/json
     * Authorization: Bearer {token}
     * ```
     *
     * ### Parameter Request
     *
     * | Parameter | Tipe | Wajib | Keterangan |
     * |---|---|---|---|
     * | kecamatanId | string | Ya | ID kecamatan yang dipilih |
     *
     * ### Contoh Request
     *
     * ```http
     * GET /api/wilayah/desa/35.18.06
     * ```
     *
     * ### Hasil Respon
     *
     * **200 - OK**
     *
     * ```json
     * {
     *   "success": true,
     *   "data": [
     *     {
     *       "id": "35.18.06.2008",
     *       "name": "NAMA DESA"
     *     }
     *   ]
     * }
     * ```
     *
     * ### Ketentuan
     *
     * 1. Menggunakan metode GET.
     * 2. ID kecamatan wajib diisi pada URL.
     * 3. Desa/kelurahan ditampilkan berdasarkan kecamatan yang dipilih.
     * 4. Token wajib jika route menggunakan middleware auth:sanctum.
     */
    public function desa(string $kecamatanId): JsonResponse
    {
        return $this->success(
            $this->wilayahIndo->desa($kecamatanId)
        );
    }

    /**
     * Detail Alamat Lengkap
     *
     * ### Fitur
     *
     * Menampilkan informasi alamat lengkap berdasarkan ID desa,
     * meliputi provinsi, kabupaten/kota, kecamatan, dan desa/kelurahan.
     *
     * ### Header Request
     *
     * ```http
     * Accept: application/json
     * Authorization: Bearer {token}
     * ```
     *
     * ### Parameter Request
     *
     * | Parameter | Tipe | Wajib | Batasan | Keterangan |
     * |---|---|---|---|---|
     * | desaId | string | Ya | Kode desa valid | ID desa/kelurahan |
     *
     * ### Contoh Request
     *
     * ```http
     * GET /api/wilayah/alamat/35.18.06.2008
     * ```
     *
     * ### Hasil Respon
     *
     * **200 - OK**
     *
     * ```json
     * {
     *   "success": true,
     *   "data": {
     *     "provinsi": "JAWA TIMUR",
     *     "kabupaten_kota": "KABUPATEN NGANJUK",
     *     "kecamatan": "NAMA KECAMATAN",
     *     "desa": "NAMA DESA"
     *   }
     * }
     * ```
     *
     * Struktur data mengikuti hasil dari layanan wilayah.
     *
     * ### Ketentuan
     *
     * 1. Menggunakan metode GET.
     * 2. ID desa wajib disertakan pada URL dengan format yang valid.
     * 3. Data alamat diambil berdasarkan ID desa yang diberikan.
     * 4. Token wajib jika route menggunakan middleware auth:sanctum.
     * 5. Respons bergantung pada ketersediaan layanan wilayah.
     */
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
