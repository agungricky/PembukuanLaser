@extends('layouts.app')
@section('content')
    <main class="flex-grow-1 overflow-auto p-3 p-lg-4">
        <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mb-4">
            <div>
                <h1 class="h3 fw-bold text-dark mb-1">Riwayat Pesanan Selesai Ditangani</h1>
                <p class="text-muted small mb-0">
                    <span class="text-muted">
                        <i class="fa-solid fa-arrow-right-arrow-left"></i>
                        Pesanan keluar
                    </span>
                    <span class="mx-2 text-secondary">/</span>
                    <span class="fw-semibold text-primary">
                        Pesanan Selesai
                    </span>
                </p>
            </div>
        </div>

        {{-- Filter Yang Aktif --}}
        <div class="alert alert-light border d-flex align-items-center gap-2 py-2 px-3 mb-3" style="font-size: 12px;">
            <i class="fa-solid fa-magnifying-glass text-primary"></i>
            <div class="border-end pe-3 me-2">
                <span class="fw-semibold text-dark">Pencarian aktif:</span>
                <span class="badge bg-primary ms-1">Tanggal</span>
            </div>
            <div class="">
                <span class="fw-semibold text-dark">Data Tampil :</span>
                <span class="badge bg-primary ms-1">1 Bulan Terakhir</span>
            </div>
        </div>

        <div id="stokCard" class="stok-card mb-3 d-none">
            <div class="stok-card-header d-flex align-items-center justify-content-between">
                <div>
                    <div class="stok-title">
                        Kebutuhan Produk
                    </div>

                    <div class="stok-subtitle">
                        Perbandingan kebutuhan SKU dengan stok yang tersedia
                    </div>
                </div>

                <span class="stok-live">
                    <span class="stok-live-dot"></span>
                    Stok Live
                </span>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 stok-table">
                    <thead class="table-light">
                        <tr>
                            <th class="px-4">
                                SKU
                            </th>

                            <th class="px-4">
                                Nama Produk
                            </th>

                            <th class="px-4 text-center" style="width:150px;">
                                Stok Tersedia
                            </th>

                            <th class="px-4 text-center" style="width:150px;">
                                Kebutuhan
                            </th>
                        </tr>
                    </thead>

                    <tbody id="stokLiveBody">
                    </tbody>

                </table>
            </div>

        </div>

        <!-- Inventory Stock Table Section -->
        <section id="stockTableSection" class="card border-0 shadow-sm rounded-4 overflow-hidden mb-0 pb-0">
            <div
                class="card-header bg-white border-bottom p-3 p-md-4 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                <div>
                    <h2 class="h5 fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                        <i class="fa-solid fa-boxes-stacked text-primary"></i>
                        Daftar Pesanan Diselesaikan
                    </h2>
                    <p class="text-muted small mb-0">
                        Rincian pesanan yang sudah selesai.
                    </p>
                </div>
                <!-- Controls: Filters & Table Search -->
                <div class="d-flex flex-nowrap align-items-center gap-2">
                    <div class="input-group input-group-sm" style="max-width: 240px;">
                        <span class="input-group-text bg-light border-end-0">
                            <i class="fa-solid fa-magnifying-glass text-muted"></i>
                        </span>
                        <input type="text" id="searchTable" placeholder="Search"
                            class="form-control form-control-sm border-start-0 bg-light" />
                    </div>
                    <select id="per_page" class="form-select form-select-sm" style="width: auto;">
                        <option value="10" selected>10</option>
                        <option value="20">20</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
            </div>

            <!-- Table Container -->
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="orderlist">
                    <thead class="table-light">
                        <tr>
                            <th class="px-3" style="width: 190px;">
                                <i class="fa-solid fa-layer-group me-1"></i>
                                Sesi
                            </th>

                            <th class="px-3" style="width: 180px;">
                                <i class="fa-regular fa-calendar-clock me-1"></i>
                                Tanggal
                            </th>
                            <th>Jam</th>
                            <th class="px-2 text-center" style="width: 120px;">
                                <i class="fa-solid fa-circle-info me-1"></i>
                                Detail
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($data as $item)
                            <tr class="align-middle border-bottom">

                                {{-- SESI --}}
                                <td class="px-3 py-3">
                                    @if ($item->sesi === 'Pagi')
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="d-flex align-items-center justify-content-center rounded-circle bg-warning-subtle"
                                                style="width: 34px; height: 34px;">
                                                <i class="fa-solid fa-sun text-warning"></i>
                                            </div>

                                            <div>
                                                <div class="fw-semibold text-dark">
                                                    Pagi
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="d-flex align-items-center justify-content-center rounded-circle bg-primary-subtle"
                                                style="width: 34px; height: 34px;">
                                                <i class="fa-solid fa-cloud-sun text-primary"></i>
                                            </div>

                                            <div>
                                                <div class="fw-semibold text-dark">
                                                    Siang
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </td>

                                {{-- TANGGAL --}}
                                <td class="px-3 py-3">
                                    <div class="fw-semibold text-dark">
                                        {{ \Carbon\Carbon::parse($item->tanggal)->translatedFormat('d M Y') }}
                                    </div>

                                    <small class="text-muted text-nowrap">
                                        <i class="fa-regular fa-calendar me-1"></i>
                                        Tanggal penyelesaian pesanan
                                    </small>
                                </td>

                                {{-- KETERANGAN --}}
                                <td class="px-3 py-3">
                                    <span class="text-muted small">
                                        @if ($item->sesi === 'Pagi')
                                            Data transaksi sebelum pukul 12:00
                                        @else
                                            Data transaksi mulai pukul 12:00
                                        @endif
                                    </span>
                                </td>

                                {{-- AKSI --}}
                                <td class="px-3 py-3 text-center">
                                    <a href="{{ route('transaksi.show', [
                                        'page' => 'diambil',
                                        'sesi' => $item->sesi,
                                        'tanggal' => $item->tanggal,
                                    ]) }}"
                                        class="btn btn-sm btn-light border rounded-pill px-3">

                                        <i class="fa-solid fa-arrow-right me-1"></i>
                                        Detail
                                    </a>
                                </td>

                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div id="loadingData" class="text-center py-3" style="display: none;">
                    <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                    <span class="ms-2">Memuat data...</span>
                </div>
            </div>
        </section>
    </main>
    @include('layouts.footer')
@endsection

@push('scripts')
    <script>
        const table = new DataTable('#orderlist', {
            pageLength: 10,
            searching: true,
            lengthChange: false,
            autoWidth: false
        });
    </script>
@endpush
