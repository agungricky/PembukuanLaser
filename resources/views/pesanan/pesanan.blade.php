@extends('layouts.app')

@section('content')
    <div class="bg-white p-4 rounded shadow-sm w-100">

        {{-- Header --}}
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0 d-flex align-items-center gap-2">
                <i class="bi bi-card-checklist text-primary"></i>
                Semua Pesanan
            </h5>

            <div class="text-muted small">
                @if ($pesanan->total())
                    {{ $pesanan->firstItem() }}–{{ $pesanan->lastItem() }}
                    dari {{ number_format($pesanan->total(), 0, ',', '.') }} pesanan
                    •
                    {{ number_format($total ?? 0, 0, ',', '.') }} item
                @else
                    0 pesanan
                @endif
            </div>
        </div>

        {{-- Filter --}}
        <form method="GET" action="{{ route('pesanan.index') }}" class="mb-3">
            @php
                $options = $allowed ?? [10, 20, 25, 50, 100];
                $currentPer = (int) request('per_page', $perPage ?? 20);
            @endphp

            <div class="d-flex align-items-center gap-2 flex-nowrap">

                {{-- Search --}}
                <div class="input-group input-group-sm" style="width: 260px;">
                    <span class="input-group-text bg-white">
                        <i class="bi bi-search"></i>
                    </span>

                    <input type="text" name="no_pesanan" class="form-control" value="{{ request('no_pesanan') }}"
                        placeholder="No. Pesanan / Resi">
                </div>

                {{-- Tanggal --}}
                <div class="input-group input-group-sm" style="width: 220px;">
                    <span class="input-group-text bg-white">
                        <i class="bi bi-calendar-event"></i>
                    </span>

                    <input type="text" id="daterange" name="tanggal" class="form-control" value="{{ $tanggal }}"
                        placeholder="Pilih tanggal" readonly>
                </div>

                {{-- Per Page --}}
                <div class="input-group input-group-sm" style="width: 125px;">
                    <span class="input-group-text bg-white">
                        <i class="bi bi-list-ol"></i>
                    </span>

                    <select class="form-select" name="per_page" id="perPageSelect">
                        @foreach ($options as $opt)
                            <option value="{{ $opt }}" @selected($currentPer === (int) $opt)>
                                {{ $opt }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Filter --}}
                <button type="submit" class="btn btn-primary btn-sm text-nowrap">
                    <i class="bi bi-funnel me-1"></i>
                    Terapkan
                </button>

                {{-- Reset --}}
                <a href="{{ route('pesanan.index') }}" class="btn btn-outline-secondary btn-sm" title="Reset">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>

                {{-- Spacer --}}
                <div class="ms-auto"></div>

                {{-- Import Pesanan --}}
                <a href="{{ route('pesanan.import') }}" class="btn btn-success btn-sm text-nowrap">
                    <i class="bi bi-file-earmark-spreadsheet me-1"></i>
                    Import Pesanan
                </a>

                {{-- Import Resi --}}
                <a href="{{ route('resi.import') }}" class="btn btn-danger btn-sm text-nowrap">
                    <i class="bi bi-file-earmark-pdf me-1"></i>
                    Import Resi
                </a>

            </div>
        </form>

        {{-- Sticky Header --}}
        <div class="bg-light px-3 py-2 border rounded small fw-semibold d-none d-md-grid mb-2"
            style="grid-template-columns: 1fr 180px 220px 150px; position: sticky; top: 56px; z-index: 1030;">
            <div>Pembeli & Produk</div>
            <div></div>
            <div class="text-center">Jumlah</div>
            <div class="text-end">Total Pesanan</div>
        </div>

        @php
            $statusColor = fn($status) => match (strtolower($status ?? '')) {
                'proses' => 'warning',
                'kirim' => 'info',
                'selesai' => 'success',
                'batal', 'return', 'pengembalian' => 'danger',
                default => 'secondary',
            };
        @endphp

        {{-- Daftar Pesanan --}}
        @forelse ($pesanan as $p)
            <div class="border rounded shadow-sm mb-3 p-3">

                {{-- Header --}}
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-person-circle fs-5 text-primary"></i>
                        <div class="fw-semibold">
                            {{ $p->nama_pembeli ?? '-' }}
                        </div>
                    </div>

                    <div class="text-muted small">
                        No. Pesanan :
                        <span class="fw-semibold text-dark">
                            {{ $p->no_pesanan }}
                        </span>

                        @if ($p->tanggal)
                            <span class="mx-2">•</span>
                            {{ $p->tanggal->format('d M Y') }}
                        @endif
                    </div>
                </div>
                <hr class="my-2">
                <div class="row">
                    {{-- Produk --}}
                    <div class="col-md-9">

                        @forelse($p->produk as $pp)
                            <div class="d-flex justify-content-between border-bottom py-2">
                                <div>
                                    <div class="fw-semibold">
                                        {{ $pp->nama_produk ?? 'Produk tidak ditemukan' }}
                                    </div>
                                    <div class="text-muted small">
                                        Variasi : {{ $pp->variasi ?? '-' }}
                                    </div>
                                </div>
                                <div class="text-muted">
                                    x{{ $pp->jumlah }}
                                </div>
                            </div>
                        @empty
                            <div class="text-muted small">
                                Tidak ada produk.
                            </div>
                        @endforelse
                    </div>

                    {{-- Total --}}
                    <div class="col-md-3 text-end">
                        <div class="fw-bold fs-6">
                            Rp{{ number_format($p->total_harga ?? 0, 0, ',', '.') }}
                        </div>
                    </div>
                </div>
                <hr class="my-2">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <span class="badge bg-{{ $statusColor($p->status) }}">
                            {{ ucfirst($p->status ?? '-') }}
                        </span>
                        <span class="text-muted small ms-2">
                            {{ $p->kurir ?? '-' }}
                            @if ($p->no_resi)
                                •
                                <span class="fw-semibold">
                                    {{ $p->no_resi }}
                                </span>
                            @endif
                        </span>
                    </div>

                    <a href="{{ route('pesanan.show', $p->no_pesanan) }}" target="_blank"
                        class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-file-earmark-text me-1"></i>
                        Rincian
                        @if ($p->status_cek)
                            <i class="bi bi-exclamation-circle-fill text-warning ms-1"></i>
                        @endif
                    </a>
                </div>
            </div>
        @empty
            <div class="text-center text-muted py-5">
                Tidak ada data pesanan.
            </div>
        @endforelse

        {{-- Pagination --}}
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2 mt-3">
            <div class="text-muted small">
                @if ($pesanan->total())
                    Menampilkan
                    {{ $pesanan->firstItem() }}
                    –
                    {{ $pesanan->lastItem() }}
                    dari
                    {{ number_format($pesanan->total(), 0, ',', '.') }}
                    pesanan
                @endif
            </div>
            <nav>
                {{ $pesanan->withQueryString()->onEachSide(1)->links('vendor.pagination.bootstrap-5-compact') }}
            </nav>
        </div>
    @endsection

    @push('scripts')
        <script>
            $(function() {
                const $dateRange = $('#daterange');
                $dateRange.daterangepicker({
                    autoUpdateInput: false,
                    opens: 'left',

                    locale: {
                        format: 'YYYY-MM-DD',
                        separator: ' s.d ',
                        applyLabel: 'Terapkan',
                        cancelLabel: 'Batal',
                        customRangeLabel: 'Custom',
                        daysOfWeek: ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'],
                        monthNames: [
                            'Januari',
                            'Februari',
                            'Maret',
                            'April',
                            'Mei',
                            'Juni',
                            'Juli',
                            'Agustus',
                            'September',
                            'Oktober',
                            'November',
                            'Desember'
                        ],
                        firstDay: 1
                    }
                });

                $dateRange.on('apply.daterangepicker', function(ev, picker) {
                    $(this).val(
                        picker.startDate.format('YYYY-MM-DD') +
                        ' s.d ' +
                        picker.endDate.format('YYYY-MM-DD')
                    );
                });

                $dateRange.on('cancel.daterangepicker', function() {
                    $(this).val('');
                });

                const oldRange = $dateRange.val();
                if (oldRange) {
                    const dates = oldRange.split(' s.d ');
                    if (dates.length === 2) {
                        const picker = $dateRange.data('daterangepicker');
                        picker.setStartDate(moment(dates[0], 'YYYY-MM-DD'));
                        picker.setEndDate(moment(dates[1], 'YYYY-MM-DD'));
                    }
                }

                $('#perPageSelect').on('change', function() {
                    $(this).closest('form').submit();
                });
            });
        </script>
    @endpush
