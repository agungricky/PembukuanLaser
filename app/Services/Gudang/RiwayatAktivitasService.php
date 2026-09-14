<?php

namespace App\Services\Gudang;

use App\Models\mutasi_stok;
use Illuminate\Http\Request;

class RiwayatAktivitasService
{
    public function riwayatAktivitasData(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | Query awal
        |--------------------------------------------------------------------------
        */
        $query = mutasi_stok::with([
            'stok_produk.produk.kategori', 'gudang', 'admin_penjualan',
        ])->whereIn('jenis_mutasi', ['keluar', 'masuk', 'edit']);

        /*
        |--------------------------------------------------------------------------
        | Total seluruh data
        |--------------------------------------------------------------------------
        */
        $totalData = mutasi_stok::count();

        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */
        if ($request->filled('search.value')) {
            $search = $request->input('search.value');
            $query->where(function ($q) use ($search) {
                // dari tabel mutasi_stok
                $q->where('jenis_mutasi', 'like', "%{$search}%")
                    ->orWhere('keterangan', 'like', "%{$search}%")
                    ->orWhereHas('stok_produk.produk', function ($q) use ($search) {
                        $q->where('sku', 'like', "%{$search}%")
                            ->orWhere('nama_produk', 'like', "%{$search}%")
                            ->orWhere('variasi', 'like', "%{$search}%");

                    })
                    ->orWhereHas('stok_produk.produk.kategori', function ($q) use ($search) {

                        $q->where('nama_kategori', 'like', "%{$search}%");

                    });

            });
        }

        /*
        |--------------------------------------------------------------------------
        | Total setelah search
        |--------------------------------------------------------------------------
        */

        $totalFiltered = $query->count();

        /*
        |--------------------------------------------------------------------------
        | Order
        |--------------------------------------------------------------------------
        |
        | Riwayat terbaru ditampilkan paling atas
        |
        */
        $orderColumn = (int) $request->input('order.0.column', 5);
        $orderDirection = $request->input('order.0.dir', 'desc');
        $orderDirection = $orderDirection === 'asc' ? 'asc' : 'desc';
        if ($orderColumn === 5) {
            $query->orderBy('created_at', $orderDirection);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        /*
        |--------------------------------------------------------------------------
        | Pagination DataTables
        |--------------------------------------------------------------------------
        */

        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 10);

        /*
        |--------------------------------------------------------------------------
        | Ambil data
        |--------------------------------------------------------------------------
        */

        $data = $query
            ->skip($start)
            ->take($length)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Format Data
        |--------------------------------------------------------------------------
        */

        $result = [];
        foreach ($data as $index => $item) {
            $produk = $item->stok_produk?->produk;
            if ($orderColumn === 0 && $orderDirection === 'desc') {
                $no = $totalFiltered - $start - $index;
            } else {
                $no = $start + $index + 1;
            }

            $result[] = [
                'no' => $no,
                'produk' => $produk?->nama_produk ?? '-',
                'sku' => $produk?->sku ?? '-',
                'variasi' => $produk?->variasi ?? '-',
                'kategori' => $produk?->kategori?->nama_kategori ?? '-',
                'hpp' => $produk?->hpp ?? 0,
                'jumlah' => $item->jumlah ?? 0,
                'jenis_mutasi' => $item->jenis_mutasi ?? '-',
                'keterangan' => $item->keterangan ?? '-',
                'admin_gudang' => $item->gudang?->name ?? '-',
                'pengambil' => $item->admin_penjualan?->name ?? '-',
                'created_at' => $item->created_at,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | Response DataTables
        |--------------------------------------------------------------------------
        */

        return response()->json([
            'draw' => (int) $request->input('draw'),
            'recordsTotal' => $totalData,
            'recordsFiltered' => $totalFiltered,
            'data' => $result,

        ]);
    }
}
