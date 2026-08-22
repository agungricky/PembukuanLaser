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
            'resiPrinter',
        ])->findOrFail($no_pesanan);

        $tokos = Toko::orderBy('marketplace')
            ->orderBy('nama_toko')
            ->get();

        $items = DB::table('pesanan_per_produk as pp')
            ->leftJoin(
                'produk as p',
                'p.sku',
                '=',
                'pp.sku'
            )
            ->select([
                'pp.id_per_produk',
                'pp.sku',
                DB::raw('COALESCE(p.nama_produk, pp.nama_produk) as nama_produk'),
                DB::raw('COALESCE(p.variasi, pp.variasi) as variasi'),
                'pp.jumlah',
                'pp.hpp',
                'pp.harga',
                DB::raw('(COALESCE(pp.harga, 0) * COALESCE(pp.jumlah, 0)) as subtotal'),
            ])
            ->where(
                'pp.no_pesanan',
                $no_pesanan
            )
            ->orderBy(
                'pp.id_per_produk'
            )
            ->get();

        $totalHarga = (float) (
            $pesanan->total_harga ?? 0
        );

        $biayaAdmin = (float) (
            $pesanan->total_admin ?? 0
        );

        $totalPencairan = (float) (
            $pesanan->pencairan ?? 0
        );

        $subtotalItems = (float) $items->sum(
            'subtotal'
        );

        $subtotal = $subtotalItems > 0
            ? $subtotalItems
            : $totalHarga;

        $totalHPP = $pesanan->total_hpp !== null
            ? (float) $pesanan->total_hpp
            : (float) DB::table(
                'pesanan_per_produk'
            )
                ->where(
                    'no_pesanan',
                    $no_pesanan
                )
                ->selectRaw(
                    'COALESCE(SUM(COALESCE(hpp, 0) * COALESCE(jumlah, 0)), 0) as total_hpp'
                )
                ->value(
                    'total_hpp'
                );

        $tarik =
            $totalHarga -
            $biayaAdmin;

        $totalPenghasilan =
            $totalPencairan -
            $totalHPP;

        $margin = $totalHarga > 0
            ? round(
                ($totalPenghasilan / $totalHarga) * 100,
                2
            )
            : 0;

        $isProfit =
            $totalPenghasilan >= 0;

        $selisih =
            $totalPencairan -
            $tarik;

        $selisihClass = $selisih < 0
            ? 'text-danger'
            : (
                $selisih > 0
                    ? 'text-success'
                    : 'text-muted'
            );

        $selisihText =
            ($selisih < 0 ? '-' : '') .
            'Rp' .
            number_format(
                abs($selisih),
                0,
                ',',
                '.'
            );

        $totalItem =
            (int) $items->count();

        $totalQty =
            (int) $items->sum('jumlah');

        $status = strtolower(
            (string) $pesanan->status
        );

        $badgeMap = [
            'proses' => 'warning',
            'kirim' => 'info',
            'selesai' => 'success',
            'affiliate' => 'primary',
            'pengiriman_gagal' => 'warning',
            'pengembalian' => 'danger',
            'batal' => 'danger',
        ];

        $statusBadge =
            $badgeMap[$status] ??
            'secondary';

        $statusLabel = match ($status) {
            'pengiriman_gagal' =>
                'Pengiriman Gagal',

            'pengembalian' =>
                'Pengembalian',

            default =>
                ucfirst(
                    $status ?: '-'
                ),
        };

        $tanggalInput = $pesanan->tanggal
            ? Carbon::parse(
                $pesanan->tanggal
            )->format('d/m/Y')
            : '-';

        $batasKirim = null;

        if ($pesanan->batas_kirim_at) {
            try {
                $batasKirim = Carbon::parse(
                    $pesanan->batas_kirim_at
                );
            } catch (\Throwable $e) {
                $batasKirim = null;
            }
        }

        $sisaJam = $batasKirim
            ? now()->diffInHours(
                $batasKirim,
                false
            )
            : null;

        if ($sisaJam === null) {
            $prioritasLabel =
                'BELUM ADA';

            $prioritasBadge =
                'secondary';
        } elseif ($sisaJam < 0) {
            $prioritasLabel =
                'TERLAMBAT';

            $prioritasBadge =
                'danger';
        } elseif ($sisaJam <= 24) {
            $prioritasLabel =
                'URGENT';

            $prioritasBadge =
                'danger';
        } elseif ($sisaJam <= 48) {
            $prioritasLabel =
                'MENDEKATI';

            $prioritasBadge =
                'warning';
        } else {
            $prioritasLabel =
                'AMAN';

            $prioritasBadge =
                'success';
        }

        $batasKirimSource = match (
            $pesanan->batas_kirim_source
        ) {
            'shopee_estimated_ship_out_date' =>
                'Shopee Excel',

            'tiktok_in_transit_by' =>
                'TikTok PDF',

            default =>
                $pesanan->batas_kirim_source
                    ?: '-',
        };

        $resiSudahDicetak =
            !is_null(
                $pesanan->resi_printed_at
            );

        $resiPrintCount =
            (int) (
                $pesanan->resi_print_count ?? 0
            );

        return view(
            'pesanan.rincian',
            [
                'pesanan' => $pesanan,
                'tokos' => $tokos,
                'items' => $items,

                'subtotal' => $subtotal,
                'totalHarga' => $totalHarga,
                'totalHPP' => $totalHPP,
                'biayaAdmin' => $biayaAdmin,
                'tarik' => $tarik,
                'totalPencairan' => $totalPencairan,
                'totalPenghasilan' => $totalPenghasilan,

                'margin' => $margin,
                'isProfit' => $isProfit,

                'selisih' => $selisih,
                'selisihClass' => $selisihClass,
                'selisihText' => $selisihText,

                'totalItem' => $totalItem,
                'totalQty' => $totalQty,

                'statusBadge' => $statusBadge,
                'statusLabel' => $statusLabel,

                'tanggalInput' => $tanggalInput,

                'batasKirim' => $batasKirim,
                'batasKirimSource' => $batasKirimSource,
                'prioritasLabel' => $prioritasLabel,
                'prioritasBadge' => $prioritasBadge,

                'resiSudahDicetak' => $resiSudahDicetak,
                'resiPrintCount' => $resiPrintCount,
            ]
        );
    }

    public function update(
        Request $request,
        string $no_pesanan
    ) {
        $validated = $request->validate([
            'tanggal' => [
                'nullable',
                'date',
            ],

            'id_toko' => [
                'required',
                'exists:toko,id_toko',
            ],

            'no_resi' => [
                'nullable',
                'string',
                'max:100',
            ],

            'status' => [
                'nullable',
                Rule::in([
                    'proses',
                    'kirim',
                    'selesai',
                    'affiliate',
                    'pengiriman_gagal',
                    'pengembalian',
                    'batal',
                ]),
            ],

            'pencairan' => [
                'nullable',
                'numeric',
            ],

            'total_hpp' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'total_harga' => [
                'nullable',
                'numeric',
                'min:0',
            ],
        ]);

        DB::transaction(
            function () use (
                $validated,
                $no_pesanan
            ) {
                $pesanan =
                    Pesanan::where(
                        'no_pesanan',
                        $no_pesanan
                    )
                        ->lockForUpdate()
                        ->firstOrFail();

                $statusLama =
                    (string) $pesanan->status;

                $statusBaru =
                    $validated['status']
                    ?? $statusLama;

                $pesanan->tanggal =
                    $validated['tanggal']
                    ?? $pesanan->tanggal;

                $pesanan->id_toko =
                    $validated['id_toko'];

                $pesanan->no_resi =
                    !empty(
                        $validated['no_resi']
                    )
                        ? trim(
                            $validated['no_resi']
                        )
                        : null;

                $pesanan->status =
                    $statusBaru;

                $pesanan->pencairan =
                    isset(
                        $validated['pencairan']
                    )
                        ? (float)
                            $validated['pencairan']
                        : 0;

                $pesanan->total_hpp =
                    isset(
                        $validated['total_hpp']
                    )
                        ? (float)
                            $validated['total_hpp']
                        : 0;

                $pesanan->total_harga =
                    isset(
                        $validated['total_harga']
                    )
                        ? (float)
                            $validated['total_harga']
                        : 0;

                if (
                    $statusBaru === 'kirim' &&
                    $statusLama !== 'kirim'
                ) {
                    if (
                        !$pesanan->tanggal_kirim
                    ) {
                        $pesanan->tanggal_kirim =
                            now();
                    }

                    if (
                        !$pesanan->id_user_kirim
                    ) {
                        $pesanan->id_user_kirim =
                            Auth::id();
                    }
                }

                $pesanan->save();
            }
        );

        return redirect()
            ->route(
                'pesanan.show',
                $no_pesanan
            )
            ->with(
                'success',
                'Pesanan berhasil diperbarui.'
            );
    }
}
