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
        use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;

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
        Gate::define('viewApiDocs', function (User $user) {
            return $user->role === 'developer';
        });

        // Boleh diakses oleh user yang tidak login
        // Gate::define('viewApiDocs', function (?User $user) {
        //     return true;
        // });

        // Merubah Format di dokumentasi dari pesanan ke Pesanan (Tab utama endpoin)
        // Scramble::configure()
        //     ->withDocumentTransformers(function (OpenApi $openApi) {
        //         foreach ($openApi->paths as $path) {
        //             foreach ($path->operations as $operation) {
        //                 $operation->tags = array_map(
        //                     fn($tag) => $tag === 'dashboard'
        //                         ? 'Dashboard'
        //                         : $tag,
        //                     $operation->tags ?? []
        //                 );
        //             }
        //         }
        //     });
    }
}
