<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pesanan;
use App\Models\Toko;
use Carbon\Carbon;

class PesananCekController extends Controller
{
    public function index(Request $request)
    {
        $allowedPerPage = [10, 20, 50, 100];

        $perPage = (int) $request->input('per_page', 20);

        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 20;
        }

        $query = Pesanan::with('toko')
            ->where('status_cek', 1)

            ->when($request->filled('no_pesanan'), function ($q) use ($request) {

                $keyword = trim($request->no_pesanan);

                $q->where(function ($sub) use ($keyword) {

                    $sub->where('no_pesanan', 'like', "%{$keyword}%")
                        ->orWhere('no_resi', 'like', "%{$keyword}%");

                });

            })

            ->when($request->filled('id_toko'), function ($q) use ($request) {

                $q->where('id_toko', $request->id_toko);

            })

            ->when($request->filled('status'), function ($q) use ($request) {

                $q->where('status', $request->status);

            });

        if ($request->filled('tanggal')) {

            $raw = trim($request->tanggal);
        
            if (str_contains($raw, ' s.d ')) {
        
                [$start, $end] = explode(' s.d ', $raw);
        
                $query->whereBetween('tanggal', [
                    Carbon::parse(trim($start))->startOfDay(),
                    Carbon::parse(trim($end))->endOfDay(),
                ]);
        
            } else {
        
                $query->whereDate('tanggal', Carbon::parse($raw)->toDateString());
        
            }
        
        }

        $pesanan = $query
            ->orderByDesc('tanggal')
            ->paginate($perPage)
            ->withQueryString();

        $jumlahPesanan = $pesanan->total();

        $daftarToko = Toko::orderBy('nama_toko')->get();

        return view('pesanan.cek', compact(
            'pesanan',
            'jumlahPesanan',
            'daftarToko',
            'perPage',
            'allowedPerPage'
        ));
    }

    public function aktifkan($no_pesanan)
    {
        Pesanan::where('no_pesanan', $no_pesanan)
            ->update([
                'status_cek' => 1
            ]);

        return back()->with('success', 'Pesanan berhasil dimasukkan ke daftar cek.');
    }

    public function selesai($no_pesanan)
    {
        Pesanan::where('no_pesanan', $no_pesanan)
            ->update([
                'status_cek' => 0
            ]);

        return back()->with('success', 'Pesanan berhasil dikeluarkan dari daftar cek.');
    }
}