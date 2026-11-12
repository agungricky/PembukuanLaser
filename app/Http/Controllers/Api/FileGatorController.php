<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Api\FileGatorService as ApiFileGatorService;
use App\Services\Storage\DriveService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class FileGatorController extends Controller
{
    /**
     * Membuat Folder
     *
     * Membuat folder baru pada direktori FileGator berdasarkan
     * nama folder dan lokasi parent yang ditentukan.
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
     * | `parent` | string | Tidak | Lokasi folder induk |
     *
     * ### Contoh Request
     *
     * ```http
     * POST /api/filegator/folder
     * ```
     *
     * ### Contoh Request Body
     *
     * ```json
     * {
     *   "name": "REQ_FOTO",
     *   "parent": "/"
     * }
     * ```
     *
     * ### Contoh Response (201 Created)
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
     * ### Contoh Response (422 Unprocessable Entity)
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
     * ### Contoh Response (502 Bad Gateway)
     *
     * ```json
     * {
     *   "success": false,
     *   "message": "Gagal membuat folder di FileGator"
     * }
     * ```
     *
     * ### Keterangan
     *
     * - `name` wajib diisi dan tidak boleh berupa `.` atau `..`.
     * - `parent` bersifat opsional dengan nilai default `/`.
     * - Jika berhasil, folder dibuat melalui ApiFileGatorService.
     * - Jika gagal pada proses FileGator, API mengembalikan status HTTP 502.
     * - Path response menggunakan format tanpa escape slash.
     */
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
     * Mengunggah file ke folder yang ditentukan pada FileGator.
     * Mendukung format JPG, JPEG, PNG, WEBP, dan PDF
     * dengan ukuran maksimal 10 MB.
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
     * | `file` | file | Ya | JPG, JPEG, PNG, WEBP, PDF. Maksimal 10 MB |
     * | `folder` | string | Ya | Path folder tujuan di FileGator, maksimal 500 karakter |
     *
     * ### Contoh Request
     *
     * ```http
     * POST /api/filegator/upload
     * ```
     *
     * ### Contoh Request Body (form-data)
     *
     * | Key | Type | Value |
     * |-----|------|-------|
     * | `file` | File | foto_produk.png |
     * | `folder` | Text | /REQ_FOTO |
     *
     * ### Contoh Response (201 Created)
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
     * ### Contoh Response (422 Unprocessable Entity)
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
     * ```json
     * {
     *   "success": false,
     *   "message": "Nama file tidak valid"
     * }
     * ```
     *
     * ### Contoh Response (502 Bad Gateway)
     *
     * ```json
     * {
     *   "success": false,
     *   "message": "Gagal upload file ke FileGator"
     * }
     * ```
     *
     * ### Keterangan
     *
     * - File wajib diunggah menggunakan `multipart/form-data`.
     * - Ukuran maksimal file adalah 10 MB (10240 KB).
     * - Format yang diizinkan: JPG, JPEG, PNG, WEBP, PDF.
     * - `folder` wajib diisi dengan path absolut, contoh `/REQ_FOTO`.
     * - Nama file menggunakan nama asli dari file yang diunggah.
     * - Nama file tidak boleh berupa `.` atau `..` maupun mengandung `/` atau `\`.
     * - Jika berhasil, API mengembalikan HTTP 201.
     * - Jika validasi gagal, API mengembalikan HTTP 422.
     * - Jika proses FileGator gagal, API mengembalikan HTTP 502.
     */
    public function upload(
        Request $request,
        ApiFileGatorService $filegator
    ): JsonResponse {
        $data = $request->validate([
            'file' => [
                'required',
                'file',
                'max:10240',
                'mimes:jpg,jpeg,png,webp,pdf',
            ],
            'folder' => [
                'required',
                'string',
                'max:500',
                'regex:/^\/(?:[^\/\\\\.][^\/\\\\]*\/?)*$/',
            ],
        ]);

        $file = $data['file'];
        $folder = $data['folder'];
        $filename = $file->getClientOriginalName();

        if (
            in_array($filename, ['.', '..'], true) ||
            str_contains($filename, '/') ||
            str_contains($filename, '\\')
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Nama file tidak valid',
            ], 422);
        }

        try {
            $filegator->login();

            $filegator->upload(
                $file,
                $filename,
                $folder
            );

            return response()->json([
                'success' => true,
                'message' => 'File berhasil diupload',
                'data' => [
                    'filename' => $filename,
                    'folder' => $folder,
                    'path' => rtrim($folder, '/') . '/' . $filename,
                ],
            ], 201, [], JSON_UNESCAPED_SLASHES);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Gagal upload file ke FileGator',
            ], 502);
        }
    }
}
