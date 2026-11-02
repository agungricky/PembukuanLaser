<?php

namespace App\Providers;

use App\Models\kategori;
use App\Models\Produk;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;

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


        // ============ Permision Akses Api Key =============== //
        // Hanya boleh diakses oleh role tertentu
        Gate::define('viewApiDocs', function (User $user) {
            return $user->role === 'pegawai';
        });

        // Boleh diakses oleh user yang tidak login
        // Gate::define('viewApiDocs', function (?User $user) {
        //     return true;
        // });
    }
}
