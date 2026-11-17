<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\dashboardController;
use App\Http\Controllers\Api\FileGatorController;
use App\Http\Controllers\Api\KategoriController;
use App\Http\Controllers\api\pesananController;
use App\Http\Controllers\Api\ProdukController;
use App\Http\Controllers\Api\RingkasanController;
use App\Http\Controllers\Api\WilayahController as ApiWilayahController;
use App\Http\Controllers\wilayahController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ChatKlarifikasiPackingController;

Route::post('/login', [AuthController::class, 'login'])->name('login');

Route::middleware(['auth:sanctum'])->name('api.')->group(function () {
    // Auth
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::prefix('chat-klarifikasi-packing')
        ->name('chat-klarifikasi-packing.')
        ->group(function () {

            // Daftar percakapan
            Route::get('/', [
                ChatKlarifikasiPackingController::class,
                'index'
            ])->name('index');

            // Packing membuat percakapan baru
            Route::post('/', [
                ChatKlarifikasiPackingController::class,
                'store'
            ])->name('store');

            // Detail percakapan dan pesan
            Route::get('/{chatId}', [
                ChatKlarifikasiPackingController::class,
                'show'
            ])->whereNumber('chatId')->name('show');

            // Packing atau CS mengirim pesan
            Route::post('/{chatId}/pesan', [
                ChatKlarifikasiPackingController::class,
                'send'
            ])->whereNumber('chatId')->name('send');

            // Menandai pesan yang sudah dibaca oleh CS atau Packing
            Route::post('/{chatId}/dibaca', [
                ChatKlarifikasiPackingController::class,
                'markRead'
            ])->whereNumber('chatId')->name('mark-read');

            // Packing menyelesaikan klarifikasi
            Route::post('/{chatId}/selesai', [
                ChatKlarifikasiPackingController::class,
                'finish'
            ])->whereNumber('chatId')->name('finish');
        });

    Route::middleware(['role:cs'])->group(function () {
        // Dashboard
        Route::get('/dashboard/cs', [dashboardController::class, 'dashboardCs'])->name('dashboard.cs');

        // Kategori
        Route::get('/kategoris', [KategoriController::class, 'index'])->name('kategori.index');

        // Produk
        Route::get('/produk/{kategori}', [ProdukController::class, 'index'])->name('produk.index');

        // Ringkasan
        Route::get('/ringkasan/cs', [RingkasanController::class, 'cs'])->name('ringkasan.cs');

        // Pesanan
        Route::prefix('pesanan')->controller(pesananController::class)->name('pesanan.')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/detail/{no_pesanan}', 'detail')->name('detail');
            Route::get('/create', 'create')->name('create');
            Route::post('/store', 'store')->name('store');
        });

        // Wilayah
        Route::prefix('wilayah')->controller(ApiWilayahController::class)->group(function () {
            Route::get('/provinsi', 'provinsi');
            Route::get('/kabupaten/{provinsiId}', 'kabupatenKota');
            Route::get('/kecamatan/{kabupatenId}', 'kecamatan');
            Route::get('/desa/{kecamatanId}', 'desa');
            Route::get('/alamat/{desaId}', 'alamatLengkap');
        });

        // Storage / Drive
        Route::prefix('filegator')->name('filegator.')
            ->controller(FileGatorController::class)
            ->group(function () {
                Route::post('/folders', 'createFolder');
                Route::post('/upload', 'upload');
                Route::get('/file', 'show')->name('file');
            });
    });
});
