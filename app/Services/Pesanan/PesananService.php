<?php

namespace App\Services\Pesanan;

use App\Models\bukti_pembayaran;
use App\Models\customer_addresses;
use App\Models\Pesanan;
use App\Models\PesananPerProduk;
use App\Services\Api\FileGatorService as ApiFileGatorService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PesananService
{
    private function generateNoPesanan(): string
    {
        $tanggal = now()->format('ymd');
        $nomorTerakhir = Pesanan::where('no_pesanan', 'like', $tanggal . '%')
            ->whereRaw(
                'no_pesanan REGEXP ?',
                ['^' . $tanggal . '[0-9]{3,}[A-Za-z]$']
            )
            ->selectRaw(
                'MAX(CAST(SUBSTRING(no_pesanan, 7, CHAR_LENGTH(no_pesanan) - 7) AS UNSIGNED)) as nomor'
            )
            ->value('nomor');

        $nomorBerikutnya = ($nomorTerakhir ?? 0) + 1;
        $nomorUrut = str_pad($nomorBerikutnya, 3, '0', STR_PAD_LEFT);
        $random = Str::upper(Str::random(1));

        return $tanggal . $nomorUrut . $random;
    }

    public function index()
    {
        $noPesananBaru = $this->generateNoPesanan();
        $user = collect(Auth::user())
            ->only(['id', 'name'])
            ->toArray();

        return (object) [
            'no_pesanan' => $noPesananBaru,
            'user' => $user,
        ];
    }

    public function store(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $pesanan = Pesanan::create([
                'no_pesanan' => $request->no_pesanan,
                'platform' => $request->platform,
                'tanggal' => Carbon::parse($request->tanggal)->toDateString(),
                'input_at' => Carbon::parse($request->tanggal)->format('Y-m-d H:i:s'),
                'no_resi' => null,
                'resi_printed_at' => null,
                'resi_last_printed_at' => null,
                'resi_printed_by' => null,
                'resi_print_count' => 0,
                'id_toko' => null,
                'id_user' => $request->user_id,
                'id_user_kirim' => null,
                'tanggal_kirim' => null,
                'nama_pembeli' => $request->nama_pembeli,
                'username' => $request->nama_pembeli,
                'kurir' => null,
                'status' => 'proses',
                'status_cek' => 0,
                'total_hpp' => $request->total_hpp,
                'total_harga' => $request->total_harga,
                'total_admin' => $request->total_admin,
                'pencairan' => null,
                'chat_masuk_at' => $request->filled('chat_masuk_at')
                    ? \Carbon\Carbon::parse($request->chat_masuk_at)
                    ->setTimezone('Asia/Jakarta')
                    ->format('Y-m-d H:i:s')
                    : null,
                'batas_kirim_at' => Carbon::parse($request->tanggal_order)->addDays(3),
            ]);

            customer_addresses::create([
                'no_pesanan' => $pesanan->no_pesanan,
                'provinsi' => $request->provinsi,
                'kabupaten_kota' => $request->kabupaten_kota,
                'kecamatan' => $request->kecamatan,
                'desa' => $request->desa,
                'alamat_lengkap' => $request->alamat_lengkap,
                'phone' => $request->phone,
            ]);

            $fileGatorService = app(ApiFileGatorService::class);
            $fileGatorService->login();

            $buktiBayar = null;
            $fileBukti = $request->file('bukti_bayar');
            if ($fileBukti) {
                $extension = strtolower(
                    $fileBukti->getClientOriginalExtension()
                );

                $namaFile = $pesanan->no_pesanan . '.' . $extension;

                $buktiBayar = $fileGatorService->upload(
                    $fileBukti,
                    $namaFile,
                    '/TF_META'
                );
            }

            bukti_pembayaran::create([
                'no_pesanan' => $pesanan->no_pesanan,
                'jenis_transaksi' => $request->jenis_transaksi,
                'cod' => $request->cod,
                'non_cod' => $request->non_cod,
                'dp' => $request->dp,
                'bukti_bayar' => $buktiBayar,
            ]);

            foreach ($request->input('pesanan_perproduk', []) as $index => $value) {
                $fotoProduk = null;
                $file = $request->file("pesanan_perproduk.$index.foto_produk");

                if ($file) {
                    $extension = strtolower(
                        $file->getClientOriginalExtension()
                    );

                    $namaFile = $pesanan->no_pesanan . '_'
                        . str_pad(
                            $index + 1,
                            2,
                            '0',
                            STR_PAD_LEFT
                        )
                        . '.' . $extension;

                    $fotoProduk = $fileGatorService->upload(
                        $file,
                        $namaFile,
                        '/REQ_FOTO'
                    );
                }

                PesananPerProduk::create([
                    'no_pesanan' => $pesanan->no_pesanan,
                    'nama_produk' => $value['nama_produk'],
                    'variasi' => $value['variasi'] ?? null,
                    'jumlah' => $value['jumlah'],
                    'hpp' => $value['hpp'],
                    'harga' => $value['harga'],
                    'sku' => $value['sku'],
                    'custom' => $value['custom'] ?? null,
                    'status_pesanan' => $value['status_pesanan'] ?? 0,
                    'mutasi_stok_id' => $value['mutasi_stok_id'] ?? null,
                    'foto_produk' => $fotoProduk,
                    'catatan' => $value['catatan'] ?? null,
                ]);
            }

            return $pesanan;
        });
    }
}
