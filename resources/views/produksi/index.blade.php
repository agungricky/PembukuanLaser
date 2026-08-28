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
                                        Item
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
                                    {{-- {{ $Card['produksi'] }} --}}
                                </h2>
                            </div>
                            <p class="text-muted small mb-0 mt-3">
                                <i class="bi bi-box-seam me-1"></i>
                                Termasuk Stok & Pesanan
                            </p>
                        </div>
                    </div>
                </div>
                <!-- Card 4: Mutasi Hari Ini -->

                <div class="col-12 col-sm-6 col-lg-3">
                    <div class="card border-0 shadow-sm rounded-4 h-100 p-3 hover-shadow">
                        <div class="card-body p-1 d-flex flex-column justify-content-between">
                            <div>
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="text-muted fw-bold" style="font-size: 10px; letter-spacing: 0.5px;">
                                        PRODUK TERLARIS
                                    </span>
                                    <div class="bg-light text-dark rounded-circle d-flex align-items-center justify-content-center"
                                        style="width: 32px; height: 32px;">
                                        {{-- <i class="bi bi-arrow-up-circle-fill"></i> --}}
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

        <!-- Inventory Stock Table Section -->
        <section id="stockTableSection" class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
            <!-- Table Header Bar -->
            <div
                class="card-header bg-white border-bottom p-3 p-md-4 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                <div>
                    <h2 class="h5 fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                        <i class="fa-solid fa-boxes-stacked text-primary"></i>
                        Daftar Pesanan Terbaru
                    </h2>
                    <p class="text-muted small mb-0">
                        Rincian pesanan yang perlu cek & disiapkan.
                    </p>
                </div>

                <!-- Controls: Filters & Table Search -->
                <div class="d-flex flex-nowrap align-items-center gap-2">
                    <!-- Table Search Input -->
                    <div class="input-group input-group-sm" style="max-width: 240px;">
                        <span class="input-group-text bg-light border-end-0">
                            <i class="fa-solid fa-magnifying-glass text-muted"></i>
                        </span>
                        <input type="text" id="searchTable" placeholder="Cari SKU / Produk..."
                            class="form-control form-control-sm border-start-0 bg-light" />
                    </div>

                    <select id="per_page" class="form-select form-select-sm" style="width: auto;">
                        <option value="10">10</option>
                        <option value="20" selected>20</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>

                    <a href="{{ route('allpesanan.index') }}" class="btn btn-primary text-nowrap">
                        <i class="fa-solid fa-list me-2"></i>Semua Pesanan
                    </a>
                </div>
            </div>

            <!-- Table Container -->
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-nowrap" id="orderlist">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" class="py-3 px-4 text-end">No</th>
                            <th scope="col" class="py-3 px-4">Nama Produk</th>
                            <th scope="col" class="py-3 px-4 text-center">Variasi</th>
                            <th scope="col" class="py-3 px-4 text-center">Hpp</th>
                            <th scope="col" class="py-3 px-4 text-center">Kebutuhan</th>
                            <th scope="col" class="py-3 px-4 text-center">Tersedia</th>
                            <th scope="col" class="py-3 px-4 text-center">Status Stok</th>
                        </tr>
                    </thead>
                    <tbody id="stockTableBody"></tbody>
                </table>
            </div>
        </section>

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
    <script>
        $(document).ready(function() {

            $.ajax({
                type: "GET",
                url: "{{ route('pesanan.json') }}",
                dataType: "JSON",
                success: function(response) {

                    let html = '';
                    let no = 1;
                    $.each(response, function(index, item) {
                        html += `
                            <tr>
                                <td class="py-3 px-4 text-center">
                                    ${no++}
                                </td>

                                <td class="py-3 px-4 text-start">
                                    <div class="fw-semibold text-dark">
                                        ${item.produk?.nama_produk ?? '-'}
                                    </div>

                                    <div class="mt-1">
                                        <span class="badge bg-light text-secondary border fw-normal"
                                            style="font-size: 10px; letter-spacing: .3px;">
                                            SKU: ${item.produk?.sku ?? '-'}
                                        </span>
                                    </div>
                                </td>
                                
                                <td class="py-3 px-4 text-start">
                                    ${(item.produk?.variasi ?? '-').replace(/,\s*/g, '<br>')}
                                </td>

                                <td class="py-3 px-4 text-center">
                                    <div class="fw-bold text-success fs-6">
                                        ${Number(item.produk?.hpp ?? 0).toLocaleString('id-ID', {
                                            style: 'currency',
                                            currency: 'IDR',
                                            minimumFractionDigits: 0
                                        })}
                                    </div>

                                    <small class="text-muted">/ Item</small>
                                </td>

                                <td class="py-3 px-4 text-center">
                                    <span class="fw-bold text-danger">
                                        ${item.kebutuhan ?? 0}
                                    </span>
                                </td>

                                <td class="py-3 px-4 text-center">
                                    <span class="fw-bold text-primary">
                                        ${item.produk?.stok_produk?.jumlah_tersedia ?? 0}
                                    </span>
                                </td>

                                <td class="py-3 px-4 text-center">
                                    ${
                                        (item.produk?.stok_produk?.jumlah_tersedia ?? 0) >=
                                        (item.kebutuhan ?? 0)

                                        ? `<span class="badge bg-success">Tersedia</span>`
                                        : `<span class="badge bg-danger">Kurang</span>`
                                    }
                                </td>
                            </tr>
                        `;
                    });

                    $('#stockTableBody').html(html);
                    $('#perludisiapkan').text(response.length + " Barang");
                    const table = new DataTable('#orderlist', {
                        pageLength: 10,
                        searching: true,
                        lengthChange: false,
                        autoWidth: false
                    });

                    $('#per_page').val(10);
                    $('#per_page').on('change', function() {
                        table.page.len(parseInt(this.value)).draw();
                    });

                    $('#searchTable').on('input', function() {
                        table.search(this.value).draw();
                    });
                },

                error: function(xhr) {
                    console.log(xhr.responseText);
                }
            });

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


            let tableStok = null;
            $('#modalStok').on('shown.bs.modal', function(event) {
                const info_btn = $(event.relatedTarget).data('card');
                if (tableStok) {
                    tableStok.clear();
                    tableStok.destroy();
                    tableStok = null;
                }
                $('#tablestockbody').empty();

                let badgeStatus = '';
                if (info_btn == "aman") {
                    $('#modalHeader').text('Stok Aman');
                    badgeStatus = `
                        <span class="badge bg-success">
                            Aman
                        </span>
                    `;
                } else if (info_btn == "menipis") {
                    $('#modalHeader').text('Stok Menipis');
                    badgeStatus = `
                        <span class="badge bg-warning text-dark">
                            Menipis
                        </span>
                    `;
                } else if (info_btn == "kritis") {
                    $('#modalHeader').text('Stok Kritis');
                    badgeStatus = `
                        <span class="badge bg-danger">
                            Kritis
                        </span>
                    `;
                } else if (info_btn == "habis") {
                    $('#modalHeader').text('Stok Habis');
                    badgeStatus = `
                        <span class="badge bg-dark">
                            Habis
                        </span>
                    `;
                } else {
                    $('#modalHeader').text('Data Stok');
                    badgeStatus = `
                        <span class="badge bg-secondary">
                            Tidak Diketahui
                        </span>
                    `;
                }

                $.ajax({
                    type: "GET",
                    url: "{{ route('gudang.detailcard.json', ':card') }}".replace(':card',
                        info_btn),
                    dataType: "JSON",
                    success: function(response) {
                        let html = '';
                        let no = 1;
                        $.each(response, function(index, item) {
                            const tersedia = item.produk?.stok_produk
                                ?.jumlah_tersedia ?? 0;
                            const kebutuhan = item.kebutuhan ?? 0;
                            const aman = tersedia >= kebutuhan;

                            html += `
                                    <tr class="align-middle">
                                        <td class="py-3 px-3 text-center">
                                            ${no++}
                                        </td>
                                        <td class="py-3 px-3">
                                            <div class="fw-semibold text-dark">
                                                ${item.produk?.nama_produk ?? '-'}
                                            </div>
                                            <div class="text-muted"
                                                style="font-size:11px;">
                                                SKU: ${item.produk?.sku ?? '-'}
                                            </div>
                                        </td>
                                        <td class="py-3 px-3">
                                            <div>
                                                ${item.produk?.variasi ?? '-'}
                                            </div>
                                            <div class="text-muted" style="font-size:11px;">
                                                Kategori : ${item.produk?.kategori?.nama_kategori ?? '-'}
                                            </div>
                                        </td>
                                        <td class="py-3 px-3 text-center">
                                            <div class="fw-semibold">
                                                ${Number(item.produk?.hpp ?? 0)
                                                    .toLocaleString('id-ID', {
                                                        style: 'currency',
                                                        currency: 'IDR',
                                                        minimumFractionDigits: 0
                                                    })}
                                            </div>
                                            <div class="text-muted"
                                                style="font-size:11px;">
                                                Tersedia:
                                                <span class="fw-bold text-primary">
                                                    ${item?.jumlah_tersedia}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="py-3 px-3 text-center">
                                            ${badgeStatus}
                                        </td>
                                    </tr>
                                `;
                        });

                        $('#tablestockbody').html(html);
                        tableStok = new DataTable('#tabledata', {
                            pageLength: 10,
                            searching: true,
                            lengthChange: true,
                            autoWidth: false
                        });
                        tableStok.columns.adjust();
                    }

                });

            });
        });
    </script>
@endpush
