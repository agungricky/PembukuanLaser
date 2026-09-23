<?php

namespace App\Services\Gudang;

use App\Exports\gudangStokExport;
use App\Imports\stokImport;
use App\Models\mutasi_stok;
use App\Models\Produk;
use App\Models\stok_produk;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class ProdukService
{
    public function produk()
    {
        return view('gudang.produk');
    }

    public function produkShow($id)
    {
        $produk = Produk::with('stok_produk', 'kategori')
            ->where('sku', $id)
            ->first();

        return response()->json($produk);
    }

    public function updatestok(Request $request)
    {
        $request->validate([
            'sku_id' => 'required',
            'btn' => 'required|in:add,edit',
        ]);

        DB::beginTransaction();
        try {
            $stok = stok_produk::where('sku_id', $request->sku_id)->first();

            if ($stok && $request->btn == 'add') {
                stok_produk::where('sku_id', $request->sku_id)->update([
                    'jumlah_tersedia' => $stok->jumlah_tersedia + $request->jumlah_add,
                ]);

                mutasi_stok::create([
                    'stok_produk_id' => $stok->id,
                    'gudang_id' => auth()->id(),
                    'jenis_mutasi' => 'masuk',
                    'jumlah' => $request->jumlah_add,
                    'keterangan' => null,
                ]);
            } elseif ($stok && $request->btn == 'edit') {
                stok_produk::where('sku_id', $request->sku_id)->update([
                    'jumlah_tersedia' => $request->jumlah_edit,
                ]);

                mutasi_stok::create([
                    'stok_produk_id' => $stok->id,
                    'gudang_id' => auth()->id(),
                    'jenis_mutasi' => 'edit',
                    'jumlah' => $request->jumlah_edit,
                    'keterangan' => $request->keterangan
                    .' (stok awal '
                    .$stok->jumlah_tersedia
                    .', stok akhir '
                    .$request->jumlah_edit
                    .')',
                ]);
            } else {
                if ($request->btn == 'add') {
                    $stokProduk = stok_produk::create([
                        'sku_id' => $request->sku_id,
                        'jumlah_tersedia' => $request->jumlah_add,
                        'min_stok' => 5,
                    ]);

                    mutasi_stok::create([
                        'stok_produk_id' => $stokProduk->id,
                        'gudang_id' => auth()->id(),
                        'jenis_mutasi' => 'masuk',
                        'jumlah' => $request->jumlah_add,
                        'keterangan' => null,
                    ]);
                } elseif ($request->btn == 'edit') {
                    $stokProduk = stok_produk::create([
                        'sku_id' => $request->sku_id,
                        'jumlah_tersedia' => $request->jumlah_edit,
                        'min_stok' => 5,
                    ]);

                    mutasi_stok::create([
                        'stok_produk_id' => $stokProduk->id,
                        'gudang_id' => auth()->id(),
                        'jenis_mutasi' => 'masuk',
                        'jumlah' => $request->jumlah_edit,
                        'keterangan' => null,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Stok berhasil di Update.',
            ]);

        } catch (Exception $e) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan stok: '.$e->getMessage(),
            ], 500);
        }
    }

    public function stokExport()
    {
        return Excel::download(new gudangStokExport, 'Stok-'.now()->format('Y-m-d').'.xlsx');
    }

    public function stokImport(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls',
        ]);

        $fullPath = null;
        try {
            $file = $request->file('file');
            $folder = storage_path('app/imports');
            if (! is_dir($folder)) {
                mkdir($folder, 0777, true);
            }

            $namaFile = time().'_'.$file->getClientOriginalName();
            $fileBaru = $file->move($folder, $namaFile);
            $fullPath = $fileBaru->getPathname();
            $import = new stokImport;
            Excel::import($import, $fullPath);
            $perubahan = $import->getDataBerubah();
            $skuTidakDitemukan = $import->getSkuTidakDitemukan();

            if (empty($perubahan) && empty($skuTidakDitemukan)) {
                return response()->json([
                    'status' => false,
                    'type' => 'no_changes',
                    'message' => 'Tidak ada perubahan stok.',
                ]);
            }

            $token = (string) Str::uuid();
            Cache::put(
                'import_stok_'.$token,
                [
                    'perubahan' => $perubahan,
                    'sku_tidak_ditemukan' => $skuTidakDitemukan,
                ],
                now()->addMinutes(10)
            );

            return response()->json([
                'status' => true,
                'jumlah' => count($perubahan),
                'jumlah_tidak_ditemukan' => count($skuTidakDitemukan),
                'data' => $perubahan,
                'sku_tidak_ditemukan' => $skuTidakDitemukan,
                'token' => $token,
            ]);

        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 500);

        } finally {
            if ($fullPath && file_exists($fullPath)) {
                unlink($fullPath);
            }
        }
    }

    public function importUpdate(Request $request)
    {
        $request->validate([
            'dataBerubah.*.stok_produk_id' => ['required', 'integer', 'exists:stok_produks,id'],
            'dataBerubah.*.sku' => ['required', 'string', 'max:100'],
            'dataBerubah.*.stok_lama' => ['required', 'integer', 'min:0'],
            'dataBerubah.*.stok_baru' => ['required', 'integer', 'min:0'],
            'dataBerubah.*.selisih' => ['required', 'integer'],

            'skuBaru.*.sku' => ['required', 'string', 'max:100'],
            'skuBaru.*.stok_excel' => ['required', 'integer', 'min:0'],
        ]);

        DB::beginTransaction();
        try {
            $dataBerubah = collect($request->input('dataBerubah', []))
                ->map(fn ($item) => (object) $item);

            $skuBaru = collect($request->input('skuBaru', []))
                ->map(fn ($item) => (object) $item);

            foreach ($dataBerubah as $value) {
                stok_produk::where('sku_id', $value->sku)->update([
                    'jumlah_tersedia' => $value->stok_baru,
                ]);

                $data = stok_produk::where('sku_id', $value->sku)->first();
                mutasi_stok::create([
                    'stok_produk_id' => $data->id,
                    'gudang_id' => Auth::user()->id,
                    'produksi_id' => null,
                    'adm_penjualan_id' => null,
                    'jenis_mutasi' => 'edit',
                    'jumlah' => $value->stok_baru,
                    'keterangan' => 'Penyesuai produk (stok awal '.$value->stok_lama.', stok akhir '.$value->stok_baru.')',
                ]);
            }

            foreach ($skuBaru as $value) {
                stok_produk::create([
                    'sku_id' => $value->sku,
                    'jumlah_tersedia' => $value->stok_excel,
                    'min_stok' => 5,
                ]);

                $data = stok_produk::where('sku_id', $value->sku)->first();
                mutasi_stok::create([
                    'stok_produk_id' => $data->id,
                    'gudang_id' => Auth::user()->id,
                    'produksi_id' => null,
                    'adm_penjualan_id' => null,
                    'jenis_mutasi' => 'masuk',
                    'jumlah' => $value->stok_excel,
                    'keterangan' => null,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Semua Data Berhasil di Simpan',
            ]);
        } catch (Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Gagal memproses barang',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
