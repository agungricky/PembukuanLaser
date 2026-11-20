<?php

namespace App\Services\Pesanan;

use App\Models\bukti_pembayaran;
use App\Models\customer_addresses;
use App\Models\Pesanan;
use App\Models\PesananPerProduk;
use App\Models\ReqFoto;
use App\Services\Api\FileGatorService as ApiFileGatorService;
use App\Services\Location\WilayahIndo;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;

class PesananService
{
    public function __construct(
        private WilayahIndo $wilayahIndo
    ) {}

    public function index(Request $request)
    {
        $pesanan = Pesanan::query()
            ->where('platform', 'meta')
            ->where('id_user', Auth::id())
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->input('search');

                $query->where(function ($q) use ($search) {
                    $q->where('no_pesanan', 'like', "%{$search}%")
                        ->orWhere('nama_pembeli', 'like', "%{$search}%")
                        ->orWhere('no_resi', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->input('status'));
            })
            ->select('no_pesanan', 'no_resi', 'input_at', 'nama_pembeli', 'total_harga', 'status')
            ->orderByDesc('input_at')
            ->get();

        return $pesanan;
    }

    public function detailpesanan($no_pesanan)
    {
        $pesanan = Pesanan::with([
            'pesanan_per_produk' => function ($query) {
                $query->select('id_per_produk', 'no_pesanan', 'jumlah', 'sku', 'catatan');
            },
            'pesanan_per_produk.produk' => function ($query) {
                $query->select('sku', 'nama_produk', 'variasi', 'kategori_id');
            },
            'pesanan_per_produk.produk.kategori' => function ($query) {
                $query->select('id', 'nama_kategori');
            },
            'pesanan_per_produk.reqfoto' => function ($query) {
                $query->select('per_produk_id', 'foto');
            },
            'pesanan_per_produk.fotoMeta' => function ($query) {
                $query->select('id', 'perproduk_id', 'foto');
            },
            'customer_address' => function ($query) {
                $query->select('no_pesanan', 'provinsi', 'kabupaten_kota', 'kecamatan', 'desa', 'alamat_lengkap', 'phone');
            },
            'bukti_pembayaran' => function ($query) {
                $query->select('no_pesanan', 'jenis_transaksi', 'cod', 'non_cod', 'dp', 'bukti_bayar');
            }
        ])
            ->where('no_pesanan', $no_pesanan)
            ->select('no_pesanan', 'nama_pembeli', 'total_harga', 'notes', 'input_at', 'no_resi', 'platform', 'foto_at')
            ->firstOrFail();

        $pesanan->pesanan_per_produk->each(function ($item) {
            $item->reqfoto->each(function ($foto) {
                if (!empty($foto->foto)) {
                    $foto->foto = URL::signedRoute(
                        'file',
                        [
                            'path' => $foto->foto,
                        ]
                    );
                }
            });

            if ($item->fotoMeta && !empty($item->fotoMeta->foto)) {
                $item->fotoMeta->foto = URL::signedRoute(
                    'file',
                    [
                        'path' => $item->fotoMeta->foto,
                    ]
                );
            }
        });

        $pesanan->bukti_pembayaran->each(function ($bayar) {
            if (!empty($bayar->bukti_bayar)) {
                $bayar->bukti_bayar = URL::signedRoute(
                    'file',
                    [
                        'path' => $bayar->bukti_bayar,
                    ]
                );
            }
        });

        $userAdress = $pesanan->customer_address;
        $adress = $this->wilayahIndo->alamatLengkap(
            $userAdress->desa
        );

        return (object) [
            'informasiPembeli' => [
                'namaPembeli' => $pesanan->nama_pembeli,
                'phone' => $userAdress->phone,
                'address' => (object) [
                    'provinsi' => $adress->provinsi->name,
                    'kabupatenKota' => $adress->kabupaten_kota->name,
                    'kecamatan' => $adress->kecamatan->name,
                    'desa' => $adress->desa->name,
                    'alamatLengkap' => $userAdress->alamat_lengkap,
                    'kodePos' => $adress->kode_pos
                ]
            ],
            'informasiProduk' => $pesanan->pesanan_per_produk,
            'pembayaran' => $pesanan->bukti_pembayaran,
            'pesanan' => [
                'noPesanan' => $pesanan->no_pesanan,
                'noResi' => $pesanan->no_resi,
                'foto_at' => $pesanan->foto_at,
                'platform' => $pesanan->platform,
                'tanggalInput' => $pesanan->input_at,
                'totalHarga' => $pesanan->total_harga,
                'catatanPesanan' => $pesanan->notes
            ]
        ];
    }

