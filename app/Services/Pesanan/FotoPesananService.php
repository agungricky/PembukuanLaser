<?php

namespace App\Services\Pesanan;

use App\Models\FotoMeta;
use App\Models\Pesanan;
use App\Models\PesananPerProduk;
use Illuminate\Support\Facades\Auth;
use GuzzleHttp\Psr7\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;

class FotoPesananService
{
    public function fotoPesananJadi($fileGatorService, $request)
    {
        $hasilFoto = [];

        DB::transaction(function () use (
            $request,
            $fileGatorService,
            &$hasilFoto
        ) {
            $pesanan = Pesanan::query()
                ->where('no_pesanan', $request->no_pesanan)
                ->where('platform', 'meta')
                ->lockForUpdate()
                ->firstOrFail();

            $perProduk = PesananPerProduk::query()
                ->where('no_pesanan', $pesanan->no_pesanan)
                ->get()
                ->keyBy('id_per_produk');

            $files = $request->file('foto', []);
            foreach ($files as $idPerProduk => $file) {
                if (!$perProduk->has($idPerProduk)) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        "foto.$idPerProduk" =>
                        "ID per produk {$idPerProduk} tidak ditemukan dalam pesanan ini.",
                    ]);
                }
            }

            if ($perProduk->count() !== count($files)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'foto' => 'Setiap per produk wajib memiliki satu foto.',
                ]);
            }

            $fileGatorService->login();
            foreach ($files as $idPerProduk => $file) {
                $extension = strtolower(
                    $file->getClientOriginalExtension()
                );

                $namaFile = $pesanan->no_pesanan . $idPerProduk . '.' . $extension;
                $pathFoto = $fileGatorService->upload($file, $namaFile, 'FOTO_META');

                if (!is_string($pathFoto) || $pathFoto === '') {
                    throw new \RuntimeException(
                        "Upload foto produk {$idPerProduk} gagal."
                    );
                }

                $fotoMeta = FotoMeta::create([
                    'perproduk_id' => $idPerProduk,
                    'foto' => $pathFoto,
                    'user_id' => Auth::id(),
                ]);

                $fotoMeta->refresh();
                $fotoMeta->foto = URL::signedRoute(
                    'file',
                    [
                        'path' => $fotoMeta->foto,
                    ]
                );

                $hasilFoto[] = $fotoMeta;
            }

            $pesanan->update([
                'foto_at' => now()
            ]);
        });

        return $hasilFoto;
    }
}
