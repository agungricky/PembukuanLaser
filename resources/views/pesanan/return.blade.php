@extends('layouts.app')

@push('styles')
    <style>
        .table thead th {
            font-size: .85rem;
            letter-spacing: .2px;
        }

        .table tbody td {
            vertical-align: middle;
        }
    </style>
@endpush

@section('content')
    <div class="bg-white p-3 p-md-4 rounded shadow-sm w-100">

        {{-- Header + ringkasan --}}
        <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-danger-subtle text-danger fw-semibold px-2 py-1">🛑</span>
                <h5 class="mb-0">Pesanan Batal / Return</h5>
            </div>

            <div class="text-muted small">
                {{ number_format($jumlahPesanan, 0, ',', '.') }} pesanan
                @isset($totalKerugian)
                    <span class="mx-1">•</span>
                    Total kerugian (halaman ini):
                    <span class="fw-semibold">Rp{{ number_format($totalKerugian, 0, ',', '.') }}</span>
                @endisset
            </div>
        </div>

        {{-- Filter --}}
        <form id="filterForm" method="GET" action="{{ route('pesanan.return') }}" class="mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="row g-3 align-items-end">

                        {{-- Cari --}}
                        <div class="col-12 col-lg-3">
                            <label class="form-label small text-muted mb-1">Cari</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                                <input type="text" name="no_pesanan" class="form-control"
                                    placeholder="No. pesanan / No. resi" value="{{ request('no_pesanan') }}">
                            </div>
                        </div>

                        {{-- Toko --}}
                        <div class="col-12 col-lg-3">
                            <label class="form-label small text-muted mb-1">Toko</label>
                            <select name="id_toko" id="id_toko" class="form-select form-select-sm">
                                <option value="">Semua toko</option>
                                @foreach ($daftarToko as $tk)
                                    <option value="{{ $tk->id_toko }}"
                                        {{ (string) $tk->id_toko === (string) request('id_toko') ? 'selected' : '' }}>
                                        {{ $tk->nama_toko }}{{ isset($tk->marketplace) ? ' [' . $tk->marketplace . ']' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Status --}}
                        <div class="col-12 col-lg-2">
                            <label class="form-label small text-muted mb-1">Status</label>
                            @php $st = (string) request('status', ''); @endphp
                            <select name="status" class="form-select form-select-sm">
                                <option value="" {{ $st === '' ? 'selected' : '' }}>Semua</option>
                                <option value="batal" {{ $st === 'batal' ? 'selected' : '' }}>Batal</option>
                                <option value="pengembalian" {{ $st === 'pengembalian' ? 'selected' : '' }}>Pengembalian
                                </option>
                                <option value="pengiriman gagal" {{ $st === 'pengiriman gagal' ? 'selected' : '' }}>
                                    Pengiriman gagal</option>
                            </select>
                        </div>

                        {{-- Urut kerugian (BARU) --}}
                        <div class="col-12 col-lg-2">
                            <label class="form-label small text-muted mb-1">Urut kerugian</label>
                            @php $dir = (string) request('dir', 'desc'); @endphp
                            <select name="dir" class="form-select form-select-sm">
                                <option value="desc" {{ $dir === 'desc' ? 'selected' : '' }}>Desc</option>
                                <option value="asc" {{ $dir === 'asc' ? 'selected' : '' }}>Asc</option>
                            </select>
                        </div>

                        {{-- Tanggal --}}
                        <div class="col-12 col-lg-2">
                            <label class="form-label small text-muted mb-1">Tanggal</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white"><i
                                        class="bi bi-calendar-event text-muted"></i></span>
                                <input type="text" id="daterange" name="tanggal" class="form-control"
                                    value="{{ (string) request('tanggal', '') }}" placeholder="Rentang tanggal" readonly>
                            </div>
                        </div>

                        {{-- Reset --}}
                        <div class="col-12 col-lg-2">
                            <a href="{{ route('pesanan.return') }}" class="btn btn-outline-secondary btn-sm w-100">
                                Reset
                            </a>
                        </div>

                    </div>
                </div>
            </div>
        </form>

        @if ($pesanan->isEmpty())
            <div class="alert alert-info mb-0">Tidak ada data pesanan.</div>
        @endif

        @if ($pesanan->isNotEmpty())
            <div class="table-responsive mt-3">
                <table class="table table-sm table-hover align-middle" style="width:100%">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" style="min-width:110px">Tanggal</th>
                            <th class="text-center" style="min-width:160px">No. Pesanan</th>
                            <th class="text-center" style="min-width:160px">No. Resi</th>
                            <th class="text-end" style="min-width:120px">Kerugian</th>
                            <th class="text-center" style="min-width:140px">Status</th>
                            <th class="text-center" style="min-width:160px">Nama Toko</th>
                            <th class="text-center" style="min-width:220px">Notes</th>
                            <th class="text-center" style="min-width:120px">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($pesanan as $p)
                            @php
                                $status = strtolower($p->status ?? '');
                                $statusMap = [
                                    'batal' => ['label' => 'Batal', 'badge' => 'bg-dark text-white'],
                                    'pengiriman gagal' => [
                                        'label' => 'Pengiriman Gagal',
                                        'badge' => 'bg-danger text-white',
                                    ],
                                    'pengembalian' => ['label' => 'Pengembalian', 'badge' => 'bg-danger text-white'],
                                ];
                                $s = $statusMap[$status] ?? [
                                    'label' => ucfirst($status ?: 'Unknown'),
                                    'badge' => 'bg-secondary-subtle text-secondary',
                                ];
                                $kerugian = (float) ($p->keuntungan ?? 0);
                            @endphp

                            <tr>
                                <td class="text-center">{{ \Carbon\Carbon::parse($p->tanggal)->format('d/m/Y') }}</td>
                                <td class="text-center fw-semibold">{{ $p->no_pesanan }}</td>
                                <td class="text-center fw-semibold">{{ $p->no_resi }}</td>
                                <td class="text-end {{ $kerugian < 0 ? 'text-danger' : 'text-muted' }}">
                                    Rp{{ number_format($kerugian, 0, ',', '.') }}
                                </td>
                                <td class="text-center">
                                    <span class="badge rounded-pill {{ $s['badge'] }}">{{ $s['label'] }}</span>
                                </td>
                                <td class="text-center">{{ $p->toko->nama_toko ?? '—' }}</td>
                                <td class="text-break">{{ $p->notes ?? '—' }}</td>
                                <td class="text-center">
                                    <a href="{{ route('pesanan.show', $p->no_pesanan) }}" target="_blank"
                                        class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-file-earmark-text me-1"></i> Rincian
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2 mt-3">
                <div class="text-muted small">
                    @if ($pesanan->total())
                        Menampilkan {{ $pesanan->firstItem() }}–{{ $pesanan->lastItem() }} dari {{ $pesanan->total() }}
                        pesanan
                    @endif
                </div>
                <nav>
                    {{ $pesanan->withQueryString()->onEachSide(1)->links('vendor.pagination.bootstrap-5-compact') }}
                </nav>
            </div>

            <div class="small text-muted mt-2">
                *“Kerugian” (field keuntungan) = Pencairan − Total HPP (bisa negatif pada return/pengembalian).
            </div>
        @endif

    </div>
