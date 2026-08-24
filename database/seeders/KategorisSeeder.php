<?php

namespace Database\Seeders;

use App\Models\kategori;
use Illuminate\Database\Seeder;

class KategorisSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $kategori = ['EMBLEM', 'KALIGRAFI'];

        foreach ($kategori as $value) {
            kategori::updateOrCreate(
                [
                    'nama_kategori' => $value,
                ],
                [
                    'nama_kategori' => $value,
                ]
            );
        }

        // Stntax untuk Rollback
        // SELECT * FROM kategoris WHERE DATE(created_at) = '2026-08-24';
        // DELETE FROM kategoris WHERE DATE(created_at) = '2026-08-24';
    }
}
