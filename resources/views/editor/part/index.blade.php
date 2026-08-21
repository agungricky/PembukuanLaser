@extends('layouts.app')

@section('content')

<div class="container-fluid">

    <div class="mb-4">
        <h4 class="fw-bold mb-1">Part Produksi</h4>

        <div class="text-muted small">
            Daftar Part yang belum selesai dikerjakan Editor
        </div>
    </div>

    @include('editor.partials.alert')

    <div class="card border-0 shadow-sm">

        <div class="table-responsive">

            <table class="table align-middle mb-0">

                <thead class="table-light">
                    <tr>
                        <th>Part</th>
                        <th>Tanggal</th>
                        <th>Isi Produksi</th>
                        <th>Item</th>
                        <th>Status</th>
                        <th>Download</th>
                        <th></th>
                    </tr>
                </thead>

                <tbody>

                    @forelse($parts as $part)

                        @php
                            $kelompok = $part->items
                                ->where('status', '!=', 'skipped')
                                ->groupBy('kelompok_produksi')
                                ->map(fn($items) => $items->sum('jumlah_awal'));
                        @endphp

                        <tr>

                            <td>
                                <div class="fw-bold">
                                    {{ $part->kode_part }}
                                </div>

                                <div class="small text-muted">
                                    Part {{ str_pad($part->nomor_part, 3, '0', STR_PAD_LEFT) }}
                                </div>
                            </td>

                            <td>
                                {{ $part->tanggal_part->format('d/m/Y') }}
                            </td>

                            <td style="min-width:300px">

                                @foreach($kelompok as $nama => $jumlah)

                                    <div class="d-flex justify-content-between gap-3 small mb-1">
                                        <span>
                                            {{ str_replace('|', ' • ', $nama) }}
                                        </span>

                                        <strong>
                                            {{ $jumlah }}/52
                                        </strong>
                                    </div>

                                @endforeach

                            </td>

                            <td>
                                <strong>
                                    {{ $part->pending_count }}
                                </strong>
                            </td>

                            <td>
                                @if($part->status === 'open')

                                    <span class="badge bg-success">
                                        SIAP DOWNLOAD
                                    </span>

                                @else

                                    <span class="badge bg-warning text-dark">
                                        SEDANG DIEDIT
                                    </span>

                                @endif
                            </td>

                            <td>
                                @if($part->downloaded_at)

                                    <div class="small">
                                        {{ $part->downloaded_at->format('d/m/Y') }}
                                    </div>

                                    <div class="small text-muted">
                                        {{ $part->downloaded_at->format('H:i') }}
                                    </div>

                                @else
                                    -
                                @endif
                            </td>

                            <td class="text-end">

                                <a href="{{ route('editor.part.show', $part) }}"
                                    class="btn btn-sm btn-primary">
                                    Detail
                                </a>

                            </td>

                        </tr>

                    @empty

                        <tr>
                            <td colspan="7"
                                class="text-center py-5 text-muted">
                                Tidak ada Part aktif.
                            </td>
                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>

    <div class="mt-3">
        {{ $parts->links() }}
    </div>

</div>

@endsection
