<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\dashboardController;
use App\Http\Controllers\Api\FileGatorController;
use App\Http\Controllers\Api\KategoriController;
use App\Http\Controllers\api\pesananController;
use App\Http\Controllers\Api\ProdukController;
use App\Http\Controllers\Api\RingkasanController;
use App\Http\Controllers\Api\WilayahController as ApiWilayahController;
use Illuminate\Support\Facades\Route;
use App\Services\GoogleSheetsService;
use Illuminate\Http\Request;

Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::get('/file', [FileGatorController::class, 'show'])
    ->withoutMiddleware('auth:sanctum')
    ->middleware('signed')
    ->name('file');

Route::middleware(['auth:sanctum'])->name('api.')->group(function () {
    // Auth
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::post('/google-sheets/test', function (
        Request $request,
        GoogleSheetsService $googleSheets
    ) {
        $data = $request->validate([

            // INFORMASI PESANAN
            'id_pesanan' => [
                'required',
                'string',
                'max:100',
            ],

            'tgl_order' => [
                'required',
                'date_format:Y-m-d H:i:s',
            ],

            'nama_cs' => [
                'nullable',
                'string',
                'max:100',
            ],

            'nama_pembeli' => [
                'required',
                'string',
                'max:150',
            ],

            'no_hp' => [
                'required',
                'string',
                'max:30',
            ],

            // DATA TAMBAHAN (TIDAK DIGUNAKAN DI SHEET)
            'id_tele_cs' => ['nullable', 'string', 'max:100'],
            'alamat' => ['nullable', 'string'],
            'jenis_pembayaran' => ['nullable', 'in:COD,NON-COD,DP'],
            'total_harga' => ['nullable', 'numeric', 'min:0'],
            'dp' => ['nullable', 'numeric', 'min:0'],

            // FORMAT SATU PRODUK
            'kategori_produk' => [
                'required_without:produk',
                'nullable',
                'string',
                'max:255',
            ],

            'jumlah' => [
                'required_without:produk',
                'nullable',
                'integer',
                'min:1',
            ],

            'notes' => [
                'nullable',
                'string',
            ],

            // FOTO FORMAT SATU PRODUK
            'reqfoto' => [
                'sometimes',
                'array',
                'max:20',
            ],

            'reqfoto.*.foto' => [
                'required',
                'url',
                'max:2048',
            ],

            // FORMAT BANYAK PRODUK
            'produk' => [
                'sometimes',
                'array',
                'min:1',
                'max:100',
            ],

            'produk.*.kategori_produk' => [
                'required',
                'string',
                'max:255',
            ],

            'produk.*.jumlah' => [
                'required',
                'integer',
                'min:1',
            ],

            'produk.*.notes' => [
                'nullable',
                'string',
            ],

            // FOTO PER PRODUK
            'produk.*.reqfoto' => [
                'sometimes',
                'array',
                'max:20',
            ],

            'produk.*.reqfoto.*.foto' => [
                'required',
                'url',
                'max:2048',
            ],

            // KOMPATIBILITAS FORMAT LAMA
            'link_foto_drive' => [
                'nullable',
                'string',
                'max:2048',
            ],
        ]);

        return response()->json(
            $googleSheets->simpanPesanan($data)
        );
    })->middleware('auth:sanctum')
        ->name('google-sheets.test');


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
            });
    });
});
