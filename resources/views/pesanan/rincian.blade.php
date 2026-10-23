@extends('layouts.app')
@section('content')
    <div class="container-fluid py-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
            <div class="d-flex align-items-center flex-wrap gap-2">
                <a href="{{ route('pesanan.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left"></i>
                    Kembali
                </a>
                <h5 class="mb-0 fw-semibold">
                    Rincian Pesanan
                </h5>
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal"
                    data-bs-target="#modalEditPesanan">
                    <i class="bi bi-pencil-square me-1"></i>
                    Edit
                </button>
                @if (!$pesanan->status_cek)
                    <form action="{{ route('pesanan.cek.aktifkan', $pesanan->no_pesanan) }}" method="POST"
                        class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-warning btn-sm">
                            <i class="bi bi-exclamation-triangle me-1"></i>
                            Masukkan Cek
                        </button>
                    </form>
                @else
                    <form action="{{ route('pesanan.cek.selesai', $pesanan->no_pesanan) }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-success btn-sm">
                            <i class="bi bi-check-circle me-1"></i>
                            Selesai Cek
                        </button>
                    </form>
                @endif
                <button type="button" id="cetakResi"
                    class="btn btn-danger btn-sm text-nowrap {{ empty($pesanan->batas_kirim_at) ? 'disabled' : '' }}">
                    <i class="fa-solid fa-print me-1"></i>
                    Cetak Resi
                </button>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge rounded-pill bg-{{ $statusBadge }} px-3 py-2">
                    {{ strtoupper($statusLabel) }}
                </span>
                @if ($pesanan->status_cek)
                    <span class="badge bg-warning text-dark">
                        <i class="bi bi-exclamation-triangle-fill me-1"></i>
                        PERLU CEK
                    </span>
                @endif
            </div>
        </div>
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                <i class="bi bi-check-circle me-1"></i>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert">
                </button>
            </div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="bi bi-exclamation-circle me-1"></i>
                {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert">
                </button>
            </div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show">
                <div class="fw-semibold mb-1">
                    Data belum dapat disimpan.
                </div>
                @foreach ($errors->all() as $error)
                    <div class="small">
                        {{ $error }}
                    </div>
                @endforeach
                <button type="button" class="btn-close" data-bs-dismiss="alert">
                </button>
            </div>
        @endif
        <div class="row g-3 mb-3">
            <div class="col-lg-8">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-white">
                        <h6 class="mb-0 fw-semibold">
                            <i class="bi bi-receipt me-2 text-primary"></i>
                            Informasi Pesanan
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <table class="table table-borderless table-sm mb-0 table-row-line">
                                    <tr>
                                        <td class="text-muted text-nowrap pe-3">No. Pesanan</td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="fw-semibold">{{ $pesanan->no_pesanan }}</span>
                                                <button type="button" class="btn btn-light btn-sm border copy-btn"
                                                    data-copy="{{ $pesanan->no_pesanan }}">
                                                    <i class="bi bi-clipboard"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Toko</td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                @if ($pesanan->toko?->marketplace)
                                                    @php
                                                        $marketplace = strtolower(trim($pesanan->toko->marketplace));
                                                    @endphp

                                                    @if (str_contains($marketplace, 'shopee'))
                                                        <img src="{{ asset('icon/Shopee.svg') }}" alt="Shopee"
                                                            height="22" title="Shopee" class="flex-shrink-0">
                                                    @elseif (str_contains($marketplace, 'tiktok'))
                                                        <img src="{{ asset('icon/TikTok.svg') }}" alt="TikTok Shop"
                                                            height="22" title="TikTok Shop" class="flex-shrink-0">
                                                    @else
                                                        <span class="small text-muted">
                                                            {{ $pesanan->toko->marketplace }}
                                                        </span>
                                                    @endif
                                                @endif

                                                <span class="fw-semibold">
                                                    {{ $pesanan->toko?->nama_toko ?? '-' }}
                                                </span>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Pembeli</td>
                                        <td>{{ $pesanan->nama_pembeli ?? '-' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Item</td>
                                        <td>{{ number_format($totalItem, 0, ',', '.') }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">Qty</td>
                                        <td>{{ number_format($totalQty, 0, ',', '.') }}</td>
                                    </tr>

                                    @if ($dataPlat != [] || $dataPlat != null)
                                        <tr class="{{ $items->contains('nama_kategori', 'PLAT') ? '' : 'd-none' }}">
                                            <td colspan="2"
                                                style="height: 30px; padding: 0 !important; border: none !important; box-shadow: none !important;">
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="2" class="text-nowrap">STATUS OPERASIONAL</td>
                                        </tr>
                                        <tr class="{{ $items->contains('nama_kategori', 'PLAT') ? '' : 'd-none' }}">
                                            <td class="text-muted">Cetak Resi</td>
                                            <td>
                                                @if ($resiSudahDicetak)
                                                    <div class="d-flex align-items-center gap-2">
                                                        <span class="badge bg-success">
                                                            SUDAH DICETAK
                                                        </span>

                                                        <span class="small text-muted">
                                                            {{ number_format($resiPrintCount, 0, ',', '.') }}x cetak
                                                        </span>
                                                    </div>
                                                @else
                                                    <span class="badge bg-secondary">
                                                        BELUM DICETAK
                                                    </span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endif
                                </table>
                            </div>

                            <div class="col-md-6">
                                <table class="table table-borderless table-sm mb-0 table-row-line">
                                    <tr>
                                        <td class="text-muted text-nowrap" width="40%">
                                            No. Resi
                                        </td>

                                        <td>
                                            <div class="d-flex align-items-center gap-2">

                                                <span class="fw-semibold">
                                                    {{ $pesanan->no_resi ?? '-' }}
                                                </span>

                                                @if ($pesanan->no_resi)
                                                    <button type="button" class="btn btn-light btn-sm border copy-btn"
                                                        data-copy="{{ $pesanan->no_resi }}">
                                                        <i class="bi bi-clipboard"></i>
                                                    </button>
                                                @endif

                                            </div>
                                        </td>
                                    </tr>

                                    <tr>
                                        <td class="text-muted">
                                            Kurir
                                        </td>
                                        <td>
                                            {{ $pesanan->kurir ?? '-' }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">
                                            Batas Kirim
                                        </td>

                                        <td>
                                            @if ($batasKirim)
                                                <div class="fw-semibold">
                                                    {{ $batasKirim->format('d/m/Y H:i') }}
                                                </div>

                                                @if ($pesanan->status_cek == 'proses')
                                                    <div class="mt-1">
                                                        <span
                                                            class="badge bg-{{ $prioritasBadge }} {{ $prioritasBadge === 'warning' ? 'text-dark' : '' }}">
                                                            {{ $prioritasLabel }}
                                                        </span>
                                                    </div>
                                                @endif
                                            @else
                                                <span class="text-muted">
                                                    -
                                                </span>
                                            @endif
                                        </td>
                                    </tr>

                                    {{-- @if ($pesanan->batas_kirim_raw) --}}
                                    <tr>
                                        <td class="text-muted">Deadline Asli</td>
                                        <td>
                                            <span class="small">{{ $pesanan->batas_kirim_raw ?? '-' }}</span>
                                        </td>
                                    </tr>
                                    {{-- @endif --}}

                                    <tr>
                                        <td class="text-muted">
                                            Sumber Deadline
                                        </td>
                                        <td>
                                            {{ $batasKirimSource }}
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card shadow-sm border-0 h-100 financial-card">
                    <div class="card-header bg-white">
                        <h6 class="mb-0 fw-semibold">
                            <i class="bi bi-cash-stack text-success me-2"></i>
                            Ringkasan Keuangan
                        </h6>
                    </div>

                    <div class="card-body">
                        <table class="table table-sm table-borderless mb-0 financial-table">
                            <tbody>
                                <tr>
                                    <td class="text-dark">
                                        Omzet
                                    </td>
                                    <td class="text-end fw-semibold">
                                        Rp{{ number_format($subtotal, 0, ',', '.') }}
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-dark">
                                        Biaya Admin
                                    </td>
                                    <td class="text-end text-danger fw-semibold">
                                        @if ($biayaAdmin > 0)
                                            -Rp{{ number_format($biayaAdmin, 0, ',', '.') }}
                                        @else
                                            Rp0
                                        @endif
                                    </td>
                                </tr>

                                <tr class="financial-section-line">
                                    <td class="fw-semibold">
                                        Dana Dicairkan
                                    </td>
                                    <td class="text-end text-primary fw-bold">
                                        Rp{{ number_format($totalPencairan, 0, ',', '.') }}
                                    </td>
                                </tr>

                                @foreach ($pencairan as $p)
                                    <tr class="financial-payout-row">
                                        <td>
                                            <div class="d-flex align-items-center gap-2 ps-2">
                                                <i class="bi bi-arrow-return-right text-muted"></i>
                                                <span class="semi-bold">
                                                    Pencairan {{ $loop->iteration }}
                                                </span>
                                            </div>

                                            <div class="financial-payout-date text-dark">
                                                <i class="bi bi-person me-1"></i>
                                                {{ ucwords(strtolower($p->user->name)) }} |
                                                {{ \Carbon\Carbon::parse($p['created_at'])->format('d/m/Y : H.i') }}
                                            </div>
                                        </td>

                                        <td class="text-end text-dark fw-medium">
                                            Rp{{ number_format($p->nominal, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                @endforeach

                                <tr class="financial-section-line">
                                    <td class="text-dark">
                                        HPP
                                    </td>
                                    <td class="text-end text-danger fw-semibold">
                                        @if ($totalHPP > 0)
                                            -Rp{{ number_format($totalHPP, 0, ',', '.') }}
                                        @else
                                            Rp0
                                        @endif
                                    </td>
                                </tr>

                                <!-- SELISIH -->
                                @if (strtolower((string) $pesanan->status) === 'selesai')
                                    <tr>
                                        <td class="text-dark">
                                            Selisih
                                        </td>
                                        <td class="text-end fw-semibold {{ $selisihClass }}">
                                            {{ $selisihText }}
                                        </td>
                                    </tr>
                                @endif


                            </tbody>
                        </table>

                        <div class="financial-result mt-3">
                            <div class="row g-2">
                                <div class="col-4">
                                    <div class="financial-result-box h-100">
                                        <div class="financial-result-label">
                                            Laba Bersih
                                        </div>

                                        <div
                                            class="financial-result-value
                    {{ $isProfit ? 'text-success' : 'text-danger' }}">
                                            {{ $totalPenghasilan < 0 ? '-' : '' }}Rp{{ number_format(abs($totalPenghasilan), 0, ',', '.') }}
                                        </div>
                                    </div>
                                </div>

                                <!-- MARGIN -->
                                <div class="col-4">
                                    <div class="financial-result-box h-100">
                                        <div class="financial-result-label">
                                            Margin
                                        </div>

                                        <div
                                            class="financial-result-value
                    {{ $margin < 0 ? 'text-danger' : 'text-success' }}">
                                            {{ number_format($margin, 2, ',', '.') }}%
                                        </div>
                                    </div>
                                </div>

                                <!-- STATUS -->
                                <div class="col-4">
                                    <div class="financial-result-box h-100">
                                        <div class="financial-result-label">
                                            Status
                                        </div>

                                        <div
                                            class="financial-result-value {{ $isProfit ? 'text-success' : 'text-danger' }}">
                                            <i
                                                class="bi {{ $isProfit ? 'bi-graph-up-arrow' : 'bi-graph-down-arrow' }} me-1"></i>
                                            {{ $isProfit ? 'Untung' : 'Rugi' }}
                                        </div>
                                    </div>
                                </div>

                            </div>


                        </div>


                    </div>
                </div>
            </div>


            <style>
                /* ===== CARD KEUANGAN ===== */

                .financial-card {
                    border-radius: 12px;
                    overflow: hidden;
                }

                .financial-card .card-header h6 {
                    font-size: 15px;
                }

                /* ===== TABEL KEUANGAN ===== */

                .financial-table {
                    font-size: 14px;
                    margin-bottom: 0;
                }

                .financial-table td,
                .financial-table th {
                    padding: 4px 4px !important;
                    vertical-align: middle;
                    line-height: 1.4;
                }

                .financial-table td:last-child {
                    white-space: nowrap;
                    font-variant-numeric: tabular-nums;
                }

                /* ===== PEMISAH ANTARBAGIAN ===== */

                .financial-section-line td {
                    padding-top: 7px !important;
                    border-top: 1px solid #e9ecef !important;
                }

                /* ===== RIWAYAT PENCAIRAN ===== */

                .financial-payout-row td {
                    padding-top: 3px !important;
                    padding-bottom: 3px !important;
                }

                .financial-payout-date {
                    font-size: 11px;
                    color: #94a3b8;
                    padding-left: 30px;
                    margin-top: 3px;
                }

                /* ===== HASIL KEUANGAN 3 KOTAK ===== */

                .financial-result {
                    background: transparent;
                    border: none;
                    padding: 0;
                }

                .financial-result-box {
                    background: #ffffff;
                    border: 1px solid #ced4da;
                    border-radius: 8px;
                    padding: 12px 9px;
                    height: 100%;
                    min-width: 0;
                }

                /* Label: Laba Bersih, Margin, Status */
                .financial-result-label {
                    font-size: 12px;
                    font-weight: 500;
                    color: #6c757d;
                    margin-bottom: 8px;
                    line-height: 1.3;
                }

                /* Nilai Laba dan Margin */
                .financial-result-value {
                    font-size: 16px;
                    font-weight: 600;
                    line-height: 1.4;
                    font-variant-numeric: tabular-nums;
                }

                .financial-result-value i {
                    font-size: 16px;
                }

                /* Badge status */
                .financial-result-box .badge {
                    font-size: 11px;
                    font-weight: 500;
                    padding: 6px 8px;
                    max-width: 100%;
                    white-space: normal;
                    line-height: 1.3;
                }

                /* ===== SELISIH ===== */

                .financial-result-selisih {
                    border-top: 1px solid #e9ecef;
                    padding-top: 12px;
                    margin-top: 15px;
                    font-size: 14px;
                }

                /* ===== RESPONSIVE ===== */

                @media (max-width: 420px) {

                    .financial-result-box {
                        padding: 10px 7px;
                    }

                    .financial-result-label {
                        font-size: 11px;
                        margin-bottom: 6px;
                    }

                    .financial-result-value {
                        font-size: 13px;
                    }

                    .financial-result-box .badge {
                        font-size: 10px;
                        padding: 5px 6px;
                    }
                }
            </style>

        </div>

        @if ($progresPesanan->isNotEmpty())
            <div class="card shadow-sm border-0 mb-3">

                <div class="card-header bg-white">
                    <h6 class="mb-0 fw-semibold">
                        <i class="bi bi-diagram-3 me-2 text-primary"></i>
                        Proses Pesanan
                    </h6>
                </div>

                <div class="card-body py-3">
                    <div class="order-flow">

                        @foreach ($progresPesanan as $role => $progres)
                            @php
                                $namaRole = match ($role) {
                                    'pegawai' => 'ADMIN PENJUALAN',
                                    'gudang' => 'GUDANG',
                                    'editor' => 'EDITOR',
                                    'produksi' => 'PRODUKSI',
                                    'packing' => 'PACKING',
                                    'dikirim' => 'DIKIRIM',
                                    default => strtoupper($role),
                                };

                                // status roll
                                $dataStep = match ($role) {
                                    'pegawai' => 'input',
                                    'gudang' => 'exported',
                                    'editor' => 'editor',
                                    'produksi' => 'produksi',
                                    'packing' => 'packing',
                                    'dikirim' => 'dikirim',
                                    default => $role,
                                };

                                // Status Centang muncul ketika ............
                                $selesai = match ($role) {
                                    'pegawai' => !is_null($pesanan->batas_kirim_at),
                                    'gudang' => !is_null($pesanan->pesanan_per_produk->first()?->mutasi_stok_id),
                                    'editor' => !is_null($dataPlat),
                                    'packing' => !is_null($pesanan->tanggal_kirim),
                                    'dikirim' => $pesanan->status == 'kirim',
                                    default => false,
                                };
                            @endphp

                            <div class="order-step" data-step="{{ $dataStep }}"
                                data-completed="{{ $selesai ? '1' : '0' }}">
                                <div class="step-inner">
                                    <div class="step-title">
                                        @if ($selesai)
                                            <i class="bi bi-check-circle-fill"></i>
                                        @endif

                                        {{ $namaRole }}
                                    </div>

                                    <div class="step-info">
                                        <strong>{{ $progres->users?->name ?? '-' }}</strong>
                                        <span>•</span>
                                        {{ $progres->updated_at?->format('d/m • H.i') }}
                                    </div>

                                </div>
                            </div>
                        @endforeach

                    </div>
                </div>
            </div>
        @endif

        <div class="card shadow-sm border-0 mb-3">
            <div class="card-header bg-white">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h6 class="mb-0 fw-semibold">
                            <i class="bi bi-box-seam text-primary me-2"></i>
                            Daftar Produk
                        </h6>
                        <small class="text-muted">
                            {{ number_format($totalItem, 0, ',', '.') }} Item
                            •
                            {{ number_format($totalQty, 0, ',', '.') }} Qty
                        </small>
                    </div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="60">
                                No
                            </th>
                            <th width="130">
                                SKU
                            </th>
                            <th>
                                Produk
                            </th>
                            <th width="100" class="text-center">
                                Qty
                            </th>
                            <th width="150" class="text-end">
                                HPP
                            </th>
                            <th width="160" class="text-end">
                                Harga
                            </th>
                            <th width="180" class="text-end">
                                Subtotal
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $index => $item)
                            <tr>
                                <td>
                                    {{ $index + 1 }}
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        {{ $item->sku ?: '-' }}
                                    </span>
                                </td>
                                <td>
                                    <div class="fw-semibold">
                                        {{ $item->nama_produk ?: '-' }}
                                    </div>
                                    <div class="small text-muted">
                                        Variasi :
                                        {{ $item->variasi ?: '-' }}
                                    </div>
                                    @if ($item->id_per_produk)
                                        <div class="small text-muted">
                                            ID Item :
                                            {{ $item->id_per_produk }}
                                        </div>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-secondary">
                                        {{ number_format((int) $item->jumlah, 0, ',', '.') }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    Rp{{ number_format((float) ($item->hpp ?? 0), 0, ',', '.') }}
                                </td>
                                <td class="text-end">
                                    Rp{{ number_format((float) ($item->harga ?? 0), 0, ',', '.') }}
                                </td>
                                <td class="text-end fw-semibold">
                                    Rp{{ number_format((float) ($item->subtotal ?? 0), 0, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <i class="bi bi-box-seam fs-1 text-muted d-block mb-2"></i>
                                    <div class="fw-semibold">
                                        Tidak Ada Produk
                                    </div>
                                    <div class="small text-muted">
                                        Pesanan ini tidak memiliki produk.
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <th colspan="6" class="text-end">
                                Total Penjualan
                            </th>
                            <th class="text-end text-success">
                                Rp{{ number_format($subtotal, 0, ',', '.') }}
                            </th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        @if ($dataPlat != [] || $dataPlat != null)
            <div class="card shadow-sm border-0 mb-3">
                <div class="card-header bg-white">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <h6 class="mb-0 fw-semibold">
                                <i class="bi bi-pencil-square text-warning me-2"></i>
                                Request Produk
                            </h6>

                            <small class="text-muted">
                                Permintaan custom dari pelanggan
                            </small>
                        </div>

                        <span class="badge bg-warning-subtle text-warning border">
                            {{ count($dataPlat) }} Request
                        </span>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="60">
                                    No
                                </th>
                                <th width="130">
                                    SKU
                                </th>
                                <th width="100" class="text-center">
                                    Plat Lengkap
                                </th>
                                <th width="200" class="text-center">
                                    Tanggal/Bulan/Tahun
                                </th>
                                <th>
                                    Nama
                                </th>
                                <th width="160" class="text-center">
                                    Korlantas
                                </th>
                                <th width="180" class="text-center">
                                    Heartbeat
                                </th>
                                <th>
                                    Status Request
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($dataPlat as $index => $item)
                                <tr class="align-middle">
                                    <td class="text-center text-muted">
                                        {{ $index + 1 }}
                                    </td>

                                    <td>
                                        <span class="badge bg-light text-dark border px-2 py-2">
                                            <i class="bi bi-upc-scan me-1"></i>
                                            {{ $item->pesananPerProduk->sku ?: '-' }}
                                        </span>
                                    </td>

                                    <td class="text-nowrap">
                                        <span class="fw-bold text-primary">
                                            {{ $item->plat_lengkap ?: '-' }}
                                        </span>
                                    </td>

                                    <td class="text-center">
                                        <div class="d-flex align-items-center justify-content-center gap-2">
                                            <i class="bi bi-calendar3 text-muted"></i>
                                            <span class="small">
                                                {{ $item->tanggal_bulan_tahun ?: '-' }}
                                            </span>
                                        </div>
                                    </td>

                                    <td>
                                        <div class="fw-semibold text-dark">
                                            {{ $item->nama ?: '-' }}
                                        </div>
                                    </td>

                                    <td class="text-center">
                                        @if ($item->tanpa_korlantas == 1)
                                            <span
                                                class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-2">
                                                <i class="bi bi-x-circle me-1"></i>
                                                Tidak
                                            </span>
                                        @else
                                            <span
                                                class="badge bg-success-subtle text-success border border-success-subtle px-2 py-2">
                                                <i class="bi bi-check-circle me-1"></i>
                                                Ya
                                            </span>
                                        @endif
                                    </td>

                                    <td class="text-center">
                                        @if ($item->tanpa_heartbeat == 1)
                                            <span
                                                class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-2">
                                                <i class="bi bi-x-circle me-1"></i>
                                                Tidak
                                            </span>
                                        @else
                                            <span
                                                class="badge bg-success-subtle text-success border border-success-subtle px-2 py-2">
                                                <i class="bi bi-check-circle me-1"></i>
                                                Ya
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-center align-middle">
                                        <span class="badge bg-light text-dark border px-2 py-2">
                                            {{ ucfirst($item->status_request ?: '-') }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-5">
                                        <i class="bi bi-box-seam fs-1 text-muted d-block mb-2"></i>
                                        <div class="fw-semibold">
                                            Tidak Ada Produk
                                        </div>
                                        <div class="small text-muted">
                                            Pesanan ini tidak memiliki produk.
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>

    <div class="modal fade" id="modalEditPesanan" tabindex="-1" aria-labelledby="modalEditPesananLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-semibold" id="modalEditPesananLabel">
                        <i class="bi bi-pencil-square text-primary me-2"></i>
                        Edit Pesanan
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal">
                    </button>
                </div>
                <form action="{{ route('pesanan.update', $pesanan->no_pesanan) }}" method="POST" id="formEditPesanan">
                    @csrf
                    @method('PUT')
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">
                                    Tanggal Input
                                </label>
                                <input type="date" name="tanggal"
                                    class="form-control @error('tanggal') is-invalid @enderror"
                                    value="{{ old('tanggal', $pesanan->tanggal ? \Carbon\Carbon::parse($pesanan->tanggal)->format('Y-m-d') : '') }}">
                                @error('tanggal')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">
                                    Status Pesanan
                                </label>
                                @php
                                    $statusOptions = [
                                        'proses' => 'Proses',
                                        'kirim' => 'Kirim',
                                        'selesai' => 'Selesai',
                                        'affiliate' => 'Affiliate',
                                        'pengiriman gagal' => 'Pengiriman Gagal',
                                        'pengembalian' => 'Pengembalian',
                                        'batal' => 'Batal',
                                    ];
                                @endphp
                                <select name="status" class="form-select @error('status') is-invalid @enderror">
                                    <option value="">
                                        -- Pilih Status --
                                    </option>
                                    @foreach ($statusOptions as $value => $label)
                                        <option value="{{ $value }}" @selected(old('status', $pesanan->status) === $value)>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">
                                    No. Resi
                                </label>
                                <input type="text" name="no_resi"
                                    class="form-control @error('no_resi') is-invalid @enderror"
                                    value="{{ old('no_resi', $pesanan->no_resi) }}" placeholder="Masukkan nomor resi"
                                    autocomplete="off">
                                @error('no_resi')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">
                                    Toko
                                </label>
                                <select name="id_toko" class="form-select @error('id_toko') is-invalid @enderror"
                                    required>
                                    <option value="">
                                        -- Pilih Toko --
                                    </option>
                                    @foreach ($tokos->groupBy('marketplace') as $marketplace => $group)
                                        <optgroup label="{{ $marketplace }}">
                                            @foreach ($group as $toko)
                                                <option value="{{ $toko->id_toko }}" @selected((string) old('id_toko', $pesanan->id_toko) === (string) $toko->id_toko)>
                                                    {{ $toko->nama_toko }}
                                                </option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </select>
                                @error('id_toko')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">
                                    Pencairan Marketplace (Rp)
                                </label>
                                <input type="text" name="pencairan" id="pencairan"
                                    class="form-control text-end @error('pencairan') is-invalid @enderror"
                                    value="{{ old('pencairan', (int) ($pesanan->pencairan ?? 0)) }}" inputmode="numeric"
                                    autocomplete="off">
                                @error('pencairan')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                                <small class="text-muted">
                                    Dana yang diterima dari marketplace.
                                </small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">
                                    Total HPP (Rp)
                                </label>
                                <input type="text" name="total_hpp" id="total_hpp"
                                    class="form-control text-end nominal @error('total_hpp') is-invalid @enderror"
                                    value="{{ old('total_hpp', (int) ($pesanan->total_hpp ?? 0)) }}" inputmode="numeric"
                                    autocomplete="off">
                                @error('total_hpp')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                                <small class="text-muted">
                                    Total modal produksi.
                                </small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">
                                    Total Harga (Rp)
                                </label>
                                <input type="text" name="total_harga" id="total_harga"
                                    class="form-control text-end nominal @error('total_harga') is-invalid @enderror"
                                    value="{{ old('total_harga', (int) ($pesanan->total_harga ?? 0)) }}"
                                    inputmode="numeric" autocomplete="off">
                                @error('total_harga')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                                <small class="text-muted">
                                    Total nilai pesanan.
                                </small>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                            Batal
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save me-1"></i>
                            Simpan Perubahan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
@push('styles')
    <style>
        .table-row-line tr {
            border-bottom: 1px solid #dee2e6;
        }

        .table-row-line tr:last-child {
            border-bottom: 0;
        }

        /* ===== ALUR PESANAN ===== */

        .order-flow {
            display: flex;
            width: 100%;
            overflow-x: auto;
            gap: 2px;
            scrollbar-width: thin;
        }

        .order-step {
            --step-bg: #dbeafe;
            --step-border: #60a5fa;
            --step-text: #1d4ed8;

            flex: 1 0 145px;
            min-width: 145px;
            height: 48px;
            padding: 1px;
            background: var(--step-border);
            cursor: help;

            clip-path: polygon(0 0,
                    calc(100% - 18px) 0,
                    100% 50%,
                    calc(100% - 18px) 100%,
                    0 100%,
                    18px 50%);
        }

        .order-step:first-child {
            clip-path: polygon(0 0,
                    calc(100% - 18px) 0,
                    100% 50%,
                    calc(100% - 18px) 100%,
                    0 100%);
        }

        .step-inner {
            height: 100%;
            padding: 3px 20px 3px 22px;
            background: var(--step-bg);
            clip-path: inherit;

            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
        }

        .order-step:first-child .step-inner {
            padding-left: 6px;
        }

        .step-title {
            font-size: 12px;
            font-weight: 700;
            color: var(--step-text);
            line-height: 17px;
            white-space: nowrap;
        }

        .step-info {
            font-size: 12px;
            font-weight: 400;
            color: #475569;
            line-height: 17px;
            white-space: nowrap;
        }

        .step-info strong {
            color: #1e293b;
            font-weight: 600;
        }

        /* Hover */
        .order-step:hover {
            filter: brightness(0.96);
        }

        /* Belum diproses */
        .order-step.pending {
            --step-bg: #f1f5f9;
            --step-border: #cbd5e1;
            --step-text: #94a3b8;
        }

        /* ===== POPOVER PUTIH ===== */

        .order-popover {
            --bs-popover-bg: #000000;
            --bs-popover-border-color: #e2e8f0;
            --bs-popover-border-radius: 9px;
            --bs-popover-max-width: 300px;

            box-shadow: 0 5px 18px rgba(0, 0, 0, 0.12);
        }

        .order-popover .popover-body {
            padding: 11px 13px;
            color: #f0f0f0;
            font-size: 12px;
        }

        .order-popover .popover-body .info-row {
            display: flex;
            align-items: flex-start;
            gap: 9px;
        }

        .order-popover .popover-body .info-row+.info-row {
            margin-top: 9px;
        }

        .order-popover .info-row i {
            color: #2563eb;
            font-size: 15px;
            flex-shrink: 0;
        }
    </style>
@endpush
@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.copy-btn').forEach(function(button) {
                button.addEventListener('click', async function() {
                    const text = button.dataset.copy;
                    if (!text) {
                        return;
                    }
                    try {
                        await navigator.clipboard.writeText(text);
                    } catch (error) {
                        const textarea = document.createElement('textarea');
                        textarea.value = text;
                        textarea.style.position = 'fixed';
                        textarea.style.opacity = '0';
                        document.body.appendChild(textarea);
                        textarea.focus();
                        textarea.select();
                        document.execCommand('copy');
                        textarea.remove();
                    }
                    const iconLama = button.innerHTML;
                    button.innerHTML =
                        '<i class="bi bi-check2 text-success"></i>';
                    setTimeout(function() {
                        button.innerHTML = iconLama;
                    }, 1200);
                });
            });

            function formatNominal(value) {
                value = String(value || '')
                    .replace(/\D/g, '');
                return value.replace(
                    /\B(?=(\d{3})+(?!\d))/g,
                    '.'
                );
            }
            document.querySelectorAll('.nominal').forEach(function(input) {
                if (input.value) {
                    input.value = formatNominal(
                        input.value
                    );
                }
                input.addEventListener('input', function() {
                    const cursor =
                        this.selectionStart || 0;
                    const oldLength =
                        this.value.length;
                    this.value =
                        formatNominal(
                            this.value
                        );
                    const newLength =
                        this.value.length;
                    const posisi =
                        Math.max(
                            0,
                            cursor +
                            (
                                newLength -
                                oldLength
                            )
                        );
                    this.setSelectionRange(
                        posisi,
                        posisi
                    );
                });
            });
            const formEdit =
                document.getElementById(
                    'formEditPesanan'
                );
            if (formEdit) {
                formEdit.addEventListener(
                    'submit',
                    function() {
                        formEdit
                            .querySelectorAll(
                                '.nominal'
                            )
                            .forEach(
                                function(input) {
                                    input.value =
                                        input.value.replace(
                                            /\./g,
                                            ''
                                        );
                                }
                            );
                    }
                );
            }
            @if ($errors->any())
                const modalElement =
                    document.getElementById(
                        'modalEditPesanan'
                    );
                if (modalElement) {
                    const modal =
                        bootstrap.Modal
                        .getOrCreateInstance(
                            modalElement
                        );
                    modal.show();
                }
            @endif
        });

        $(document).ready(function() {
            $('#cetakResi').on('click', function() {
                const pesanan = "{{ $pesanan->no_pesanan }}";
                if (!pesanan) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Pesanan Tidak Ditemukan',
                        text: 'Nomor pesanan tidak tersedia.'
                    });

                    return;
                }

                const alasanExport = "Cetak Ulang Resi (Pesanan Selesai Ditangani)";
                const button = $(this);
                button
                    .prop('disabled', true)
                    .html(`
                        <span class="spinner-border spinner-border-sm me-1"></span>
                        Memproses...
                    `);

                $.ajax({
                    type: "POST",
                    url: "{{ route('pesanan.cetak-resi') }}",
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    contentType: "application/json",
                    dataType: "json",
                    data: JSON.stringify({
                        pesanan: [pesanan],
                        alasan_export: alasanExport,
                        source: "admin_penjualan"
                    }),

                    beforeSend: function() {
                        Swal.fire({
                            title: 'Memproses Resi',
                            html: `
                                <div class="text-muted">
                                    Sedang memproses resi...
                                </div>
                            `,
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                            showConfirmButton: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });
                    },

                    success: function(response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil',
                            text: response.message ??
                                'Resi berhasil diproses.'
                        });

                        if (response.success && response.preview_url) {
                            window.open(
                                response.preview_url,
                                '_blank'
                            );
                        }
                    },

                    error: function(xhr) {
                        const response = xhr.responseJSON;
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal',
                            text: response?.message ??
                                'Terjadi kesalahan saat memproses resi.'
                        });
                    },

                    complete: function() {
                        button
                            .prop('disabled', false)
                            .html(`
                                <i class="fa-solid fa-print me-1"></i>
                                Cetak Resi
                            `);
                    }
                });
            });

            const prosesInfo = {
                input: [{
                        icon: 'bi-file-earmark-excel',
                        text: 'Telah melakukan input Excel'
                    },
                    {
                        icon: 'bi-check-circle-fill',
                        text: 'Telah melakukan import resi'
                    }
                ],

                exported: [{
                        icon: 'bi-file-earmark-arrow-down',
                        text: 'Pesanan telah diexport'
                    },
                    {
                        icon: 'bi-check-circle-fill',
                        text: 'Pesanan telah keluar dari gudang'
                    }
                ],

                editor: [{
                        icon: 'bi-palette',
                        text: 'Pesanan masuk antrian editor'
                    },
                    {
                        icon: 'bi-check-circle-fill',
                        text: 'Request diinput & desain selesai diproses'
                    }
                ],

                produksi: [{
                        icon: 'bi-gear',
                        text: 'Pesanan masuk proses produksi'
                    },
                    {
                        icon: 'bi-check-circle-fill',
                        text: 'Produk selesai diproduksi'
                    }
                ],

                packing: [{
                        icon: 'bi-box-seam',
                        text: 'Resi pesanan telah dicetak'
                    },
                    {
                        icon: 'bi-check-circle-fill',
                        text: 'Pesanan selesai dikemas'
                    }
                ],

                dikirim: [{
                        icon: 'bi-truck',
                        text: 'Pesanan disiapkan untuk pengiriman'
                    },
                    {
                        icon: 'bi-check-circle-fill',
                        text: 'Paket telah diserahkan ke kurir'
                    }
                ]
            };

            // Inisialisasi Popover
            $('.order-step[data-step]').each(function() {
                const element = this;
                const step = element.dataset.step;
                const items = prosesInfo[step];

                if (!items) {
                    console.warn('Role tidak ditemukan:', step);
                    return;
                }

                const oldPopover = bootstrap.Popover.getInstance(element);

                if (oldPopover) {
                    oldPopover.dispose();
                }

                new bootstrap.Popover(element, {
                    container: 'body',
                    trigger: 'hover focus',
                    placement: 'top',
                    html: true,
                    sanitize: true,
                    customClass: 'order-popover',
                    content: function() {
                        const container = $('<div>');
                        items.forEach(function(item) {
                            const row = $('<div>', {
                                class: 'info-row'
                            });
                            $('<i>', {
                                class: 'bi ' + item.icon
                            }).appendTo(row);
                            $('<span>').text(item.text).appendTo(row);
                            container.append(row);
                        });

                        return container[0];
                    }
                });
            });

        });
    </script>
@endpush
