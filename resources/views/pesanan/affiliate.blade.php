@extends('layouts.app')

@section('content')
    <div class="bg-white p-3 p-md-4 rounded shadow-sm w-100">

        <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-warning text-dark fw-semibold px-2 py-1">⭐</span>
                <h5 class="mb-0">Pesanan Affiliate</h5>
            </div>

            <div class="text-muted small">
                {{ number_format($jumlahPesanan, 0, ',', '.') }} pesanan
                <span class="mx-1">•</span>
                Total item:
                <span class="fw-semibold">{{ number_format($totalItem, 0, ',', '.') }}</span>
                <span class="mx-1">•</span>
                Pencairan halaman ini:
                <span class="fw-semibold">Rp{{ number_format($totalPencairan, 0, ',', '.') }}</span>
            </div>
        </div>

        <form method="GET" action="{{ route('pesanan.affiliate') }}" id="filterForm" class="mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="row g-3 align-items-end">

                        <div class="col-12 col-lg-3">
                            <label class="form-label small text-muted mb-1">Cari</label>
                            <input type="text" name="q" class="form-control form-control-sm"
                                placeholder="No pesanan / resi / pembeli" value="{{ request('q') }}">
                        </div>

                        <div class="col-12 col-lg-3">
                            <label class="form-label small text-muted mb-1">Toko</label>
                            <select name="id_toko" id="id_toko" class="form-select form-select-sm"
                                data-placeholder="Semua toko">
                                <option value="">Semua toko</option>
                                @foreach ($daftarToko as $tk)
                                    <option value="{{ $tk->id_toko }}"
                                        {{ (string) $tk->id_toko === (string) request('id_toko') ? 'selected' : '' }}>
                                        {{ $tk->nama_toko }}{{ isset($tk->marketplace) ? ' [' . $tk->marketplace . ']' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12 col-md-2">
                            <label class="form-label mb-1 small text-muted">Tanggal</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white border-end-0">
                                    <i class="bi bi-calendar-event text-muted"></i>
                                </span>
                                <input type="text" id="daterange" name="tanggal"
                                    class="form-control form-control-sm border-start-0" value="{{ request('tanggal') }}"
                                    placeholder="Tanggal" readonly>

                                <input type="text" name="min_date" id="min_date" hidden>
                                <input type="text" name="max_date" id="max_date" hidden>
                            </div>
                        </div>

                        <div class="col-12 col-lg-2">
                            <label class="form-label small text-muted mb-1">Per halaman</label>
                            <select name="per_page" class="form-select form-select-sm">
                                @foreach ($allowedPerPage as $opt)
                                    <option value="{{ $opt }}"
                                        {{ (int) $perPage === (int) $opt ? 'selected' : '' }}>
                                        {{ $opt }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- <div class="col-6 col-lg-2">
                            <button type="submit" class="btn btn-primary btn-sm w-100">
                                Filter
                            </button>
                        </div> --}}

                        <div class="col-6 col-lg-2">
                            <a href="{{ route('pesanan.affiliate') }}" class="btn btn-outline-secondary btn-sm w-100">
                                Reset
                            </a>
                        </div>

                    </div>
                </div>
            </div>
        </form>

        @if ($pesanan->isEmpty())
            <div class="alert alert-info mb-0">
                Tidak ada pesanan affiliate.
            </div>
        @endif

        @if ($pesanan->isNotEmpty())
            <div class="table-responsive mt-3">
                <table class="table table-sm table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center">Tanggal</th>
                            <th class="text-center">No Pesanan</th>
                            <th class="text-center">No Resi</th>
                            <th class="text-center">Toko</th>
                            <th class="text-center">Pembeli</th>
                            <th class="text-center">Kurir</th>
                            <th class="text-end">HPP</th>
                            <th class="text-end">Pencairan</th>
                            <th class="text-end">Selisih</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($pesanan as $p)
                            @php
                                $hpp = (float) ($p->total_hpp ?? 0);
                                $pencairan = (float) ($p->pencairan ?? 0);
                                $selisih = (float) ($p->keuntungan ?? 0);
                            @endphp

                            <tr>
                                <td class="text-center">
                                    {{ $p->tanggal ? \Carbon\Carbon::parse($p->tanggal)->format('d/m/Y') : '-' }}
                                </td>

                                <td class="text-center fw-semibold">
                                    {{ $p->no_pesanan }}
                                </td>

                                <td class="text-center">
                                    {{ $p->no_resi ?? '-' }}
                                </td>

                                <td class="text-center">
                                    {{ $p->toko->nama_toko ?? '-' }}
                                </td>

                                <td class="text-center">
                                    {{ $p->nama_pembeli ?? '-' }}
                                </td>

                                <td class="text-center">
                                    {{ $p->kurir ?? '-' }}
                                </td>

                                <td class="text-end">
                                    Rp{{ number_format($hpp, 0, ',', '.') }}
                                </td>

                                <td class="text-end">
                                    Rp{{ number_format($pencairan, 0, ',', '.') }}
                                </td>

                                <td class="text-end {{ $selisih < 0 ? 'text-danger' : 'text-success' }}">
                                    Rp{{ number_format($selisih, 0, ',', '.') }}
                                </td>

                                <td class="text-center">
                                    <span class="badge rounded-pill bg-warning text-dark">
                                        Affiliate
                                    </span>
                                </td>

                                <td class="text-center">
                                    <a href="{{ route('pesanan.show', $p->no_pesanan) }}" target="_blank"
                                        class="btn btn-sm btn-outline-primary">
                                        Rincian
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2 mt-3">
                <div class="text-muted small">
                    @if ($pesanan->total())
                        Menampilkan {{ $pesanan->firstItem() }}–{{ $pesanan->lastItem() }}
                        dari {{ $pesanan->total() }} pesanan
                    @endif
                </div>

                <nav>
                    {{ $pesanan->withQueryString()->onEachSide(1)->links('vendor.pagination.bootstrap-5-compact') }}
                </nav>
            </div>
        @endif

    </div>

    @push('scripts')
        <script>
            const form = document.getElementById('filterForm');

            $('#id_toko').select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: $('#id_toko').data('placeholder'),
                allowClear: true
            });

            // Filter toko
            $('#id_toko').on('change', function() {
                form.submit();
            });

            // Daterangepicker
            $('#daterange').daterangepicker({
                locale: {
                    format: 'YYYY-MM-DD',
                    separator: ' s.d ',
                    applyLabel: 'Terapkan',
                    cancelLabel: 'Batal',
                    customRangeLabel: 'Custom',
                    daysOfWeek: ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'],
                    monthNames: ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus',
                        'September', 'Oktober', 'November', 'Desember'
                    ],
                    firstDay: 1
                },
                autoUpdateInput: false,
                opens: 'left',
            });

            $('#daterange').on('apply.daterangepicker', function(ev, picker) {
                const min = picker.startDate.format('YYYY-MM-DD');
                const max = picker.endDate.format('YYYY-MM-DD');

                $('#min_date').val(min);
                $('#max_date').val(max);

                form.submit();
            });

            $('#daterange').on('cancel.daterangepicker', function() {
                $(this).val('');
                form.submit();
            });

            const oldRange = $('#daterange').val();
            if (oldRange) {
                const dates = oldRange.split(' s.d ');
                if (dates.length === 2) {
                    $('#daterange').data('daterangepicker').setStartDate(moment(dates[0], 'YYYY-MM-DD'));
                    $('#daterange').data('daterangepicker').setEndDate(moment(dates[1], 'YYYY-MM-DD'));
                }
            }

        </script>
    @endpush
@endsection
