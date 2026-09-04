@extends('layouts.app')

@section('content')

<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1">
                Antrian Editor
            </h4>

            <div class="text-muted small">
                Daftar antrian Editor Shopee dan TikTok berdasarkan sesi PAGI, SIANG, dan MALAM
            </div>
        </div>
    </div>

    @include('editor.partials.alert')

    <div class="card border-0 shadow-sm">

        <div class="table-responsive">

            <table class="table align-middle mb-0">

                <thead class="table-light">
                    <tr>
                        <th>Antrian</th>
                        <th>Marketplace</th>
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
                            $kelompok = $part->items
                                ->where('status', 'pending')
                                ->groupBy('kelompok_produksi')
                                ->map(fn ($items) => $items->sum('jumlah_awal'));

                            $sesi = strtoupper($part->sesi ?? '-');
                            $marketplace = strtoupper($part->marketplace ?? '-');

                            $badgeSesi = match($part->sesi) {
                                'pagi' => 'bg-primary',
                                'siang' => 'bg-warning text-dark',
                                'malam' => 'bg-dark',
                                default => 'bg-secondary',
                            };

                            $badgeMarketplace = match(strtolower($part->marketplace ?? '')) {
                                'shopee' => 'bg-danger',
                                'tiktok' => 'bg-dark',
                                default => 'bg-secondary',
                            };
                        @endphp

                        <tr>

                            <td>
                                <div class="fw-bold">
                                    {{ $part->kode_part }}
                                </div>

                                <div class="mt-1">
                                    <span class="badge {{ $badgeSesi }}">
                                        {{ $sesi }}
                                    </span>
                                </div>
                            </td>

                            <td>
                                <span class="badge {{ $badgeMarketplace }}">
                                    {{ $marketplace }}
                                </span>
                            </td>

                            <td>
                                <div class="fw-semibold">
                                    {{ $part->tanggal_part->format('d/m/Y') }}
                                </div>
                            </td>

                            <td style="min-width: 330px">

                                @forelse($kelompok as $nama => $jumlah)

                                    <div class="d-flex justify-content-between align-items-center gap-3 mb-2">

                                        <div class="small">
                                            {{ str_replace('|', ' • ', $nama) }}
                                        </div>

                                        <span class="badge bg-light text-dark border">
                                            {{ number_format($jumlah) }} pcs
                                        </span>

                                    </div>

                                @empty

                                    <span class="text-muted small">
                                        Tidak ada item pending
                                    </span>

                                @endforelse

                            </td>

                            <td class="text-center">

                                <span class="fw-bold">
                                    {{ number_format($part->pending_count) }}
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

                                @else

                                    <span class="badge bg-secondary">
                                        {{ strtoupper($part->status) }}
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

                                <div class="d-flex justify-content-end gap-2 flex-wrap">

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

                            <td colspan="8"
                                class="text-center py-5">

                                <div class="text-muted mb-2">
                                    <i class="fa-solid fa-box-open fa-2x"></i>
                                </div>

                                <div class="fw-semibold">
                                    Tidak ada antrian aktif
                                </div>

                                <div class="small text-muted">
                                    Antrian akan muncul otomatis ketika ada pesanan PLT Shopee atau TikTok yang perlu dikerjakan.
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