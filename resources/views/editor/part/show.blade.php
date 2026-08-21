@extends('layouts.app')

@section('content')

<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">

        <div>
            <a href="{{ route('editor.part.index') }}"
                class="text-decoration-none small">
                <i class="bi bi-arrow-left"></i>
                Kembali
            </a>

            <h4 class="fw-bold mb-1 mt-2">
                {{ $part->kode_part }}
            </h4>

            <div class="text-muted small">
                {{ $part->tanggal_part->translatedFormat('d F Y') }}
            </div>
        </div>

        <div class="d-flex gap-2">

            @if(in_array($part->status, ['open', 'downloaded']))

                <a href="{{ route('editor.part.download', $part) }}"
                    class="btn btn-success">

                    <i class="bi bi-file-earmark-excel me-1"></i>

                    {{ $part->status === 'downloaded'
                        ? 'Download Ulang Excel'
                        : 'Download Excel' }}
                </a>

            @endif

        </div>

    </div>

    @include('editor.partials.alert')

    <div class="row g-3 mb-4">

        @foreach($kelompok as $nama => $data)

            @php
                $persen = min(
                    100,
                    ($data['jumlah'] / 52) * 100
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
                                {{ $data['jumlah'] }}/52
                            </strong>
                        </div>

                        <div class="progress"
                            style="height:7px">

                            <div class="progress-bar"
                                style="width:{{ $persen }}%">
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

    <div class="card border-0 shadow-sm">

        <div class="card-header bg-white py-3">
            <strong>Daftar Item</strong>
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
                        <th>Qty</th>
                        <th>Batas Kirim</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>

                    @foreach($part->items as $partItem)

                        @php
                            $item = $partItem->item;
                            $pesanan = $item?->pesanan;
                            $deadline = $pesanan?->batas_kirim_at;
                        @endphp

                        <tr>

                            <td>
                                {{ $partItem->urutan }}
                            </td>

                            <td>
                                {{ $partItem->id_per_produk }}
                            </td>

                            <td>
                                <div class="fw-semibold">
                                    {{ $item?->no_pesanan ?? '-' }}
                                </div>

                                <div class="small text-muted">
                                    {{ $pesanan?->nama_pembeli ?? '-' }}
                                </div>
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

                                <div class="small text-muted">
                                    {{ $item?->variasi ?? '-' }}
                                </div>
                            </td>

                            <td class="fw-bold">
                                {{ $partItem->jumlah_awal }}
                            </td>

                            <td>
                                @if($deadline)
                                    {{ $deadline->format('d/m/Y H:i') }}
                                @else
                                    -
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
                                @else
                                    <span class="badge bg-danger">
                                        MENUNGGU
                                    </span>
                                @endif
                            </td>

                        </tr>

                    @endforeach

                </tbody>

            </table>

        </div>

    </div>

</div>

@endsection
