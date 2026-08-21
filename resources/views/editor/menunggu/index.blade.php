@extends('layouts.app')

@section('content')

<div class="container-fluid">

    <div class="mb-4">
        <h4 class="fw-bold mb-1">
            Menunggu Request
        </h4>

        <div class="text-muted small">
            Pesanan yang belum memberikan request desain
        </div>
    </div>

    @include('editor.partials.alert')

    <div class="card border-0 shadow-sm">

        <div class="table-responsive">

            <table class="table align-middle mb-0">

                <thead class="table-light">
                    <tr>
                        <th>No Pesanan</th>
                        <th>SKU</th>
                        <th>Produk</th>
                        <th>Qty</th>
                        <th>Part Lama</th>
                        <th>Batas Kirim</th>
                        <th>Prioritas</th>
                        <th></th>
                    </tr>
                </thead>

                <tbody>

                    @forelse($items as $partItem)

                        @php
                            $item = $partItem->item;
                            $pesanan = $item?->pesanan;
                            $deadline = $pesanan?->batas_kirim_at;
                            $sisaJam = $deadline
                                ? now()->diffInHours($deadline, false)
                                : null;
                        @endphp

                        <tr>

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
                                {{ $partItem->part?->kode_part ?? '-' }}
                            </td>

                            <td>
                                @if($deadline)
                                    <div>
                                        {{ $deadline->format('d/m/Y') }}
                                    </div>

                                    <div class="small text-muted">
                                        {{ $deadline->format('H:i') }}
                                    </div>
                                @else
                                    -
                                @endif
                            </td>

                            <td>
                                @if($sisaJam === null)

                                    <span class="badge bg-secondary">
                                        BELUM ADA
                                    </span>

                                @elseif($sisaJam <= 24)

                                    <span class="badge bg-danger">
                                        URGENT
                                    </span>

                                @elseif($sisaJam <= 48)

                                    <span class="badge bg-warning text-dark">
                                        MENDEKATI
                                    </span>

                                @else

                                    <span class="badge bg-success">
                                        AMAN
                                    </span>

                                @endif
                            </td>

                            <td class="text-end">

                                <form method="POST"
                                    action="{{ route('editor.menunggu.siap', $partItem) }}"
                                    onsubmit="return confirm('Request customer sudah tersedia dan masukkan ke Part berikutnya?')">

                                    @csrf

                                    <button type="submit"
                                        class="btn btn-sm btn-primary">
                                        <i class="bi bi-arrow-right-circle me-1"></i>
                                        Masukkan ke Part
                                    </button>

                                </form>

                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="8"
                                class="text-center py-5">

                                <i class="bi bi-check-circle fs-1 text-success d-block mb-2"></i>

                                <div class="fw-semibold">
                                    Tidak Ada Request Menunggu
                                </div>

                                <div class="small text-muted">
                                    Semua request customer sudah tersedia.
                                </div>

                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>

</div>

@endsection
