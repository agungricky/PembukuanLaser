<?php

namespace App\Http\Controllers;

use App\Models\Produk;
use Illuminate\Http\Request;

class SkuController extends Controller
{
    /**
     * Menampilkan daftar produk.
     */
    public function index(Request $request)
    {
        $search = trim((string) $request->query('search', ''));

        $produk = Produk::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('sku', 'like', "%{$search}%")
                        ->orWhere('nama_produk', 'like', "%{$search}%")
                        ->orWhere('variasi', 'like', "%{$search}%")
                        ->orWhere('hpp', 'like', "%{$search}%");
                });
            })

            /*
             * Mengurutkan SKU secara natural:
             *
             * BNR1
             * BNR2
             * BNR3
             * ...
             * BNR10
             *
             * Bukan:
             * BNR1
             * BNR10
             * BNR11
             * BNR2
             */
            ->orderByRaw("
                REGEXP_REPLACE(sku, '[0-9]+$', '') ASC
            ")
            ->orderByRaw("
                CASE
                    WHEN sku REGEXP '[0-9]+$'
                    THEN CAST(
                        REGEXP_SUBSTR(sku, '[0-9]+$')
                        AS UNSIGNED
                    )
                    ELSE 0
                END ASC
            ")
            ->orderBy('sku', 'asc')
            ->paginate(10)
            ->withQueryString();

        return view('master.sku', compact(
            'produk',
            'search'
        ));
    }

    /**
     * Menyimpan produk baru.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'sku' => [
                'required',
                'string',
                'max:50',
                'unique:produk,sku',
            ],

            'nama_produk' => [
                'nullable',
                'string',
                'max:255',
            ],

            'variasi' => [
                'nullable',
                'string',
                'max:255',
            ],

            'hpp' => [
                'required',
                'numeric',
                'min:0',
                'max:99999999.99',
            ],
        ]);

        $data['sku'] = strtoupper(
            trim($data['sku'])
        );

        $data['nama_produk'] = $this->nullableText(
            $data['nama_produk'] ?? null
        );

        $data['variasi'] = $this->nullableText(
            $data['variasi'] ?? null
        );

        Produk::create($data);

        return redirect()
            ->route('sku.index')
            ->with(
                'success',
                'Produk berhasil ditambahkan.'
            );
    }

    /**
     * Memperbarui produk.
     */
    public function update(
        Request $request,
        string $sku
    ) {
        $produk = Produk::findOrFail($sku);

        $data = $request->validate([
            'nama_produk' => [
                'nullable',
                'string',
                'max:255',
            ],

            'variasi' => [
                'nullable',
                'string',
                'max:255',
            ],

            'hpp' => [
                'required',
                'numeric',
                'min:0',
                'max:99999999.99',
            ],
        ]);

        $data['nama_produk'] = $this->nullableText(
            $data['nama_produk'] ?? null
        );

        $data['variasi'] = $this->nullableText(
            $data['variasi'] ?? null
        );

        $produk->update($data);

        return redirect()
            ->route('sku.index', [
                'search' => $request->input('search'),
                'page'   => $request->input('page'),
            ])
            ->with(
                'success',
                'Produk berhasil diperbarui.'
            );
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
                'page'   => $request->input('page'),
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