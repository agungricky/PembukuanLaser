<?php

namespace App\Services\Storage;

use App\Services\Api\FileGatorService as ApiFileGatorService;


class DriveService
{
    public function createFolder($filegator, $data): array
    {
        $name = $data['name'];
        $parent = $data['parent'] ?? '/';

        $filegator->login();
        $filegator->createFolder($name, $parent);

        return [
            'name' => $name,
            'path' => rtrim($parent, '/') . '/' . $name,
        ];
    }


    public function uploadFiles($filegator, array $data): array
    {
        $file = $data['file'];
        $folder = $data['folder'];
        $filename = $file->getClientOriginalName();

        if (
            in_array($filename, ['.', '..'], true) ||
            str_contains($filename, '/') ||
            str_contains($filename, '\\')
        ) {
            return [
                'success' => false,
                'message' => 'Nama file tidak valid',
            ];
        }

        $filegator->login();
        $filegator->upload($file, $filename, $folder);
        return [
            'success' => true,
            'message' => 'File berhasil diupload',
            'data' => [
                'filename' => $filename,
                'folder' => $folder,
                'path' => rtrim($folder, '/') . '/' . $filename,
            ],
        ];
    }
}
