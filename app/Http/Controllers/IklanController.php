<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Iklan;
use App\Models\Toko;

class IklanController extends Controller
{
    public function index(Request $request)
    {
        $query = Iklan::with('toko');

        // Search
        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('no_iklan', 'like', "%{$search}%")
                  ->orWhere('metode_pembayaran', 'like', "%{$search}%")
                  ->orWhereHas('toko', function ($toko) use ($search) {
                      $toko->where('nama_toko', 'like', "%{$search}%")
                           ->orWhere('marketplace', 'like', "%{$search}%");
                  });
            });
        }

        // Filter marketplace
        if ($request->filled('marketplace')) {
            $query->whereHas('toko', function ($q) use ($request) {
                $q->where('marketplace', $request->marketplace);
            });
        }

        // Filter toko
        if ($request->filled('id_toko')) {
            $query->where('id_toko', $request->id_toko);
        }

        // Filter tanggal dari
        if ($request->filled('tanggal_dari')) {
            $query->whereDate('tanggal', '>=', $request->tanggal_dari);
        }

        // Filter tanggal sampai
        if ($request->filled('tanggal_sampai')) {
            $query->whereDate('tanggal', '<=', $request->tanggal_sampai);
        }

        $iklan = $query->orderBy('tanggal', 'desc')
                       ->paginate(10)
                       ->withQueryString();

        $totalPembayaran = (clone $query)->sum('jumlah_pembayaran');

        $iklan = $query->orderBy('tanggal', 'desc')
                       ->paginate(10)
                       ->withQueryString();
        
        return view('master.iklan', compact('iklan', 'totalPembayaran'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'no_iklan'            => 'required|unique:iklan,no_iklan',
            'tanggal'             => 'required|date',
            'id_toko'             => 'required|exists:toko,id_toko',
            'jumlah_pembayaran'   => 'required|numeric',
            'saldo'               => 'required|numeric',
            'metode_pembayaran'   => 'required|string|max:50',
        ]);

        Iklan::create([
            'no_iklan'           => $request->no_iklan,
            'tanggal'            => $request->tanggal,
            'id_toko'            => $request->id_toko,
            'jumlah_pembayaran'  => $request->jumlah_pembayaran,
            'saldo'              => $request->saldo,
            'metode_pembayaran'  => $request->metode_pembayaran,
        ]);

        return redirect()->back()->with('success', 'Data iklan berhasil ditambahkan.');
    }

    public function update(Request $request, $no_iklan)
    {
        $request->validate([
            'tanggal'             => 'required|date',
            'id_toko'             => 'required|exists:toko,id_toko',
            'jumlah_pembayaran'   => 'required|numeric',
            'saldo'               => 'required|numeric',
            'metode_pembayaran'   => 'required|string|max:50',
        ]);

        $iklan = Iklan::findOrFail($no_iklan);

        $iklan->update([
            'tanggal'            => $request->tanggal,
            'id_toko'            => $request->id_toko,
            'jumlah_pembayaran'  => $request->jumlah_pembayaran,
            'saldo'              => $request->saldo,
            'metode_pembayaran'  => $request->metode_pembayaran,
        ]);

        return redirect()->back()->with('success', 'Data iklan berhasil diperbarui.');
    }

    public function destroy($no_iklan)
    {
        $iklan = Iklan::findOrFail($no_iklan);
        $iklan->delete();

        return redirect()->back()->with('success', 'Data iklan berhasil dihapus.');
    }

    public function show($no_iklan)
    {
        $iklan = Iklan::with('toko')->findOrFail($no_iklan);

        return response()->json($iklan);
    }

    /**
     * AJAX Marketplace
     */
    public function getMarketplace()
    {
        $marketplace = Toko::select('marketplace')
            ->whereNotNull('marketplace')
            ->distinct()
            ->orderBy('marketplace')
            ->pluck('marketplace');

        return response()->json($marketplace);
    }

    /**
     * AJAX Toko berdasarkan Marketplace
     */
    public function getTokoByMarketplace(Request $request)
    {
        $query = Toko::query();

        if ($request->filled('marketplace')) {
            $query->where('marketplace', $request->marketplace);
        }

        $toko = $query->orderBy('nama_toko')
                      ->get([
                          'id_toko',
                          'nama_toko',
                          'marketplace'
                      ]);

        return response()->json($toko);
    }
}