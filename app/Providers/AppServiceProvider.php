<?php

namespace App\Providers;

use App\Models\Exporter;
use App\Models\kategori;
use App\Models\PesananPerProduk;
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

            $reguler = Exporter::where('user_id', Auth::id())
                ->where('status', 'proses')
                ->where('source_type', 'reguler')
                ->first();

            $jumlahReguler = $reguler
                ? PesananPerProduk::where('tracking', $reguler->id)
                    ->distinct()
                    ->count('sku')
                : 0;
                
            $produksi = [
                'reguler' => $jumlahReguler,
            ];

            $view->with([
                'dataLogin' => $dataLogin,
                'countProduk' => $countProduk,
                'countKategori' => $countKategori,
                'produksi' => $produksi,
            ]);
        });
    }
}
