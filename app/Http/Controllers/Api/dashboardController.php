<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Dashboard\CsService;
use Illuminate\Http\Request;

class dashboardController extends Controller
{
    /**
     * Dashboard CS
     *
     * Menampilkan ringkasan pesanan CS berdasarkan user yang login.
     * Hanya menghitung pesanan dengan platform META.
     *
     * Data yang ditampilkan:
     * - Total seluruh pesanan
     * - Total pesanan dalam proses
     * - Total pesanan dikirim
     * - Total omzet
     * - Pesanan
     *
     * ### Parameter
     *
     * | Parameter | Tipe | Wajib | Keterangan |
     * |-----------|------|-------|------------|
     * | `user_id` | integer | Ya | ID user Login |
     *
     * ### Header Request
     *
     * ```http
     * Authorization: Bearer {token}
     * Accept: application/json
     * ```
     *
     * ### Contoh Request
     *
     * ```http
     * GET /api/dashboard/cs/33
     * ```
     *
     * ### Contoh Response (200 OK)
     *
     * ```json
     * {
     *   "success": true,
     *   "data": {
     *     "total_pesanan": 150,
     *     "dalam_proses": 45,
     *     "dikirim": 80,
     *     "omzet": 12500000
     *   }
     * }
     * ```
     *
     * @param int $user_id ID user CS
     */
    public function dashboardCs(CsService $csService, $user_id)
    {
        $data = $csService->cs($user_id);

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }
}
