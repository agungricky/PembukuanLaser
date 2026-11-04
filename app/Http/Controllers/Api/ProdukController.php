<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
     * Mengambil daftar produk dari database PembukuanLaser
     * menggunakan DataTables server-side.
     *
     * Mendukung pagination, pencarian, filter kategori,
     * dan pengurutan data produk.
     *
     * ### URL Request
     *
     * ```http
     * GET /api/produk-sku
     * ```
     *
     * ### Header Request
     *
     * ```http
     * Authorization: Bearer {token}
     * Accept: application/json
     * ```
     *
     * ### Query Parameter
     *
     * | Parameter | Tipe | Wajib | Keterangan |
     * |-----------|------|-------|------------|
     * | draw | integer | Tidak | Nomor urut request DataTables |
     * | start | integer | Tidak | Posisi awal data, dimulai dari 0 |
     * | length | integer | Tidak | Jumlah data yang ditampilkan |
     * | search[value] | string | Tidak | Pencarian SKU atau nama produk |
     * | kategori_id | integer | Tidak | Filter berdasarkan ID kategori |
     *
     * ### Contoh Request
     *
     * Mengambil 10 produk pertama.
     *
     * ```http
     * GET /api/produk-sku?draw=1&start=0&length=10
     * ```
     *
     * ### Filter Berdasarkan Kategori
     *
     * Menampilkan produk dengan kategori ID 15.
     *
     * ```http
     * GET /api/produk-sku?draw=1&start=0&length=10&kategori_id=15
     * ```
     *
     * ### Pencarian Produk
     *
     * Mencari produk berdasarkan SKU atau nama produk.
     *
     * ```http
     * GET /api/produk-sku?draw=1&start=0&length=10&search[value]=BNR
     * ```
     *
     * ### Pagination
     *
     * Pagination menggunakan parameter `start` dan `length`.
     * Parameter `draw` merupakan nomor urut request,
     * bukan nomor halaman.
     *
     * **Halaman Pertama**
     *
     * ```http
     * GET /api/produk-sku?draw=1&start=0&length=10
     * ```
     *
     * **Halaman Kedua**
     *
     * ```http
     * GET /api/produk-sku?draw=2&start=10&length=10
     * ```
     *
     * **Halaman Ketiga**
     *
     * ```http
     * GET /api/produk-sku?draw=3&start=20&length=10
     * ```
     *
     * ### Struktur Response
     *
     * | Field | Tipe | Keterangan |
     * |-------|------|------------|
     * | draw | integer | Nomor request DataTables |
     * | recordsTotal | integer | Total seluruh produk |
     * | recordsFiltered | integer | Total produk setelah filter |
     * | data | array | Daftar produk sesuai pagination |
     * | DT_RowIndex | integer | Nomor urut baris |
     * | sku | string | Kode SKU produk |
     * | nama_produk | string | Nama produk |
     * | variasi | string/null | Variasi produk |
     * | stok | integer | Jumlah stok tersedia |
     * | kategori_nama | string/null | Nama kategori produk |
     *
     * ### Response Berhasil (200)
     *
     * ```json
     * {
     *   "draw": 1,
     *   "recordsTotal": 6571,
     *   "recordsFiltered": 6571,
     *   "data": [
     *     {
     *       "DT_RowIndex": 1,
     *       "sku": "BNR001C",
     *       "nama_produk": "BANNER JARING SOUND",
     *       "variasi": "MODEL 1, 50 × 25 CM",
     *       "stok": 0,
     *       "kategori_nama": "Banner"
     *     }
     *   ]
     * }
     * ```
     *
     * ### Response Gagal
     *
     * - `401` Unauthorized: Token tidak valid atau belum login.
     * - `403` Forbidden: Tidak memiliki izin mengakses data.
     * - `422` Validation Error: Parameter tidak sesuai validasi.
     * - `500` Internal Server Error: Kesalahan pada server.
     *
     * ### Catatan
     *
     * - Endpoint memerlukan Bearer Token Laravel Sanctum.
     * - Token diperoleh melalui endpoint POST /api/login.
     * - Parameter pagination menggunakan indeks mulai dari 0.
     * - Nilai `draw` dikelola oleh masing-masing DataTables.
     * - Pencarian dan filter dapat digunakan secara bersamaan.
     * - Jumlah hasil mengikuti parameter `length`.
     */

    public function produkshow(
        Request $request,
        SkuServices $skuServices
    ): JsonResponse {

        $request->validate([
            /**
             * Posisi awal pengambilan data.
             * @example 0
             */
            'start' => ['sometimes', 'integer', 'min:0'],

            /**
             * Jumlah produk yang ingin ditampilkan.
             * Minimal 1 dan maksimal 100.
             * @example 10
             */
            'length' => [
                'sometimes',
                'integer',
                'between:1,100',
            ],

            /**
             * Filter produk berdasarkan ID kategori.
             * @example 15
             */
            'kategori_id' => [
                'sometimes',
                'nullable',
                'integer',
                'exists:kategoris,id',
            ],

            /**
             * Parameter pencarian DataTables.
             */
            'search' => ['sometimes'],

            /**
             * Kata kunci pencarian produk.
             * @example "BNR"
             */
            'search.value' => [
                'sometimes',
                'nullable',
                'string',
                'max:100',
            ],

        ]);

        return $skuServices->produk($request);
    }
}
