<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Produk\ProdukService;
use App\Services\Produk\SkuServices;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Dedoc\Scramble\Attributes\Response;


/**
 * @tags Produk
 */
class ProdukController extends Controller
{

    /**
     * Daftar Produk Berdasarkan Kategori
     *
     * API ini digunakan untuk mengambil daftar produk aktif
     * berdasarkan ID kategori yang dipilih.
     *
     * Setiap produk ditampilkan bersama daftar variasinya,
     * termasuk SKU, nama variasi, dan HPP (Harga Pokok Produksi).
     *
     * Produk dengan nama yang sama dikelompokkan menjadi satu,
     * sehingga seluruh variasinya berada dalam satu daftar produk.
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
     * | `kategori` | integer | Ya | ID kategori produk yang ingin ditampilkan |
     *
     * ### Contoh Request
     *
     * Mengambil seluruh produk aktif dari kategori dengan ID `15`.
     *
     * ```http
     * GET /api/produk/15
     * ```
     *
     * Angka `15` merupakan ID kategori produk.
     * Ganti angka tersebut sesuai kategori yang ingin dicari.
     *
     * ### Contoh Response (200 OK - Produk Berhasil Diambil)
     *
     * Server berhasil mengambil daftar produk aktif
     * beserta seluruh variasi yang tersedia.
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
     * ### Penjelasan Data Response
     *
     * | Field | Tipe | Keterangan |
     * |-------|------|------------|
     * | `success` | boolean | Menunjukkan apakah permintaan berhasil |
     * | `data` | array | Daftar produk berdasarkan kategori |
     * | `nama_produk` | string | Nama produk utama |
     * | `variasi` | array | Daftar pilihan variasi produk |
     * | `sku` | string | Kode unik untuk setiap variasi |
     * | `nama` | string | Nama variasi, seperti model, warna, atau ukuran |
     * | `hpp` | number | Harga Pokok Produksi dalam Rupiah |
     *
     * ### Contoh Response (200 OK - Produk Tidak Ditemukan)
     *
     * Jika kategori tidak memiliki produk yang sesuai,
     * API dapat mengembalikan daftar kosong.
     *
     * ```json
     * {
     *   "success": true,
     *   "data": []
     * }
     * ```
     *
     * ### Contoh Response (401 Unauthorized - Belum Terautentikasi)
     *
     * Terjadi jika pengguna belum login, Bearer Token
     * tidak dikirim, atau token sudah tidak valid.
     *
     * ```json
     * {
     *   "message": "Unauthenticated."
     * }
     * ```
     *
     * ### Contoh Response (500 Internal Server Error)
     *
     * Terjadi jika server mengalami kesalahan
     * saat mengambil atau memproses data produk.
     *
     * ```json
     * {
     *   "message": "Server Error"
     * }
     * ```
     *
     * ### Status Response
     *
     * | Kode | Status | Keterangan |
     * |------|--------|------------|
     * | `200` | OK | Daftar produk berdasarkan kategori berhasil diambil |
     * | `401` | Unauthorized | Pengguna belum login atau token tidak valid |
     * | `500` | Internal Server Error | Terjadi kesalahan server saat mengambil data produk |
     *
     * ### Ketentuan
     *
     * - Parameter `kategori` wajib berupa angka ID kategori.
     * - ID kategori dikirim melalui URL, bukan request body.
     * - API menggunakan metode `GET`.
     * - Hanya produk dengan status aktif yang ditampilkan.
     * - Produk dikelompokkan berdasarkan nama produk.
     * - Setiap variasi memiliki SKU dan HPP masing-masing.
     * - Nilai `hpp` ditampilkan dalam Rupiah tanpa format mata uang.
     * - Jika tidak ada produk yang sesuai, data dapat berupa array kosong.
     * - Endpoint memerlukan Bearer Token jika dilindungi autentikasi.
     *
     * @param int $kategori ID kategori produk yang ingin ditampilkan.
     */

    #[Response(
        status: 200,
        description: 'Daftar produk berdasarkan kategori berhasil diambil'
    )]
    #[Response(
        status: 401,
        description: 'Pengguna belum login atau token tidak valid'
    )]
    #[Response(
        status: 500,
        description: 'Terjadi kesalahan server saat mengambil data produk'
    )]
    public function index(ProdukService $produkService, int $kategori)
    {
        $produk = $produkService->produk($kategori);

        return response()->json([
            'success' => true,
            'data' => $produk
        ]);
    }
}
