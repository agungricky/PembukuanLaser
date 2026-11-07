<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\KategoriController;
use App\Http\Controllers\Api\ProdukController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])->name('login');

Route::middleware(['auth:sanctum'])->name('api.')->group(function () {
    // Auth
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // ================================================== //
    // =================== CS =========================== //
    // ================================================== //

    // Kategori
    Route::get('/kategoris', [KategoriController::class, 'index'])->name('kategori.index');

    // Produk
    Route::get('/produk/{kategori}', [ProdukController::class, 'index'])->name('produk.index');
});