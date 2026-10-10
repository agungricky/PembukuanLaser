<?php

namespace App\Providers;

use App\Models\Exporter;
use App\Models\kategori;
use App\Models\PesananPerProduk;
use App\Models\Produk;
use App\Models\stok_produk;
use Carbon\Carbon;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Carbon::setLocale('id');
        Paginator::useBootstrapFive();
        View::composer('*', function ($view) {
            // Data Login Untuk semua Halaman
            $dataLogin = Auth::user();

            // Sidebar untuk menu jumlah sku & Kategori
            $countProduk = Produk::count();
            $countKategori = kategori::count();

            $view->with([
                'dataLogin' => $dataLogin,
                'countProduk' => $countProduk,
                'countKategori' => $countKategori,
            ]);
        });
    }
}
