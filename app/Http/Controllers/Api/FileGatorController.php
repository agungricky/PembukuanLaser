<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Api\FileGatorService as ApiFileGatorService;
use App\Services\Storage\DriveService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;
use Dedoc\Scramble\Attributes\Response;
use App\Services\Api\FileGatorService;
use Dedoc\Scramble\Attributes\ExcludeRouteFromDocs;
use Illuminate\Support\Facades\Http;
use GuzzleHttp\Cookie\CookieJar;
use RuntimeException;

use InvalidArgumentException;
use OutOfBoundsException;

use function React\Promise\all;

class FileGatorController extends Controller
{
    /**
     * Create Folder
     *
     * API ini digunakan untuk membuat folder di penyimpanan FileGator.
     * Folder dapat dibuat di direktori utama atau folder di dalam folder.
     *
     * ### Header Request
     *
     * ```http
     * Authorization: Bearer {token}
     * Accept: application/json
     * Content-Type: application/json
     * ```
     *
     * ### Parameter
     *
     * | Parameter | Tipe | Wajib | Keterangan |
     * |-----------|------|-------|------------|
     * | `name` | string | Ya | Nama folder |
     * | `parent` | string | Tidak | Perent Folder |
     *
     * ### Contoh Request
     *
     * ```http
     * POST /api/filegator/folder
     * ```
     *
     * ### Contoh Request Body
     *
     * Membuat folder `REQ_FOTO` di direktori utama FileGator.
     *
     * ```json
     * {
     *   "name": "REQ_FOTO",
     *   "parent": "/"
     * }
     * ```
     *
     * Jika ingin membuat folder di dalam folder lain,
     * gunakan lokasi folder tersebut pada parameter `parent`.
     *
     * Contoh membuat folder `PESANAN` di dalam `REQ_FOTO`:
     *
     * ```json
     * {
     *   "name": "PESANAN",
     *   "parent": "/REQ_FOTO"
     * }
     * ```
     *
     * ### Contoh Response (201 Created - Folder Berhasil Dibuat)
     *
     * Server berhasil membuat folder baru dan mengembalikan
     * informasi nama serta lokasi folder.
     *
     * ```json
     * {
     *   "success": true,
     *   "message": "Folder berhasil dibuat",
     *   "data": {
     *     "name": "REQ_FOTO",
     *     "path": "/REQ_FOTO"
     *   }
     * }
     * ```
     *
     * ### Contoh Response (422 Unprocessable Entity - Data Tidak Valid)
     *
     * Terjadi jika data yang dikirim tidak sesuai ketentuan,
     * misalnya nama folder kosong atau tidak diisi.
     *
     * ```json
     * {
     *   "message": "The name field is required.",
     *   "errors": {
     *     "name": [
     *       "The name field is required."
     *     ]
     *   }
     * }
     * ```
     *
     * ### Contoh Response (502 Bad Gateway - Gagal Membuat Folder)
     *
     * Terjadi ketika proses pembuatan folder di FileGator gagal.
     *
     * ```json
     * {
     *   "success": false,
     *   "message": "Gagal membuat folder di FileGator"
     * }
     * ```
     *
     * ### Ketentuan
     *
     * - Nama folder wajib diisi, maksimal 100 karakter.
     * - Nama folder tidak boleh mengandung `/` atau `\`.
     * - Nama folder tidak boleh berupa `.` atau `..`.
     * - Lokasi `parent` maksimal 500 karakter.
     * - Jika `parent` tidak diisi, folder dibuat di direktori utama `/`.
     * - Gunakan `/` untuk memisahkan nama folder dalam path.
     */
    #[Response(status: 201, description: 'Folder berhasil dibuat di FileGator')]
    #[Response(status: 401, description: 'Pengguna belum login atau token tidak valid')]
    #[Response(status: 422, description: 'Data yang dikirim tidak valid')]
    #[Response(status: 502, description: 'Gagal membuat folder di FileGator')]
    public function createFolder(
        Request $request,
        ApiFileGatorService $filegator,
        DriveService $driveService
    ): JsonResponse {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'regex:/^[^\/\\\\]+$/', 'not_in:.,..'],
            'parent' => ['nullable', 'string', 'max:500', 'regex:/^\/(?:[^\/\\\\.][^\/\\\\]*\/?)*$/'],
        ]);

        try {
            $result = $driveService->createFolder($filegator, $data);

            return response()->json([
                'success' => true,
                'message' => 'Folder berhasil dibuat',
                'data' => $result,
            ], 201, [], JSON_UNESCAPED_SLASHES);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat folder di FileGator',
            ], 502);
        }
    }

    /**
     * Upload File
     *
     * Endpoin ini digunakan untuk mengunggah file ke folder tertentu
     * di FileGator. 
     *
     * ### Header Request
     *
     * ```http
     * Authorization: Bearer {token}
     * Accept: application/json
     * Content-Type: multipart/form-data
     * ```
     *
     * ### Parameter
     *
     * | Parameter | Tipe | Wajib | Keterangan |
     * |-----------|------|-------|------------|
     * | `file` | file | Ya | File yang akan diunggah |
     * | `folder` | string | Ya | Lokasi folder tujuan |
     *
     * ### Contoh Request
     *
     * ```http
     * POST /api/filegator/upload
     * ```
     *
     * ### Contoh Request Body (form-data)
     *
     * Kirim data menggunakan `multipart/form-data`, bukan JSON.
     *
     * ```text
     * file   = foto_produk.png (Upload File)
     * folder = "/REQ_FOTO"
     * ```
     * ### Contoh Response (201 Created - File Berhasil Diupload)
     *
     * File berhasil diunggah ke FileGator.
     * Server akan mengembalikan nama file dan lokasi penyimpanannya.
     *
     * ```json
     * {
     *   "success": true,
     *   "message": "File berhasil diupload",
     *   "data": {
     *     "filename": "foto_produk.png",
     *     "folder": "/REQ_FOTO",
     *     "path": "/REQ_FOTO/foto_produk.png"
     *   }
     * }
     * ```
     *
     * ### Contoh Response (401 Unauthorized - Belum Terautentikasi)
     *
     * Terjadi jika token autentikasi tidak dikirim,
     * tidak valid, atau sudah tidak berlaku.
     *
     * ```json
     * {
     *   "message": "Unauthenticated."
     * }
     * ```
     *
     * ### Contoh Response (422 Unprocessable Entity - Data Tidak Valid)
     *
     * Terjadi jika file tidak dikirim, ukuran file terlalu besar,
     * format tidak didukung, atau lokasi folder tidak valid.
     *
     * Contoh ketika parameter `file` tidak diisi:
     *
     * ```json
     * {
     *   "message": "The file field is required.",
     *   "errors": {
     *     "file": [
     *       "The file field is required."
     *     ]
     *   }
     * }
     * ```
     *
     * ### Contoh Response (422 - Nama File Tidak Valid)
     *
     * Terjadi jika nama file mengandung karakter yang
     * tidak diperbolehkan.
     *
     * ```json
     * {
     *   "success": false,
     *   "message": "Nama file tidak valid"
     * }
     * ```
     *
     * ### Contoh Response (502 Bad Gateway - Upload Gagal)
     *
     * Terjadi jika proses upload gagal karena terdapat masalah
     * saat menghubungi atau memproses file di FileGator.
     *
     * ```json
     * {
     *   "success": false,
     *   "message": "Gagal upload file ke FileGator"
     * }
     * ```
     *
     * ### Ketentuan Upload
     *
     * - File wajib dikirim menggunakan `multipart/form-data`.
     * - Ukuran maksimal file adalah 10 MB.
     * - Format yang diizinkan adalah JPG, JPEG, PNG, WEBP, dan PDF.
     * - Parameter `file` dan `folder` wajib diisi.
     * - Parameter `folder` memiliki panjang maksimal 500 karakter.
     * - Path folder harus diawali `/`, contoh `/REQ_FOTO`.
     * - Nama file menggunakan nama asli dari file yang diunggah.
     * - Nama file tidak boleh berupa `.` atau `..`.
     * - Nama file tidak boleh mengandung karakter `/` atau `\`.
     * - Folder tujuan harus tersedia di FileGator.
     * - File akan disimpan sesuai lokasi folder yang ditentukan.
     */

    #[Response(status: 201, description: 'File berhasil diunggah ke FileGator')]
    #[Response(status: 401, description: 'Pengguna belum login atau token tidak valid')]
    #[Response(status: 422, description: 'Data file atau folder tidak valid')]
    #[Response(status: 502, description: 'Gagal upload file ke FileGator')]
    public function upload(
        Request $request,
        ApiFileGatorService $filegator,
        DriveService $driveService
    ): JsonResponse {
        $data = $request->validate([
            'file' => ['required', 'file', 'max:10240', 'mimes:jpg,jpeg,png,webp,pdf'],
            'folder' => ['required', 'string', 'max:500', 'regex:/^\/(?:[^\/\\\\.][^\/\\\\]*\/?)*$/'],
        ]);

        try {
            $result = $driveService->uploadFiles($filegator, $data);

            if (!$result['success']) {
                return response()->json($result, 422);
            }

            return response()->json([
                'success' => true,
                'message' => 'File berhasil diupload',
                'data' => $result['data'],
            ], 201, [], JSON_UNESCAPED_SLASHES);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Gagal upload file ke FileGator',
            ], 502);
        }
    }

    #[ExcludeRouteFromDocs]
    public function show(
        Request $request,
        FileGatorService $fileGatorService
    ) {
        $validated = $request->validate([
            'path' => ['required', 'string', 'max:1024'],
        ]);

        $path = $validated['path'];
        abort_if(
            str_contains($path, '..') ||
                str_starts_with($path, '/') ||
                str_contains($path, '\\') ||
                str_contains($path, "\0"),
            400,
            'Path file tidak valid.'
        );

        $file = $fileGatorService->downloadFile($path);

        $inlineTypes = [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'application/pdf',
        ];

        $disposition = in_array(
            $file['mime'],
            $inlineTypes,
            true
        ) ? 'inline' : 'attachment';

        return response($file['content'], 200, [
            'Content-Type' => $file['mime'],
            'Content-Disposition' => $disposition,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    // public function delete(Request $request): JsonResponse
    // {
    //     $data = $request->validate([
    //         'paths' => ['required','array','min:1','max:50'],
    //         'paths.*' => ['required','string','max:500','distinct'],
    //     ]);

    //     try {
    //         $items = [];
    //         $directories = [];

    //         foreach ($data['paths'] as $path) {
    //             $path = '/' . ltrim($path, '/');
    //             if (
    //                 $path === '/' ||
    //                 str_ends_with($path, '/') ||
    //                 str_contains($path, '\\') ||
    //                 str_contains($path, '%') ||
    //                 str_contains($path, '//') ||
    //                 preg_match('#(^|/)\.{1,2}(/|$)#', $path) ||
    //                 preg_match('/[\x00-\x1F\x7F]/', $path)
    //             ) {
    //                 return response()->json([
    //                     'success' => false,
    //                     'message' => 'Path file tidak valid',
    //                     'path' => $path,
    //                 ], 422);
    //             }

    //             $items[$path] = [
    //                 'type' => 'file',
    //                 'path' => $path,
    //             ];
    //         }

    //         $items = array_values($items);

    //         // KONFIGURASI FILEGATOR LOGIN
    //         $url = rtrim(config('services.filegator.url'), '/');
    //         $cookies = new CookieJar();
    //         $client = Http::acceptJson()
    //             ->asJson()
    //             ->timeout(30)
    //             ->connectTimeout(10)
    //             ->withOptions([
    //                 'cookies' => $cookies,
    //                 'allow_redirects' => false,
    //             ]);

    //         $init = $client->get(
    //             $url . '/?r=/getconfig'
    //         );
    //         $init->throw();
    //         $csrfToken = $init->header('X-CSRF-Token');
    //         if (!$init->successful() || !$csrfToken) {
    //             throw new RuntimeException(
    //                 'CSRF Token tidak ditemukan'
    //             );
    //         }

    //         $login = $client
    //             ->replaceHeaders([
    //                 'Accept' => 'application/json',
    //                 'Content-Type' => 'application/json',
    //                 'X-CSRF-Token' => $csrfToken,
    //             ])
    //             ->post($url . '/?r=/login', [
    //                 'username' => config(
    //                     'services.filegator.username'
    //                 ),
    //                 'password' => config(
    //                     'services.filegator.password'
    //                 ),
    //             ]);

    //         $login->throw();
    //         if (!$login->successful()) {
    //             throw new RuntimeException(
    //                 'Login FileGator gagal'
    //             );
    //         }

    //         $session = $client->get($url . '/?r=/getuser');
    //         $session->throw();
    //         $csrfToken = $session->header('X-CSRF-Token');
    //         if (!$session->successful() || !$csrfToken) {
    //             throw new RuntimeException(
    //                 'Session CSRF tidak ditemukan'
    //             );
    //         }

    //         $client->replaceHeaders([
    //             'Accept' => 'application/json',
    //             'Content-Type' => 'application/json',
    //             'X-CSRF-Token' => $csrfToken,
    //         ]);

    //         // MEMASTIKAN TARGET ADALAH FILE
    //         foreach ($items as $item) {
    //             $path = $item['path'];
    //             $folder = dirname($path);
    //             if (!array_key_exists($folder, $directories)) {
    //                 $listing = $client->post(
    //                     $url . '/?r=/changedir', ['to' => $folder]
    //                 );

    //                 $listing->throw();
    //                 $files = $listing->json('data.files');
    //                 $location = $listing->json('data.location');

    //                 if (
    //                     !$listing->successful() ||
    //                     !is_array($files) ||
    //                     $location !== $folder
    //                 ) {
    //                     throw new RuntimeException(
    //                         'Gagal membaca direktori FileGator'
    //                     );
    //                 }

    //                 $directories[$folder] = $files;
    //             }

    //             // Cari target berdasarkan path
    //             $target = collect($directories[$folder])
    //                 ->first(function ($file) use ($path) {
    //                     return ($file['path'] ?? null) === $path;
    //                 });

    //             // Pastikan target ditemukan
    //             if (!$target) {
    //                 return response()->json([
    //                     'success' => false,
    //                     'message' => 'File tidak ditemukan',
    //                     'path' => $path,
    //                 ], 404);
    //             }

    //             // Pastikan target benar-benar file
    //             if (($target['type'] ?? null) !== 'file') {
    //                 return response()->json([
    //                     'success' => false,
    //                     'message' => 'Penghapusan folder tidak diizinkan',
    //                     'path' => $path,
    //                 ], 422);
    //             }
    //         }

    //         // 08. HAPUS FILE
    //         $response = $client->post(
    //             $url . '/?r=/deleteitems',
    //             [
    //                 'items' => $items,
    //             ]
    //         );

    //         $response->throw();
    //         $result = $response->json('data')
    //             ?? $response->json();

    //         if (
    //             !$response->successful() ||
    //             $result !== 'Done'
    //         ) {
    //             throw new RuntimeException(
    //                 'FileGator gagal mengonfirmasi penghapusan file'
    //             );
    //         }

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'File berhasil dihapus',
    //             'data' => [
    //                 'total' => count($items),
    //                 'paths' => array_column($items, 'path'),
    //             ],
    //         ], 200, [], JSON_UNESCAPED_SLASHES);
    //     } catch (Throwable $e) {
    //         report($e);
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Gagal menghapus atau mengonfirmasi penghapusan file dari FileGator',
    //             'error' => config('app.debug')
    //                 ? $e->getMessage()
    //                 : null,
    //         ], 502);
    //     }
    // }

    public function delete(DriveService $driveService, Request $request): JsonResponse
    {
        $data = $request->validate([
            'paths' => ['required', 'array', 'min:1', 'max:50'],
            'paths.*' => ['required', 'string', 'max:500', 'distinct'],
        ]);

        try {
            $result = $this->$driveService->delete($data['paths']);

            return response()->json([
                'success' => true,
                'message' => 'File berhasil dihapus',
                'data' => $result,
            ], 200, [], JSON_UNESCAPED_SLASHES);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (OutOfBoundsException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 404);
        } catch (Throwable $e) {
            report($e);
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus atau mengonfirmasi penghapusan file dari FileGator',
                'error' => config('app.debug')
                    ? $e->getMessage()
                    : null,
            ], 502);
        }
    }
}
