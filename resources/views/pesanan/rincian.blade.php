@extends('layouts.app')

@section('content')

<div class="container-fluid py-3">

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">

        <div class="d-flex align-items-center flex-wrap gap-2">

            <a href="{{ route('pesanan.index') }}"
                class="btn btn-outline-secondary btn-sm">

                <i class="bi bi-arrow-left"></i>
                Kembali

            </a>

            <h5 class="mb-0 fw-semibold">
                Rincian Pesanan
            </h5>

            <button type="button"
                class="btn btn-primary btn-sm"
                data-bs-toggle="modal"
                data-bs-target="#modalEditPesanan">

                <i class="bi bi-pencil-square me-1"></i>
                Edit

            </button>

            @if(!$pesanan->status_cek)

                <form action="{{ route('pesanan.cek.aktifkan', $pesanan->no_pesanan) }}"
                    method="POST"
                    class="d-inline">

                    @csrf

                    <button type="submit"
                        class="btn btn-warning btn-sm">

                        <i class="bi bi-exclamation-triangle me-1"></i>
                        Masukkan Cek

                    </button>

                </form>

            @else

                <form action="{{ route('pesanan.cek.selesai', $pesanan->no_pesanan) }}"
                    method="POST"
                    class="d-inline">

                    @csrf

                    <button type="submit"
                        class="btn btn-success btn-sm">

                        <i class="bi bi-check-circle me-1"></i>
                        Selesai Cek

                    </button>

                </form>

            @endif

        </div>

        <div class="d-flex align-items-center gap-2">

            <span class="badge rounded-pill bg-{{ $statusBadge }} px-3 py-2">
                {{ strtoupper($statusLabel) }}
            </span>

            @if($pesanan->status_cek)

                <span class="badge bg-warning text-dark">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                    PERLU CEK
                </span>

            @endif

        </div>

    </div>

    @if(session('success'))

        <div class="alert alert-success alert-dismissible fade show">

            <i class="bi bi-check-circle me-1"></i>
            {{ session('success') }}

            <button type="button"
                class="btn-close"
                data-bs-dismiss="alert">
            </button>

        </div>

    @endif

    @if(session('error'))

        <div class="alert alert-danger alert-dismissible fade show">

            <i class="bi bi-exclamation-circle me-1"></i>
            {{ session('error') }}

            <button type="button"
                class="btn-close"
                data-bs-dismiss="alert">
            </button>

        </div>

    @endif

    @if($errors->any())

        <div class="alert alert-danger alert-dismissible fade show">

            <div class="fw-semibold mb-1">
                Data belum dapat disimpan.
            </div>

            @foreach($errors->all() as $error)

                <div class="small">
                    {{ $error }}
                </div>

            @endforeach

            <button type="button"
                class="btn-close"
                data-bs-dismiss="alert">
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

                            <table class="table table-borderless table-sm mb-0">

                                <tr>

                                    <td class="text-muted"
                                        width="40%">

                                        No. Pesanan

                                    </td>

                                    <td>

                                        <div class="d-flex align-items-center gap-2">

                                            <span class="fw-semibold">
                                                {{ $pesanan->no_pesanan }}
                                            </span>

                                            <button type="button"
                                                class="btn btn-light btn-sm border copy-btn"
                                                data-copy="{{ $pesanan->no_pesanan }}">

                                                <i class="bi bi-clipboard"></i>

                                            </button>

                                        </div>

                                    </td>

                                </tr>

                                <tr>

                                    <td class="text-muted">
                                        Tanggal Input
                                    </td>

                                    <td>
                                        {{ $tanggalInput }}
                                    </td>

                                </tr>

                                <tr>

                                    <td class="text-muted">
                                        Pembeli
                                    </td>

                                    <td>
                                        {{ $pesanan->nama_pembeli ?? '-' }}
                                    </td>

                                </tr>

                                <tr>

                                    <td class="text-muted">
                                        Toko
                                    </td>

                                    <td>

                                        <div class="fw-semibold">
                                            {{ $pesanan->toko?->nama_toko ?? '-' }}
                                        </div>

                                        @if($pesanan->toko?->marketplace)

                                            <div class="small text-muted">
                                                {{ $pesanan->toko->marketplace }}
                                            </div>

                                        @endif

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

                                        @if($batasKirim)

                                            <div class="fw-semibold">
                                                {{ $batasKirim->format('d/m/Y H:i') }}
                                            </div>

                                            <div class="mt-1">

                                                <span class="badge bg-{{ $prioritasBadge }} {{ $prioritasBadge === 'warning' ? 'text-dark' : '' }}">
                                                    {{ $prioritasLabel }}
                                                </span>

                                            </div>

                                        @else

                                            <span class="text-muted">
                                                -
                                            </span>

                                        @endif

                                    </td>

                                </tr>

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

                        <div class="col-md-6">

                            <table class="table table-borderless table-sm mb-0">

                                <tr>

                                    <td class="text-muted"
                                        width="40%">

                                        No. Resi

                                    </td>

                                    <td>

                                        <div class="d-flex align-items-center gap-2">

                                            <span class="fw-semibold">
                                                {{ $pesanan->no_resi ?? '-' }}
                                            </span>

                                            @if($pesanan->no_resi)

                                                <button type="button"
                                                    class="btn btn-light btn-sm border copy-btn"
                                                    data-copy="{{ $pesanan->no_resi }}">

                                                    <i class="bi bi-clipboard"></i>

                                                </button>

                                            @endif

                                        </div>

                                    </td>

                                </tr>

                                <tr>

                                    <td class="text-muted">
                                        Tgl Kirim
                                    </td>

                                    <td>

                                        @if($pesanan->tanggal_kirim)

                                            @php
                                                try {
                                                    $tanggalKirim = \Carbon\Carbon::parse(
                                                        $pesanan->tanggal_kirim
                                                    );
                                                } catch (\Throwable $e) {
                                                    $tanggalKirim = null;
                                                }
                                            @endphp

                                            @if($tanggalKirim)

                                                <div>
                                                    {{ $tanggalKirim->format('d/m/Y') }}
                                                </div>

                                                <div class="small text-muted">
                                                    {{ $tanggalKirim->format('H:i') }}
                                                </div>

                                            @else

                                                -

                                            @endif

                                        @else

                                            -

                                        @endif

                                    </td>

                                </tr>

                                <tr>

                                    <td class="text-muted">
                                        Dikirim Oleh
                                    </td>

                                    <td>
                                        {{ $pesanan->userKirim?->name ?? '-' }}
                                    </td>

                                </tr>

                                <tr>

                                    <td class="text-muted">
                                        Cetak Resi
                                    </td>

                                    <td>

                                        @if($resiSudahDicetak)

                                            <span class="badge bg-success">
                                                SUDAH DICETAK
                                            </span>

                                            <div class="small text-muted mt-1">
                                                {{ number_format($resiPrintCount, 0, ',', '.') }}x cetak
                                            </div>

                                            @if($pesanan->resi_printed_at)

                                                @php
                                                    try {
                                                        $resiPrintedAt = \Carbon\Carbon::parse(
                                                            $pesanan->resi_printed_at
                                                        );
                                                    } catch (\Throwable $e) {
                                                        $resiPrintedAt = null;
                                                    }
                                                @endphp

                                                @if($resiPrintedAt)

                                                    <div class="small text-muted">
                                                        Pertama:
                                                        {{ $resiPrintedAt->format('d/m/Y H:i') }}
                                                    </div>

                                                @endif

                                            @endif

                                            @if($pesanan->resiPrinter)

                                                <div class="small text-muted">
                                                    Oleh:
                                                    {{ $pesanan->resiPrinter->name }}
                                                </div>

                                            @endif

                                        @else

                                            <span class="badge bg-secondary">
                                                BELUM DICETAK
                                            </span>

                                        @endif

                                    </td>

                                </tr>

                                <tr>

                                    <td class="text-muted">
                                        Item
                                    </td>

                                    <td>
                                        {{ number_format($totalItem, 0, ',', '.') }}
                                    </td>

                                </tr>

                                <tr>

                                    <td class="text-muted">
                                        Qty
                                    </td>

                                    <td class="fw-semibold">
                                        {{ number_format($totalQty, 0, ',', '.') }}
                                    </td>

                                </tr>

                                @if($pesanan->batas_kirim_raw)

                                    <tr>

                                        <td class="text-muted">
                                            Deadline Asli
                                        </td>

                                        <td>
                                            <span class="small">
                                                {{ $pesanan->batas_kirim_raw }}
                                            </span>
                                        </td>

                                    </tr>

                                @endif

                            </table>

                        </div>

                    </div>

                </div>

            </div>

        </div>

        <div class="col-lg-4">

            <div class="card shadow-sm border-0 h-100">

                <div class="card-header bg-white">

                    <h6 class="mb-0 fw-semibold">

                        <i class="bi bi-cash-stack text-success me-2"></i>
                        Ringkasan Keuangan

                    </h6>

                </div>

                <div class="card-body">

                    <table class="table table-sm table-borderless mb-0">

                        <tr>

                            <td>
                                Omzet
                            </td>

                            <td class="text-end fw-semibold">
                                Rp{{ number_format($subtotal, 0, ',', '.') }}
                            </td>

                        </tr>

                        <tr>

                            <td>
                                Biaya Admin
                            </td>

                            <td class="text-end text-danger">

                                @if($biayaAdmin > 0)
                                    -Rp{{ number_format($biayaAdmin, 0, ',', '.') }}
                                @else
                                    Rp0
                                @endif

                            </td>

                        </tr>

                        <tr>

                            <td>
                                Dana Dicairkan
                            </td>

                            <td class="text-end text-primary">
                                Rp{{ number_format($totalPencairan, 0, ',', '.') }}
                            </td>

                        </tr>

                        <tr>

                            <td>
                                HPP
                            </td>

                            <td class="text-end text-danger">

                                @if($totalHPP > 0)
                                    -Rp{{ number_format($totalHPP, 0, ',', '.') }}
                                @else
                                    Rp0
                                @endif

                            </td>

                        </tr>

                        <tr class="border-top">

                            <th>
                                Laba Bersih
                            </th>

                            <th class="text-end {{ $isProfit ? 'text-success' : 'text-danger' }}">

                                {{ $totalPenghasilan < 0 ? '-' : '' }}
                                Rp{{ number_format(abs($totalPenghasilan), 0, ',', '.') }}

                            </th>

                        </tr>

                        <tr>

                            <td>
                                Margin
                            </td>

                            <td class="text-end {{ $margin < 0 ? 'text-danger' : '' }}">
                                {{ number_format($margin, 2, ',', '.') }} %
                            </td>

                        </tr>

                        @if(strtolower((string) $pesanan->status) === 'selesai')

                            <tr>

                                <td>
                                    Selisih
                                </td>

                                <td class="text-end {{ $selisihClass }}">
                                    {{ $selisihText }}
                                </td>

                            </tr>

                        @endif

                    </table>

                    <div class="text-center mt-3">

                        <span class="badge bg-{{ $isProfit ? 'success' : 'danger' }} px-3 py-2">
                            {{ $isProfit ? 'Menguntungkan' : 'Rugi' }}
                        </span>

                    </div>

                </div>

            </div>

        </div>

    </div>

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

                        <th width="100"
                            class="text-center">

                            Qty

                        </th>

                        <th width="150"
                            class="text-end">

                            HPP

                        </th>

                        <th width="160"
                            class="text-end">

                            Harga

                        </th>

                        <th width="180"
                            class="text-end">

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

                                @if($item->id_per_produk)

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

                            <td colspan="7"
                                class="text-center py-5">

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

                        <th colspan="6"
                            class="text-end">

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

