<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Ringkasan\CsService;
use Illuminate\Http\Request;

class RingkasanController extends Controller
{
    /**
     * Ringkasan Cs
     *
     * Menampilkan ringkasan pesanan META milik CS yang sedang login
     * berdasarkan bulan dan tahun berjalan.
     * 
     * ### Fitur
     *
     * Data meliputi total pesanan, pesanan valid, pesanan batal,
     * omzet, dan distribusi pesanan berdasarkan status.
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
     * Tidak memerlukan parameter request.
     * ID CS diperoleh otomatis dari pengguna yang login.
     *
     * ### Contoh Request
     *
     * ```http
     * GET /api/ringkasan/cs
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
     *     "totalPesanan": 150,
     *     "pesananValid": 135,
     *     "pesananBatal": 12,
     *     "omzet": 12500000,
     *     "distribusiPesanan": {
     *       "proses": 35,
     *       "packing": 25,
     *       "dikirim": 40,
     *       "selesai": 35,
     *       "batal": 12
     *     }
     *   }
     * }
     * ```
     *
     * **Keterangan Response:**
     *
     * - `totalPesanan`: Seluruh pesanan termasuk batal dan affiliate.
     * - `pesananValid`: Pesanan selain status batal dan affiliate.
     * - `pesananBatal`: Jumlah pesanan berstatus batal.
     * - `omzet`: Total harga seluruh pesanan valid.
     * - `distribusiPesanan`: Jumlah pesanan berdasarkan status.
     *
     * ### Ketentuan
     *
     * 1. Menggunakan metode GET tanpa parameter atau request body.
     * 2. Wajib menggunakan Bearer Token jika route dilindungi Sanctum.
     * 3. Hanya menghitung pesanan platform META milik CS yang login.
     * 4. Periode berdasarkan input_at pada bulan dan tahun berjalan.
     * 5. Pesanan valid tidak termasuk status batal dan affiliate.
     * 6. Omzet dihitung dari total_harga seluruh pesanan valid.
     */
    public function cs(CsService $csService)
    {
        $data = $csService->cs();
        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }
}
