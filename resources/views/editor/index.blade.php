@extends('layouts.app')

@section('content')

<div class="container-fluid">

    <div class="mb-4">
        <h4 class="fw-bold mb-1">Dashboard Editor</h4>
        <div class="text-muted small">
            Monitoring pekerjaan Editor dan Part Produksi
        </div>
    </div>

    @include('editor.partials.alert')

    <div class="row g-3 mb-4">

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <div class="text-muted small">
                        Part Aktif
                    </div>

                    <div class="fs-2 fw-bold text-primary">
                        {{ number_format($totalPartAktif) }}
                    </div>

                    <div class="small text-muted">
                        open / sedang diedit
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
                <strong>Part Terbaru</strong>

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
                        <th>Part</th>
                        <th>Tanggal</th>
                        <th>Item</th>
                        <th>Pending</th>
                        <th>Locked</th>
                        <th>Skip</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>

                    @forelse($partsTerbaru as $part)

                        <tr>
                            <td>
                                <a href="{{ route('editor.part.show', $part) }}"
                                    class="fw-bold text-decoration-none">
                                    {{ $part->kode_part }}
                                </a>
                            </td>

                            <td>
                                {{ $part->tanggal_part->format('d/m/Y') }}
                            </td>

                            <td>
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
                                        OPEN
                                    </span>
                                @elseif($part->status === 'downloaded')
                                    <span class="badge bg-warning text-dark">
                                        DIEDIT
                                    </span>
                                @else
                                    <span class="badge bg-secondary">
                                        SELESAI
                                    </span>
                                @endif
                            </td>
                        </tr>

                    @empty

                        <tr>
                            <td colspan="7"
                                class="text-center text-muted py-5">
                                Belum ada Part Produksi.
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>

</div>

@endsection
