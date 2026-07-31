<?php

namespace App\Http\Controllers;

use App\Models\Pesanan;
use App\Models\Toko;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PesananDetailController extends Controller
{
    public function show(string $no_pesanan)
    {
        $pesanan = Pesanan::with([
            'user',
            'toko',
            'userKirim',
        ])->findOrFail($no_pesanan);
    
        $tokos = Toko::orderBy('nama_toko')->get();
    
        $items = DB::table('pesanan_per_produk')
            ->select([
                'nama_produk',
                'variasi',
                'jumlah',
                'harga',
                DB::raw('(harga * jumlah) as subtotal'),
            ])
            ->where('no_pesanan', $no_pesanan)
            ->orderBy('nama_produk')
            ->get();

        $totalHarga     = (float) ($pesanan->total_harga ?? 0);
        $biayaAdmin     = (float) ($pesanan->total_admin ?? 0);
        $totalPencairan = (float) ($pesanan->pencairan ?? 0);
    
        $subtotalItems = (float) $items->sum('subtotal');
    
        $subtotal = $subtotalItems > 0
            ? $subtotalItems
            : $totalHarga;
    
        $totalHPP = $pesanan->total_hpp !== null
            ? (float) $pesanan->total_hpp
            : (float) DB::table('pesanan_per_produk')
                ->where('no_pesanan', $no_pesanan)
                ->selectRaw('COALESCE(SUM(hpp * jumlah),0) as total_hpp')
                ->value('total_hpp');
    
        $tarik = $totalHarga - $biayaAdmin;
    
        $totalPenghasilan = $totalPencairan - $totalHPP;
    
        $margin = $totalHarga > 0
            ? round(($totalPenghasilan / $totalHarga) * 100, 2)
            : 0;
    
        $isProfit = $totalPenghasilan >= 0;
    
        $selisih = $totalPencairan - $tarik;
    
        $selisihClass = $selisih < 0
            ? 'text-danger'
            : ($selisih > 0 ? 'text-success' : 'text-muted');
    
        $selisihText = ($selisih < 0 ? '-' : '')
            . 'Rp'
            . number_format(abs($selisih), 0, ',', '.');

        $totalItem = (int) $items->count();
    
        $totalQty = (int) $items->sum('jumlah');

        $status = strtolower((string) $pesanan->status);
    
        $badgeMap = [
            'proses'           => 'warning',
            'kirim'            => 'info',
            'selesai'          => 'success',
            'affiliate'        => 'primary',
            'pengiriman gagal' => 'warning',
            'pengembalian'     => 'danger',
            'batal'            => 'danger',
        ];
    
        $statusBadge = $badgeMap[$status] ?? 'secondary';
    
        $statusLabel = ucfirst($status ?: '-');

        $tanggalInput = $pesanan->tanggal
            ? $pesanan->tanggal->format('d/m/Y')
            : '-';
    
        return view('pesanan.rincian', [
    
            'pesanan' => $pesanan,
            'tokos'   => $tokos,
            'items'   => $items,
    
            'subtotal'         => $subtotal,
            'totalHarga'       => $totalHarga,
            'totalHPP'         => $totalHPP,
            'biayaAdmin'       => $biayaAdmin,
            'tarik'            => $tarik,
            'totalPencairan'   => $totalPencairan,
            'totalPenghasilan' => $totalPenghasilan,
    
            'margin'           => $margin,
            'isProfit'         => $isProfit,
    
            'selisih'          => $selisih,
            'selisihClass'     => $selisihClass,
            'selisihText'      => $selisihText,
    
            'totalItem'        => $totalItem,
            'totalQty'         => $totalQty,
    
            'statusBadge'      => $statusBadge,
            'statusLabel'      => $statusLabel,
    
            'tanggalInput'     => $tanggalInput,
    
        ]);
    }
    
    public function update(Request $request, string $no_pesanan)
    {
        $validated = $request->validate([
            'tanggal'      => ['nullable', 'date'],
            'id_toko'      => ['required', 'exists:toko,id_toko'],
        
            'no_resi'      => ['nullable', 'string', 'max:100'],
        
            'status'       => [
                'nullable',
                Rule::in([
                    'proses',
                    'kirim',
                    'selesai',
                    'affiliate',
                    'pengiriman gagal',
                    'pengembalian',
                    'batal',
                ]),
            ],
        
            'pencairan'    => ['nullable', 'numeric'],
            'total_hpp'    => ['nullable', 'numeric', 'min:0'],
            'total_harga'  => ['nullable', 'numeric', 'min:0'],
        ]);
    
        foreach (['pencairan', 'total_hpp', 'total_harga'] as $field) {
    
            if (!isset($validated[$field]) || $validated[$field] === '') {
                $validated[$field] = 0;
            }
    
        }
    
        DB::transaction(function () use ($validated, $no_pesanan) {
    
            $pesanan = Pesanan::findOrFail($no_pesanan);
    
            $statusBaru = $validated['status'] ?? $pesanan->status;
    
            $pesanan->fill([
    
                'tanggal'     => $validated['tanggal'] ?? $pesanan->tanggal,
                'id_toko'     => $validated['id_toko'],
                'no_resi'     => $validated['no_resi'] ?? null,
                'status'      => $statusBaru,
                'pencairan'   => $validated['pencairan'],
                'total_hpp'   => $validated['total_hpp'],
                'total_harga' => $validated['total_harga'],
            ]);
    
            if (!$pesanan->tanggal_kirim) {
                $pesanan->tanggal_kirim = now();
            }
    
            if (!$pesanan->id_user_kirim) {
                $pesanan->id_user_kirim = Auth::id();
            }

            if (in_array($statusBaru, [
                'pengiriman gagal',
                'pengembalian',
            ], true)) {
    
                $pesanan->total_admin = null;
                $pesanan->total_harga = null;
    
                DB::table('pesanan_per_produk')
                    ->where('no_pesanan', $no_pesanan)
                    ->update([
                        'jumlah' => 0,
                        'hpp'    => null,
                        'harga'  => null,
                    ]);
    
            }

            if ($statusBaru === 'batal') {
    
                $pesanan->total_admin = 0;
                $pesanan->total_harga = 0;
                $pesanan->total_hpp   = 0;
    
                DB::table('pesanan_per_produk')
                    ->where('no_pesanan', $no_pesanan)
                    ->update([
                        'jumlah' => 0,
                        'hpp'    => 0,
                        'harga'  => 0,
                    ]);
    
            }
    
            $pesanan->save();
    
        });
    
        return redirect()
            ->route('pesanan.show', $no_pesanan)
            ->with('success', 'Pesanan berhasil diperbarui.');
    }
}