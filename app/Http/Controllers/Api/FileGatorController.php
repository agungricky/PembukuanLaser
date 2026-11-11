<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Api\FileGatorService as ApiFileGatorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class FileGatorController extends Controller
{
    public function createFolder(
        Request $request,
        ApiFileGatorService $filegator
    ): JsonResponse {
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                'regex:/^[^\/\\\\]+$/',
                'not_in:.,..',
            ],
            'parent' => [
                'nullable',
                'string',
                'max:500',
                'regex:/^\/(?:[^\/\\\\.][^\/\\\\]*\/?)*$/',
            ],
        ]);

        $name = $data['name'];
        $parent = $data['parent'] ?? '/';

        try {
            $filegator->login();
            $filegator->createFolder($name, $parent);

            return response()->json([
                'success' => true,
                'message' => 'Folder berhasil dibuat',
                'data' => [
                    'name' => $name,
                    'path' => rtrim($parent, '/') . '/' . $name,
                ],
            ], 201, [], JSON_UNESCAPED_SLASHES);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat folder di FileGator',
            ], 502);
        }
    }

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
