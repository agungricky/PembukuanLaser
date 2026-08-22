@extends('layouts.app')

@section('content')

<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">

        <div>

            @if($part->status === 'processed')

                <a href="{{ route('editor.riwayat.index') }}"
                    class="text-decoration-none small">

                    <i class="fa-solid fa-arrow-left me-1"></i>
                    Kembali ke Riwayat

                </a>

            @else

                <a href="{{ route('editor.part.index') }}"
                    class="text-decoration-none small">

                    <i class="fa-solid fa-arrow-left me-1"></i>
                    Kembali ke Part Produksi

                </a>

            @endif

            <h4 class="fw-bold mb-1 mt-2">
                {{ $part->kode_part }}
            </h4>

            <div class="text-muted small">
                {{ $part->tanggal_part->translatedFormat('d F Y') }}
            </div>

        </div>

        <div class="d-flex gap-2 flex-wrap">

            @if(in_array($part->status, ['open', 'downloaded']))

                <a href="{{ route('editor.part.download', $part) }}"
                    class="btn btn-success">

                    <i class="fa-solid fa-file-excel me-1"></i>

                    {{ $part->status === 'downloaded'
                        ? 'Download Ulang Excel'
                        : 'Download Excel' }}

                </a>

            @endif

            @if($part->status === 'processed')

                <a href="{{ route('editor.part.qr.pdf', $part) }}"
                    class="btn btn-dark">

                    <i class="fa-solid fa-file-pdf me-1"></i>
                    Download QR PDF

                </a>

            @endif

        </div>

    </div>

    @include('editor.partials.alert')

    <div class="row g-3 mb-4">

        <div class="col-xl-3 col-md-6">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-body">

                    <div class="small text-muted mb-2">
                        Status Part
                    </div>

                    @if($part->status === 'open')

                        <span class="badge bg-success fs-6">
                            SIAP DOWNLOAD
                        </span>

                    @elseif($part->status === 'downloaded')

                        <span class="badge bg-warning text-dark fs-6">
                            SEDANG DIEDIT
                        </span>

                    @elseif($part->status === 'processed')

                        <span class="badge bg-primary fs-6">
                            SELESAI
                        </span>

                    @else

                        <span class="badge bg-secondary fs-6">
                            {{ strtoupper($part->status) }}
                        </span>

                    @endif

                </div>

            </div>

        </div>

        <div class="col-xl-3 col-md-6">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-body">

                    <div class="small text-muted mb-1">
                        Total Item
                    </div>

                    <div class="fs-4 fw-bold">
                        {{ $part->items->count() }}
                    </div>

                </div>

            </div>

        </div>

        <div class="col-xl-3 col-md-6">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-body">

                    <div class="small text-muted mb-1">
                        Locked
                    </div>

                    <div class="fs-4 fw-bold text-success">
                        {{ $part->items->where('status', 'locked')->count() }}
                    </div>

                </div>

            </div>

        </div>

        <div class="col-xl-3 col-md-6">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-body">

                    <div class="small text-muted mb-1">
                        Menunggu Request
                    </div>

                    <div class="fs-4 fw-bold text-danger">
                        {{ $part->items->where('status', 'skipped')->count() }}
                    </div>

                </div>

            </div>

        </div>

    </div>

    @if($kelompok->isNotEmpty())

        <div class="row g-3 mb-4">

            @foreach($kelompok as $nama => $data)

                @php
                    $kapasitas = $part->kapasitas_per_kelompok ?: 52;

                    $persen = min(
                        100,
                        ($data['jumlah'] / $kapasitas) * 100
                    );
                @endphp

                <div class="col-xl-4 col-md-6">

                    <div class="card border-0 shadow-sm h-100">

                        <div class="card-body">

                            <div class="small text-muted mb-1">
                                Kelompok Produksi
                            </div>

                            <div class="fw-bold mb-3">
                                {{ str_replace('|', ' • ', $nama) }}
                            </div>

                            <div class="d-flex justify-content-between mb-2">

                                <span class="text-muted small">
                                    Kapasitas
                                </span>

                                <strong>
                                    {{ $data['jumlah'] }}/{{ $kapasitas }}
                                </strong>

                            </div>

                            <div class="progress"
                                style="height: 7px">

                                <div class="progress-bar"
                                    role="progressbar"
                                    style="width: {{ $persen }}%">
                                </div>

                            </div>

                            <div class="small text-muted mt-2">
                                {{ $data['item'] }} item pesanan
                            </div>

                        </div>

                    </div>

                </div>

            @endforeach

        </div>

    @endif

    <div class="card border-0 shadow-sm">

        <div class="card-header bg-white py-3">

            <div class="d-flex justify-content-between align-items-center">

                <strong>
                    Daftar Item
                </strong>

                <span class="text-muted small">
                    {{ $part->items->count() }} item
                </span>

            </div>

        </div>

        <div class="table-responsive">

            <table class="table align-middle mb-0">

                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>ID Item</th>
                        <th>No Pesanan</th>
                        <th>SKU</th>
                        <th>Produk</th>
                        <th class="text-center">Qty Awal</th>

                        @if($part->status === 'processed')
                            <th class="text-center">Qty Hasil</th>
                        @endif

                        <th>Batas Kirim</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>

                    @forelse($part->items as $partItem)

                        @php
                            $item = $partItem->item;
                            $pesanan = $item?->pesanan;
                            $deadline = $pesanan?->batas_kirim_at;
                        @endphp

                        <tr class="{{ $partItem->status === 'skipped' ? 'table-light' : '' }}">

                            <td>
                                {{ $partItem->urutan }}
                            </td>

                            <td>
                                <span class="small fw-semibold">
                                    {{ $partItem->id_per_produk }}
                                </span>
                            </td>

                            <td>

                                <div class="fw-semibold">
                                    {{ $item?->no_pesanan ?? '-' }}
                                </div>

                                @if($pesanan?->nama_pembeli)

                                    <div class="small text-muted">
                                        {{ $pesanan->nama_pembeli }}
                                    </div>

                                @endif

                            </td>

                            <td>

                                <span class="badge bg-light text-dark border">
                                    {{ $partItem->sku }}
                                </span>

                            </td>

                            <td>

                                <div>
                                    {{ $item?->nama_produk ?? '-' }}
                                </div>

                                @if($item?->variasi)

                                    <div class="small text-muted">
                                        {{ $item->variasi }}
                                    </div>

                                @endif

                            </td>

                            <td class="text-center fw-bold">
                                {{ $partItem->jumlah_awal }}
                            </td>

                            @if($part->status === 'processed')

                                <td class="text-center">

                                    @if($partItem->status === 'locked')

                                        <strong>
                                            {{ $partItem->jumlah_final ?? 0 }}
                                        </strong>

                                    @else

                                        <span class="text-muted">
                                            -
                                        </span>

                                    @endif

                                </td>

                            @endif

                            <td>

                                @if($deadline)

                                    <div class="fw-semibold">
                                        {{ $deadline->format('d/m/Y') }}
                                    </div>

                                    <div class="small text-muted">
                                        {{ $deadline->format('H:i') }}
                                    </div>

                                @else

                                    <span class="text-muted">
                                        -
                                    </span>

                                @endif

                            </td>

                            <td>

                                @if($partItem->status === 'pending')

                                    <span class="badge bg-warning text-dark">
                                        PENDING
                                    </span>

                                @elseif($partItem->status === 'locked')

                                    <span class="badge bg-success">
                                        LOCKED
                                    </span>

                                @elseif($partItem->status === 'skipped')

                                    <span class="badge bg-danger">
                                        MENUNGGU
                                    </span>

                                @else

                                    <span class="badge bg-secondary">
                                        {{ strtoupper($partItem->status) }}
                                    </span>

                                @endif

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td colspan="{{ $part->status === 'processed' ? 9 : 8 }}"
                                class="text-center py-5 text-muted">

                                Tidak ada item dalam Part ini.

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>

</div>

@endsection
