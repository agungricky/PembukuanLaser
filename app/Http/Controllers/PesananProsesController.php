<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Pesanan;
use App\Models\Toko;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PesananProsesController extends Controller
{
    public function index(Request $request)
    {
        $allowedPerPage = [10, 20, 50, 100];
        $perPage = (int) $request->input('per_page', 20);
        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 50;
        }
    
        $query = Pesanan::with([
                'produk',
                'toko:id_toko,nama_toko',
            ])
            ->where('status', 'proses')

            ->when($request->filled('no_pesanan'), function ($q) use ($request) {
                $keyword = $request->input('no_pesanan');
    
                $q->where(function ($sub) use ($keyword) {
                    $sub->where('no_pesanan', 'like', "%{$keyword}%")
                        ->orWhere('no_resi', 'like', "%{$keyword}%");
                });
            })

            ->when($request->filled('id_toko'), function ($q) use ($request) {
                $q->where('id_toko', (int) $request->input('id_toko'));
            })
    
            ->orderByDesc('tanggal');

        if ($request->filled('tanggal')) {
    
            $raw = trim((string) $request->input('tanggal'));
    
            if (str_contains($raw, ' s.d ')) {
                [$startRaw, $endRaw] = explode(' s.d ', $raw, 2);
                $start = Carbon::parse($startRaw)->startOfDay();
                $end   = Carbon::parse($endRaw)->endOfDay();
            } else {
                $start = Carbon::parse($raw)->startOfDay();
                $end   = Carbon::parse($raw)->endOfDay();
            }
    
            if ($end->lt($start)) {
                [$start, $end] = [
                    $end->copy()->startOfDay(),
                    $start->copy()->endOfDay()
                ];
            }
    
            $query->whereBetween('tanggal', [$start, $end]);
        }

        $pesanan = $query->paginate($perPage)->withQueryString();

        foreach ($pesanan as $p) {
            $p->total = (int) $p->produk->sum('jumlah');
        }
    
        $total         = (int) $pesanan->sum('total');
        $jumlahPesanan = $pesanan->total();
    
        $daftarToko = Toko::select('id_toko', 'nama_toko', 'marketplace')
            ->orderBy('nama_toko')
            ->get();
    
        return view('pesanan.proses', [
            'pesanan'       => $pesanan,
            'daftarToko'    => $daftarToko,
            'total'         => $total,
            'jumlahPesanan' => $jumlahPesanan,
            'perPage'       => $perPage,
            'allowed'       => $allowedPerPage,
        ]);
    }

    public function ubahStatus(Request $request)
    {
        $request->validate([
            'selected'   => 'required|array|min:1',
            'selected.*' => 'string',
            'status'     => 'required|string|in:proses,kirim,selesai,return,pengembalian,batal',
            'notes'      => 'nullable|string|max:255',
        ]);

        $status   = $request->input('status');
        $selected = $request->input('selected');
        $notes    = $request->input('notes');

        if ($status === 'batal') {
            if (count($selected) !== 1) {
                return response('Pesanan hanya bisa dibatalkan satu per satu.', 422);
            }

            $noPesanan = $selected[0];

            try {
                DB::transaction(function () use ($noPesanan, $notes) {
                    $affected = Pesanan::where('no_pesanan', $noPesanan)
                        ->lockForUpdate()
                        ->update([
                            'status'       => 'batal',
                            'notes'        => $notes,
                            'total_hpp'    => 0,
                            'total_harga'  => 0,
                            'total_admin'  => 0,
                            'pencairan'    => 0,
                        ]);

                    if ($affected === 0) {
                        throw new \RuntimeException('Pesanan tidak ditemukan.');
                    }

                    DB::table('pesanan_per_produk')
                        ->where('no_pesanan', $noPesanan)
                        ->update([
                            'hpp'   => 0,
                            'harga' => 0,
                        ]);
                });
            } catch (\Throwable $e) {
                return response($e->getMessage(), 404);
            }

            return response()->json(['message' => 'Pesanan dibatalkan.']);
        }

        DB::transaction(function () use ($selected, $status, $notes) {
            $data = ['status' => $status];

            if (!is_null($notes)) {
                $data['notes'] = $notes;
            }

            Pesanan::whereIn('no_pesanan', $selected)->update($data);
        });

        return response()->json(['message' => 'Status pesanan berhasil diperbarui.']);
    }
}