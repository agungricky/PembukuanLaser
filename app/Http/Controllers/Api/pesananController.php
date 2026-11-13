<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Services\Pesanan\PesananService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Dedoc\Scramble\Attributes\Group;

#[Group('Pesanan')]
class pesananController extends Controller
{
    public function index(PesananService $pesananService, Request $request)
    {
        $request->validate([
            'search' => 'nullable|string|max:255',
            'status' => 'nullable|in:proses,kirim,selesai,batal,affiliate,pengembalian,pengiriman_gagal',
        ]);
        $data = $pesananService->index($request);

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    public function detail(PesananService $pesananService, $no_pesanan){
        $data = $pesananService->detailpesanan($no_pesanan);
        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * Create Pesanan
     *
     * Mengambil nomor pesanan secara otomatis saat akan buat pesanan.
     *
     * ### Header Request
     *
     * ```http
     * Authorization: Bearer {token}
     * Accept: application/json
     * ```
     *
     * ### Contoh Request
     *
     * ```http
     * GET /api/pesanan
     * ```
     *
     * ### Contoh Response (200 OK)
     *
     * ```json
     * {
     *   "success": true,
     *   "data": {
     *     "no_pesanan": "261112001I",
     *     "user": {
     *       "id": 35,
     *       "name": "dev"
     *     }
     *   }
     * }
     * ```
     *
     * ### Keterangan
     *
     * - No pesanan diambil dari nomor pesanan terakhir `+1`.
     */
    public function create(PesananService $pesananService)
    {
        $pesanan = $pesananService->create();

        return response()->json([
            'success' => true,
            'data' => $pesanan
        ]);
    }

    /**
     * Store Pesanan
     *
     * Membuat pesanan baru dari platform Marketplace atau Meta.
     * Satu pesanan dapat memiliki beberapa produk dengan request 
     * foto custom dan catatan masing-masing.
     *
     * ## Header Request
     *
     * ```
     * Accept: application/json
     * Authorization: Bearer {token}
     * Content-Type: multipart/form-data
     * ```
     *
     * ## Parameter Request
     *
     * ### 01. Data Pesanan
     *
     * | Parameter | Tipe | Wajib | Keterangan |
     * |-----------|------|-------|------------|
     * | no_pesanan | string | Ya | Nomor pesanan unik |
     * | platform | string | Ya | marketplace / meta |
     * | tanggal | datetime | Ya | Tanggal pesanan |
     * | tanggal_order | datetime | Ya | Tanggal order |
     * | user_id | integer | Ya | ID pengguna / CS |
     * | nama_pembeli | string | Ya | Nama pembeli |
     * | total_hpp | integer | Ya | Total HPP |
     * | total_harga | integer | Ya | Total harga jual |
     * | total_admin | integer | Ya | Biaya admin |
     * | chat_masuk_at | datetime | Ya | Waktu chat masuk |
     * | resi_print_count | integer | Ya | Jumlah cetak resi |
     *
     * ### 02. Alamat Pengiriman
     *
     * | Parameter | Tipe | Wajib | Keterangan |
     * |-----------|------|-------|------------|
     * | provinsi | string | Ya | Kode provinsi |
     * | kabupaten_kota | string | Ya | Kode kabupaten/kota |
     * | kecamatan | string | Ya | Kode kecamatan |
     * | desa | string | Ya | Kode lengkap desa |
     * | alamat_lengkap | string | Ya | Alamat pembeli |
     * | phone | string | Ya | Nomor telepon format 628.... |
     *
     * ### 03. Pembayaran
     *
     * | Parameter | Tipe | Wajib | Keterangan |
     * |-----------|------|-------|------------|
     * | jenis_transaksi | string | Ya | cod / non-cod / dp |
     * | cod | numeric | Tidak | Nominal COD |
     * | non_cod | numeric | Tidak | Nominal NON-COD |
     * | dp | numeric | Tidak | Nominal uang muka |
     * | bukti_bayar | file | Tidak | JPG, PNG, PDF maks. 5 MB |
     *
     * ### 04. Pesanan Per Produk
     *
     * Parameter `pesanan_perproduk` berupa array.
     * Minimal 1 produk dan dapat berisi banyak produk.
     *
     * | Parameter | Tipe | Wajib | Keterangan |
     * |-----------|------|-------|------------|
     * | nama_produk | string | Ya | Nama produk |
     * | variasi | string | Ya | Variasi produk |
     * | jumlah | integer | Ya | Jumlah produk |
     * | hpp | numeric | Ya | HPP per satuan |
     * | harga | numeric | Ya | Harga jual per satuan |
     * | sku | string | Ya | SKU produk |
     * | custom | string | Ya | Detail custom |
     * | status_pesanan | integer | Tidak | Status produk |
     * | mutasi_stok_id | integer | Tidak | ID mutasi stok |
     * | catatan | string | Ya | Catatan editor |
     * | foto_produk | file | Ya | Foto JPG/PNG maks. 5 MB |
     *
     * ## Contoh Request
     *
     * Contoh data pesanan utama:
     *
     * ```json
     * {
     *   "no_pesanan": "261111004A",
     *   "platform": "meta",
     *   "tanggal": "2026-11-11 11:30:00",
     *   "tanggal_order": "2026-11-11 11:30:00",
     *   "user_id": 33,
     *   "nama_pembeli": "Yanto",
     *   "total_hpp": 45000,
     *   "total_harga": 150000,
     *   "total_admin": 10000,
     *   "chat_masuk_at": "2026-11-11 11:30:00",
     *   "resi_print_count": 0,
     *   "provinsi": "35",
     *   "kabupaten_kota": "18",
     *   "kecamatan": "06",
     *   "desa": "35.18.06.2008",
     *   "alamat_lengkap": "Tanjung Tani, Prambon, Nganjuk",
     *   "phone": "6281276435511",
     *   "jenis_transaksi": "dp",
     *   "cod": 100000,
     *   "non_cod": 0,
     *   "dp": 50000
     * }
     * ```
     *
     * ### Contoh Pesanan Per Produk
     *
     * Contoh array berisi 2 produk:
     *
     * ```json
     * [
     *   {
     *     "nama_produk": "Gantungan Kunci Akrilik Custom Foto",
     *     "variasi": "CUSTOM FOTO, 6 CM",
     *     "jumlah": 2,
     *     "hpp": 10000,
     *     "harga": 25000,
     *     "sku": "GCK001",
     *     "custom": "Foto wajah dan nama Yanto",
     *     "status_pesanan": 0,
     *     "mutasi_stok_id": null,
     *     "catatan": "Gunakan foto sesuai request",
     *     "foto_produk": "[FILE: foto1.jpg]"
     *   },
     *   {
     *     "nama_produk": "Emblem Akrilik Timbul Custom",
     *     "variasi": "GOLD, 10 CM",
     *     "jumlah": 1,
     *     "hpp": 25000,
     *     "harga": 100000,
     *     "sku": "EMB001",
     *     "custom": "Logo komunitas",
     *     "status_pesanan": 0,
     *     "mutasi_stok_id": null,
     *     "catatan": "Warna gold",
     *     "foto_produk": "[FILE: foto2.png]"
     *   }
     * ]
     * ```
     *
     * **Catatan:** JSON di atas adalah ilustrasi data.
     * File foto harus diunggah menggunakan multipart/form-data,
     * bukan sebagai string nama file.
     *
     * ### Contoh Multipart Form
     *
     * Setiap produk dikirim menggunakan indeks array.
     *
     * ```
     * pesanan_perproduk[0][nama_produk] = Gantungan Kunci Akrilik
     * pesanan_perproduk[0][variasi] = CUSTOM FOTO, 6 CM
     * pesanan_perproduk[0][jumlah] = 2
     * pesanan_perproduk[0][hpp] = 10000
     * pesanan_perproduk[0][harga] = 25000
     * pesanan_perproduk[0][sku] = GCK001
     * pesanan_perproduk[0][custom] = Foto Yanto
     * pesanan_perproduk[0][catatan] = Gunakan foto sesuai request
     * pesanan_perproduk[0][foto_produk] = (FILE)
     *
     * pesanan_perproduk[1][nama_produk] = Emblem Akrilik
     * pesanan_perproduk[1][variasi] = GOLD, 10 CM
     * pesanan_perproduk[1][jumlah] = 1
     * pesanan_perproduk[1][hpp] = 25000
     * pesanan_perproduk[1][harga] = 100000
     * pesanan_perproduk[1][sku] = EMB001
     * pesanan_perproduk[1][custom] = Logo komunitas
     * pesanan_perproduk[1][catatan] = Warna gold
     * pesanan_perproduk[1][foto_produk] = (FILE)
     *
     * ```
     *
     * ## Contoh Response
     *
     * ### Berhasil - HTTP 201
     *
     * ```json
     * {
     *   "success": true,
     *   "message": "Pesanan berhasil dibuat",
     *   "data": {
     *     "no_pesanan": "261111005A",
     *     "platform": "meta",
     *     "tanggal": "2026-11-10T17:00:00.000000Z",
     *     "input_at": "2026-11-11T04:30:00.000000Z",
     *     "no_resi": null,
     *     "resi_printed_at": null,
     *     "resi_last_printed_at": null,
     *     "resi_printed_by": null,
     *     "resi_print_count": 0,
     *     "id_toko": null,
     *     "id_user": "33",
     *     "id_user_kirim": null,
     *     "tanggal_kirim": null,
     *     "nama_pembeli": "Yanto",
     *     "username": "Yanto",
     *     "kurir": null,
     *     "status": "proses",
     *     "status_cek": false,
     *     "total_hpp": "45000.00",
     *     "total_harga": "150000.00",
     *     "total_admin": "10000.00",
     *     "pencairan": null,
     *     "chat_masuk_at": "2026-11-11 11:30:00",
     *     "batas_kirim_at": "2026-11-14T04:30:00.000000Z"
     *   }
     * }
     * ```
     *
     * ### Gagal Validasi - HTTP 422
     *
     * ```json
     * {
     *   "message": "The pesanan perproduk field is required.",
     *   "errors": {
     *     "pesanan_perproduk": [
     *       "The pesanan perproduk field is required."
     *     ]
     *   }
     * }
     * ```
     *
     * ### Gagal Menyimpan - HTTP 500
     *
     * ```json
     * {
     *   "success": false,
     *   "message": "Gagal membuat pesanan",
     *   "error": null
     * }
     * ```
     *
     * ## Catatan
     *
     * - Request wajib multipart/form-data.
     * - Minimal satu produk dalam satu pesanan.
     * - Setiap produk wajib memiliki foto_produk.
     * - Setiap produk disimpan sebagai record terpisah.
     * - Foto produk diunggah ke FileGator.
     * - Nomor pesanan harus unik.
     * - Contoh data hanya untuk testing.
     *
     * @requestMediaType multipart/form-data
     */
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
            'notes' => 'nullable|string',

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
            'pesanan_perproduk.*.variasi' => 'required|string',
            'pesanan_perproduk.*.jumlah' => 'required|integer|min:1',
            'pesanan_perproduk.*.hpp' => 'required|numeric|min:0',
            'pesanan_perproduk.*.harga' => 'required|numeric|min:0',
            'pesanan_perproduk.*.sku' => 'required|string',
            'pesanan_perproduk.*.custom' => 'required|string',
            'pesanan_perproduk.*.status_pesanan' => 'nullable|integer',
            'pesanan_perproduk.*.mutasi_stok_id' => 'nullable|integer',
            'pesanan_perproduk.*.catatan' => 'required|string',
            'pesanan_perproduk.*.foto_produk' => 'required|file|mimes:jpg,jpeg,png|max:5120',
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
