<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProdukController as ApiProdukController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])->name('login');

Route::middleware(['auth:sanctum'])->name('api.')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');


    Route::get('/produk-sku', [ApiProdukController::class, 'produkshow'])->name('produk.json');

});