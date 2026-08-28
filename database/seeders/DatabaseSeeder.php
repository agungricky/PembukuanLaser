<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'ricko',
            'role' => 'editor',
            'email' => 'editor@example.com',
            'password' => Hash::make('123456')
        ]);

        // User::factory()->create([
        //     'name' => 'santo',
        //     'role' => 'gudang',
        //     'email' => 'test@example.com',
        //     'password' => Hash::make('123456')
        // ]);

        // User::factory()->create([
        //     'name' => 'rega',
        //     'role' => 'produksi',
        //     'email' => 'produksi@example.com',
        //     'password' => Hash::make('123456')
        // ]);

        // $this->call([
        //     KategorisSeeder::class,
        //     EmblemSeeder::class,
        //     KaligrafiSeeder::class
        // ]);
    }
}
