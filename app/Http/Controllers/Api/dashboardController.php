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

    /**
     * Dashboard Packing
     *
     * Menampilkan ringkasan jumlah pesanan berdasarkan proses
     * packing dan pengiriman. Data disesuaikan secara otomatis
     * dengan platform pengguna yang sedang login.
     *
     * ## Fitur
     *
     * Menampilkan empat informasi:
     *
     * - totalMenunggu: Jumlah pesanan yang masih menunggu.
     * - siapDikirim: Jumlah pesanan yang siap dikirim.
     * - dikirim: Jumlah pesanan berstatus kirim.
     * - terlambat: Jumlah pesanan yang melewati batas kirim.
     *
     * ### Platform Marketplace
     *
     * - totalMenunggu:
     *   Pesanan berstatus proses, batas kirim
     *   H+2 atau setelahnya, dan semua item perproduk berstatus 1 / sudah dikerjakan.
     *
     * - siapDikirim:
     *   Pesanan berstatus proses, batas kirim
     *   hari ini, dan semua item produk berstatus 1 / sudah di kerjakan.
     *
     * - dikirim:
     *   Pesanan berstatus kirim, memiliki batas kirim,
     *   dan masuk pada bulan berjalan (bulan ini).
     *
     * - terlambat:
     *   Pesanan berstatus proses, melewati batas kirim,
     *   dan masuk pada bulan berjalan.
     *
     * ### Platform Meta
     *
     * - totalMenunggu:
     *   Status proses, belum memiliki resi dan foto.
     *
     * - siapDikirim:
     *   Status proses, sudah memiliki resi dan foto.
     *
     * - dikirim:
     *   Status kirim, sudah memiliki resi dan foto.
     *
     * - terlambat:
     *   Status proses dan melewati batas kirim.
     *
     * Seluruh perhitungan Meta berdasarkan pesanan
     * yang masuk pada bulan berjalan.
     *
     * ## Header Request
     *
     * ```http
     * Accept: application/json
     * Authorization: Bearer {token}
     * ```
     *
     * ## Parameter Request
     *
     * Tidak memerlukan parameter maupun request body.
     * Platform diambil otomatis dari userLogin()=>platform.
     *
     * ## Contoh Request
     *
     * ```http
     * GET /api/packing/dashboard
     * ```
     *
     * ## Hasil Respon
     *
     * **HTTP 200 - Berhasil**
     *
     * ```json
     * {
     *   "success": true,
     *   "data": {
     *     "totalMenunggu": 42,
     *     "siapDikirim": 18,
     *     "dikirim": 95,
     *     "terlambat": 7
     *   }
     * }
     * ```
     *
     * ## Keterangan Error
     *
     * | Kode | Status | Keterangan |
     * |------|--------|------------|
     * | 200 | OK | Data berhasil diambil |
     * | 401 | Unauthorized | Pengguna belum login |
     * | 403 | Forbidden | Tidak memiliki izin akses |
     * | 500 | Server Error | Kesalahan pada server |
     *
     * ## Ketentuan
     *
     * 1. Platform ditentukan otomatis dari pengguna login.
     * 2. Pengguna tidak perlu mengirim parameter platform.
     * 3. Data Meta dan Marketplace dibatasi bulan berjalan.
     * 4. Terlambat dihitung jika batas kirim sebelum hari ini.
     * 5. Pesanan tanpa batas kirim tidak dihitung terlambat.
     * 6. Jika tidak ada data, jumlah dikembalikan 0.
     */
    public function dashboardPacking(PackingService $packingService)
    {
        $data = $packingService->packing();

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }
}
