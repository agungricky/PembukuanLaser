<?php

namespace App\Providers;

use App\Models\kategori;
use App\Models\Produk;
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
            $dataLogin = Auth::user();
            $countProduk = Produk::count();
            $countKategori = kategori::count();
            $produksiMenipis = Produk::with('stok_produk')
            ->whereNotNull('nama_produk')
            ->where(function ($query) {
                $query->whereDoesntHave('stok_produk')
                    ->orWhereHas('stok_produk', function ($query) {
                        $query->where('jumlah_tersedia', '<', 5);
                    });
            })->count();

            $view->with([
                'dataLogin' => $dataLogin,
                'countProduk' => $countProduk,
                'countKategori' => $countKategori,
                'produksiMenipis' => $produksiMenipis
            ]);
        });
    }
}
