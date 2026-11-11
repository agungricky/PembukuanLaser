<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Services\Pesanan\PesananService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class pesananController extends Controller
{
    public function index(PesananService $pesananService)
    {
        $pesanan = $pesananService->index();

        return response()->json([
            'success' => true,
            'data' => $pesanan
        ]);
    }

    public function store(PesananService $pesananService, Request $request)
    {
        $request->validate([
            // 01. DATA PESANAN
            'no_pesanan' => 'required|string|unique:pesanan,no_pesanan',
            'platform' => 'required|in:marketplace,meta',
            'tanggal' => 'required|date|before_or_equal:now',
            'tanggal_order' => 'required|date|before_or_equal:now',
            'user_id' => 'required|integer|exists:users,id',
            'nama_pembeli' => 'required|string|max:255',
            'total_hpp' => 'required|integer|min:0',
            'total_harga' => 'required|integer|min:0',
            'total_admin' => 'required|integer|min:0',
            'chat_masuk_at' => 'required|date|before_or_equal:now',
            'resi_print_count' => 'required|int',

            // 02. ALAMAT PENGIRIMAN
            'provinsi' => 'required|string',
            'kabupaten_kota' => 'required|string',
            'kecamatan' => 'required|string',
            'desa' => 'required|string',
            'alamat_lengkap' => 'required|string|max:1000',
            'phone' => ['required', 'regex:/^628[0-9]{7,12}$/'],

            // 03. PEMBAYARAN
            'jenis_transaksi' => 'required|in:cod,non-cod,dp',
            'cod' => 'nullable|numeric|min:0',
            'non_cod' => 'nullable|numeric|min:0',
            'dp' => 'nullable|numeric|min:0',
            'bukti_bayar' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',

            // 04. PESANAN PER PRODUK
            'pesanan_perproduk' => 'required|array|min:1',
            'pesanan_perproduk.*.nama_produk' => 'required|string',
            'pesanan_perproduk.*.variasi' => 'nullable|string',
            'pesanan_perproduk.*.jumlah' => 'required|integer|min:1',
            'pesanan_perproduk.*.hpp' => 'required|numeric|min:0',
            'pesanan_perproduk.*.harga' => 'required|numeric|min:0',
            'pesanan_perproduk.*.sku' => 'required|string',
            'pesanan_perproduk.*.custom' => 'nullable|string',
            'pesanan_perproduk.*.status_pesanan' => 'nullable|integer',
            'pesanan_perproduk.*.mutasi_stok_id' => 'nullable|integer',
            'pesanan_perproduk.*.catatan' => 'nullable|string',
            'pesanan_perproduk.*.foto_produk' => 'nullable|file|mimes:jpg,jpeg,png|max:5120',
        ]);

        DB::beginTransaction();

        try {
            $pesanan = $pesananService->store($request);
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pesanan berhasil dibuat',
                'data' => $pesanan
            ], 201);
        } catch (\Throwable $th) {
            DB::rollBack();
            report($th);
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat pesanan',
                'error' => config('app.debug') ? $th->getMessage() : null
            ], 500);
        }
    }
}
