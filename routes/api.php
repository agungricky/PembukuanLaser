<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\ProdukController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])->name('login');

Route::middleware(['auth:sanctum'])->name('api.')->group(function () {
    Route::get('/produk-sku', [ProdukController::class, 'produkshow'])->name('produk.json');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});