@endsection

@push('scripts')
    <script>
        $(function() {
            const form = document.getElementById('filterForm');

            $('#daterange').daterangepicker({
                locale: {
                    format: 'YYYY-MM-DD',
                    separator: ' s.d ',
                    applyLabel: 'Terapkan',
                    cancelLabel: 'Batal',
                    daysOfWeek: ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'],
                    monthNames: ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus',
                        'September', 'Oktober', 'November', 'Desember'
                    ],
                    firstDay: 1
                },
                autoUpdateInput: false,
                opens: 'left'
            });

            // apply/cancel event dari daterangepicker [web:21]
            $('#daterange').on('apply.daterangepicker', function(ev, picker) {
                $(this).val(picker.startDate.format('YYYY-MM-DD') + ' s.d ' + picker.endDate.format(
                    'YYYY-MM-DD'));
                form.submit();
            });

            $('#daterange').on('cancel.daterangepicker', function() {
                $(this).val('');
                form.submit();
            });

            // restore old range
            const oldRange = $('#daterange').val();
            if (oldRange) {
                const parts = oldRange.split(' s.d ');
                if (parts.length === 2) {
                    const drp = $('#daterange').data('daterangepicker');
                    drp.setStartDate(moment(parts[0], 'YYYY-MM-DD'));
                    drp.setEndDate(moment(parts[1], 'YYYY-MM-DD'));
                }
            }

            function debounce(fn, delay) {
                let timer = null;
                return function(...args) {
                    clearTimeout(timer);
                    timer = setTimeout(() => fn.apply(this, args), delay);
                };
            }

            // auto submit search (debounce)
            const searchInput = form.querySelector('[name="no_pesanan"]');
            if (searchInput) {
                const submitSearch = debounce(() => form.submit(), 500);
                searchInput.addEventListener('input', submitSearch);
            }

            // auto submit saat pilih toko / status / urut kerugian
            const tokoSelect = form.querySelector('[name="id_toko"]');
            if (tokoSelect) tokoSelect.addEventListener('change', () => form.submit());

            const statusSelect = form.querySelector('[name="status"]');
            if (statusSelect) statusSelect.addEventListener('change', () => form.submit());

            const dirSelect = form.querySelector('[name="dir"]');
            if (dirSelect) dirSelect.addEventListener('change', () => form.submit());

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
        });
    </script>
@endpush
