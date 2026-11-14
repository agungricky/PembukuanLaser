<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Api\DeveloperTrialToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{

    /**
     * Login
     *
     * Melakukan autentikasi menggunakan username dan password.
     * Jika berhasil, sistem mengembalikan Bearer Token
     * untuk mengakses API yang memerlukan autentikasi.
     *
     * ### URL Request
     *
     * ```http
     * POST /api/login
     * ```
     *
     * ### Header Request
     *
     * ```http
     * Accept: application/json
     * Content-Type: application/json
     * ```
     *
     * ### Parameter Request (JSON Body)
     *
     * | Parameter | Tipe | Wajib | Keterangan |
     * |-----------|------|-------|------------|
     * | name | string | Ya | Username pengguna |
     * | password | string | Ya | Password pengguna |
     *
     * ### Contoh Body Request
     *
     * ```json
     * {
     *   "name": "admin",
     *   "password": "password123"
     * }
     * ```
     *
     * ### Response Berhasil (200)
     *
     * ```json
     * {
     *   "status": "success",
     *   "message": "Login berhasil",
     *   "token": "1|xxxxxxxx",
     *   "token_type": "Bearer",
     *   "user": {
     *     "id": 1,
     *     "name": "admin",
     *     "role": "admin"
     *   }
     * }
     * ```
     *
     * ### Response Gagal
     *
     * - `401` Unauthorized: Username atau password salah.
     * - `422` Validation Error: Parameter tidak valid atau kosong.
     *
     * ### Masa Berlaku Token
     *
     * - **Developer:** Menggunakan token khusus developer.
     * - **Pengguna lain:** Token berlaku selama 30 hari.
     *
     * ### Penggunaan Token
     *
     * Setelah login berhasil, simpan token dengan aman
     * dan sertakan pada setiap request yang memerlukan autentikasi.
     *
     * ```http
     * Authorization: Bearer {token}
     * Accept: application/json
     * ```
     *
     * Token tidak diperlukan saat melakukan login.
     */

    public function login(Request $request)
    {
        try {
            $message = [
                'name.required' => 'Username harus diisi',
                'password.required' => 'Password harus diisi',
            ];

            $request->validate([
                'name' => 'required|string',
                'password' => 'required|string',
            ], $message);

            $user = User::where('name', $request->name)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                /**
                 * @description Gagal Login
                 */
                return response()->json([
                    'status' => 'error',
                    'message' => 'Username atau password salah',
                ], 401);
            }

            $token = $user->role === 'developer'
                ? DeveloperTrialToken::issueForApi($user)
                : $user->createToken(
                    'authToken',
                    ['*'],
                    now()->addDays(30)
                )->plainTextToken;

            /**
             * @description Login Berhasil
             */
            return response()->json([
                'status' => 'success',
                'message' => 'Login berhasil',
                'token' => $token,
                'token_type' => 'Bearer',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'role' => $user->role,
                    'leader' => $user->leader
                ],
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validasi gagal',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * Cek Token
     *
     * Memeriksa apakah Bearer Token masih valid
     * dan dapat digunakan lagi tanpa login.
     *
     * Endpoint ini digunakan aplikasi mobile
     * untuk memeriksa status login pengguna.
     *
     * ### URL Request
     *
     * ```http
     * GET /api/me
     * ```
     *
     * ### Header Request
     *
     * | Parameter | Tipe | Keterangan |
     * |-----------|------|------------|
     * | Authorization | Bearer Token | Token dari hasil login |
     * | Accept | string | application/json |
     *
     * ### Contoh Header
     *
     * ```http
     * Authorization: Bearer {token}
     * Accept: application/json
     * ```
     * 
     *### Contoh Token
     *
     * ```http
     * Salah: 33|4f7WjoYuCKKY5aAIO4Nj0TBg6
     * Benar: 4f7WjoYuCKKY5aAIO4Nj0TBg6
     * ```
     *
     * ### Parameter Query / Body
     *
     * Tidak ada.

     *
     * ### Response Berhasil (200)
     *
     * ```json
     * {
     *   "status": "success",
     *   "message": "Token valid",
     *   "user": {
     *     "id": 1,
     *     "name": "admin",
     *     "role": "admin"
     *   }
     * }
     * ```
     *
     * ### Response Gagal
     *
     * - `401` Unauthorized: Token tidak valid, kedaluwarsa,
     *   atau sudah dicabut.
     */
    public function me(Request $request)
    {
        $user = $request->user();

        /** @description Token Valid */
        return response()->json([
            'status' => 'success',
            'message' => 'Token valid',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $user->role,
                'leader' => $user->leader
            ],
        ], 200);
    }

    /**
     * Logout
     *
     * Mengakhiri sesi autentikasi dengan menghapus
     * Bearer Token yang digunakan saat ini.
     *
     * Setelah logout berhasil, token tersebut tidak dapat
     * digunakan kembali untuk mengakses API.
     *
     * ### URL Request
     *
     * ```http
     * POST /api/logout
     * ```
     *
     * ### Header Request
     *
     * ```http
     * Authorization: Bearer {token}
     * Accept: application/json
     * ```
     *
     * ### Response Berhasil (200)
     *
     * ```json
     * {
     *   "status": "success",
     *   "message": "Logout berhasil"
     * }
     * ```
     *
     * ### Response Gagal
     *
     * - `401` Unauthorized: Token tidak valid atau tidak tersedia.
     */
    public function logout(Request $request)
    {
        $token = $request->user()->currentAccessToken();

        if ($token) {
            $token->delete();
        }

        /** @description Logout Berhasil */
        return response()->json([
            'status' => 'success',
            'message' => 'Logout berhasil',
        ], 200);
    }
}
