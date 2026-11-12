<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Produk\ProdukService;
use App\Services\Produk\SkuServices;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @tags Produk
 */
class ProdukController extends Controller
{

    /**
     * Daftar Produk
     *
     * Mengambil seluruh produk aktif berdasarkan kategori,
     * lengkap dengan daftar variasi, SKU, dan HPP.
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
     * |-----------|------|-------|------------|
     * | kategori | integer | Ya | ID kategori produk |
     *
     * ### Contoh Request
     *
     * ```http
     * GET /api/produk/15
     * ```
     *
     * ### Contoh Response (200 OK)
     *
     * ```json
     * {
     *   "success": true,
     *   "data": [
     *     {
     *       "nama_produk": "BANNER JARING SOUND",
     *       "variasi": [
     *         {
     *           "sku": "BNR001C",
     *           "nama": "MODEL 1, 50 X 25 CM",
     *           "hpp": 11750
     *         },
     *         {
     *           "sku": "BNR002C",
     *           "nama": "MODEL 1, 100 X 50 CM",
     *           "hpp": 35000
     *         }
     *       ]
     *     }
     *   ]
     * }
     * ```
     *
     * @param int $kategori ID kategori produk.
     */

    public function index(ProdukService $produkService, int $kategori)
    {
        $produk = $produkService->produk($kategori);
        return response()->json([
            'success' => true,
            'data' => $produk
        ]);
    }
}
