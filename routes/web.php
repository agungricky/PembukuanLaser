<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EditorController;
use App\Http\Controllers\GudangController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\IklanController;
use App\Http\Controllers\kategoriController;
use App\Http\Controllers\kesalahanController;
use App\Http\Controllers\PackingPesananController;
use App\Http\Controllers\PembeliController;
use App\Http\Controllers\PenjualanController;
use App\Http\Controllers\PesananAffiliateController;
use App\Http\Controllers\PesananBatalController;
use App\Http\Controllers\PesananCekController;
use App\Http\Controllers\PesananController;
use App\Http\Controllers\PesananDetailController;
use App\Http\Controllers\PesananDiterimaController;
use App\Http\Controllers\PesananKirimController;
use App\Http\Controllers\PesananProsesController;
use App\Http\Controllers\ProdukController;
use App\Http\Controllers\produkcustomController;
use App\Http\Controllers\ResiImportController;
use App\Http\Controllers\SkuController;
use App\Http\Controllers\stokProdukController;
use App\Http\Controllers\TokoController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function () {

    Route::get('/user/{role}', [UserController::class, 'datauser'])->name('user.data');

    // ===================== ADMIN PENJUALAN ===================== //
    Route::middleware(['role:pegawai'])->group(function () {
        Route::get('/me', [UserController::class, 'me'])->name('users.me');
        Route::resource('/kesalahan', kesalahanController::class);
        
        Route::post('/produk/export', [ProdukController::class, 'export'])->name('produk.export');
        Route::post('/produk/import', [ProdukController::class, 'import'])->name('produk.import');
        Route::post('/produk/import/confirm', [ProdukController::class, 'confirmImport'])->name('produk.import.confirm');

        Route::resource('/kategori', kategoriController::class);
    });

    // ===================== MANAGER ===================== //
    Route::middleware(['role:manager'])->group(function () {
        Route::resource('users', UserController::class)->only(['index', 'store', 'update', 'destroy']);
    });

    // ===================== EDITOR ===================== //
    Route::prefix('editor') ->name('editor.')->middleware('auth')->group(function () {
        Route::get('/', [EditorController::class, 'index'])
            ->name('index');

        Route::get('/part', [EditorController::class, 'partIndex'])
            ->name('part.index');

        Route::get('/part/{part}', [EditorController::class, 'partShow'])
            ->name('part.show');

        Route::get('/part/{part}/download', [EditorController::class, 'downloadPlat'])
            ->name('part.download');

        Route::get('/menunggu', [EditorController::class, 'menungguIndex'])
            ->name('menunggu.index');

        Route::post('/menunggu/{partItem}/siap', [EditorController::class, 'menungguSiap'])
            ->name('menunggu.siap');

        Route::get('/import', [EditorController::class, 'importPage'])
            ->name('import.page');

        Route::post('/import', [EditorController::class, 'importEditor'])
            ->name('import');

        Route::get('/riwayat', [EditorController::class, 'riwayatIndex'])
            ->name('riwayat.index');

        Route::get('/part/{part}/qr-pdf',[EditorController::class, 'downloadQrPart'])
            ->name('part.qr.pdf');
    });

    // ===================== PACKING ===================== //
    Route::middleware(['role:packing'])->group(function () {

        Route::get('/packing/pesanan', [PackingPesananController::class, 'index'])
            ->name('packing.pesanan');

        Route::post('/packing/scan', [PackingPesananController::class, 'scan'])
            ->name('packing.scan');

        Route::get('/packing/stats', [PackingPesananController::class, 'stats'])
            ->name('packing.stats');

        Route::get('/packing/cetak-resi', [PackingPesananController::class, 'cetakIndex'])
            ->name('packing.cetak.index');

        Route::post('/packing/cari-request', [PackingPesananController::class, 'cariRequest'])
            ->name('packing.cariRequest');

        Route::post('/packing/cetak-resi', [PackingPesananController::class, 'cetakResi'])
            ->name('packing.cetakResi');

    });

    // ===================== Admin & Manager ===================== //
    Route::middleware(['role:manager,pegawai'])->group(function () {
        // Dashboard
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Detail Pesanan

        // Pesanan
        Route::prefix('pesanan')
            ->controller(PesananController::class)
            ->group(function () {

                // halaman import baru
                Route::get('import', 'importPage')->name('pesanan.import');

                // proses import
                Route::post('preview-upload', 'uploadPreviewExcel')->name('pesanan.preview');
                Route::get('preview-data', 'getPreviewData')->name('pesanan.getPreview');
                Route::post('simpan-import', 'simpanImport')->name('pesanan.simpanImport');

                // ajax toko berdasarkan marketplace
                Route::get('get-toko/{market}', 'getTokoByMarketplace')->name('pesanan.getToko');

                Route::get('pesanan-detail/{id}', 'pesananDetail')->name('pesanan.detail');
            });

        Route::prefix('resi')->controller(ResiImportController::class)
            ->group(function () {
                Route::get('/import', 'index')->name('resi.import');
                Route::post('/preview', 'preview')->name('resi.preview');
                Route::post('/simpan', 'store')->name('resi.store');
            });

        Route::controller(PesananDetailController::class)
            ->group(function () {
                Route::get('pesanan/{no_pesanan}', 'show')->name('pesanan.show');
                Route::put('pesanan/{no_pesanan}', 'update')->name('pesanan.update');
            });

        Route::resource('pesanan', PesananController::class)
            ->except([
                'show',
            ]);

        // Pesanan Proses
        Route::get('/proses', [PesananProsesController::class, 'index'])->name('pesanan.proses');
        Route::post('/pesanan/ubah-status', [PesananProsesController::class, 'ubahStatus'])->name('pesanan.ubahStatus');

        // Pesanan KIRIM
        Route::get('/kirim', [PesananKirimController::class, 'index'])->name('pesanan.kirim');
        Route::post('/kirim/ubah-status', [PesananKirimController::class, 'ubahStatus'])->name('kirim.ubahStatus');
        Route::get('/kirim/import', [PesananKirimController::class, 'importPage'])->name('kirim.importPage');
        Route::post('/kirim/preview-pencairan', [PesananKirimController::class, 'previewPencairan'])->name('kirim.previewPencairan');
        Route::get('/kirim/preview-data', [PesananKirimController::class, 'getPreviewPencairan'])->name('kirim.getPreviewPencairan');
        Route::post('/kirim/simpan-pencairan', [PesananKirimController::class, 'simpanPencairan'])->name('kirim.simpanPencairan');

        // Pesanan Diterima
        Route::get('/terima', [PesananDiterimaController::class, 'index'])->name('pesanan.terima');

        // Pesanan Batal
        Route::get('/return', [PesananBatalController::class, 'index'])->name('pesanan.return');

        // Pesanan Afiliate
        Route::get('/affiliate', [PesananAffiliateController::class, 'index'])->name('pesanan.affiliate');

        // Pesanan Cek
        Route::prefix('cek')
            ->controller(PesananCekController::class)
            ->group(function () {

                Route::get('/', 'index')->name('pesanan.cek');
                Route::post('/{no_pesanan}/aktifkan', 'aktifkan')->name('pesanan.cek.aktifkan');
                Route::post('/{no_pesanan}/selesai', 'selesai')->name('pesanan.cek.selesai');
            });

        // Toko
        Route::resource('toko', TokoController::class)->only(['index', 'store', 'update', 'destroy', 'show']);

        // SKU
        Route::resource('sku', SkuController::class)->only(['index', 'store', 'edit', 'update', 'destroy', 'show']);
        Route::get('sku-json', [SkuController::class, 'skudata'])->name('sku.json');
        Route::post('/sku-view', [SkuController::class, 'viewstore'])->name('sku.viewstore');

        // Iklan
        Route::resource('iklan', IklanController::class)->only(['index', 'store', 'update', 'destroy', 'show']);
        Route::get('/ajax/marketplace', [IklanController::class, 'getMarketplace'])->name('ajax.marketplace');
        Route::get('/ajax/toko-by-marketplace', [IklanController::class, 'getTokoByMarketplace'])->name('ajax.toko.marketplace');

        // Penjualan
        Route::get('/penjualan', [PenjualanController::class, 'performance'])->name('penjualan');

        // Pembeli
        Route::get('/pembeli', [PembeliController::class, 'index'])->name('pembeli');
        Route::post('/pembeli/filter', [PembeliController::class, 'storeFilter'])->name('pembeli.filter');
        Route::post('/pembeli/reset', [PembeliController::class, 'resetFilter'])->name('pembeli.reset');

        // Produk
        Route::get('/produk', [ProdukController::class, 'index'])->name('produk');
    });

    Route::middleware(['role:gudang'])->group(function () {
        Route::get('/gudang', [GudangController::class, 'gudang'])->name('gudang.index');
        Route::get('/gudang/pesanan', [GudangController::class, 'gudanginventory'])->name('pesanan.json');
        Route::get('/semua-pesanan', [GudangController::class, 'allindex'])->name('allpesanan.index');
        Route::get('/semua-pesanan/{filter}', [GudangController::class, 'allpesanan'])->name('allpesanan.json');
        Route::resource('/transaksi', GudangController::class);
        Route::get('/show/{filter}', [GudangController::class, 'showdata'])->name('showdata.json');
        Route::post('/transaksi/update-status', [GudangController::class, 'updateStatus'])->name('transaksi.updatestatus');
        Route::get('/semua-produk', [GudangController::class, 'produk'])->name('gudang.produk');
        Route::get('/semua-produk/{sku}', [GudangController::class, 'produkShow'])->name('produkshow.json');
        Route::patch('/update-stok/{sku}', [GudangController::class, 'updatestok'])->name('updatestok.json');
        Route::get('/kategori-produk', [GudangController::class, 'kategori'])->name('gudang.kategori');
        Route::get('/riwayat-aktivitas/gudang', [GudangController::class, 'riwayataktivitas'])->name('gudang.aktivitas');
        Route::get('/riwayat-aktivitas/data', [GudangController::class, 'riwayatAktivitasData'])->name('gudang.riwayataktivitas.data');
        Route::get('/card-detail/{card}', [GudangController::class, 'detailcard'])->name('gudang.detailcard.json');
        Route::get('/kebutuhan/detail-pesanan/{filter}/{sku}', [GudangController::class, 'detailpesanan'])->name('kebutuhan.detailpesanan');
        Route::get('/sampel', [GudangController::class, 'barangsampel'])->name('gudang.sampel');
        Route::post('/sampel/create', [GudangController::class, 'sampelcreate'])->name('gudang.sampel.create');
        Route::get('/retur', [GudangController::class, 'barangretur'])->name('gudang.retur');
        Route::get('/retur/perpesanan/{no_pesanan}', [GudangController::class, 'detailRetur'])->name('gudang.retur.json');
        Route::post('/gudang/retur/create', [GudangController::class, 'returCreate'])->name('gudang.retur.create');
        Route::get('/produk-custom', [GudangController::class, 'produkcustom'])->name('produk-custom.index');
    });
});

Route::get('/kategori-produk/{id}', [GudangController::class, 'kategorishow'])->name('gudang.kategori.json');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/', [HomeController::class, 'index']);
Route::get('/emblem', [HomeController::class, 'emblem']);
Route::get('/punyalcknihbossenggoldong', [HomeController::class, 'gancinama']);
Route::get('/cek-template-editor', function () {
    $path = storage_path('app/templates/editor_plat.xlsx');

    return [
        'path' => $path,
        'exists' => file_exists($path),
        'readable' => is_readable($path),
        'size' => file_exists($path)
            ? filesize($path)
            : null,
    ];
});