@extends('layouts.app')

@section('content')

<div class="container-fluid">

    <div class="mb-4">

        <h4 class="fw-bold mb-1">
            Riwayat Antrian Editor
        </h4>

        <div class="text-muted small">
            Antrian PAGI, SIANG, dan MALAM yang sudah selesai diproses Editor
        </div>

    </div>

    @include('editor.partials.alert')

    <div class="card border-0 shadow-sm">

        <div class="table-responsive">

            <table class="table align-middle mb-0">

                <thead class="table-light">
                    <tr>
                        <th>Antrian</th>
                        <th>Tanggal</th>
                        <th>Item</th>
                        <th>Locked</th>
                        <th>Menunggu</th>
                        <th>Download</th>
                        <th>Upload</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>

                <tbody>

                    @forelse($parts as $part)

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

                                <div class="fw-bold">
                                    {{ $part->kode_part }}
                                </div>

                                <div class="d-flex gap-1 flex-wrap mt-1">

                                    <span class="badge {{ $badgeSesi }}">
                                        {{ $sesi }}
                                    </span>

                                    <span class="badge bg-success">
                                        SELESAI
                                    </span>

                                </div>

                            </td>

                            <td>
                                {{ $part->tanggal_part->format('d/m/Y') }}
                            </td>

                            <td class="fw-semibold">
                                {{ $part->jumlah_item }}
                            </td>

                            <td class="text-success fw-bold">
                                {{ $part->locked_count }}
                            </td>

                            <td class="text-danger fw-bold">
                                {{ $part->skipped_count }}
                            </td>

                            <td>

                                @if($part->downloaded_at)

                                    <div class="fw-semibold">
                                        {{ $part->downloaded_at->format('d/m/Y') }}
                                    </div>

                                    <div class="small text-muted">
                                        {{ $part->downloaded_at->format('H:i') }}
                                    </div>

                                @else

                                    <span class="text-muted">
                                        -
                                    </span>

                                @endif

                            </td>

                            <td>

                                @if($part->uploaded_at)

                                    <div class="fw-semibold">
                                        {{ $part->uploaded_at->format('d/m/Y') }}
                                    </div>

                                    <div class="small text-muted">
                                        {{ $part->uploaded_at->format('H:i') }}
                                    </div>

                                @else

                                    <span class="text-muted">
                                        -
                                    </span>

                                @endif

                            </td>

                            <td class="text-end">

                                <a href="{{ route('editor.part.show', $part) }}"
                                    class="btn btn-sm btn-outline-primary">

                                    <i class="fa-solid fa-eye me-1"></i>
                                    Detail

                                </a>

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td colspan="8"
                                class="text-center py-5">

                                <div class="text-muted mb-2">
                                    <i class="fa-solid fa-clock-rotate-left fa-2x"></i>
                                </div>

                                <div class="fw-semibold">
                                    Belum Ada Riwayat Antrian
                                </div>

                                <div class="small text-muted">
                                    Antrian yang sudah selesai diproses akan muncul di sini.
                                </div>

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>

    @if($parts->hasPages())

        <div class="mt-3">
            {{ $parts->links() }}
        </div>

    @endif

</div>

@endsection