    private function generateNoPesanan(): string
    {
        $tanggal = now()->format('ymd');
        $nomorTerakhir = Pesanan::where('no_pesanan', 'like', $tanggal . '%')
            ->whereRaw(
                'no_pesanan REGEXP ?',
                ['^' . $tanggal . '[0-9]{3,}[A-Z]$']
            )
            ->selectRaw(
                'MAX(CAST(SUBSTRING(no_pesanan, 7, CHAR_LENGTH(no_pesanan) - 7) AS UNSIGNED)) as nomor'
            )
            ->value('nomor');

        $nomorBerikutnya = ($nomorTerakhir ?? 0) + 1;
        $nomorUrut = str_pad($nomorBerikutnya, 3, '0', STR_PAD_LEFT);
        $random = chr(random_int(65, 90));

        return $tanggal . $nomorUrut . $random;
    }

    public function create()
    {
        $noPesananBaru = $this->generateNoPesanan();
        $user = collect(Auth::user())
            ->only(['id', 'name'])
            ->toArray();

        return (object) [
            'no_pesanan' => $noPesananBaru,
            'user' => $user,
        ];
    }

    public function store(Request $request)
    {
        return DB::transaction(function () use ($request) {
            $pesanan = Pesanan::create([
                'no_pesanan' => $request->no_pesanan,
                'platform' => $request->platform,
                'tanggal' => Carbon::parse($request->tanggal)->toDateString(),
                'input_at' => Carbon::parse($request->tanggal)->format('Y-m-d H:i:s'),
                'no_resi' => null,
                'resi_printed_at' => null,
                'resi_last_printed_at' => null,
                'resi_printed_by' => null,
                'resi_print_count' => 0,
                'id_toko' => null,
                'id_user' => $request->user_id,
                'id_user_kirim' => null,
                'tanggal_kirim' => null,
                'nama_pembeli' => $request->nama_pembeli,
                'username' => $request->nama_pembeli,
                'kurir' => null,
                'status' => 'proses',
                'status_cek' => 0,
                'total_hpp' => $request->total_hpp,
                'total_harga' => $request->total_harga,
                'total_admin' => $request->total_admin,
                'pencairan' => null,
                'notes' => $request->notes,
                'chat_masuk_at' => $request->filled('chat_masuk_at')
                    ? \Carbon\Carbon::parse($request->chat_masuk_at)
                    ->setTimezone('Asia/Jakarta')
                    ->format('Y-m-d H:i:s')
                    : null,
                'batas_kirim_at' => Carbon::parse($request->tanggal_order)->addDays(3),
            ]);

            $customerAddress = customer_addresses::create([
                'no_pesanan' => $pesanan->no_pesanan,
                'provinsi' => $request->provinsi,
                'kabupaten_kota' => $request->kabupaten_kota,
                'kecamatan' => $request->kecamatan,
                'desa' => $request->desa,
                'alamat_lengkap' => $request->alamat_lengkap,
                'phone' => $request->phone,
            ]);

            $fileGatorService = app(ApiFileGatorService::class);
            $fileGatorService->login();

            $buktiBayar = null;
            $fileBukti = $request->file('bukti_bayar');
            if ($fileBukti) {
                $extension = strtolower(
                    $fileBukti->getClientOriginalExtension()
                );
                $namaFile = $pesanan->no_pesanan . '.' . $extension;
                $buktiBayar = $fileGatorService->upload($fileBukti, $namaFile, 'TF_META');
            }

            $buktiBayar = bukti_pembayaran::create([
                'no_pesanan' => $pesanan->no_pesanan,
                'jenis_transaksi' => $request->jenis_transaksi,
                'cod' => $request->cod,
                'non_cod' => $request->non_cod,
                'dp' => $request->dp,
                'bukti_bayar' => $buktiBayar,
            ]);

            foreach ($request->input('pesanan_perproduk', []) as $index => $value) {
                $perproduk = PesananPerProduk::create([
                    'no_pesanan' => $pesanan->no_pesanan,
                    'nama_produk' => $value['nama_produk'],
                    'variasi' => $value['variasi'] ?? null,
                    'jumlah' => $value['jumlah'],
                    'hpp' => $value['hpp'],
                    'harga' => $value['harga'],
                    'sku' => $value['sku'],
                    'custom' => $value['custom'] ?? null,
                    'status_pesanan' => $value['status_pesanan'] ?? 0,
                    'mutasi_stok_id' => $value['mutasi_stok_id'] ?? null,
                    'catatan' => $value['catatan'] ?? null,
                ]);

                $fotoProduks = $request->file(
                    "pesanan_perproduk.$index.foto_produk",
                    []
                );

                foreach ($fotoProduks as $fotoIndex => $file) {

                    $extension = strtolower(
                        $file->getClientOriginalExtension()
                    );

                    $namaFile = $pesanan->no_pesanan . '_'
                        . str_pad($index + 1, 2, '0', STR_PAD_LEFT)
                        . '_'
                        . str_pad($fotoIndex + 1, 2, '0', STR_PAD_LEFT)
                        . '.' . $extension;

                    $fotoPath = $fileGatorService->upload(
                        $file,
                        $namaFile,
                        'REQ_FOTO'
                    );

                    ReqFoto::create([
                        'per_produk_id' => $perproduk->id_per_produk,
                        'foto' => $fotoPath,
                    ]);
                }
            }


            return [
                'pesanan' => $pesanan,
                'customerAddress' => $customerAddress,
                'buktiBayar' => $customerAddress
            ];
        });
    }

