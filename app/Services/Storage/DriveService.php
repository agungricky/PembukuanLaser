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
}
