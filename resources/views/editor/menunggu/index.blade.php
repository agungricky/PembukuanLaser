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
                        <th class="text-center">Qty</th>
                        <th>Antrian Asal</th>
                        <th>Batas Kirim</th>
                        <th>Prioritas</th>
                        <th class="text-end">Aksi</th>
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

                            $partAsal = $partItem->part;

                            $sesi = strtoupper(
                                $partAsal?->sesi ?? '-'
                            );

                            $badgeSesi = match($partAsal?->sesi) {
                                'pagi' => 'bg-primary',
                                'siang' => 'bg-warning text-dark',
                                'malam' => 'bg-dark',
                                default => 'bg-secondary',
                            };
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

                                @if($item?->variasi)

                                    <div class="small text-muted">
                                        {{ $item->variasi }}
                                    </div>

                                @endif

                            </td>

                            <td class="text-center fw-bold">
                                {{ $partItem->jumlah_awal }}
                            </td>

                            <td>

                                @if($partAsal)

                                    <div class="fw-semibold">
                                        {{ $partAsal->kode_part }}
                                    </div>

                                    <div class="mt-1">

                                        <span class="badge {{ $badgeSesi }}">
                                            {{ $sesi }}
                                        </span>

                                    </div>

                                @else

                                    <span class="text-muted">
                                        -
                                    </span>

                                @endif

                            </td>

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
                                    onsubmit="return confirm('Request customer sudah tersedia dan masukkan item ke antrian Editor berikutnya?')">

                                    @csrf

                                    <button type="submit"
                                        class="btn btn-sm btn-primary">

                                        <i class="bi bi-arrow-right-circle me-1"></i>
                                        Masukkan ke Antrian

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
