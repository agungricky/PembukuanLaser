<?php

namespace App\Http\Controllers;

use App\Models\Pesanan;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PackingPesananController extends Controller
{
    public function index(Request $request)
    {
        $query = Pesanan::with([
                'toko:id_toko,nama_toko'
            ])
            ->where('status', 'proses')
            ->orderByDesc('tanggal')
            ->orderByDesc('no_pesanan');
    
        if ($request->filled('no_pesanan')) {
    
            $keyword = trim($request->no_pesanan);
    
            $query->where(function ($q) use ($keyword) {
    
                $q->where('no_pesanan', 'like', "%{$keyword}%")
                  ->orWhere('no_resi', 'like', "%{$keyword}%");
    
            });
    
        }
    
        if ($request->filled('tanggal')) {
    
            $raw = trim((string) $request->tanggal);
    
            try {
    
                if (str_contains($raw, ' s.d ')) {
    
                    [$startRaw, $endRaw] = explode(' s.d ', $raw, 2);
    
                    $start = Carbon::parse($startRaw)->startOfDay();
                    $end   = Carbon::parse($endRaw)->endOfDay();
    
                } else {
    
                    $start = Carbon::parse($raw)->startOfDay();
                    $end   = Carbon::parse($raw)->endOfDay();
    
                }
    
                if ($end->lt($start)) {
                    [$start, $end] = [$end, $start];
                }
    
                $query->whereBetween('tanggal', [
                    $start->toDateString(),
                    $end->toDateString(),
                ]);
    
            } catch (\Throwable $e) {
    
                Log::warning('Filter tanggal packing gagal.', [
                    'tanggal' => $raw,
                    'error'   => $e->getMessage(),
                ]);
    
            }
    
        }
    
        $pesanan = $query->get();
    
        $jumlahPesanan = $pesanan->count();
    
        $hariIni = Pesanan::whereDate('tanggal_kirim', today())
            ->where('id_user_kirim', Auth::id())
            ->count();
    
        $kurirHariIni = $this->getKurirHariIni();
    
        return view('packing.pesanan', [
            'pesanan'       => $pesanan,
            'jumlahPesanan' => $jumlahPesanan,
            'hariIni'       => $hariIni,
            'kurirHariIni'  => $kurirHariIni,
        ]);
    }
    
    public function scan(Request $request)
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => '❌ Auth error',
            ], 401);
        }
    
        $request->validate([
            'no_pesanan' => ['required', 'string', 'max:100'],
        ]);
    
        $kode = preg_replace('/\s+/', '', trim($request->no_pesanan));
    
        Log::info('Packing Scan', [
            'user_id' => Auth::id(),
            'kode'    => $kode,
        ]);
    
        $pesanan = Pesanan::with('toko')
            ->where('status', 'proses')
            ->where(function ($q) use ($kode) {
                $q->where('no_resi', $kode)
                  ->orWhere('no_pesanan', $kode);
            })
            ->first();
    
        if (!$pesanan) {
    
            $pesananLama = Pesanan::where(function ($q) use ($kode) {
                    $q->where('no_resi', $kode)
                      ->orWhere('no_pesanan', $kode);
                })
                ->first();
    
            if ($pesananLama) {
    
                return response()->json([
                    'success' => false,
                    'message' => "⚠ Pesanan {$pesananLama->no_pesanan} sudah berstatus {$pesananLama->status}",
                ], 409);
    
            }
    
            return response()->json([
                'success' => false,
                'message' => '❌ No. Pesanan / Resi tidak ditemukan',
            ], 404);
    
        }
    
        DB::transaction(function () use ($pesanan) {
    
            $pesanan->status = 'kirim';
            $pesanan->id_user_kirim = Auth::id();
            $pesanan->tanggal_kirim = now();
    
            $pesanan->save();
    
        });
    
        $ekspedisi = $this->detectEkspedisi($pesanan->kurir);
    
        Log::info('Packing Success', [
            'no_pesanan' => $pesanan->no_pesanan,
            'no_resi'    => $pesanan->no_resi,
            'kurir'      => $pesanan->kurir,
            'user_id'    => Auth::id(),
            'scan_time'  => $pesanan->tanggal_kirim,
        ]);
    
        $sisaProses = Pesanan::where('status', 'proses')->count();
    
        $hariIni = Pesanan::whereDate('tanggal_kirim', today())
            ->where('id_user_kirim', Auth::id())
            ->count();
    
        $kurirHariIni = $this->getKurirHariIni();
    
        return response()->json([
    
            'success' => true,
    
            'message' => "✅ {$pesanan->no_pesanan} berhasil dikirim",
    
            'no_pesanan' => $pesanan->no_pesanan,
    
            'no_resi' => $pesanan->no_resi,
    
            'scan_time' => $pesanan->tanggal_kirim->format('H:i:s'),
    
            'status' => 'kirim',
    
            'ekspedisi' => $ekspedisi,
    
            'remaining_proses' => $sisaProses,
    
            'hari_ini' => $hariIni,
    
            'kurir_hari_ini' => [
    
                'spx' => (int) ($kurirHariIni->spx ?? 0),
    
                'jnt' => (int) ($kurirHariIni->jnt ?? 0),
    
                'anteraja' => (int) ($kurirHariIni->anteraja ?? 0),
    
                'jne' => (int) ($kurirHariIni->jne ?? 0),
    
            ],
    
        ]);
    }
    
    public function stats()
    {
        $hariIni = Pesanan::whereDate('tanggal_kirim', today())
            ->where('id_user_kirim', Auth::id())
            ->count();
    
        $totalProses = Pesanan::where('status', 'proses')->count();
    
        $kurirHariIni = $this->getKurirHariIni();
    
        return response()->json([
            'hari_ini' => $hariIni,
    
            'total' => $totalProses,
    
            'kurir_hari_ini' => [
                'spx'       => (int) ($kurirHariIni->spx ?? 0),
                'jnt'       => (int) ($kurirHariIni->jnt ?? 0),
                'anteraja' => (int) ($kurirHariIni->anteraja ?? 0),
                'jne'       => (int) ($kurirHariIni->jne ?? 0),
            ],
        ]);
    }
    
    private function getKurirHariIni()
    {
        return Pesanan::whereDate('tanggal_kirim', today())
            ->where('id_user_kirim', Auth::id())
            ->selectRaw("
                SUM(CASE WHEN LOWER(kurir) LIKE '%spx%' THEN 1 ELSE 0 END) AS spx,
                SUM(
                    CASE
                        WHEN LOWER(kurir) LIKE '%j&t%'
                          OR LOWER(kurir) LIKE '%jnt%'
                        THEN 1
                        ELSE 0
                    END
                ) AS jnt,
                SUM(CASE WHEN LOWER(kurir) LIKE '%anteraja%' THEN 1 ELSE 0 END) AS anteraja,
                SUM(CASE WHEN LOWER(kurir) LIKE '%jne%' THEN 1 ELSE 0 END) AS jne
            ")
            ->first();
    }
    
    private function detectEkspedisi(?string $kurir): string
    {
        $kurir = strtolower(trim($kurir ?? ''));
    
        if (str_contains($kurir, 'spx')) {
            return 'spx';
        }
    
        if (
            str_contains($kurir, 'j&t') ||
            str_contains($kurir, 'jnt')
        ) {
            return 'jnt';
        }
    
        if (str_contains($kurir, 'anteraja')) {
            return 'anteraja';
        }
    
        if (str_contains($kurir, 'jne')) {
            return 'jne';
        }
    
        return 'unknown';
    }
}