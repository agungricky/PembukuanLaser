<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\CsService;
use App\Services\Dashboard\PackingService;
use Dedoc\Scramble\Attributes\Group;

#[Group('Dashboard')]
class dashboardController extends Controller
{
    /**
     * Dashboard CS
     *
     * Menampilkan ringkasan pesanan milik Customer Service (CS)
     * berdasarkan akun pengguna yang sedang login.
     * 
     * ### Fitur
     *
     * Dashboard hanya mengambil pesanan dengan ketentuan:
     *
     * - Platform pesanan adalah META.
     * - Pesanan dimiliki oleh CS yang sedang login.
     * - Pesanan diinput pada bulan dan tahun berjalan.
     * - Filter tanggal menggunakan kolom `input_at`.
     *
     * Informasi yang dikembalikan:
     *
     * - Total seluruh pesanan CS.
     * - Jumlah pesanan berstatus proses.
     * - Jumlah pesanan berstatus kirim.
     * - Jumlah pesanan berstatus selesai.
     * - Total omzet seluruh pesanan yang memenuhi filter.
     * - Daftar pesanan beserta produk dan nama produknya.
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
     * Endpoint tidak membutuhkan parameter request.
     *
     * | Parameter | Wajib | Keterangan |
     * |---|---|---|
     * | user_id |No | ID pengguna diperoleh otomatis melalui Auth::id(). |
     * | bulan | No | Bulan ditentukan otomatis berdasarkan waktu server. |
     * | tahun | No | Tahun ditentukan otomatis berdasarkan waktu server. |
     *
     * **Ketentuan parameter:**
     *
     * - Tidak menggunakan path parameter.
     * - Tidak menggunakan query parameter.
     * - Tidak membutuhkan JSON Body.
     * - Tidak membutuhkan form-data.
     * - Identitas CS diperoleh dari token autentikasi.
     * - Periode laporan menggunakan bulan dan tahun berjalan.
     *
     * ### Contoh Request
     *
     * **Endpoint:**
     *
     * ```http
     * GET /api/dashboard/cs
     * ```
     *
     * ### Hasil Respon
     *
     * **Contoh Response JSON 200:**
     *
     * ```json
     * {
     *   "success": true,
     *   "data": {
     *     "totalPesanan": 3,
     *     "proses": 1,
     *     "dikirim": 1,
     *     "selesai": 1,
     *     "omzet": 285000,
     *     "pesanan": [
     *       {
     *         "no_pesanan": "20261114001A",
     *         "status": "proses",
     *         "total_harga": "95000.00",
     *         "pesanan_per_produk": [
     *           {
     *             "no_pesanan": "20261114001A",
     *             "id_per_produk": 259654,
     *             "sku": "EMB0001",
     *             "produk": {
     *               "sku": "EMB0001",
     *               "nama_produk": "LOGO ONE PIECE"
     *             }
     *           }
     *         ]
     *       },
     *       {
     *         "no_pesanan": "20261115001B",
     *         "status": "kirim",
     *         "total_harga": "90000.00",
     *         "pesanan_per_produk": [
     *           {
     *             "no_pesanan": "20261115001B",
     *             "id_per_produk": 259655,
     *             "sku": "GKA0906C",
     *             "produk": {
     *               "sku": "GKA0906C",
     *               "nama_produk": "CHIBI RIDER CLUB"
     *             }
     *           }
     *         ]
     *       },
     *       {
     *         "no_pesanan": "20261116001C",
     *         "status": "selesai",
     *         "total_harga": "100000.00",
     *         "pesanan_per_produk": [
     *           {
     *             "no_pesanan": "20261116001C",
     *             "id_per_produk": 259656,
     *             "sku": "LBL281C",
     *             "produk": {
     *               "sku": "LBL281C",
     *               "nama_produk": "COLOR CRUSH"
     *             }
     *           }
     *         ]
     *       }
     *     ]
     *   }
     * }
     * ```
     *
     * Contoh data di atas hanya ilustrasi.
     *
     * **Contoh Response Jika Tidak Ada Pesanan:**
     *
     * ```json
     * {
     *   "success": true,
     *   "data": {
     *     "totalPesanan": 0,
     *     "proses": 0,
     *     "dikirim": 0,
     *     "selesai": 0,
     *     "omzet": 0,
     *     "pesanan": []
     *   }
     * }
     * ```
     *
     * ### Keterangan Error
     *
     * **200 — OK**
     *
     * Dashboard CS berhasil diambil beserta ringkasan pesanan,
     * jumlah status pesanan, omzet, dan daftar produk.
     *
     * **401 — Unauthorized**
     *
     * Token autentikasi tidak tersedia, tidak valid,
     * atau pengguna belum terautentikasi.
     *
     * **403 — Forbidden**
     *
     * Pengguna tidak memiliki izin mengakses Dashboard CS
     * apabila endpoint dilindungi middleware otorisasi.
     *
     * **500 — Internal Server Error**
     *
     * Terjadi kesalahan pada server saat mengambil
     * atau menghitung data pesanan CS.
     */
    public function dashboardCs(CsService $csService)
    {
        $data = $csService->cs();

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    public function dashboardPacking(PackingService $packingService){
        $data = $packingService->packing();

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }
}
