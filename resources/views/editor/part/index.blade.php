@extends('layouts.app')

@section('content')

<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1">Part Produksi</h4>

            <div class="text-muted small">
                Daftar Part aktif yang perlu dikerjakan Editor
            </div>
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
                        <th class="text-center">Item</th>
                        <th>Status</th>
                        <th>Download</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>

                <tbody>

                    @forelse($parts as $part)

                        @php
                            $kapasitas = $part->kapasitas_per_kelompok ?: 52;

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

                            <td style="min-width: 330px">

                                @forelse($kelompok as $nama => $jumlah)

                                    @php
                                        $penuh = $jumlah >= $kapasitas;
                                    @endphp

                                    <div class="d-flex justify-content-between align-items-center gap-3 mb-2">

                                        <div class="small">
                                            {{ str_replace('|', ' • ', $nama) }}
                                        </div>

                                        <span class="badge {{ $penuh ? 'bg-success' : 'bg-light text-dark border' }}">
                                            {{ $jumlah }}/{{ $kapasitas }}
                                        </span>

                                    </div>

                                @empty

                                    <span class="text-muted small">
                                        Tidak ada item produksi
                                    </span>

                                @endforelse

                            </td>

                            <td class="text-center">
                                <span class="fw-bold">
                                    {{ $part->pending_count }}
                                </span>

                                <div class="small text-muted">
                                    item
                                </div>
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

                                @endif

                            </td>

                            <td>

                                @if($part->downloaded_at)

                                    <div class="small fw-semibold">
                                        {{ $part->downloaded_at->format('d/m/Y') }}
                                    </div>

                                    <div class="small text-muted">
                                        {{ $part->downloaded_at->format('H:i') }}
                                    </div>

                                @else

                                    <span class="text-muted">
                                        Belum
                                    </span>

                                @endif

                            </td>

                            <td class="text-end">

                                <div class="d-flex justify-content-end gap-2">

                                    <a href="{{ route('editor.part.download', $part) }}"
                                        class="btn btn-sm btn-success">

                                        <i class="fa-solid fa-file-excel me-1"></i>

                                        {{ $part->status === 'downloaded'
                                            ? 'Download Ulang'
                                            : 'Download Excel' }}

                                    </a>

                                    <a href="{{ route('editor.part.show', $part) }}"
                                        class="btn btn-sm btn-primary">

                                        <i class="fa-solid fa-eye me-1"></i>
                                        Detail

                                    </a>

                                </div>

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
                                    Tidak ada Part aktif
                                </div>

                                <div class="small text-muted">
                                    Part akan muncul otomatis ketika ada pesanan PLT yang perlu dikerjakan.
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
