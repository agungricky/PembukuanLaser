<?php

namespace App\Http\Controllers;

use App\Models\kategori;
use App\Models\Produk;
use Illuminate\Http\Request;

class SkuController extends Controller
{
    /**
     * Menampilkan daftar produk.
     */
    public function index()
    {
        $kategori = kategori::all();

        return view('master.sku', compact('kategori'));
    }

    public function skudata()
    {
        $produk = Produk::with([
            'kategori',
            'stok_produk',
        ])
            ->orderBy('updated_at', 'DESC')
            ->get();

        return response()->json($produk);
    }

    /**
     * Menyimpan produk baru.
     */
    public function viewstore(Request $request)
    {
        $request->validate([
            'sku' => ['required', 'string', 'max:50', 'unique:produk,sku'],
            'nama_produk' => ['nullable', 'string', 'max:255'],
            'variasi' => ['nullable', 'string', 'max:255'],
            'hpp' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'custom' => ['required'],
        ]);

        $prefix = strtoupper(trim($request->sku ?? ''));
        $data = Produk::whereRaw(
            'sku REGEXP ?',
            ['^'.preg_quote($prefix).'[0-9]+C?$']
        )
            ->orderByRaw(
                'CAST(SUBSTRING(sku, ?) AS UNSIGNED) DESC',
                [strlen($prefix) + 1]
            )
            ->first();

        if ($data === null) {
            if ($request->custom === 'Y') {
                $view = [
                    'sku' => $request->sku. 1 .'C',
                    'nama_produk' => $request->nama_produk,
                    'variasi' => $request->variasi,
                    'hpp' => $request->hpp,
                    'status' => 'aktif',
                    'kategori_id' => $request->kategori_id,
                ];
            } elseif ($request->custom === 'T') {
                $view = [
                    'sku' => $request->sku. 1,
                    'nama_produk' => $request->nama_produk,
                    'variasi' => $request->variasi,
                    'hpp' => $request->hpp,
                    'status' => 'aktif',
                    'kategori_id' => $request->kategori_id,
                ];
            }

            return response()->json([
                'status' => true,
                'terakhir' => ['sku' => 'SKU Tidak ditemukan atau mungkin merupakan SKU Baru'],
                'baru' => [
                    'sku' => $view['sku'],
                    'nama_produk' => $view['nama_produk'],
                    'variasi' => $view['variasi'],
                    'hpp' => $view['hpp'],
                    'status' => $view['status'],
                    'kategori_id' => $view['kategori_id'],
                ],
            ]);
        }

        $angka = (int) preg_replace('/\D/', '', $data->sku);
        $angka++;
        $angkaFormat = str_pad($angka, 3, '0', STR_PAD_LEFT);
        $skuBaru = $prefix.$angkaFormat;

        if ($request->custom === 'Y') {
            $view = [
                'sku' => $skuBaru.'C',
                'nama_produk' => $request->nama_produk,
                'variasi' => $request->variasi,
                'hpp' => $request->hpp,
                'status' => 'aktif',
                'kategori_id' => $request->kategori_id,
            ];
        } elseif ($request->custom === 'T') {
            $view = [
                'sku' => $skuBaru,
                'nama_produk' => $request->nama_produk,
                'variasi' => $request->variasi,
                'hpp' => $request->hpp,
                'status' => $request->status,
                'kategori_id' => $request->kategori_id,
            ];
        }

        return response()->json([
            'status' => true,
            'terakhir' => [
                'sku' => $data->sku,
                'nama_produk' => $data->nama_produk,
                'variasi' => $data->variasi,
                'hpp' => $data->hpp,
                'status' => $data->status,
                'kategori_id' => $data->kategori_id,
            ],
            'baru' => [
                'sku' => $view['sku'],
                'nama_produk' => $view['nama_produk'],
                'variasi' => $view['variasi'],
                'hpp' => $view['hpp'],
                'status' => $view['status'],
                'kategori_id' => $view['kategori_id'],
            ],
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'sku' => ['required', 'string', 'max:50', 'unique:produk,sku'],
            'nama_produk' => ['nullable', 'string', 'max:255'],
            'variasi' => ['nullable', 'string', 'max:255'],
            'hpp' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
        ]);

        Produk::create([
            'sku' => $request->sku,
            'nama_produk' => $request->nama_produk,
            'variasi' => $request->variasi,
            'hpp' => $request->hpp,
            'status' => 'aktif',
            'kategori_id' => $request->kategori_id,
        ]);

        return response()->json([
            'sukses' => true,
            'message' => 'Data berhasil dibuat',
        ]);
    }

    public function edit($sku)
    {
        $produk = Produk::with('kategori')->where('sku', $sku)->firstOrFail();

        return response()->json([
            'sku' => $produk->sku,
            'nama_produk' => $produk->nama_produk,
            'variasi' => $produk->variasi,
            'hpp' => $produk->hpp,
            'status' => $produk->status,
            'kategori' => $produk->kategori?->nama_kategori ?? '-',
            'kategori_id' => $produk->kategori?->id ?? null,
        ]);
    }

    /**
     * Memperbarui produk.
     */
    public function update(Request $request, $sku)
    {
        $request->validate([
            'nama_produk' => ['nullable', 'string', 'max:255'],
            'variasi' => ['nullable', 'string', 'max:255'],
            'hpp' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
        ]);

        Produk::where('sku', $sku)->update([
            'status' => $request->status,
            'nama_produk' => $request->nama_produk,
            'variasi' => $request->variasi,
            'hpp' => $request->hpp,
            'kategori_id' => $request->kategori_id,
            'status' => $request->status,
        ]);

        return response()->json([
            'message' => 'Produk berhasil diperbarui.',
        ]);
    }

    /**
     * Menghapus produk.
     */
    public function destroy(
        Request $request,
        string $sku
    ) {
        $produk = Produk::findOrFail($sku);

        $produk->delete();

        return redirect()
            ->route('sku.index', [
                'search' => $request->input('search'),
                'page' => $request->input('page'),
            ])
            ->with(
                'success',
                'Produk berhasil dihapus.'
            );
    }

    /**
     * Menampilkan detail produk dalam format JSON.
     */
    public function show(string $sku)
    {
        $produk = Produk::findOrFail($sku);

        return response()->json($produk);
    }

    /**
     * Mengubah teks kosong menjadi null.
     */
    private function nullableText(
        ?string $value
    ): ?string {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value === ''
            ? null
            : $value;
    }
}
