@extends('layouts.app')

@section('content')
    @push('styles')
        <link rel="stylesheet" href="{{ asset('css/dashboard.css') }}">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
    @endpush

    <div class="dashboard-content">
        <header class="dashboard-header">
            <div class="header-left">
                <h1 class="page-title">
                    <span class="emoji-badge" aria-hidden="true">📊</span>
                    Dashboard Utama
                </h1>
                <p class="page-subtitle">Lihat performa penjualan dan pembeli dalam satu tempat.</p>
            </div>

            <div class="header-right">
                <form method="GET" action="{{ route('dashboard') }}" id="filterForm" class="daterange-wrap"
                    aria-label="Filter Periode">
                    <input type="text" id="daterange" class="daterange-input" placeholder="Pilih Periode" autocomplete="off"
                        readonly aria-label="Pilih periode data">
                    <input type="hidden" name="start_date" id="start_date" value="{{ e($startDate) }}">
                    <input type="hidden" name="end_date" id="end_date" value="{{ e($endDate) }}">

                    <button class="btn-clear" type="button" id="clearRange" title="Bersihkan filter">Bersihkan</button>
                </form>
            </div>
        </header>

        <section class="card" aria-labelledby="kpi-title">
            <div class="card-header">
                <span id="kpi-title" class="card-title">Performa Toko</span>
                <a href="{{ url('penjualan') }}" class="cta-link">
                    Lihat semua <span aria-hidden="true">→</span>
                </a>
            </div>

            <div class="kpi-grid">
                @foreach ($metrics as $metric)
                    @php
                        $change = $metric['change'];
                        $isUp = is_numeric($change) && $change > 0;
                        $isDown = is_numeric($change) && $change < 0;
                    
                        // format nilai seperti sudah kamu lakukan…
                        switch ($metric['type']) {
                            case 'currency':
                                $formattedValue = 'Rp' . number_format($metric['value'], 0, ',', '.');
                                break;
                            case 'percent':
                                $formattedValue = number_format($metric['value'], 2, ',', '.') . '%';
                                break;
                            default:
                                $formattedValue = number_format($metric['value'], 0, ',', '.');
                        }
                    
                        // arah yang dianggap bagus: default 'up', khusus batal = 'down'
                        $goodDir = $metric['good'] ?? 'up';
                    
                        // tentukan kelas chip (warna)
                        if (is_null($change)) {
                            $chipClass = 'neutral';
                        } else {
                            if ($goodDir === 'up') {
                                $chipClass = $isUp ? 'positive' : ($isDown ? 'negative' : 'neutral');
                            } else { // good = 'down'
                                $chipClass = $isDown ? 'positive' : ($isUp ? 'negative' : 'neutral');
                            }
                        }
                    @endphp
                    
                    <article class="kpi-card" aria-live="polite">
                        <div class="kpi-head">
                            <span class="kpi-label">{{ e($metric['label']) }}</span>
                    
                            @if (is_null($change))
                                <span class="kpi-chip neutral">—</span>
                            @else
                                <span class="kpi-chip {{ $chipClass }}">
                                    <span class="arrow" aria-hidden="true">
                                        {{ $isUp ? '▲' : ($isDown ? '▼' : '—') }}
                                    </span>
                                    {{ number_format(abs($change), 2, ',', '.') }}%
                                </span>
                            @endif
                        </div>
                    
                        <div class="kpi-value">{{ $formattedValue }}</div>
                    </article>
                @endforeach
            </div>
        </section>

        <section class="card" aria-labelledby="buyer-title">
            <div class="card-header">
                <span id="buyer-title" class="card-title">Pembeli</span>
                <a href="{{ url('pembeli') }}" class="cta-link">Lihat semua <span aria-hidden="true">→</span></a>
            </div>

            @php
                $pembeliBaru = (int) ($buyer['baru'] ?? 0);
                $pembeliLama = (int) ($buyer['lama'] ?? 0);
                $totalPembeli = (int) ($buyer['total'] ?? 0);

                $persenBaru = isset($buyer['persenBaru'])
                    ? (float) $buyer['persenBaru']
                    : ($totalPembeli > 0 ? ($pembeliBaru / $totalPembeli) * 100 : 0);

                $persenLama = isset($buyer['persenLama'])
                    ? (float) $buyer['persenLama']
                    : ($totalPembeli > 0 ? ($pembeliLama / $totalPembeli) * 100 : 0);

                $persenBaru = max(0, min(100, $persenBaru));
                $persenLama = max(0, min(100, $persenLama));

                $changes = $buyer['changes'] ?? [];
            @endphp

            <div class="buyer-grid">
                <div class="buyer-left">
                    <div class="progress-wrap" role="img"
                        aria-label="Pembeli baru {{ number_format($persenBaru, 2, ',', '.') }}% dan pembeli lama {{ number_format($persenLama, 2, ',', '.') }}%">
                        <div class="progress-outer">
                            <div class="bar-seg seg-baru" style="width: {{ $persenBaru }}%"></div>
                            <div class="bar-seg seg-lama" style="width: {{ $persenLama }}%"></div>
                        </div>
                        <div class="progress-center">
                            <div class="progress-total">{{ number_format($totalPembeli, 0, ',', '.') }}</div>
                            <div class="progress-caption">Total Pembeli</div>
                        </div>
                    </div>

                    <div class="legend">
                        <span class="dot dot-baru"></span>
                        Pembeli Baru
                        <strong>{{ number_format($pembeliBaru, 0, ',', '.') }}</strong>
                        ({{ number_format($persenBaru, 2, ',', '.') }}%)
                    </div>
                    <div class="legend">
                        <span class="dot dot-lama"></span>
                        Pembeli Lama
                        <strong>{{ number_format($pembeliLama, 0, ',', '.') }}</strong>
                        ({{ number_format($persenLama, 2, ',', '.') }}%)
                    </div>
                </div>

                <div class="buyer-right">
                    @php
                        $buyerCards = [
                            ['label' => 'Pembeli Baru', 'value' => $pembeliBaru, 'change' => $changes['baru'] ?? null, 'icon' => '🆕'],
                            ['label' => 'Pembeli Lama', 'value' => $pembeliLama, 'change' => $changes['lama'] ?? null, 'icon' => '♻️'],
                            ['label' => 'Tingkat Pembelian Berulang', 'value' => $buyer['repeatRate'] ?? 0, 'type' => 'percent', 'change' => $changes['repeatRate'] ?? null, 'icon' => '🔁'],
                        ];
                    @endphp

                    @foreach ($buyerCards as $card)
                        @php
                            $chg = $card['change'] ?? null;
                            $isUp = is_numeric($chg) && $chg > 0;
                            $isDown = is_numeric($chg) && $chg < 0;
                            $displayValue = ($card['type'] ?? 'number') === 'percent'
                                ? number_format((float) $card['value'], 2, ',', '.') . '%'
                                : number_format((float) $card['value'], 0, ',', '.');
                        @endphp

                        <div class="mini-card">
                            <div class="mini-left">
                                <div class="mini-icon" aria-hidden="true">{{ $card['icon'] }}</div>
                                <div class="mini-info">
                                    <div class="mini-label">{{ e($card['label']) }}</div>
                                    <div class="mini-value">{{ $displayValue }}</div>
                                </div>
                            </div>
                            <div class="mini-change {{ $isUp ? 'pos' : ($isDown ? 'neg' : 'neu') }}">
                                @if (is_null($chg))
                                    —
                                @elseif ($isUp)
                                    ▲ {{ number_format(abs($chg), 2, ',', '.') }}%
                                @elseif ($isDown)
                                    ▼ {{ number_format(abs($chg), 2, ',', '.') }}%
                                @else
                                    —
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="card" aria-labelledby="produk-title">
            <div class="card-header">
                <span id="produk-title" class="card-title">Performa Produk</span>
                <a href="{{ url('produk') }}" class="cta-link">Lihat semua <span aria-hidden="true">→</span></a>
            </div>

            <div class="segmented" role="tablist" aria-label="Urutan Performa Produk">
                <button type="button" class="seg-btn active" role="tab" aria-selected="true" data-tab="penjualan"
                    aria-controls="table-penjualan">Berdasarkan Penjualan</button>
                <button type="button" class="seg-btn" role="tab" aria-selected="false" data-tab="produk"
                    aria-controls="table-produk">Berdasarkan Produk</button>
            </div>

            <div class="table-wrap" id="table-penjualan">
                <div class="table-modern" role="table" aria-label="Peringkat berdasarkan nilai penjualan">
                    <div class="t-head" role="row">
                        <div class="col-rank" role="columnheader">#</div>
                        <div class="col-info" role="columnheader">Produk</div>
                        <div class="col-sales" role="columnheader">Nilai</div>
                    </div>
                    @forelse ($rankingPenjualan as $index => $item)
                        <div class="t-row" role="row">
                            <div class="col-rank" role="cell">{{ $index + 1 }}</div>
                            <div class="col-info" role="cell">
                                <div class="product-name">{{ $item->nama_produk }}</div>
                                <div class="product-price">Rp{{ number_format($item->harga_satuan, 0, ',', '.') }}</div>
                            </div>
                            <div class="col-sales" role="cell">Rp{{ number_format($item->total_penjualan, 0, ',', '.') }}</div>
                        </div>
                    @empty
                        <div class="empty-row">Belum ada data untuk periode ini.</div>
                    @endforelse
                </div>
            </div>

            <div class="table-wrap" id="table-produk" hidden>
                <div class="table-modern" role="table" aria-label="Peringkat berdasarkan jumlah terjual">
                    <div class="t-head" role="row">
                        <div class="col-rank" role="columnheader">#</div>
                        <div class="col-info" role="columnheader">Produk</div>
                        <div class="col-sales" role="columnheader">Jumlah Terjual</div>
                    </div>
                    @forelse ($rankingProduk as $index => $item)
                        <div class="t-row" role="row">
                            <div class="col-rank" role="cell">{{ $index + 1 }}</div>
                            <div class="col-info" role="cell">
                                <div class="product-name">{{ $item->nama_produk }}</div>
                                <div class="product-price">Rp{{ number_format($item->harga_satuan, 0, ',', '.') }}</div>
                            </div>
                            <div class="col-sales" role="cell">{{ number_format($item->total_terjual, 0, ',', '.') }}</div>
                        </div>
                    @empty
                        <div class="empty-row">Belum ada data untuk periode ini.</div>
                    @endforelse
                </div>
            </div>
        </section>
    </div>
