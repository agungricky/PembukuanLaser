@extends('layouts.app')

@section('content')
    <main class="flex-grow-1 overflow-auto p-3 p-lg-4">

        <!-- Dashboard Title & Pulse Badge -->
        <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mb-4">
            <div>
                <h1 class="h3 fw-bold text-dark mb-1">
                    Dashboard Produksi
                </h1>
                <p class="text-muted small mb-0">
                    Monitoring proses produksi, progres pengerjaan, kebutuhan terhadap pesanan.
                </p>
            </div>
            <div>
                <span
                    class="badge bg-white text-primary border rounded-pill px-3 py-2 fw-bold shadow-xs d-inline-flex align-items-center gap-2">
                    <span class="spinner-grow spinner-grow-sm text-primary" style="width: 6px; height: 6px;"
                        role="status"></span>
                    LIVE MONITORING : <span id="livedate"></span>
                </span>
            </div>
        </div>

        <!-- Info Notice Banner -->
        @if ($Card['alert'] != 0)
            <div
                class="alert alert-warning border-warning-subtle rounded-4 shadow-xs d-flex align-items-start gap-3 p-3 mb-4">
                <i class="fa-solid fa-circle-info text-primary mt-0.5"></i>
                <div class="small">
                    <strong class="text-warning-emphasis">
                        Permintaan Produksi :
                    </strong>

                    Halo Operator Produksi,
                    terdapat <strong class="text-danger">{{ $Card['alert'] }} produk</strong>
                    yang belum memiliki stok barang dan perlu segera diproduksi.
                </div>
            </div>
        @endif

        <!-- 8 Metric Status Cards Grid -->
        <div class="mb-4">

            <!-- Grid Row 1: General Metrics Cards -->
            <div class="row g-3 mb-3">

                <!-- Card 1: Pesanan Custom -->
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="card border-0 shadow-sm rounded-4 h-100 p-3 hover-shadow">
                        <div class="card-body p-1 d-flex flex-column justify-content-between">
                            <div>
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="text-muted fw-bold" style="font-size: 10px; letter-spacing: 0.5px;">
                                        TOTAL PESANAN CUSTOM
                                    </span>
                                    <div class="bg-light text-dark rounded-circle d-flex align-items-center justify-content-center"
                                        style="width: 32px; height: 32px;">
                                        <i class="bi bi-boxes"></i>
                                    </div>
                                </div>
                                <h2 id="cardTotalStock" class="fw-bold text-dark mb-1 d-flex align-items-baseline gap-2">

                                    @if ($Card['custom'] >= 300)
                                        <i class="fa-solid fa-triangle-exclamation" style="color: #dc3545;"></i>
                                    @endif

                                    <span class="{{ $Card['custom'] >= 300 ? 'text-danger' : 'text-dark' }}"
                                        style="font-size: 2rem; line-height: 1;">
                                        {{ $Card['custom'] }}
                                    </span>

                                    <span class="text-secondary fw-semibold" style="font-size: .95rem;">
                                        Pcs
                                    </span>
                                </h2>
                            </div>
                            <p class="text-muted small mb-0 mt-3">
                                <i class="fa-solid fa-gears me-1"></i>
                                Perlu di Produksi.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Card 2: Stok Menipis -->
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="card border-0 shadow-sm rounded-4 h-100 p-3 hover-shadow">
                        <div class="card-body p-1 d-flex flex-column justify-content-between">
                            <div>
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="text-muted fw-bold" style="font-size: 10px; letter-spacing: 0.5px;">
                                        STOK MENIPIS
                                    </span>
                                    <div class="bg-warning-subtle text-success rounded-circle d-flex align-items-center justify-content-center"
                                        style="width: 32px; height: 32px;">
                                        <i class="bi bi-exclamation-circle-fill text-warning"></i>
                                    </div>
                                </div>
                                <h2 id="cardTotalStock" class="fw-bold text-dark mb-1 d-flex align-items-baseline gap-2">
                                    <span style="font-size: 2rem; line-height: 1;">
                                        {{ $Card['menipis'] }}
                                    </span>

                                    <span class="text-secondary fw-semibold" style="font-size: .95rem;">
                                        Produk
                                    </span>
                                </h2>
                            </div>
                            <p class="text-muted small mb-0 mt-3">
                                <i class="fa-solid fa-gears me-1"></i>
                                Memiliki stok menipis.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Card 3: Stok Keluar Hari Ini -->
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="card border-0 shadow-sm rounded-4 h-100 p-3 hover-shadow">
                        <div class="card-body p-1 d-flex flex-column justify-content-between">
                            <div>
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="text-muted fw-bold" style="font-size: 10px; letter-spacing: 0.5px;">
                                        PRODUKSI HARI INI
                                    </span>
                                    <div class="bg-light text-dark rounded-circle d-flex align-items-center justify-content-center"
                                        style="width: 32px; height: 32px;">
                                        <i class="bi bi-arrow-left-right"></i>
                                    </div>
                                </div>
                                <h2 id="cardMutationCount" class="h2 fw-bold text-dark my-1">
                                    {{ $Card['produksi'] ?? 0 }}
                                    <span class="text-secondary fw-semibold" style="font-size: .95rem;">
                                        Pcs
                                    </span>
                                </h2>
                            </div>
                            <p class="text-muted small mb-0 mt-3">
                                <i class="bi bi-box-seam me-1"></i>
                                Termasuk Stok & Pesanan
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Card 4: Produk Terlaris -->
                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="card border-0 shadow-sm rounded-4 h-100 p-3 hover-shadow">
                        <div class="card-body p-1 d-flex flex-column justify-content-between">
                            <div>
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="text-muted fw-bold" style="font-size: 10px; letter-spacing: 0.5px;">
                                        PRODUK TERLARIS | <span id="mounthTerlaris"></span>
                                    </span>
                                    <div class="bg-light text-dark rounded-circle d-flex align-items-center justify-content-center"
                                        style="width: 32px; height: 32px;">
                                        <i class="bi bi-trophy-fill text-warning"></i>
                                    </div>
                                </div>
                                <h2 id="cardStockOut" class="h2 fw-bold text-dark my-1">
                                    {{ $Card['terlaris'] }}
                                </h2>
                            </div>
                            <p class="text-muted small mb-0 mt-3">
                                Pesanan Produk > 1000
                            </p>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body">

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h5 class="fw-bold mb-1">Produk Terlaris</h5>
                        <small class="text-muted">30 Produk Terlaris & Penjualan 3 bulan terakhir</small>
                    </div>
                    <div class="bg-light rounded-circle d-flex align-items-center justify-content-center"
                        style="width: 40px; height: 40px;">
                        <i class="bi bi-trophy-fill fs-5"></i>
                    </div>
                </div>

                <div style="height: 300px;">
                    <canvas id="produkTerlarisChart"></canvas>
                </div>

            </div>
        </div>
    </main>

    @include('layouts.footer')
