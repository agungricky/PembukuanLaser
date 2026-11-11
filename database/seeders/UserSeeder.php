<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['name' => 'santo'],
            [
                'role' => 'gudang',
                'email' => 'test@example.com',
                'password' => Hash::make('123456'),
            ]
        );

        User::firstOrCreate(
            ['name' => 'rega'],
            [
                'role' => 'produksi',
                'email' => 'produksi@example.com',
                'password' => Hash::make('123456'),
            ]
        );

        User::firstOrCreate(
            ['name' => 'pras'],
            [
                'role' => 'editor',
                'email' => 'pras@gmail.com',
                'password' => Hash::make('rico2345'),
            ]
        );

        User::firstOrCreate(
            ['name' => 'dev'],
            [
                'role' => 'developer',
                'email' => 'dev@gmail.com',
                'password' => Hash::make('dev2026'),
            ]
        );

        User::firstOrCreate(
            ['name' => 'bambang'],
            [
                'role' => 'manager',
                'email' => 'manager@gmail.com',
                'password' => Hash::make('123456'),
            ]
        );

        User::firstOrCreate(
            ['name' => 'ika'],
            [
                'role' => 'cs',
                'email' => 'cs@gmail.com',
                'password' => Hash::make('123456'),
            ]
        );
    }
}