@endsection

@push('scripts')
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/min/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    <script>
        $(function () {
            const start = @json($startDate);
            const end = @json($endDate);

            $('#daterange').daterangepicker({
                startDate: start ? moment(start, 'YYYY-MM-DD') : moment(),
                endDate: end ? moment(end, 'YYYY-MM-DD') : moment(),
                locale: {
                    format: 'DD-MM-YYYY',
                    separator: ' s.d ',
                    applyLabel: 'Terapkan',
                    cancelLabel: 'Batal',
                    customRangeLabel: 'Custom',
                    daysOfWeek: ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'],
                    monthNames: [
                        'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
                        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
                    ],
                    firstDay: 1
                },
                autoUpdateInput: false,
                opens: 'left'
            });

            if (start && end) {
                $('#daterange').val(
                    moment(start, 'YYYY-MM-DD').format('DD-MM-YYYY') +
                    ' s.d ' +
                    moment(end, 'YYYY-MM-DD').format('DD-MM-YYYY')
                );
            }

            $('#daterange').on('apply.daterangepicker', function (ev, picker) {
                $(this).val(picker.startDate.format('DD-MM-YYYY') + ' s.d ' + picker.endDate.format('DD-MM-YYYY'));
                $('#start_date').val(picker.startDate.format('YYYY-MM-DD'));
                $('#end_date').val(picker.endDate.format('YYYY-MM-DD'));
                $('#filterForm').trigger('submit');
            });

            $('#daterange').on('cancel.daterangepicker', function () {
                $(this).val('');
                $('#start_date').val('');
                $('#end_date').val('');
                $('#filterForm').trigger('submit');
            });

            $('#clearRange').on('click', function () {
                $('#daterange').val('');
                $('#start_date').val('');
                $('#end_date').val('');
                $('#filterForm').trigger('submit');
            });

            $('.seg-btn').on('click', function () {
                $('.seg-btn').removeClass('active').attr('aria-selected', 'false');
                $(this).addClass('active').attr('aria-selected', 'true');
                const id = '#table-' + $(this).data('tab');
                $('.table-wrap').attr('hidden', true);
                $(id).removeAttr('hidden');
            });
        });
    </script>
@endpush