</div>

<div class="modal fade"
    id="modalEditPesanan"
    tabindex="-1"
    aria-labelledby="modalEditPesananLabel"
    aria-hidden="true">

    <div class="modal-dialog modal-lg modal-dialog-scrollable">

        <div class="modal-content">

            <div class="modal-header">

                <h5 class="modal-title fw-semibold"
                    id="modalEditPesananLabel">

                    <i class="bi bi-pencil-square text-primary me-2"></i>
                    Edit Pesanan

                </h5>

                <button type="button"
                    class="btn-close"
                    data-bs-dismiss="modal">
                </button>

            </div>

            <form action="{{ route('pesanan.update', $pesanan->no_pesanan) }}"
                method="POST"
                id="formEditPesanan">

                @csrf
                @method('PUT')

                <div class="modal-body">

                    <div class="row g-3">

                        <div class="col-md-6">

                            <label class="form-label">
                                Tanggal Input
                            </label>

                            <input type="date"
                                name="tanggal"
                                class="form-control @error('tanggal') is-invalid @enderror"
                                value="{{ old(
                                    'tanggal',
                                    $pesanan->tanggal
                                        ? \Carbon\Carbon::parse($pesanan->tanggal)->format('Y-m-d')
                                        : ''
                                ) }}">

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
                                    'pengiriman_gagal' => 'Pengiriman Gagal',
                                    'pengembalian' => 'Pengembalian',
                                    'batal' => 'Batal',
                                ];
                            @endphp

                            <select name="status"
                                class="form-select @error('status') is-invalid @enderror">

                                <option value="">
                                    -- Pilih Status --
                                </option>

                                @foreach($statusOptions as $value => $label)

                                    <option value="{{ $value }}"
                                        @selected(
                                            old(
                                                'status',
                                                $pesanan->status
                                            ) === $value
                                        )>

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

                            <input type="text"
                                name="no_resi"
                                class="form-control @error('no_resi') is-invalid @enderror"
                                value="{{ old('no_resi', $pesanan->no_resi) }}"
                                placeholder="Masukkan nomor resi"
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

                            <select name="id_toko"
                                class="form-select @error('id_toko') is-invalid @enderror"
                                required>

                                <option value="">
                                    -- Pilih Toko --
                                </option>

                                @foreach($tokos->groupBy('marketplace') as $marketplace => $group)

                                    <optgroup label="{{ $marketplace }}">

                                        @foreach($group as $toko)

                                            <option value="{{ $toko->id_toko }}"
                                                @selected(
                                                    (string) old(
                                                        'id_toko',
                                                        $pesanan->id_toko
                                                    ) ===
                                                    (string) $toko->id_toko
                                                )>

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

                            <input type="text"
                                name="pencairan"
                                id="pencairan"
                                class="form-control text-end @error('pencairan') is-invalid @enderror"
                                value="{{ old(
                                    'pencairan',
                                    (int) ($pesanan->pencairan ?? 0)
                                ) }}"
                                inputmode="numeric"
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

                            <input type="text"
                                name="total_hpp"
                                id="total_hpp"
                                class="form-control text-end nominal @error('total_hpp') is-invalid @enderror"
                                value="{{ old(
                                    'total_hpp',
                                    (int) ($pesanan->total_hpp ?? 0)
                                ) }}"
                                inputmode="numeric"
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

                            <input type="text"
                                name="total_harga"
                                id="total_harga"
                                class="form-control text-end nominal @error('total_harga') is-invalid @enderror"
                                value="{{ old(
                                    'total_harga',
                                    (int) ($pesanan->total_harga ?? 0)
                                ) }}"
                                inputmode="numeric"
                                autocomplete="off">

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

                    <button type="button"
                        class="btn btn-light"
                        data-bs-dismiss="modal">

                        Batal

                    </button>

                    <button type="submit"
                        class="btn btn-primary">

                        <i class="bi bi-save me-1"></i>
                        Simpan Perubahan

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>

@endsection

@push('scripts')

<script>
document.addEventListener('DOMContentLoaded', function () {

    document.querySelectorAll('.copy-btn').forEach(function (button) {

        button.addEventListener('click', async function () {

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

            setTimeout(function () {

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


    document.querySelectorAll('.nominal').forEach(function (input) {

        if (input.value) {

            input.value = formatNominal(
                input.value
            );

        }

        input.addEventListener('input', function () {

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
            function () {

                formEdit
                    .querySelectorAll(
                        '.nominal'
                    )
                    .forEach(
                        function (input) {

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


    @if($errors->any())

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
</script>

@endpush