@endsection

@push('styles')
    <style>
        .rank-box {
            width: 34px;
            height: 34px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 13px;
        }

        #modalStok .dt-search {
            display: flex !important;
            align-items: center;
            justify-content: flex-end;
            padding: 0px 12px;
            gap: 6px;
        }

        #modalStok .dt-search label {
            font-size: 11px;
            color: #6c757d;
            margin: 0;
        }

        #modalStok .dt-search input {
            width: 160px !important;
            height: 30px;
            padding: 4px 9px;
            font-size: 11px;
            border: 1px solid #dee2e6;
            border-radius: 7px;
            background: #fff;
            outline: none;
            margin-left: 4px !important;
        }

        #modalStok .dt-search input:focus {
            border-color: #86b7fe;
            box-shadow: 0 0 0 2px rgba(13, 110, 253, 0.08);
        }
    </style>
@endpush

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        $(document).ready(function() {
            const bulan = [
                'JANUARI',
                'FEBRUARI',
                'MARET',
                'APRIL',
                'MEI',
                'JUNI',
                'JULI',
                'AGUSTUS',
                'SEPTEMBER',
                'OKTOBER',
                'NOVEMBER',
                'DESEMBER'
            ];

            const sekarang = new Date();
            document.getElementById('livedate').textContent =
                `${bulan[sekarang.getMonth()]} ${sekarang.getFullYear()}`;
            document.getElementById('mounthTerlaris').textContent =
                `${bulan[sekarang.getMonth()]}`;

            function diagramTerlaris() {
                const produk = @json($produkTerlaris);

                const labels = produk.map(item => item.sku);
                const jumlahKeluar = produk.map(item => item.jumlah);
                const stok = produk.map(item => item.stok);

                // Cari nilai tertinggi lalu tambah spare 100
                const nilaiTertinggi = Math.max(
                    ...jumlahKeluar,
                    ...stok
                );
                const batasMax = nilaiTertinggi + 100;
                const ctx = document.getElementById('produkTerlarisChart').getContext('2d');

                new Chart(ctx, {
                    type: 'bar',

                    data: {
                        labels: labels,

                        datasets: [{
                                label: 'Jumlah Keluar',
                                data: jumlahKeluar,
                                backgroundColor: '#D90000',
                                borderColor: '#D90000',
                                borderWidth: 0,
                                borderRadius: 6,
                                barThickness: 25
                            },
                            {
                                label: 'Stok',
                                data: stok,
                                backgroundColor: '#0D47A1',
                                borderColor: '#0D47A1',
                                borderWidth: 0,
                                borderRadius: 6,
                                barThickness: 25
                            }
                        ]
                    },

                    options: {
                        responsive: true,
                        maintainAspectRatio: false,

                        plugins: {
                            legend: {
                                display: true,
                                position: 'top'
                            },

                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return context.dataset.label +
                                            ': ' +
                                            context.raw.toLocaleString('id-ID') +
                                            ' pcs';
                                    }
                                }
                            }
                        },

                        scales: {
                            x: {
                                grid: {
                                    display: false
                                },

                                border: {
                                    display: false
                                },

                                ticks: {
                                    autoSkip: false,
                                    minRotation: 45,
                                    maxRotation: 45
                                }
                            },

                            y: {
                                beginAtZero: true,
                                max: batasMax,

                                ticks: {
                                    precision: 0
                                },

                                border: {
                                    display: false
                                }
                            }
                        }
                    }
                });
            }

            diagramTerlaris();
        });
    </script>
@endpush
