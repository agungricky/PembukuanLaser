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
use Dedoc\Scramble\Support\Generator\Operation;
use Dedoc\Scramble\Support\Generator\Response;

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


        Scramble::configure()
            ->withOperationTransformers(function (Operation $operation) {

                $statuses = [
                    200 => 'OK - Permintaan berhasil',
                    201 => 'Created - Data berhasil dibuat',
                    202 => 'Accepted - Permintaan diterima',
                    204 => 'No Content - Berhasil tanpa konten',

                    400 => 'Bad Request - Permintaan tidak valid',
                    401 => 'Unauthorized - Token tidak valid',
                    403 => 'Forbidden - Akses ditolak',
                    404 => 'Not Found - Data tidak ditemukan',
                    405 => 'Method Not Allowed - Metode tidak diizinkan',
                    409 => 'Conflict - Konflik data',
                    413 => 'Content Too Large - Ukuran request terlalu besar',
                    415 => 'Unsupported Media Type - Format tidak didukung',
                    422 => 'Validation Error - Validasi gagal',
                    429 => 'Too Many Requests - Terlalu banyak permintaan',

                    500 => 'Internal Server Error - Kesalahan server',
                    502 => 'Bad Gateway - Kesalahan gateway',
                    503 => 'Service Unavailable - Server tidak tersedia',
                    504 => 'Gateway Timeout - Waktu gateway habis',
                ];

                foreach ($operation->responses ?? [] as $response) {

                    if (! $response instanceof Response) {
                        continue;
                    }

                    $status = (int) $response->code;

                    if (
                        isset($statuses[$status]) &&
                        trim($response->description) === ''
                    ) {
                        $response->setDescription($statuses[$status]);
                    }
                }
            });
    }
}
