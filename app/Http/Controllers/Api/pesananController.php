<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pesanan;
use App\Models\Produk;
use App\Models\User;
use App\Services\Pesanan\PesananService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Dedoc\Scramble\Attributes\Group;
use App\Services\GoogleSheetsService;
use App\Services\Pesanan\FotoPesananService;
use App\Services\Api\FileGatorService as ApiFileGatorService;
use App\Services\Pesanan\ScanPesananService;
use Dedoc\Scramble\Attributes\ExcludeRouteFromDocs;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

#[Group('Pesanan')]
class pesananController extends Controller
{
    /**
     * Daftar Pesanan
     *
     * Menampilkan daftar pesanan dari platform Meta berdasarkan
     * pengguna yang sedang login.
     * 
     * **Fitur**
     *
     * - Menampilkan seluruh pesanan milik pengguna.
     * - Mencari pesanan berdasarkan nomor pesanan, nama pembeli,
     *   atau nomor resi.
     * - Memfilter pesanan berdasarkan status.
     * - Mengurutkan pesanan berdasarkan waktu input terbaru.
     *
     * **Header Request**
     *
     * ```http
     * Accept: application/json
     * Authorization: Bearer {token}
     * ```
     *
     * **Parameter Request**
     *
     * | Parameter | Tipe | Wajib |
     * |---|---|---|
     * | search | string | Tidak |
     * | status | string | Tidak |
     *
     * **Pilihan Status**
     *
     * ```text
     * proses
     * kirim
     * selesai
     * batal
     * affiliate
     * pengembalian
     * pengiriman_gagal
     * ```
     * **Contoh Request**
     *
     * ```http
     * GET /api/pesanan?search=Yanto&status=proses
     * ```
     *
     *
     * **Pilihan Search**
     *
     * ```text
     * no_pesanan    
     * nama_pembeli 
     * no_resi   
     * ```
     *
     * **Contoh Penggunaan Search**
     *
     * ```http
     * GET /api/pesanan?search=20261111001A
     * GET /api/pesanan?search=Yanto
     * GET /api/pesanan?search=JP123456789
     * ```
     *
     * ### Hasil Respon
     *
     * **200 — Berhasil**
     *
     * ```json
     * {
     *   "success": true,
     *   "data": [
     *     {
     *       "no_pesanan": "20261111001A",
     *       "no_resi": null,
     *       "input_at": "2026-11-11 11:30:00",
     *       "nama_pembeli": "Yanto",
     *       "total_harga": 95000,
     *       "status": "proses"
     *     }
     *   ]
     * }
     * ```
     *
     * **200 — Data Kosong**
     *
     * ```json
     * {
     *   "success": true,
     *   "data": []
     * }
     * ```
     *
     * ### Keterangan Error
     *
     * ```text
     * KODE  STATUS                  KETERANGAN
     * ------------------------------------------------------------
     * 200   OK                      Berhasil mengambil daftar pesanan.
     * 401   Unauthorized            Token tidak tersedia atau tidak valid.
     * 422   Unprocessable Entity    Parameter tidak sesuai validasi.
     * 500   Internal Server Error   Kesalahan internal server.
     * ------------------------------------------------------------
     * ```
     *
     * **401 — Unauthorized**
     *
     * ```json
     * {
     *   "message": "Unauthenticated."
     * }
     * ```
     *
     * **422 — Validation Error**
     *
     * ```json
     * {
     *   "message": "The selected status is invalid.",
     *   "errors": {
     *     "status": [
     *       "The selected status is invalid."
     *     ]
     *   }
     * }
     * ```
     *
     * **500 — Internal Server Error**
     *
     * ```json
     * {
     *   "message": "Server Error"
     * }
     * ```
     *
     * ### Ketentuan
     *
     * 1. Endpoint menggunakan metode GET.
     * 2. Memerlukan Bearer Token yang valid.
     * 3. Hanya menampilkan pesanan platform `meta`.
     * 4. Pesanan dibatasi berdasarkan pengguna yang sedang login.
     * 5. Parameter `search` dan `status` tidak wajib dikirim.
     * 6. Parameter `search` maksimal 255 karakter.
     * 7. Parameter `status` hanya menerima pilihan yang tersedia.
     * 8. Jika parameter kosong, seluruh pesanan pengguna ditampilkan.
     * 9. Jika data tidak ditemukan, respons berupa array kosong.
     * 10. Data diurutkan berdasarkan `input_at` terbaru.
     * 11. Endpoint tidak menggunakan Body Request.
     */
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

