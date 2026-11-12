<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\dashboardController;
use App\Http\Controllers\Api\FileGatorController;
use App\Http\Controllers\Api\KategoriController;
use App\Http\Controllers\api\pesananController;
use App\Http\Controllers\Api\ProdukController;
use App\Http\Controllers\wilayahController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])->name('login');

Route::middleware(['auth:sanctum'])->name('api.')->group(function () {
    // Auth
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::middleware(['role:cs'])->group(function () {
        // Dashboard
        Route::get('/dashboard/cs/{user_id}', [dashboardController::class, 'dashboardCs'])->name('dashboard.cs');

        // Kategori
        Route::get('/kategoris', [KategoriController::class, 'index'])->name('kategori.index');

        // Produk
        Route::get('/produk/{kategori}', [ProdukController::class, 'index'])->name('produk.index');

        // Pesanan
        Route::get('/pesanan', [pesananController::class, 'create'])->name('pesanan.index');
        Route::post('/pesanan/store', [pesananController::class, 'store'])->name('pesanan.store');

        // Wilayah
        Route::prefix('wilayah')->controller(wilayahController::class)->group(function () {
            Route::get('/provinsi', 'provinsi');
            Route::get('/kabupaten/{provinsiId}', 'kabupatenKota');
            Route::get('/kecamatan/{kabupatenId}', 'kecamatan');
            Route::get('/desa/{kecamatanId}', 'desa');
            Route::get('/alamat/{desaId}', 'alamatLengkap');
        });

        // Storage / Drive
        Route::prefix('filegator')->group(function () {
            Route::post('/folders', [FileGatorController::class, 'createFolder']);
            Route::post('/upload', [FileGatorController::class, 'upload']);
        });
    });
});
