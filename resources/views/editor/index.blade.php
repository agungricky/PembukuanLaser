@extends('layouts.app')

@section('content')

<div class="container-fluid">

    <div class="mb-4">
        <h4 class="fw-bold mb-1">
            Dashboard Editor
        </h4>

        <div class="text-muted small">
            Monitoring pekerjaan Editor dan Antrian Produksi
        </div>
    </div>

    @include('editor.partials.alert')

    <div class="row g-3 mb-4">

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">

                    <div class="text-muted small">
                        Antrian Aktif
                    </div>

                    <div class="fs-2 fw-bold text-primary">
                        {{ number_format($totalPartAktif) }}
                    </div>

                    <div class="small text-muted">
                        siap download / sedang diedit
                    </div>

                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">

                    <div class="text-muted small">
                        Belum Dikerjakan
                    </div>

                    <div class="fs-2 fw-bold text-warning">
                        {{ number_format($totalBelumEditor) }}
                    </div>

                    <div class="small text-muted">
                        item
                    </div>

                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">

                    <div class="text-muted small">
                        Sudah Dikunci
                    </div>

                    <div class="fs-2 fw-bold text-success">
                        {{ number_format($totalSelesaiEditor) }}
                    </div>

                    <div class="small text-muted">
                        item
                    </div>

                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">

                    <div class="text-muted small">
                        Menunggu Request
                    </div>

                    <div class="fs-2 fw-bold text-danger">
                        {{ number_format($totalMenunggu) }}
                    </div>

                    <div class="small text-muted">
                        item
                    </div>

                </div>
            </div>
        </div>

    </div>

    <div class="card border-0 shadow-sm">

        <div class="card-header bg-white py-3">

            <div class="d-flex justify-content-between align-items-center">

                <strong>
                    Antrian Terbaru
                </strong>

                <a href="{{ route('editor.part.index') }}"
                    class="btn btn-sm btn-outline-primary">

                    Lihat Semua

                </a>

            </div>

        </div>

        <div class="table-responsive">

            <table class="table align-middle mb-0">

                <thead class="table-light">
                    <tr>
                        <th>Antrian</th>
                        <th>Tanggal</th>
                        <th>Item</th>
                        <th>Pending</th>
                        <th>Locked</th>
                        <th>Menunggu</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>

                    @forelse($partsTerbaru as $part)

                        @php
                            $sesi = strtoupper($part->sesi ?? '-');

                            $badgeSesi = match($part->sesi) {
                                'pagi' => 'bg-primary',
                                'siang' => 'bg-warning text-dark',
                                'malam' => 'bg-dark',
                                default => 'bg-secondary',
                            };
                        @endphp

                        <tr>

                            <td>

                                <a href="{{ route('editor.part.show', $part) }}"
                                    class="fw-bold text-decoration-none">

                                    {{ $part->kode_part }}

                                </a>

                                <div class="mt-1">

                                    <span class="badge {{ $badgeSesi }}">
                                        {{ $sesi }}
                                    </span>

                                </div>

                            </td>

                            <td>
                                {{ $part->tanggal_part->format('d/m/Y') }}
                            </td>

                            <td class="fw-semibold">
                                {{ $part->jumlah_item }}
                            </td>

                            <td>
                                {{ $part->pending_count }}
                            </td>

                            <td class="text-success fw-semibold">
                                {{ $part->locked_count }}
                            </td>

                            <td class="text-danger fw-semibold">
                                {{ $part->skipped_count }}
                            </td>

                            <td>

                                @if($part->status === 'open')

                                    <span class="badge bg-success">
                                        SIAP DOWNLOAD
                                    </span>

                                @elseif($part->status === 'downloaded')

                                    <span class="badge bg-warning text-dark">
                                        SEDANG DIEDIT
                                    </span>

                                @elseif($part->status === 'processed')

                                    <span class="badge bg-primary">
                                        SELESAI
                                    </span>

                                @else

                                    <span class="badge bg-secondary">
                                        {{ strtoupper($part->status) }}
                                    </span>

                                @endif

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td colspan="7"
                                class="text-center py-5">

                                <div class="text-muted mb-2">
                                    <i class="fa-solid fa-box-open fa-2x"></i>
                                </div>

                                <div class="fw-semibold">
                                    Belum Ada Antrian Editor
                                </div>

                                <div class="small text-muted">
                                    Antrian akan muncul otomatis ketika ada pesanan PLT yang perlu dikerjakan.
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