    /**
     * Detail Pesanan
     *
     * Mengambil informasi detail pesanan berdasarkan nomor pesanan.
     * 
     * ### Fitur
     *
     * Informasi yang ditampilkan meliputi:
     *
     * - Identitas pembeli berupa nama dan nomor telepon.
     * - Alamat pembeli berdasarkan provinsi, kabupaten/kota,
     *   kecamatan, desa, dan kode pos.
     * - Daftar produk pesanan beserta SKU, variasi, jumlah,
     *   kategori, dan catatan produk.
     * - Foto permintaan customer untuk setiap produk.
     * - Informasi pembayaran COD, NON-COD, dan DP.
     * - Bukti pembayaran apabila tersedia.
     * - Total harga dan catatan umum pesanan.
     *
     * Foto produk dan bukti pembayaran dikembalikan sebagai
     * URL lengkap API Laravel yang terhubung dengan FileGator.
     *
     * ### Header Request
     *
     * ```http
     * Accept: application/json
     * Authorization: Bearer {token}
     * ```
     *
     * ### Parameter Request
     *
     * Parameter dikirim melalui URL.
     *
     * | Parameter | Tipe | Wajib | Keterangan |
     * |---|---|---|---|
     * | no_pesanan | string | Ya | Nomor unik pesanan yang akan ditampilkan. |
     *
     * **Ketentuan parameter:**
     *
     * - `no_pesanan` wajib dikirim sebagai path parameter.
     * - Nomor pesanan harus tersedia pada database.
     * - Tidak ada validasi format eksplisit pada controller.
     * - Endpoint tidak menerima JSON Body atau form-data.
     *
     * ### Contoh Request
     *
     * **Endpoint:**
     *
     * ```http
     * GET /api/pesanan/detail/{no_pesanan}
     * ```
     *
     * **Contoh URL:**
     *
     * ```text
     * https://pembukuan.test/api/pesanan/detail/20261114001A
     * ```
     *
     * **Contoh Response JSON:**
     *
     * ```json
     * {
     *   "success": true,
     *   "data": {
     *     "informasiPembeli": {
     *       "namaPembeli": "Pembeli Uji",
     *       "phone": "6281234567890",
     *       "address": {
     *         "provinsi": "Jawa Timur",
     *         "kabupatenKota": "Kabupaten Nganjuk",
     *         "kecamatan": "Prambon",
     *         "desa": "Tanjungtani",
     *         "kodePos": "64484"
     *       }
     *     },
     *     "informasiProduk": [
     *       {
     *         "id_per_produk": 259654,
     *         "no_pesanan": "20261114001A",
     *         "jumlah": 1,
     *         "sku": "EMB0001",
     *         "catatan": "Contoh catatan produk",
     *         "produk": {
     *           "sku": "EMB0001",
     *           "nama_produk": "LOGO ONE PIECE",
     *           "variasi": "SILVER GOLD MERAH",
     *           "kategori_id": 1,
     *           "kategori": {
     *             "id": 1,
     *             "nama_kategori": "EMBLEM AKRILIK"
     *           }
     *         },
     *         "reqfoto": [
     *           {
     *             "per_produk_id": 259654,
     *             "foto": "https://pembukuan.test/api/filegator/file?path=REQ_FOTO%2F20261114001A_01_01.jpg"
     *           },
     *           {
     *             "per_produk_id": 259654,
     *             "foto": "https://pembukuan.test/api/filegator/file?path=REQ_FOTO%2F20261114001A_01_02.jpg"
     *           }
     *         ]
     *       }
     *     ],
     *     "pembayaran": [
     *       {
     *         "no_pesanan": "20261114001A",
     *         "jenis_transaksi": "cod",
     *         "cod": "95000.00",
     *         "non_cod": "0.00",
     *         "dp": "0.00",
     *         "bukti_bayar": "https://pembukuan.test/api/filegator/file?path=TF_META%2F20261114001A.jpg"
     *       }
     *     ],
     *     "pesanan": {
     *       "totalHarga": "95000.00",
     *       "catatanPesanan": "Pengujian API multipart"
     *     }
     *   }
     * }
     * ```
     *
     * ### Keterangan Error
     *
     * **Status Response**
     *
     * | Kode | Status | Keterangan |
     * |---|---|---|
     * | 200 | OK | Detail pesanan berhasil diambil. |
     * | 401 | Unauthorized | Token autentikasi tidak tersedia atau tidak valid. |
     * | 403 | Forbidden | Pengguna tidak memiliki izin mengakses endpoint jika dibatasi middleware otorisasi. |
     * | 404 | Not Found | Nomor pesanan tidak ditemukan. |
     * | 500 | Internal Server Error | Terjadi kesalahan server saat mengambil detail pesanan atau data wilayah. |
     *
     * Contoh pesan error dapat berbeda sesuai konfigurasi
     * exception handler dan middleware Laravel.
     *
     * ### Ketentuan
     *
     * 1. Endpoint menggunakan metode GET.
     * 2. Nomor pesanan dikirim melalui path parameter.
     * 3. Endpoint tidak membutuhkan request body.
     * 4. Pesanan dicari berdasarkan kolom no_pesanan.
     */
    public function detail(PesananService $pesananService, $no_pesanan)
    {
        $data = $pesananService->detailpesanan($no_pesanan);

        return response()->json([
            'success' => true,
            'data' => $data
        ], 200, [], JSON_UNESCAPED_SLASHES);
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
            'desa' => 'nullable|string',
            'alamat_lengkap' => 'required|string|max:1000',
            'phone' => ['required', 'regex:/^628[0-9]{7,12}$/'],

            // 03. PEMBAYARAN
            'jenis_transaksi' => 'required|in:cod,non-cod,dp,por',
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

            // FOTO PRODUK (MAKSIMAL 5 FOTO PER PRODUK)
            'pesanan_perproduk.*.foto_produk' => 'nullable|array|min:1|max:5',
            'pesanan_perproduk.*.foto_produk.*' => 'nullable|file|mimes:jpg,jpeg,png|max:5120',
        ]);

        DB::beginTransaction();

        try {
            $data = $pesananService->store($request);
            // $sheed = $this->googleSheet($request, app(GoogleSheetsService::class));

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pesanan berhasil dibuat',
                'data' => $data,
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

    /**
     * Edit Pesanan
     *
     * Mengambil detail pesanan berdasarkan nomor pesanan
     * untuk kebutuhan pengisian form edit pesanan.
     *
     * ### Fitur
     *
     * - Menampilkan informasi pembeli.
     * - Menampilkan informasi pesanan.
     * - Menampilkan produk dalam pesanan.
     * - Menampilkan alamat customer.
     * - Menampilkan bukti pembayaran.
     *
     * ### Header Request
     *
     * | Header | Value |
     * | --- | --- |
     * | Authorization | Bearer {token} |
     * | Accept | application/json |
     *
     * ### Parameter
     *
     * | Parameter | Tipe | Keterangan |
     * | --- | --- | --- |
     * | no_pesanan | string | Nomor pesanan yang akan diedit |
     *
     * ### Contoh Request
     *
     * ```http
     * GET /api/pesanan/edit/261126001A
     * ```
     *
     * ### Catatan
     *
     * - Nomor pesanan harus terdaftar dalam database.
     * - wilayah menggunakan nilai asli dari database yang merupakan kode wilayah.
     * - Field `nama_file` berisi path asli file pada FileGator.
     * - Field `foto` dan `bukti_bayar` bisa digunakan untuk menampilkan foto.
     * - Endpoint ini hanya mengambil data untuk di tampilkan di form dan tidak mengubah pesanan.
     * - Jika pesanan tidak ditemukan, API mengembalikan HTTP 404.
     */
    public function edit(PesananService $pesananService, $no_pesanan)
    {
        $data = $pesananService->edit($no_pesanan);

        return response()->json([
            'success' => true,
            'data' => $data
        ], 200, [], JSON_UNESCAPED_SLASHES);
    }

    /**
     * Update Pesanan
     *
     * Memperbarui informasi pembeli, pesanan, alamat pengiriman,
     * dan data pembayaran berdasarkan nomor pesanan.
     *
     * ### Ketentuan Update
     *
     * - Jika `pengajuan_resi_meta = false`, data pesanan bisa langsung diperbarui dari cs.
     * - Jika `pengajuan_resi_meta = true`, perubahan disimpan sebagai pengajuan edit pesanan untuk di tinjau Admin logistik.
     * - Pengajuan edit pesanan memiliki status awal `pending`.
     * - Pengajuan tidak langsung mengubah data pesanan asli.
     * - Daftar produk pesanan tidak diperbarui melalui endpoint ini.
     * - Namun cara pembayaran atau bukti pembayaran bisa melalui endpoin ini termasuk metode POR (payment on Ready) yang dimana bukti pembayaran dapat di input diakhir.
     *
     * ### Header Request
     *
     * | Header | Value |
     * | --- | --- |
     * | Authorization | Bearer {token} |
     * | Accept | application/json |
     * | Content-Type | multipart/form-data |
     *
     * ### Data Pesanan
     *
     * | Parameter | Tipe | Wajib | Keterangan |
     * | --- | --- | --- | --- |
     * | no_pesanan | string | Ya | Nomor pesanan yang terdaftar |
     * | tanggal_order | datetime | Ya | Format Y-m-d H:i:s, tidak melebihi waktu sekarang |
     * | nama_pembeli | string | Ya | Nama pembeli, maksimal 255 karakter |
     * | total_hpp | integer | Ya | Total HPP, minimal 0 |
     * | total_harga | integer | Ya | Total harga, minimal 0 |
     * | total_admin | integer | Ya | Total biaya admin, minimal 0 |
     * | notes | string | Tidak | Catatan pesanan |
     * | chat_masuk_at | datetime | Tidak | Waktu chat masuk, digunakan pada pengajuan edit |
     *
     * ### Alamat Pengiriman
     *
     * | Parameter | Tipe | Wajib | Keterangan |
     * | --- | --- | --- | --- |
     * | provinsi | string | Ya | Kode provinsi |
     * | kabupaten_kota | string | Ya | Kode kabupaten atau kota |
     * | kecamatan | string | Ya | Kode kecamatan |
     * | desa | string | Tidak | Kode desa |
     * | alamat_lengkap | string | Ya | Alamat lengkap, maksimal 1000 karakter |
     * | phone | string | Ya | Nomor WhatsApp dengan awalan 628 |
     *
     * ### Pembayaran
     *
     * | Parameter | Tipe | Wajib | Keterangan |
     * | --- | --- | --- | --- |
     * | jenis_transaksi | string | Ya | cod, non-cod, dp, por |
     * | cod | numeric | Tidak | Nominal pembayaran COD |
     * | non_cod | numeric | Tidak | Nominal pembayaran Non-COD |
     * | dp | numeric | Tidak | Nominal uang muka |
     * | por | numeric | Tidak | Nominal pembayaran POR |
     * | bukti_bayar | file | Tidak | JPG, JPEG, PNG, PDF, maksimal 5 MB |
     *
     * ### Ketentuan Bukti Pembayaran
     *
     * - Upload bukti pembayaran bersifat opsional.
     * - Jika update langsung tanpa file baru, bukti pembayaran lama dipertahankan.
     * - Jika update langsung dengan file baru, bukti pembayaran lama diganti.
     * - Jika pengajuan edit tanpa file baru, bukti pembayaran pengajuan bernilai NULL.
     * - File bukti pembayaran pengajuan menggunakan suffix `_EP`.
     * - Bukti pembayaran asli tidak diubah ketika membuat pengajuan.
     * - Seluruh file bukti pembayaran disimpan pada folder `TF_META`.
     *
     * ### Contoh Nama File
     *
     * | Jenis | Nama File |
     * | --- | --- |
     * | Update langsung | 261126001A.jpg |
     * | Pengajuan edit | 261126001A_EP.jpg |
     *
     * ### Response Update Langsung
     *
     * ```json
     * {
     *     "success": true,
     *     "data": {
     *         "success": true,
     *         "message": "Pesanan berhasil diperbarui."
     *     }
     * }
     * ```
     *
     * ### Response Pengajuan Edit
     *
     * ```json
     * {
     *     "success": true,
     *     "data": {
     *         "success": true,
     *         "message": "Pengajuan edit pesanan berhasil dibuat.",
     *         "data": {
     *             "no_pesanan": "261126001A",
     *             "status": "pending"
     *         }
     *     }
     * }
     * ```
     *
     * ### HTTP Status Code
     *
     * | Status | Keterangan |
     * | --- | --- |
     * | 200 | Update atau pengajuan edit berhasil |
     * | 401 | Bearer Token tidak valid |
     * | 403 | Tidak memiliki hak akses |
     * | 404 | Data terkait pesanan tidak ditemukan |
     * | 422 | Validasi request gagal |
     * | 500 | Terjadi kesalahan pada server |
     *
     * ### Catatan
     *
     * - Nomor pesanan harus tersedia pada tabel pesanan.
     * - Batas kirim update langsung dihitung 3 hari dari tanggal order, jika tanggal order di edit batas kirim akan dihitung ulang otomatis 3 hari.
     * - User pengajuan diambil otomatis dari pengguna yang login.
     * - Nominal pembayaran yang kosong pada pengajuan disimpan sebagai 0.
     */
    public function update(PesananService $pesananService, Request $request)
    {
        $request->validate([
            // 01. DATA PESANAN
            'no_pesanan' => ['required', 'string', Rule::exists('pesanan', 'no_pesanan')],
            'tanggal_order' => 'required|date_format:Y-m-d H:i:s|before_or_equal:now',
            'nama_pembeli' => 'required|string|max:255',
            'total_hpp' => 'required|integer|min:0',
            'total_harga' => 'required|integer|min:0',
            'total_admin' => 'required|integer|min:0',
            'notes' => 'nullable|string',

            // 02. ALAMAT PENGIRIMAN
            'provinsi' => 'required|string|max:255',
            'kabupaten_kota' => 'required|string|max:255',
            'kecamatan' => 'required|string|max:255',
            'desa' => 'nullable|string|max:255',
            'alamat_lengkap' => 'required|string|max:1000',
            'phone' => ['required', 'string', 'regex:/^628[0-9]{7,12}$/'],

            // 03. PEMBAYARAN
            'jenis_transaksi' => 'required|in:cod,non-cod,dp,por',
            'cod' => 'nullable|numeric|min:0',
            'non_cod' => 'nullable|numeric|min:0',
            'dp' => 'nullable|numeric|min:0',
            'por' => 'nullable|numeric|min:0',
            'bukti_bayar' => ['sometimes', 'nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        $pesanan = Pesanan::where('no_pesanan', $request->no_pesanan)
            ->select('pengajuan_resi_meta')->first();

        $pengajuanResi = $pesanan->pengajuan_resi_meta;
        if ($pengajuanResi == true) {
            $data = $pesananService->pengajuanUpdate($request);
        } elseif ($pengajuanResi == false) {
            $data = $pesananService->update($request);
        }

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * Hapus Pesanan
     *
     * Menghapus pesanan secara langsung atau mengajukan penghapusan
     * pesanan berdasarkan status pengajuan resi dan nomor resi.
     *
     * ### Header Request
     * ```http
     * Authorization: Bearer {token}
     * Accept: application/json
     * Content-Type: application/json
     * ```
     *
     * ### Parameter Request
     * - **no_pesanan** : string, wajib diisi dan terdaftar pada tabel pesanan.
     * - **keterangan** : string, wajib diisi apabila pengajuan penghapusan pesanan & tidak wajib di isi jika dihapus langsung.
     *
     * ### Catatan
     * - Pada aplikasi mobile, field keterangan dapat
     *   ditampilkan apabila `no_resi` tidak bernilai `null` dan
     *   `pengajuan_resi_meta` bernilai `1` (true).
     * - Field tersebut wajib diisi sebagai alasan pengajuan penghapusan pesanan.
     * - Apabila kondisi tersebut tidak terpenuhi, field keterangan tidak perlu ditampilkan.
     * 
     * ### Contoh Request
     * ```http
     * DELETE /api/pesanan/delete/261126001A
     * ```
     *
     * ### Body Request
     * ```json
     * {
     *     "keterangan": "Pembeli membatalkan pesanan"
     * }
     * ```
     *
     * ### Hasil Respon
     *
     * **HTTP 200 - Berhasil**
     * ```json
     * {
     *     "success": true,
     *     "message": "Pesanan berhasil dihapus"
     * }
     * ```
     *
     * **HTTP 422 - Gagal**
     * ```json
     * {
     *     "success": false,
     *     "message": "Pesanan tidak memenuhi syarat penghapusan."
     * }
     * ```
     *
     * ### Keterangan Error
     * - **401** : Token tidak valid atau pengguna belum login.
     * - **403** : Pengguna tidak memiliki hak akses.
     * - **404** : Pesanan tidak ditemukan setelah validasi.
     * - **422** : Validasi gagal atau pesanan tidak memenuhi syarat.
     * - **500** : Terjadi kesalahan pada server.
     *
     * ### Ketentuan
     * - Jika pengajuan_resi_meta bernilai false dan no_resi bernilai null,
     *   pesanan akan langsung dihapus beserta data terkait.
     * - Jika pengajuan_resi_meta bernilai true dan no_resi tidak null,
     *   pesanan akan masuk ke pengajuan penghapusan.
     * - Pengajuan penghapusan memiliki status awal pending.
     * - Pesanan yang masuk pengajuan tidak langsung dihapus.
     * - Status pengajuan terdiri dari pending, approve, dan reject.
     */
    public function delete(PesananService $pesananService, $no_pesanan, Request $request)
    {
        Validator::make(['no_pesanan' => $no_pesanan], [
            'no_pesanan' => 'required|string|exists:pesanan,no_pesanan',
        ])->validate();

        $pesanan = Pesanan::where('no_pesanan', $no_pesanan)->firstOrFail();
        if ($pesanan->pengajuan_resi_meta == true && $pesanan->no_resi !== null) {
            $request->validate([
                'keterangan' => 'required|string',
            ]);
            $pesananService->pengajuanDelete($pesanan, $request);

            return response()->json([
                'success' => true,
                'message' => "Hapus pesanan berhasil di ajukan",
            ]);
        } elseif ($pesanan->pengajuan_resi_meta == false && $pesanan->no_resi === null) {
            $pesananService->delete($pesanan);

            return response()->json([
                'success' => true,
                'message' => "Pesanan berhasil dihapus",
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Pesanan tidak memenuhi syarat penghapusan.',
            ], 422);
        }
    }

    #[ExcludeRouteFromDocs]
    public function googleSheet(Request $request, GoogleSheetsService $googleSheets)
    {
        $data = $request->validate([
            // INFORMASI PESANAN
            'no_pesanan' => ['required', 'string', 'max:100'],
            'tanggal_order' => ['required', 'date'],
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'nama_pembeli' => ['required', 'string', 'max:150'],
            'phone' => ['required', 'string', 'max:30'],

            // FORMAT BANYAK PRODUK
            'pesanan_perproduk' => ['sometimes', 'array', 'min:1', 'max:100'],
            'pesanan_perproduk.*.sku' => ['required', 'string', 'max:100'],
            'pesanan_perproduk.*.catatan' => ['required', 'string'],
            'pesanan_perproduk.*.jumlah' => ['required', 'integer'],

            // FOTO PER PRODUK
            'pesanan_perproduk.*.reqfoto' => ['sometimes', 'array', 'max:20'],
            'pesanan_perproduk.*.reqfoto.*.foto' => ['required', 'url', 'max:2048'],
        ]);

        $user = User::findOrFail($data['user_id']);
        $skuList = collect($data['pesanan_perproduk'])
            ->pluck('sku')
            ->unique();

        $produkList = Produk::with('kategori')->whereIn('sku', $skuList)
            ->get()
            ->keyBy('sku');
        $data['nama_cs'] = $user->name;

        foreach ($data['pesanan_perproduk'] as &$item) {
            $produk = $produkList->get($item['sku']);
            $item['kategori'] = $produk->kategori->nama_kategori;
            $item['kategori'] = $produk->kategori->nama_kategori;
        }

        unset($item);

        dd($data);

        return response()->json(
            $googleSheets->simpanPesanan($data)
        );
    }

    /**
     * Antrian Pesanan Meta
     *
     * Menampilkan daftar pesanan Khusus Meta yang masih menunggu
     * proses foto barang yang sudah jadi.
     *
     * ## Fitur
     *
     * - Menampilkan pesanan Meta berstatus proses.
     * - Menampilkan pesanan yang belum memiliki foto.
     * - Pesanan yang sudah memiliki foto tidak akan ditampilkan
     * - Data dibatasi berdasarkan input 3 bulan terakhir.
     * - Mendukung pencarian nomor pesanan, nomor resi,
     *   dan nama pembeli.
     * - Mendukung filter tanggal batas kirim.
     * - Mengurutkan pesanan berdasarkan batas kirim terdekat.
     *
     * ## Header Request
     *
     * ```http
     * Accept: application/json
     * Authorization: Bearer {token}
     * ```
     *
     * ## Parameter Request
     *
     * | Parameter | Wajib | Tipe | Keterangan |
     * |-----------|-------|------|------------|
     * | search | Tidak | string | Cari nomor pesanan, nomor resi, atau nama pembeli |
     * | batas_kirim | Tidak | date | Filter tanggal batas kirim (YYYY-MM-DD) |
     *
     * ## Contoh Request
     *
     * Tanpa filter:
     *
     * ```http
     * GET /api/pesanan/antrian
     * Accept: application/json
     * Authorization: Bearer {token}
     * ```
     *
     * Dengan pencarian dan filter tanggal:
     *
     * ```http
     * GET /api/pesanan/antrian?search=20261119062B&batas_kirim=2026-11-22
     * Accept: application/json
     * Authorization: Bearer {token}
     * ```
     *
     * ## Hasil Respon
     *
     * **HTTP 200 - Berhasil**
     *
     * ```json
     * {
     *   "success": true,
     *   "data": [
     *     {
     *       "no_pesanan": "20261119062B",
     *       "no_resi": null,
     *       "input_at": "2026-11-19T01:30:00.000000Z",
     *       "nama_pembeli": "Siti Aminah",
     *       "status": "proses",
     *       "batas_kirim_at": "2026-11-22T01:30:00.000000Z",
     *       "foto_at": 0
     *     }
     *   ]
     * }
     * ```
     *
     * Keterangan field:
     *
     * - no_pesanan: Nomor unik pesanan.
     * - no_resi: Nomor resi pesanan, dapat bernilai null.
     * - input_at: Tanggal dan waktu pesanan masuk.
     * - nama_pembeli: Nama pelanggan.
     * - status: Status pesanan, yaitu proses.
     * - batas_kirim_at: Tanggal dan waktu batas pengiriman.
     * - foto_at: Status foto dalam bentuk angka.
     *
     * Status foto:
     *
     * - 0: Belum memiliki foto.
     * - 1: Sudah memiliki foto.
     *
     * Pada endpoint ini, foto_at selalu bernilai 0
     * karena hanya mengambil pesanan tanpa foto.
     *
     * Jika tidak ada pesanan, data dikembalikan sebagai [].
     *
     * ## Keterangan Error
     *
     * | Kode | Status | Keterangan |
     * |------|--------|------------|
     * | 200 | OK | Data antrian berhasil diambil |
     * | 401 | Unauthorized | Pengguna belum login atau token tidak valid |
     * | 403 | Forbidden | Pengguna tidak memiliki izin akses |
     * | 422 | Validation Error | Parameter request tidak valid jika divalidasi |
     * | 500 | Server Error | Terjadi kesalahan pada server |
     *
     * ## Ketentuan
     *
     * 1. Hanya pesanan dengan platform meta.
     * 3. Status pesanan harus proses.
     * 4. Pesanan belum memiliki foto (foto_at = null).
     * 5. Pesanan diambil dari 3 bulan terakhir berdasarkan input_at.
     * 6. Pencarian berlaku pada no_pesanan, no_resi,
     *    dan nama_pembeli.
     * 7. Filter batas_kirim menggunakan tanggal saja.
     * 8. Jika filter tidak dikirim, seluruh antrian
     *    yang memenuhi ketentuan ditampilkan.
     * 9. Data diurutkan berdasarkan batas_kirim_at
     *    dari yang paling awal.
     * 10. Response berisi daftar pesanan dalam bentuk array.
     */
    public function antrian(PesananService $pesananService, Request $request)
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'batas_kirim' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $data = $pesananService->antrian($request);

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * Pesanan Siap Dikirim
     *
     * Menampilkan daftar pesanan yang siap dikirim berdasarkan
     * platform pengguna yang sedang login.
     *
     * ## Fitur
     *
     * - Menampilkan daftar pesanan siap dikirim.
     * - Data dipisahkan berdasarkan platform Meta dan Marketplace.
     * - Mengurutkan pesanan berdasarkan batas kirim paling awal.
     * - Menampilkan nomor pesanan, resi, nama pembeli,
     *   platform, marketplace, dan batas kirim.
     *
     * ### Platform Meta
     *
     * - Platform pesanan harus meta.
     * - Status pesanan harus proses.
     * - Foto pesanan sudah tersedia (foto_at tidak null).
     * - Nomor resi sudah tersedia.
     * - Tidak ada batasan tanggal pada query.
     *
     * ### Platform Marketplace
     *
     * - Platform pesanan harus marketplace.
     * - Status pesanan harus proses.
     * - Batas kirim wajib tersedia.
     * - Batas kirim hari ini atau tanggal sebelumnya.
     * - Pesanan dengan batas kirim besok tidak ditampilkan.
     *
     * ## Header Request
     *
     * ```http
     * Accept: application/json
     * Authorization: Bearer {token}
     * ```
     *
     * ## Parameter Request
     *
     * Tidak memerlukan parameter request maupun request body.
     *
     * Platform ditentukan otomatis berdasarkan pengguna login.
     *
     * ## Contoh Request
     *
     * ```http
     * GET /api/pesanan/ready
     * Accept: application/json
     * Authorization: Bearer {token}
     * ```
     *
     * ## Hasil Respon
     *
     * **HTTP 200 - Berhasil**
     *
     * ```json
     * {
     *   "success": true,
     *   "data": [
     *     {
     *       "noPesanan": "20261120001A",
     *       "platform": "meta",
     *       "noResi": "JNE123456789",
     *       "pembeli": "Siti Aminah",
     *       "marketplace": null,
     *       "batasKirim": "2026-11-20T12:00:00.000000Z"
     *     }
     *   ]
     * }
     * ```
     *
     * Keterangan field:
     *
     * | Field | Tipe | Keterangan |
     * |-------|------|------------|
     * | noPesanan | string | Nomor unik pesanan |
     * | platform | string | meta atau marketplace |
     * | noResi | string/null | Nomor resi pengiriman |
     * | pembeli | string | Nama pembeli |
     * | marketplace | string/null | Nama marketplace dari toko |
     * | batasKirim | datetime/null | Batas waktu pengiriman |
     *
     * Jika tidak ada pesanan:
     *
     * ```json
     * {
     *   "success": true,
     *   "data": []
     * }
     * ```
     *
     * ## Keterangan Error
     *
     * | Kode | Status | Keterangan |
     * |------|--------|------------|
     * | 200 | OK | Data berhasil diambil |
     * | 401 | Unauthorized | Token tidak valid atau belum login |
     * | 403 | Forbidden | Tidak memiliki izin akses |
     * | 500 | Server Error | Kesalahan pada server |
     *
     * ## Ketentuan
     *
     * 1. Platform diambil otomatis dari userLogin()->platform.
     * 2. Pengguna platform meta hanya menerima pesanan Meta.
     * 3. Pengguna selain platform meta masuk ke query Marketplace.
     * 4. Meta wajib memiliki foto_at dan no_resi.
     * 5. Marketplace wajib memiliki batas_kirim_at.
     * 6. Marketplace hanya menampilkan batas kirim hari ini
     *    atau tanggal sebelumnya.
     * 7. Status semua pesanan harus proses.
     * 8. Data diurutkan berdasarkan batas_kirim_at ASC.
     * 9. Relasi toko digunakan untuk mengambil marketplace.
     * 10. Jika toko tidak ditemukan, marketplace bernilai null.
     * 11. Response berupa array berisi object pesanan.
     */
    public function ready(PesananService $pesananService)
    {
        $data = $pesananService->ready();

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * Detail Pesanan
     *
     * Menampilkan informasi lengkap pesanan berdasarkan nomor
     * pesanan, termasuk data pembeli, produk, pembayaran,
     * foto produk, dan status kesiapan pengiriman.
     *
     * ## Fitur
     *
     * - Menampilkan informasi pembeli dan alamat lengkap.
     * - Menampilkan daftar produk beserta variasinya.
     * - Menampilkan foto request dan foto pesanan jadi.
     * - Menampilkan informasi pembayaran.
     * - Menampilkan nomor resi dan batas informasi pesanan.
     * - Menentukan keterangan kesiapan pengiriman otomatis.
     *
     * ## Header Request
     *
     * ```http
     * Accept: application/json
     * Authorization: Bearer {token}
     * ```
     *
     * ## Parameter Request
     *
     * | Parameter | Wajib | Tipe | Keterangan |
     * |-----------|-------|------|------------|
     * | no_pesanan | Ya | string | Nomor unik pesanan yang ingin ditampilkan |
     *
     * Parameter dikirim melalui URL (path parameter).
     *
     * ## Contoh Request
     *
     * ```http
     * GET /api/pesanan/detail/20261119062B
     * Accept: application/json
     * Authorization: Bearer {token}
     * ```
     *
     * ## Hasil Respon
     *
     * **HTTP 200 - Berhasil**
     *
     * ```json
     * {
     *   "success": true,
     *   "data": {
     *     "informasiPembeli": {
     *       "namaPembeli": "Siti Aminah",
     *       "phone": "6281200000062",
     *       "address": {
     *         "provinsi": "Jawa Timur",
     *         "kabupatenKota": "Kabupaten Nganjuk",
     *         "kecamatan": "Prambon",
     *         "desa": "Tanjungtani",
     *         "alamatLengkap": "Jl. Mawar No. 12",
     *         "kodePos": "64484"
     *       }
     *     },
     *     "informasiProduk": [
     *       {
     *         "id_per_produk": 262184,
     *         "no_pesanan": "20261119062B",
     *         "jumlah": 3,
     *         "sku": "GKA0886C",
     *         "catatan": "Tiga nama berbeda",
     *         "produk": {
     *           "sku": "GKA0886C",
     *           "nama_produk": "CUSTOM WAJAH KARAKTER",
     *           "variasi": "BOYS",
     *           "kategori_id": 5,
     *           "kategori": {
     *             "id": 5,
     *             "nama_kategori": "GANTUNGAN KUNCI AKRILIK UV"
     *           }
     *         },
     *         "reqfoto": [],
     *         "foto_meta": null
     *       }
     *     ],
     *     "pembayaran": [
     *       {
     *         "no_pesanan": "20261119062B",
     *         "jenis_transaksi": "non-cod",
     *         "cod": "0.00",
     *         "non_cod": "90000.00",
     *         "dp": "0.00",
     *         "bukti_bayar": null
     *       }
     *     ],
     *     "pesanan": {
     *       "noPesanan": "20261119062B",
     *       "noResi": null,
     *       "foto_at": null,
     *       "platform": "meta",
     *       "tanggalInput": "2026-11-19T01:30:00.000000Z",
     *       "totalHarga": "90000.00",
     *       "catatanPesanan": "Gantungan kunci nama custom",
     *       "keterangan": "Menunggu Foto dan Resi"
     *     }
     *   }
     * }
     * ```
     *
     * ## Keterangan Status Pesanan
     *
     * | Keterangan | Kondisi |
     * |------------|---------|
     * | Menunggu Foto dan Resi | Foto dan nomor resi belum tersedia |
     * | Menunggu Resi | Foto tersedia, nomor resi belum tersedia |
     * | Menunggu Foto | Nomor resi tersedia, foto belum tersedia |
     * | Siap Dikirim | Foto dan nomor resi sudah tersedia |
     *
     * Keterangan dibuat berdasarkan nilai foto_at dan noResi.
     * Status siap dikirim tidak otomatis mengubah status database.
     *
     * ## Keterangan Error
     *
     * | Kode | Status | Keterangan |
     * |------|--------|------------|
     * | 200 | OK | Detail pesanan berhasil diambil |
     * | 401 | Unauthorized | Pengguna belum login |
     * | 403 | Forbidden | Tidak memiliki izin akses |
     * | 404 | Not Found | Nomor pesanan tidak ditemukan |
     * | 500 | Server Error | Terjadi kesalahan pada server |
     *
     * ## Ketentuan
     *
     * 1. Nomor pesanan wajib dikirim melalui URL.
     * 2. Data pesanan diambil berdasarkan no_pesanan.
     * 3. Informasi produk dapat berisi lebih dari satu item.
     * 4. reqfoto berupa array foto request produk.
     * 5. foto_meta berupa object atau null jika relasi hasOne.
     * 6. URL foto menggunakan signed URL jika tersedia.
     * 7. Informasi pembayaran dapat berisi beberapa transaksi.
     * 8. Bukti pembayaran dapat bernilai null.
     * 9. Keterangan ditentukan otomatis berdasarkan foto_at
     *    dan noResi.
     * 10. Keterangan hanya ditambahkan ke response API,
     *     bukan disimpan ke database.
     */
    public function showDetail(PesananService $pesananService, $no_pesanan)
    {
        $data = $pesananService->detailpesanan($no_pesanan);

        if ($data && isset($data->pesanan)) {
            $pesanan = &$data->pesanan;

            if (empty($pesanan['noResi']) && empty($pesanan['foto_at'])) {
                $pesanan['keterangan'] = 'Menunggu Foto dan Resi';
            } elseif (empty($pesanan['noResi'])) {
                $pesanan['keterangan'] = 'Menunggu Resi';
            } elseif (empty($pesanan['foto_at'])) {
                $pesanan['keterangan'] = 'Menunggu Foto';
            } else {
                $pesanan['keterangan'] = 'Siap Dikirim';
            }
        }

        return response()->json([
            'success' => true,
            'data' => $data
        ], 200, [], JSON_UNESCAPED_SLASHES);
    }

    /**
     * Upload Foto Pesanan Jadi
     *
     * Mengunggah foto hasil produksi yang telahjadi untuk setiap perproduk
     * dalam satu pesanan Meta sebelum proses pengiriman.
     *
     * ## Fitur
     *
     * - Mendukung upload beberapa foto untuk beberapa produk dalam 1 pesanan.
     * - Setiap perproduk wajib menyertakan satu foto.
     * - Memastikan Idperproduk ada dalam pesanan.
     * - Menyimpan foto ke FileGator folder FOTO_META.
     * - Menyimpan informasi foto pada tabel foto_metas.
     * - Menghasilkan signed URL untuk setiap foto.
     * - Memperbarui foto_at setelah seluruh foto berhasil diproses.
     *
     * ## Header Request
     *
     * ```http
     * Accept: application/json
     * Authorization: Bearer {token}
     * Content-Type: multipart/form-data
     * ```
     *
     * ## Parameter Request
     *
     * | Parameter | Wajib | Tipe | Keterangan |
     * |-----------|-------|------|------------|
     * | no_pesanan | Ya | string | Nomor pesanan dengan platform meta |
     * | foto | Ya | array | Kumpulan foto berdasarkan ID per produk |
     * | foto[idPerproduk] | Ya | file | Satu foto untuk setiap ID per produk |
     *
     * ID pada foto harus sesuai dengan id_per_produk
     * yang dimiliki pesanan.
     *
     * ## Contoh Request
     *
     * ```http
     * POST /api/pesanan/foto-jadi
     * Accept: application/json
     * Authorization: Bearer {token}
     * Content-Type: multipart/form-data
     * ```
     *
     * Contoh Body (form-data):
     *
     * | Key | Type | Value |
     * |-----|------|-------|
     * | no_pesanan | Text | 20261120001A |
     * | foto[262184] | File | produk_1.jpg |
     * | foto[262185] | File | produk_2.jpg |
     * | foto[262186] | File | produk_3.png |
     *
     * ## Hasil Respon
     *
     * **HTTP 200 - Berhasil**
     *
     * ```json
     * {
     *   "success": true,
     *   "data": [
     *     {
     *       "id": 1,
     *       "perproduk_id": 262184,
     *       "foto": "https://domain.com/file?path=FOTO_META%2Ffoto1.jpg&signature=...",
     *       "user_id": 33
     *     },
     *     {
     *       "id": 2,
     *       "perproduk_id": 262185,
     *       "foto": "https://domain.com/file?path=FOTO_META%2Ffoto2.jpg&signature=...",
     *       "user_id": 33
     *     },
     *     {
     *       "id": 3,
     *       "perproduk_id": 262186,
     *       "foto": "https://domain.com/file?path=FOTO_META%2Ffoto3.png&signature=...",
     *       "user_id": 33
     *     }
     *   ]
     * }
     * ```
     *
     * URL foto merupakan signed URL yang digunakan
     * untuk mengakses file hasil upload.
     *
     * **HTTP 422 - ID Produk Tidak Valid**
     *
     * ```json
     * {
     *   "message": "ID per produk 999999 tidak ditemukan dalam pesanan ini.",
     *   "errors": {
     *     "foto.999999": [
     *       "ID per produk 999999 tidak ditemukan dalam pesanan ini."
     *     ]
     *   }
     * }
     * ```
     *
     * **HTTP 422 - Jumlah Foto Tidak Sesuai**
     *
     * ```json
     * {
     *   "message": "Setiap per produk wajib memiliki satu foto.",
     *   "errors": {
     *     "foto": [
     *       "Setiap per produk wajib memiliki satu foto."
     *     ]
     *   }
     * }
     * ```
     *
     * ## Keterangan Error
     *
     * | Kode | Status | Keterangan |
     * |------|--------|------------|
     * | 200 | OK | Foto berhasil diunggah |
     * | 401 | Unauthorized | Token tidak valid atau belum login |
     * | 403 | Forbidden | Tidak memiliki izin akses |
     * | 404 | Not Found | Pesanan Meta tidak ditemukan |
     * | 422 | Validation Error | ID produk salah atau jumlah foto tidak sesuai |
     * | 500 | Server Error | Upload FileGator atau proses server gagal |
     *
     * ## Ketentuan
     *
     * 1. Pesanan wajib menggunakan platform meta.
     * 2. Nomor pesanan harus terdaftar di database.
     * 3. Setiap id_per_produk wajib memiliki satu foto.
     * 4. ID produk harus termasuk dalam nomor pesanan.
     * 5. Jumlah foto harus sama dengan jumlah produk
     *    pada pesanan.
     * 6. Nama file dibentuk dari no_pesanan,
     *    id_per_produk, dan ekstensi file.
     * 7. File disimpan dalam folder FOTO_META.
     * 8. Informasi foto disimpan pada tabel foto_metas.
     * 9. user_id diambil dari pengguna login.
     * 10. Response foto menggunakan signed URL.
     * 11. foto_at diperbarui setelah seluruh foto diproses.
     * 12. Database menggunakan transaksi dan penguncian
     *     pesanan untuk mencegah perubahan bersamaan.
     */
    public function uploadFoto(
        ApiFileGatorService $fileGatorService,
        FotoPesananService $fotoPesananService,
        Request $request
    ) {
        $request->validate([
            'no_pesanan' => ['required', 'string', 'exists:pesanan,no_pesanan'],
            'foto' => ['required', 'array', 'min:1', 'max:5'],
            'foto.*' => ['required', 'file', 'mimes:jpg,jpeg,png', 'max:5120'],
        ]);

        try {
            $data = $fotoPesananService->fotoPesananJadi($fileGatorService, $request);

            return response()->json([
                'success' => true,
                'message' => 'Foto pesanan berhasil diupload.',
                'data' => $data,
            ], 200, [], JSON_UNESCAPED_SLASHES);
        } catch (\Throwable $th) {
            report($th);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengupload foto.',
                'error' => config('app.debug')
                    ? $th->getMessage()
                    : null,
            ], 500);
        }
    }

    /**
     * Scan Nomor Resi Pesanan
     *
     * Menampilkan informasi pesanan berdasarkan nomor resi
     * yang dipindai atau dimasukkan oleh pengguna.
     *
     * ## Fitur
     *
     * - Mencari pesanan berdasarkan nomor resi.
     * - Menampilkan informasi pesanan yang ditemukan.
     * - Mengambil informasi toko melalui relasi toko.
     * - Mendukung pencarian untuk semua platform pesanan.
     *
     * ## Catatan:
     *
     * Jika aplikasi mobile membutuhkan notifikasi suara MP3
     * saat proses scan, file audio dapat disimpan secara lokal
     * di sisi aplikasi mobile.
     *
     * Pemutaran suara dilakukan langsung oleh aplikasi mobile
     * berdasarkan hasil response API, sehingga server tidak
     * perlu menyediakan atau mengirimkan file MP3.
     *
     * ## Header Request
     *
     * ```http
     * Accept: application/json
     * Authorization: Bearer {token}
     * ```
     *
     * ## Parameter Request
     *
     * | Parameter | Wajib | Tipe | Keterangan |
     * |-----------|-------|------|------------|
     * | no_resi | Ya | string | Nomor resi pesanan yang akan dicari |
     *
     * Parameter no_resi dikirim melalui URL.
     *
     * ## Contoh Request
     *
     * ```http
     * GET /api/pesanan/scan/JNE123456789
     * Accept: application/json
     * Authorization: Bearer {token}
     * ```
     *
     * ## Hasil Respon
     *
     * **HTTP 200 - Berhasil**
     *
     * ```json
     * {
     *   "success": true,
     *   "data": {
     *     "no_pesanan": "20261120001A",
     *     "no_resi": "JNE123456789",
     *     "nama_pembeli": "Siti Aminah",
     *     "platform": "marketplace",
     *     "status": "proses",
     *     "batas_kirim_at": "2026-11-22 12:00:00",
     *     "toko": {
     *       "marketplace": "Shopee"
     *     }
     *   }
     * }
     * ```
     *
     * Contoh di atas merupakan ilustrasi field response.
     * Field sebenarnya mengikuti model Pesanan dan relasi toko.
     *
     * **HTTP 200 - Pesanan Tidak Ditemukan**
     *
     * ```json
     * {
     *   "success": true,
     *   "data": null
     * }
     * ```
     *
     * ## Keterangan Error
     *
     * | Kode | Status | Keterangan |
     * |------|--------|------------|
     * | 200 | OK | Proses pencarian berhasil |
     * | 401 | Unauthorized | Pengguna belum login atau token tidak valid |
     * | 403 | Forbidden | Tidak memiliki izin akses |
     * | 500 | Server Error | Terjadi kesalahan pada server |
     *
     * ## Ketentuan
     *
     * 1. Nomor resi wajib dikirim melalui URL.
     * 2. Pencarian menggunakan kolom no_resi.
     * 3. Pencarian tidak dibatasi berdasarkan platform.
     * 4. Pencarian tidak dibatasi berdasarkan status pesanan.
     * 5. Relasi toko diambil menggunakan eager loading.
     * 6. Hanya satu pesanan pertama yang dikembalikan.
     * 7. Jika pesanan tidak ditemukan, hasil query bernilai null.
     * 8. Method ini hanya mengambil data tanpa mengubah status.
     */
    public function scanPesanan(
        ScanPesananService $scanPesanan,
        $no_resi
    ) {
        $data = $scanPesanan->scan($no_resi);

        if (!$data) {
            return response()->json([
                'success' => false,
                'message' => 'Pesanan dengan nomor resi tersebut tidak ditemukan.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => (object) [
                'noPesanan' => $data->no_pesanan,
                'noResi' => $data->no_resi,
                'platform' => $data->platform,
                'pembeli' => $data->nama_pembeli,
                'kurir' => $data->kurir,
                'marketplace' => $data->toko->marketplace ?? null,
            ],
        ], 200);
    }

    /**
     * Update Status Pesanan Setelah Scan
     *
     * Mengubah status pesanan menjadi dikirim berdasarkan
     * nomor pesanan serta mencatat riwayat pengiriman.
     *
     * ## Fitur
     *
     * - Mencari pesanan berdasarkan nomor pesanan.
     * - Mengubah status pesanan menjadi kirim.
     * - Mencatat pengguna yang melakukan pengiriman pesanan (orang yangmelakukan scan).
     * - Menambahkan riwayat progres pesanan berguna untuk melakukantracking pesanan.
     *
     * ## Header Request
     *
     * ```http
     * Accept: application/json
     * Authorization: Bearer {token}
     * ```
     *
     * ## Parameter Request
     *
     * | Parameter | Wajib | Tipe | Keterangan |
     * |-----------|-------|------|------------|
     * | no_pesanan | Ya | string | Nomor unik pesanan yang akan diperbarui |
     *
     * Parameter no_pesanan dikirim melalui URL.
     *
     * ## Contoh Request
     *
     * ```http
     * PATCH /api/pesanan/scan/20261120001A
     * Accept: application/json
     * Authorization: Bearer {token}
     * ```
     *
     * Request tidak memerlukan body.
     *
     * ## Hasil Respon
     *
     * **HTTP 200 - Berhasil**
     *
     * ```json
     * {
     *   "success": true,
     *   "data": {
     *     "no_pesanan": "20261120001A",
     *     "status": "kirim",
     *   }
     * }
     * ```
     *
     * Contoh response hanya menampilkan sebagian field.
     * Data sebenarnya mengikuti atribut model Pesanan
     * yang dikembalikan controller.
     *
     * ## Keterangan Error
     *
     * | Kode | Status | Keterangan |
     * |------|--------|------------|
     * | 200 | OK | Status pesanan berhasil diperbarui |
     * | 401 | Unauthorized | Pengguna belum login atau token tidak valid |
     * | 403 | Forbidden | Tidak memiliki izin akses |
     * | 404 | Not Found | Nomor pesanan tidak ditemukan |
     * | 500 | Server Error | Terjadi kesalahan pada server |
     *
     * ## Ketentuan
     *
     * 1. Nomor pesanan wajib dikirim melalui URL.
     * 2. Pesanan harus terdaftar dalam database.
     * 3. Status pesanan diperbarui menjadi kirim.
     * 4. id_user_kirim diambil dari pengguna login.
     * 5. Sistem membuat riwayat pada tabel progres_pesanans.
     * 6. Pesanan dikunci selama proses perubahan berlangsung.
     *
     * **Catatan Mobile:**
     *
     * Aplikasi mobile dapat memutar suara MP3 berdasarkan
     * response API sebagai indikator keberhasilan atau
     * kegagalan scan.
     *
     * File MP3 dapat disimpan secara lokal di aplikasi mobile
     * sehingga tidak perlu dikirim melalui API.
     */
    public function scanUpdate(
        ScanPesananService $scanPesananService,
        $no_pesanan
    ) {
        $data = $scanPesananService->scanUpdate($no_pesanan);

        if (!$data['success']) {
            return response()->json([
                'success' => false,
                'message' => $data['message'],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => $data['message'],
            'data' => (object) [
                'no_pesanan' => $data['data']->no_pesanan,
                'status' => $data['data']->status,
            ],
        ], 200);
    }

    /**
     * Riwayat Pesanan Dikirim
     *
     * Menampilkan riwayat pesanan yang telah dikirim
     * berdasarkan pengguna yang sedang login.
     *
     * ## Fitur
     *
     * - Menampilkan daftar riwayat pesanan dikirim.
     * - Menampilkan jumlah pesanan pada field dikirim.
     * - Mendukung pencarian pesanan.
     * - Mendukung filter tanggal.
     * - Mendukung filter marketplace Shopee dan TikTok.
     * - Menampilkan nomor pesanan, nomor resi, platform,
     *   nama pembeli, kurir, dan marketplace.
     *
     * ## Header Request
     *
     * ```http
     * Accept: application/json
     * Authorization: Bearer {token}
     * ```
     *
     * ## Parameter Request
     *
     * | Parameter | Wajib | Tipe | Batasan | Keterangan |
     * |-----------|-------|------|---------|------------|
     * | search | Tidak | string | Maks. 255 karakter | Pencarian nomor pesanan, resi, atau pembeli |
     * | tanggal | Tidak | date | YYYY-MM-DD | Filter berdasarkan tanggal |
     * | marketplace | Tidak | string | Shopee, TikTok | Filter berdasarkan marketplace |
     *
     * Semua parameter dikirim melalui query string.
     *
     * Jika parameter tidak diisi, filter tersebut tidak diterapkan.
     *
     * ## Contoh Request
     *
     * Tanpa filter:
     *
     * ```http
     * GET /api/pesanan/riwayat
     * Accept: application/json
     * Authorization: Bearer {token}
     * ```
     *
     * Dengan pencarian:
     *
     * ```http
     * GET /api/pesanan/riwayat?search=JNE123456789
     * ```
     *
     * Dengan filter tanggal:
     *
     * ```http
     * GET /api/pesanan/riwayat?tanggal=2026-11-20
     * ```
     *
     * Dengan filter marketplace:
     *
     * ```http
     * GET /api/pesanan/riwayat?marketplace=Shopee
     * ```
     *
     * Dengan semua filter:
     *
     * ```http
     * GET /api/pesanan/riwayat?search=JNE123456789&tanggal=2026-11-20&marketplace=Shopee
     * ```
     *
     * ## Hasil Respon
     *
     * **HTTP 200 - Berhasil**
     *
     * ```json
     * {
     *   "success": true,
     *   "dikirim": 2,
     *   "data": [
     *     {
     *       "noPesanan": "20261120001A",
     *       "noResi": "JNE123456789",
     *       "platform": "marketplace",
     *       "pembeli": "Siti Aminah",
     *       "kurir": "JNE",
     *       "marketplace": "Shopee"
     *     },
     *     {
     *       "noPesanan": "20261120002A",
     *       "noResi": "SPX987654321",
     *       "platform": "marketplace",
     *       "pembeli": "Budi Santoso",
     *       "kurir": "SPX",
     *       "marketplace": "Shopee"
     *     }
     *   ]
     * }
     * ```
     *
     * Keterangan field:
     *
     * | Field | Tipe | Keterangan |
     * |-------|------|------------|
     * | success | boolean | Status keberhasilan request |
     * | dikirim | integer | Jumlah pesanan yang ditemukan |
     * | data | array | Daftar riwayat pesanan |
     * | noPesanan | string | Nomor unik pesanan |
     * | noResi | string/null | Nomor resi pengiriman |
     * | platform | string | Platform pesanan |
     * | pembeli | string | Nama pembeli |
     * | kurir | string/null | Nama kurir pengiriman |
     * | marketplace | string/null | Nama marketplace dari relasi toko |
     *
     * **HTTP 200 - Data Kosong**
     *
     * ```json
     * {
     *   "success": true,
     *   "dikirim": 0,
     *   "data": []
     * }
     * ```
     *
     * **HTTP 422 - Parameter Tidak Valid**
     *
     * ```json
     * {
     *   "message": "The selected marketplace is invalid.",
     *   "errors": {
     *     "marketplace": [
     *       "The selected marketplace is invalid."
     *     ]
     *   }
     * }
     * ```
     *
     * ## Keterangan Error
     *
     * | Kode | Status | Keterangan |
     * |------|--------|------------|
     * | 200 | OK | Riwayat berhasil diambil |
     * | 401 | Unauthorized | Token tidak valid atau belum login |
     * | 403 | Forbidden | Tidak memiliki izin akses |
     * | 422 | Validation Error | Parameter pencarian atau filter tidak valid |
     * | 500 | Server Error | Terjadi kesalahan pada server |
     *
     * ## Ketentuan
     *
     * 1. Parameter search bersifat opsional.
     * 2. Panjang search maksimal 255 karakter.
     * 3. Parameter tanggal wajib menggunakan YYYY-MM-DD
     *    jika dikirim.
     * 4. Marketplace hanya menerima Shopee atau TikTok.
     * 5. Untuk menampilkan semua marketplace, parameter
     *    marketplace tidak perlu dikirim.
     * 6. Nilai marketplace=null sebagai teks tidak valid.
     * 7. Filter dapat digunakan secara bersamaan.
     * 8. Field dikirim dihitung berdasarkan jumlah data
     *    yang dikembalikan oleh service.
     * 9. Nama marketplace diambil melalui relasi toko.
     * 10. Jika relasi toko kosong, marketplace bernilai null.
     * 11. Jika tidak ada data, dikirim bernilai 0
     *     dan data berupa array kosong.
     * 12. Endpoint hanya menampilkan riwayat,
     *     tidak melakukan perubahan status pesanan.
     */
    public function riwayat(ScanPesananService $scanPesananService, Request $request)
    {
        $request->validate([
            'search' => 'nullable|string|max:255',
            'tanggal' => 'nullable|date_format:Y-m-d',
            'marketplace' => 'nullable|in:Shopee,TikTok',
        ]);

        $data = $scanPesananService->riwayat($request);

        return response()->json([
            'success' => true,
            'dikirim' => $data->count(),
            'data' => $data->map(function ($item) {
                return (object) [
                    'noPesanan' => $item->no_pesanan,
                    'noResi' => $item->no_resi,
                    'platform' => $item->platform,
                    'pembeli' => $item->nama_pembeli,
                    'kurir' => $item->kurir,
                    'marketplace' => $item->toko?->marketplace,
                ];
            }),
        ], 200);
    }
}
