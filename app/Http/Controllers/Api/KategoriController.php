<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Kategori\KategoriService;
use Illuminate\Http\Request;

class KategoriController extends Controller
{

    /**
     * Daftar Kategori
     *
     * Mengambil seluruh daftar kategori produk PembukuanLaser.
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
     * Endpoint ini tidak memerlukan parameter.
     *
     * ### Contoh Request
     *
     * ```http
     * GET /api/kategoris
     * ```
     *
     * ### Contoh Response (200 OK)
     *
     * ```json
     * {
     *   "success": true,
     *   "data": [
     *     {
     *       "id": 1,
     *       "nama": "Plat Nomor"
     *     },
     *     {
     *       "id": 2,
     *       "nama": "Gantungan Kunci"
     *     },
     *     {
     *       "id": 3,
     *       "nama": "Stiker"
     *     }
     *   ]
     * }
     * ```
     */
    public function index(KategoriService $kategoriService)
    {
        $kategori = $kategoriService->index();
        return response()->json([
            'success' => true,
            'data' => $kategori,
        ], 200);
    }
}
