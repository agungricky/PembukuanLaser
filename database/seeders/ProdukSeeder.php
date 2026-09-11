<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProdukSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
         $data = [
            ['nama_produk' => 'BUKAN HASIL KORUPSI', 'variasi' => 'GOLD',   'sku' => 'EMB0733', 'hpp' => 9000],
            ['nama_produk' => 'BUKAN HASIL KORUPSI', 'variasi' => 'SILVER', 'sku' => 'EMB0734', 'hpp' => 9000],
            ['nama_produk' => 'BUKAN HASIL KORUPSI', 'variasi' => 'MERAH',  'sku' => 'EMB0735', 'hpp' => 9000],
            ['nama_produk' => 'BUKAN HASIL KORUPSI', 'variasi' => 'BIRU',   'sku' => 'EMB0736', 'hpp' => 9000],
            ['nama_produk' => 'BUKAN HASIL KORUPSI', 'variasi' => 'HITAM',  'sku' => 'EMB0737', 'hpp' => 9000],
            ['nama_produk' => 'BUKAN HASIL KORUPSI', 'variasi' => 'HIJAU',  'sku' => 'EMB0738', 'hpp' => 9000],
            ['nama_produk' => 'BUKAN HASIL KORUPSI', 'variasi' => 'PINK',   'sku' => 'EMB0739', 'hpp' => 9000],

            ['nama_produk' => 'Shirohige', 'variasi' => 'GOLD',   'sku' => 'EMB0740', 'hpp' => 9000],
            ['nama_produk' => 'Shirohige', 'variasi' => 'SILVER', 'sku' => 'EMB0741', 'hpp' => 9000],
            ['nama_produk' => 'Shirohige', 'variasi' => 'MERAH',  'sku' => 'EMB0742', 'hpp' => 9000],
            ['nama_produk' => 'Shirohige', 'variasi' => 'BIRU',   'sku' => 'EMB0743', 'hpp' => 9000],
            ['nama_produk' => 'Shirohige', 'variasi' => 'HITAM',  'sku' => 'EMB0744', 'hpp' => 9000],
            ['nama_produk' => 'Shirohige', 'variasi' => 'HIJAU',  'sku' => 'EMB0745', 'hpp' => 9000],
            ['nama_produk' => 'Shirohige', 'variasi' => 'PINK',   'sku' => 'EMB0746', 'hpp' => 9000],
        ];

        foreach ($data as $item) {
            DB::table('produk')->updateOrInsert(
                ['sku' => $item['sku']],
                [
                    'nama_produk' => $item['nama_produk'],
                    'variasi' => $item['variasi'],
                    'hpp' => $item['hpp'],
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }
}
