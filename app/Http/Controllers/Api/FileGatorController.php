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
use App\Models\ReqFoto;
use Illuminate\Support\Facades\DB;

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

    /**
     * Show File
     *     
     * 
     * Mengambil dan menampilkan file dari FileGator melalui API Laravel.
     *
     *  ### Fitur
     *
     * - Menampilkan foto permintaan customer (REQ_FOTO).
     * - Menampilkan bukti pembayaran (TF_META).
     * - Memeriksa keberadaan path file pada database.
     * - Menampilkan gambar dan PDF secara langsung.
     * - Mengunduh format file lainnya.
     *
     * ### Header Request
     *
     * ```http
     * Accept: image/jpeg, image/png, image/gif, image/webp, application/pdf
     * Authorization: Bearer {token}
     * ```
     *
     * ### Parameter Request
     *
     * | Parameter | Tipe | Wajib | Batasan |
     * |---|---|---|---|
     * | path | string | Ya | Max 1024 karakter|
     *
     * Contoh:
     *
     * - `REQ_FOTO/20261114001A_01_05.jpg`
     * - `TF_META/20261114001A.jpg`
     *
     * ### Contoh Request
     *
     * **Endpoint:**
     *
     * ```http
     * GET /api/filegator/file?path=REQ_FOTO%2F20261114001A_01_05.jpg
     * ```
     *
     * ### Hasil Respon
     *
     * **Contoh Response Gambar:**
     *
     * ```http
     * HTTP/1.1 200 OK
     * Content-Type: image/jpeg
     * Content-Disposition: inline
     * X-Content-Type-Options: nosniff
     * Cache-Control: private, no-store
     * ```
     *
     * **Contoh Response PDF:**
     *
     * ```http
     * HTTP/1.1 200 OK
     * Content-Type: application/pdf
     * Content-Disposition: inline
     * ```
     *
     * **Contoh Response File Unduhan:**
     *
     * ```http
     * HTTP/1.1 200 OK
     * Content-Type: application/zip
     * Content-Disposition: attachment
     * ```
     *
     * **Jenis File:**
     *
     * | Format | Hasil |
     * |---|---|
     * | JPG / JPEG | Ditampilkan langsung |
     * | PNG | Ditampilkan langsung |
     * | GIF | Ditampilkan langsung |
     * | WEBP | Ditampilkan langsung |
     * | PDF | Ditampilkan langsung |
     * | Format lainnya | Diunduh |
     *
     * ### Ketentuan
     *
     * 1. Request menggunakan metode GET.
     * 2. Parameter path wajib diisi, maksimal 1024 karakter.
     * 3. Path harus terdaftar sebagai foto pesanan atau bukti pembayaran.
     * 4. Tidak memerlukan JSON Body atau form-data.
     * 5. Bearer Token wajib jika route menggunakan auth:sanctum.
     * 6. JPG, JPEG, PNG, GIF, WEBP, dan PDF dapat ditampilkan langsung.
     * 7. Format lainnya dikirim sebagai file unduhan.
     * 8. Tidak ada batas ukuran file yang ditetapkan secara eksplisit.
     * 9. Timeout pengambilan file dari FileGator adalah 60 detik.
     * 10. File yang tidak ditemukan menghasilkan HTTP 404.
     */
    public function show(
        Request $request,
        FileGatorService $fileGatorService
    ) {
        $validated = $request->validate([
            'path' => ['required', 'string', 'max:1024'],
        ]);

        $path = $validated['path'];
        $isReqFoto = ReqFoto::where('foto', $path)->exists();
        $isBuktiBayar = DB::table('bukti_pembayarans')
            ->where('bukti_bayar', $path)
            ->exists();

        abort_unless(
            $isReqFoto || $isBuktiBayar,
            404,
            'File tidak ditemukan'
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
}