    public function antrian($request)
    {
        $pesanan = Pesanan::query()
            ->where('platform', 'meta')
            ->where('status', 'proses')
            ->where('foto_at', null)
            ->where('input_at', '>=', now()->subMonths(3))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->input('search');

                $query->where(function ($q) use ($search) {
                    $q->where('no_pesanan', 'like', "%{$search}%")
                        ->orWhere('nama_pembeli', 'like', "%{$search}%")
                        ->orWhere('no_resi', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('batas_kirim'), function ($query) use ($request) {
                $query->whereDate('batas_kirim_at', $request->input('batas_kirim'));
            })
            ->select('no_pesanan', 'no_resi', 'input_at', 'nama_pembeli', 'status', 'batas_kirim_at', 'foto_at')
            ->orderBy('batas_kirim_at', 'asc')
            ->get()
            ->map(function ($item) {
                $item->foto_at = $item->foto_at === null ? 0 : 1;

                return $item;
            });;

        return (object) $pesanan;
    }

    public function ready()
    {
        if (userLogin()?->platform === 'meta') {
            $pesanan = Pesanan::query()
                ->with('toko')
                ->where('platform', 'meta')
                ->where('status', 'proses')
                ->whereNotNull('foto_at')
                ->whereNotNull('no_resi')
                ->orderBy('batas_kirim_at', 'asc')
                ->get();
        } else {
            $pesanan = Pesanan::query()
                ->with('toko')
                ->where('platform', 'marketplace')
                ->where('status', 'proses')
                ->whereNotNull('batas_kirim_at')
                ->whereDate('batas_kirim_at', '<=', today())
                ->orderBy('batas_kirim_at', 'asc')
                ->get();
        }

        return $pesanan->map(function ($item) {
            return (object) [
                'noPesanan' => $item->no_pesanan,
                'platform' => $item->platform,
                'noResi' => $item->no_resi,
                'pembeli' => $item->nama_pembeli,
                'marketplace' => $item->toko?->marketplace,
                'batasKirim' => $item->batas_kirim_at,
            ];
        });
    }
}
