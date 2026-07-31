@extends('layouts.app')

@section('content')

<div class="container-fluid py-3">

    {{-- ===================== HEADER ===================== --}}
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

            <button
                type="button"
                class="btn btn-primary btn-sm"
                data-bs-toggle="modal"
                data-bs-target="#modalEditPesanan">

                <i class="bi bi-pencil-square me-1"></i>
                Edit

            </button>

            @if(!$pesanan->status_cek)

                <form action="{{ route('pesanan.cek.aktifkan',$pesanan->no_pesanan) }}"
                      method="POST"
                      class="d-inline">

                    @csrf

                    <button class="btn btn-warning btn-sm">

                        <i class="bi bi-exclamation-triangle me-1"></i>

                        Masukkan Cek

                    </button>

                </form>

            @else

                <form action="{{ route('pesanan.cek.selesai',$pesanan->no_pesanan) }}"
                      method="POST"
                      class="d-inline">

                    @csrf

                    <button class="btn btn-success btn-sm">

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

    {{-- ===================== INFORMASI & RINGKASAN ===================== --}}
    <div class="row g-3 mb-3">
    
        {{-- Informasi Pesanan --}}
        <div class="col-lg-8">
    
            <div class="card shadow-sm border-0 h-100">
    
                <div class="card-header bg-white">
    
                    <h6 class="mb-0 fw-semibold">
                        <i class="bi bi-receipt me-2 text-primary"></i>
                        Informasi Pesanan
                    </h6>
    
                </div>
    
                <div class="card-body">
    
                    <div class="row">
    
                        <div class="col-md-6">
    
                            <table class="table table-borderless table-sm mb-0">
    
                                <tr>
                                    <td class="text-muted" width="40%">No. Pesanan</td>
                                    <td class="fw-semibold">{{ $pesanan->no_pesanan }}</td>
                                </tr>
    
                                <tr>
                                    <td class="text-muted">Tanggal</td>
                                    <td>{{ $tanggalInput }}</td>
                                </tr>
    
                                <tr>
                                    <td class="text-muted">Pembeli</td>
                                    <td>{{ $pesanan->nama_pembeli ?? '-' }}</td>
                                </tr>
    
                                <tr>
                                    <td class="text-muted">Toko</td>
                                    <td>{{ $pesanan->toko?->nama_toko ?? '-' }}</td>
                                </tr>
    
                                <tr>
                                    <td class="text-muted">Kurir</td>
                                    <td>{{ $pesanan->kurir ?? '-' }}</td>
                                </tr>
    
                            </table>
    
                        </div>
    
                        <div class="col-md-6">
    
                            <table class="table table-borderless table-sm mb-0">
    
                                <tr>
    
                                    <td class="text-muted" width="40%">
                                        No. Resi
                                    </td>
    
                                    <td>
    
                                        <div class="d-flex align-items-center gap-2">
    
                                            <span class="fw-semibold">
    
                                                {{ $pesanan->no_resi ?? '-' }}
    
                                            </span>
    
                                            @if($pesanan->no_resi)
    
                                                <button
                                                    type="button"
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
                                
                                            {{ $pesanan->tanggal_kirim->format('d M Y') }}
                                
                                            <small class="text-muted">
                                                {{ $pesanan->tanggal_kirim->format('H:i') }} WIB
                                            </small>
                                
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
                                        Item
                                    </td>
    
                                    <td>
    
                                        {{ $totalItem }}
    
                                    </td>
    
                                </tr>
    
                                <tr>
    
                                    <td class="text-muted">
                                        Qty
                                    </td>
    
                                    <td>
    
                                        {{ number_format($totalQty,0,',','.') }}
    
                                    </td>
    
                                </tr>
    
                            </table>
    
                        </div>
    
                    </div>
    
                </div>
    
            </div>
    
        </div>
    
        {{-- Ringkasan --}}
        <div class="col-lg-4">
    
            <div class="card shadow-sm border-0">
    
                <div class="card-header bg-white">
    
                    <h6 class="mb-0 fw-semibold">
    
                        <i class="bi bi-cash-stack text-success me-2"></i>
    
                        Ringkasan Keuangan
    
                    </h6>
    
                </div>
    
                <div class="card-body">
    
                    <table class="table table-sm table-borderless mb-0">
    
                        <tr>
    
                            <td>Omzet</td>
    
                            <td class="text-end fw-semibold">
                                Rp{{ number_format($subtotal,0,',','.') }}
                            </td>
    
                        </tr>
    
                        <tr>
    
                            <td>Biaya Admin</td>
    
                            <td class="text-end text-danger">
                                -Rp{{ number_format($biayaAdmin,0,',','.') }}
                            </td>
    
                        </tr>
    
                        <tr>
    
                            <td>Dana Dicairkan</td>
    
                            <td class="text-end text-primary">
                                Rp{{ number_format($totalPencairan,0,',','.') }}
                            </td>
    
                        </tr>
    
                        <tr>
    
                            <td>HPP</td>
    
                            <td class="text-end text-danger">
                                -Rp{{ number_format($totalHPP,0,',','.') }}
                            </td>
    
                        </tr>
    
                        <tr class="border-top">
    
                            <th>Laba Bersih</th>
    
                            <th class="text-end {{ $isProfit ? 'text-success':'text-danger' }}">
    
                                Rp{{ number_format($totalPenghasilan,0,',','.') }}
    
                            </th>
    
                        </tr>
    
                        <tr>
    
                            <td>Margin</td>
    
                            <td class="text-end">
    
                                {{ number_format($margin,2,',','.') }} %
    
                            </td>
    
                        </tr>
    
                        @if(strtolower($pesanan->status)=='selesai')
    
                            <tr>
    
                                <td>Selisih</td>
    
                                <td class="text-end {{ $selisihClass }}">
    
                                    {{ $selisihText }}
    
                                </td>
    
                            </tr>
    
                        @endif
    
                    </table>
    
                    <div class="text-center mt-3">
    
                        <span class="badge bg-{{ $isProfit ? 'success':'danger' }} px-3 py-2">
    
                            {{ $isProfit ? 'Menguntungkan' : 'Rugi' }}
    
                        </span>
    
                    </div>
    
                </div>
    
            </div>
    
        </div>
    
    </div>

    {{-- ===================== DAFTAR PRODUK ===================== --}}
    <div class="card shadow-sm border-0 mb-3">

        <div class="card-header bg-white">

            <div class="d-flex justify-content-between align-items-center">

                <div>

                    <h6 class="mb-0 fw-semibold">

                        <i class="bi bi-box-seam text-primary me-2"></i>

                        Daftar Produk

                    </h6>

                    <small class="text-muted">

                        {{ number_format($totalItem,0,',','.') }} Item
                        •
                        {{ number_format($totalQty,0,',','.') }} Qty

                    </small>

                </div>

            </div>

        </div>

        <div class="table-responsive">

            <table class="table table-hover align-middle mb-0">

                <thead class="table-light">

                    <tr>

                        <th width="60">No</th>

                        <th>Produk</th>

                        <th width="140" class="text-center">

                            Qty

                        </th>

                        <th width="170" class="text-end">

                            Harga

                        </th>

                        <th width="190" class="text-end">

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

                                <div class="fw-semibold">

                                    {{ $item->nama_produk }}

                                </div>

                                <small class="text-muted">

                                    Variasi :
                                    {{ $item->variasi ?: '-' }}

                                </small>

                            </td>

                            <td class="text-center">

                                <span class="badge bg-secondary">

                                    {{ number_format($item->jumlah,0,',','.') }}

                                </span>

                            </td>

                            <td class="text-end">

                                Rp{{ number_format($item->harga,0,',','.') }}

                            </td>

                            <td class="text-end fw-semibold">

                                Rp{{ number_format($item->subtotal,0,',','.') }}

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td colspan="5" class="text-center py-5 text-muted">

                                Tidak ada produk.

                            </td>

                        </tr>

                    @endforelse

                </tbody>

                <tfoot class="table-light">

                    <tr>

                        <th colspan="4" class="text-end">

                            Total Penjualan

                        </th>

                        <th class="text-end text-success">

                            Rp{{ number_format($subtotal,0,',','.') }}

                        </th>

                    </tr>

                </tfoot>

            </table>

        </div>

    </div>
{{-- ===================== MODAL EDIT PESANAN ===================== --}}
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

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal">
                </button>

            </div>

            <form action="{{ route('pesanan.update',$pesanan->no_pesanan) }}"
                  method="POST">

                @csrf
                @method('PUT')

                <div class="modal-body">

                    <div class="row g-3">

                        <div class="col-md-6">

                            <label class="form-label">

                                Tanggal Input

                            </label>

                            <input
                                type="date"
                                name="tanggal"
                                class="form-control"

                                value="{{ old(
                                    'tanggal',
                                    optional($pesanan->tanggal)->format('Y-m-d')
                                ) }}">

                        </div>

                        <div class="col-md-6">

                            <label class="form-label">

                                Status Pesanan

                            </label>

                            <select
                                name="status"
                                class="form-select">

                                <option value="">

                                    -- Pilih Status --

                                </option>

                                @foreach([
                                    'proses',
                                    'kirim',
                                    'selesai',
                                    'affiliate',
                                    'pengiriman gagal',
                                    'pengembalian',
                                    'batal'
                                ] as $status)

                                    <option
                                        value="{{ $status }}"
                                        @selected(old('status',$pesanan->status)==$status)>

                                        {{ ucfirst($status) }}

                                    </option>

                                @endforeach

                            </select>

                        </div>
                        
                        <div class="col-12 col-md-6">
                            <label class="form-label">No. Resi</label>
                        
                            <input
                                type="text"
                                name="no_resi"
                                class="form-control"
                                value="{{ old('no_resi', $pesanan->no_resi) }}"
                                placeholder="Masukkan nomor resi">
                        </div>


                        <div class="col-md-6">

                            <label class="form-label">

                                Toko

                            </label>

                            <select
                                name="id_toko"
                                class="form-select">

                                <option value="">

                                    -- Pilih Toko --

                                </option>

                                @foreach($tokos->groupBy('marketplace') as $marketplace => $group)

                                    <optgroup label="{{ $marketplace }}">

                                        @foreach($group as $toko)

                                            <option
                                                value="{{ $toko->id_toko }}"
                                                @selected(old('id_toko',$pesanan->id_toko)==$toko->id_toko)>

                                                {{ $toko->nama_toko }}

                                            </option>

                                        @endforeach

                                    </optgroup>

                                @endforeach

                            </select>

                        </div>


                        <div class="col-md-6">

                            <label class="form-label">

                                Pencairan Marketplace (Rp)

                            </label>

                            <input
                                type="text"
                                name="pencairan"
                                id="pencairan"
                                class="form-control text-end nominal"

                                value="{{ old('pencairan',(int)($pesanan->pencairan ?? 0)) }}">

                            <small class="text-muted">

                                Dana yang diterima dari marketplace.

                            </small>

                        </div>

                        <div class="col-md-6">

                            <label class="form-label">

                                Total HPP (Rp)

                            </label>

                            <input
                                type="text"
                                name="total_hpp"
                                id="total_hpp"
                                class="form-control text-end nominal"

                                value="{{ old('total_hpp',(int)($pesanan->total_hpp ?? 0)) }}">

                            <small class="text-muted">

                                Total modal produksi.

                            </small>

                        </div>


                        <div class="col-md-6">

                            <label class="form-label">

                                Total Harga (Rp)

                            </label>

                            <input
                                type="text"
                                name="total_harga"
                                id="total_harga"
                                class="form-control text-end nominal"

                                value="{{ old('total_harga',(int)($pesanan->total_harga ?? 0)) }}">

                            <small class="text-muted">

                                Total nilai pesanan.

                            </small>

                        </div>

                    </div>

                </div>

                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn btn-light"
                        data-bs-dismiss="modal">

                        Batal

                    </button>

                    <button
                        type="submit"
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

document.addEventListener('DOMContentLoaded', () => {

    document.querySelectorAll('.copy-btn').forEach(button => {

        button.addEventListener('click', async () => {

            const text = button.dataset.copy;

            if (!text) return;

            try {

                await navigator.clipboard.writeText(text);

            } catch (e) {

                const textarea = document.createElement('textarea');

                textarea.value = text;

                document.body.appendChild(textarea);

                textarea.select();

                document.execCommand('copy');

                textarea.remove();

            }

            button.innerHTML =
                '<i class="bi bi-check2 text-success"></i>';

            setTimeout(() => {

                button.innerHTML =
                    '<i class="bi bi-clipboard"></i>';

            },1200);

        });

    });

    const formatNominal = (value) => {

        value = value.toString().replace(/\D/g,'');

        return value.replace(/\B(?=(\d{3})+(?!\d))/g,'.');

    };



    document.querySelectorAll('.nominal').forEach(input => {

        if(input.value){

            input.value = formatNominal(input.value);

        }

        input.addEventListener('input', function(){

            let cursor = this.selectionStart;

            let oldLength = this.value.length;

            this.value = formatNominal(this.value);

            let newLength = this.value.length;

            this.setSelectionRange(

                cursor + (newLength-oldLength),

                cursor + (newLength-oldLength)

            );

        });

    });


    document.querySelectorAll('form').forEach(form => {

        form.addEventListener('submit', () => {

            form.querySelectorAll('.nominal').forEach(input => {

                input.value = input.value.replace(/\./g,'');

            });

        });

    });

});

</script>

@endpush