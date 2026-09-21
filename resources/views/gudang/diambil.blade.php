@extends('layouts.app')
@section('content')
    <main class="flex-grow-1 overflow-auto p-3 p-lg-4">
        <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mb-4">
            <div>
                <h1 class="h3 fw-bold text-dark mb-1">Pesanan Selesai & Keluar Gudang</h1>
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
                <span class="badge bg-primary ms-1">No Pesanan</span>
                <span class="badge bg-primary ms-1">Nama Toko</span>
                <span class="badge bg-primary ms-1">SKU</span>
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
                        Daftar Pesanan Terbaru
                    </h2>
                    <p class="text-muted small mb-0">
                        Rincian pesanan yang perlu disiapkan.
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
                    <select id="filterMarketplace" class="form-select form-select-sm"
                        style="width: auto; min-width: 140px;">
                        <option value="">Semua Toko</option>
                        <option value="Shopee">Shopee</option>
                        <option value="TikTok">TikTok</option>
                    </select>
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
                            <th class="text-center px-2" data-dt-order="disable" style="width: 44px;">
                                <input type="checkbox" class="form-check-input" id="checkAll"
                                    style="width: 16px; height: 16px;">
                            </th>
                            <th class="px-3" style="width: 190px;">
                                <i class="fa-regular fa-user me-1"></i>
                                Pesanan
                            </th>
                            <th class="px-3" style="width: 180px;">
                                <i class="fa-solid fa-truck-fast me-1"></i>
                                No Resi
                            </th>
                            <th class="px-3" style="min-width: 280px;">
                                <i class="fa-solid fa-box me-1"></i>
                                Produk
                            </th>
                            <th class="px-2 text-center" style="width: 75px;">
                                <i class="fa-solid fa-hashtag me-1"></i>
                                Qty
                            </th>
                            <th class="px-2 text-center" style="width: 120px;">
                                <i class="fa-solid fa-user me-1"></i>
                                Aktor
                            </th>
                        </tr>
                    </thead>
                    <tbody id="stockTableBody">

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
@push('styles')
    <style>
        #orderlist {
            font-size: 13px;
        }

        #orderlist thead th {
            font-size: 12px;
            font-weight: 600;
            color: #6c757d;
            white-space: nowrap;
            vertical-align: middle;
        }

        #orderlist tbody td {
            vertical-align: middle !important;
        }

        .order-main {
            font-size: 15px;
            font-weight: 600;
            color: #212529;
        }

        .order-sub {
            font-size: 12px;
            color: #6c757d;
            line-height: 1.35;
        }

        .resi-main {
            font-size: 14px;
            font-weight: 600;
            color: #212529;
        }

        .product-item {
            padding: 6px 0;
            line-height: 1.3;
        }

        .product-item+.product-item {
            border-top: 1px solid #eeeeee;
        }

        .product-sku {
            font-size: 13px;
            font-weight: 700;
            color: #212529;
        }

        .product-name {
            font-size: 13px;
            color: #495057;
        }

        .product-variant {
            font-size: 11px;
            color: #8a8f94;
        }

        .status-proses {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 5px 8px;
            border-radius: 5px;
            font-size: 11px;
            font-weight: 600;
            color: #e65100;
            background: #fff3e0;
            border: 1px solid #ffe0b2;
        }

        .tanggal-label {
            font-size: 10px;
            color: #adb5bd;
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: .3px;
        }

        .tanggal-value {
            font-size: 12px;
            font-weight: 600;
            color: #495057;
        }

        .qty-value {
            font-size: 14px;
            font-weight: 700;
            color: #212529;
        }

        .btn-detail-table {
            width: 34px;
            height: 34px;
            padding: 0;
            border-radius: 7px;
        }

        .stok-card {
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            overflow: hidden;
            background: #fff;
        }

        .stok-card-header {
            padding: 16px 18px;
            border-bottom: 1px solid #e9ecef;
            background: #fff;
        }

        .stok-title {
            font-size: 16px;
            font-weight: 700;
            color: #212529;
        }

        .stok-subtitle {
            font-size: 12px;
            color: #6c757d;
            margin-top: 2px;
        }

        .product-row-sync {
            box-sizing: border-box;
            display: flex;
            align-items: center;
        }

        .product-row-sync.border-top {
            border-top: 1px solid #dee2e6 !important;
        }

        .produk-cell,
        .jumlah-cell,
        .stok-cell {
            vertical-align: middle !important;
        }
    </style>
@endpush
@push('scripts')
    <script>
        $(document).ready(function() {
            let filter = "{{ $page }}";
            let selected = [];
            let pengambilbarang = null;
            let cekDetail = [];
            let table;
            let kebutuhan = [];

            $('#per_page').val(10);
            $('#per_page').on('change', function() {
                resetChecklist();
                if (table) {
                    table.page.len(parseInt(this.value)).draw();
                }
            });

            $('#searchTable').on('input', function() {
                resetChecklist();
                if (table) {
                    table.search(this.value).draw();
                }
            });

            $('#filterMarketplace').on('change', function() {
                resetChecklist();
                if (table) {
                    table.ajax.reload();
                }
            });

            // Mengosongkan CheckAll saat halaman baru dimuat
            $('#checkAll').prop('checked', false);
            $('.item-checkbox').prop('checked', false);

            // Melakukan singkronisasi tinggi sebuah ROW
            function syncProductRows() {
                $('#orderlist tbody tr').each(function() {

                    const row = $(this);

                    const produk = row.find('.produk-item');
                    const jumlah = row.find('.jumlah-item');
                    const stok = row.find('.stok-item');

                    produk.css('height', '');
                    jumlah.css('height', '');
                    stok.css('height', '');

                    produk.each(function(index) {

                        const produkItem = produk.eq(index);
                        const jumlahItem = jumlah.eq(index);
                        const stokItem = stok.eq(index);

                        const tinggi = Math.ceil(
                            Math.max(
                                produkItem.outerHeight() || 0,
                                jumlahItem.outerHeight() || 0,
                                stokItem.outerHeight() || 0
                            )
                        );

                        produkItem.css('height', tinggi + 'px');
                        jumlahItem.css('height', tinggi + 'px');
                        stokItem.css('height', tinggi + 'px');

                    });

                });

            }

            $('#stockTableBody').empty();

            // Datatable Utama
            table = new DataTable('#orderlist', {
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('showdata.json', ':filter') }}".replace(':filter', filter),
                    type: "GET",
                    data: function(d) {
                        d.marketplace = $('#filterMarketplace').val();
                    }
                },
                pageLength: 10,
                searching: true,
                lengthChange: false,
                autoWidth: false,
                order: [],
                columns: [
                    // CHECKBOX
                    {
                        data: 'id',
                        name: 'id',
                        orderable: false,
                        searchable: false,
                        className: 'text-center px-2',
                        render: function(data, type, row) {
                            return `
                                <input
                                    type="checkbox"
                                    class="form-check-input item-checkbox"
                                    value="${data ?? ''}"
                                    data-no-pesanan="${row.no_pesanan ?? ''}"
                                    style="width: 16px; height: 16px;"
                                >
                            `;
                        }
                    },

                    // PESANAN
                    {
                        data: 'no_pesanan',
                        name: 'no_pesanan',
                        orderable: false,
                        searchable: true,
                        className: 'px-3 py-2',
                        render: function(data, type, row) {
                            const batasKirim = row.batas_kirim_at ?
                                new Date(row.batas_kirim_at) :
                                null;

                            const barang = Array.isArray(row.pesanan_per_produk) ?
                                row.pesanan_per_produk : [];

                            // Ambil updated_at paling terbaru
                            const updatedAtTerbaru = barang
                                .filter(item => item.updated_at)
                                .map(item => new Date(item.updated_at))
                                .sort((a, b) => b - a)[0] ?? null;

                            return `
                                <div class="d-flex flex-column gap-1">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="fa-solid fa-receipt text-primary"
                                            style="font-size: 12px;"></i>

                                        <span class="fw-bold text-dark"
                                            style="font-size: 14px;">
                                            ${data ?? '-'}
                                        </span>
                                    </div>

                                    <div class="d-flex flex-column">
                                        <div class="d-flex align-items-center gap-2 text-nowrap">
                                            <span class="text-muted fw-semibold"
                                                style="font-size: 10px;">

                                                <i class="fa-solid fa-truck me-1"></i>
                                                Batas Kirim
                                            </span>

                                            <span class="fw-semibold text-danger"
                                                style="font-size: 11px;">
                                                ${
                                                    batasKirim
                                                        ? batasKirim.toLocaleString('id-ID', {
                                                            day: '2-digit',
                                                            month: 'short',
                                                            year: 'numeric',
                                                            hour: '2-digit',
                                                            minute: '2-digit',
                                                            hour12: false,
                                                            timeZone: 'Asia/Jakarta'
                                                        })
                                                        : '-'
                                                }
                                            </span>
                                        </div>

                                        <div class="d-flex align-items-center gap-2 text-nowrap">
                                            <span class="text-muted fw-semibold"
                                                style="font-size: 10px;">
                                                <i class="fa-solid fa-dolly me-1"></i>
                                                Diambil
                                            </span>
                                            <span class="fw-semibold text-success"
                                                style="font-size: 11px;">
                                                ${
                                                    updatedAtTerbaru
                                                        ? updatedAtTerbaru.toLocaleString('id-ID', {
                                                            day: '2-digit',
                                                            month: 'short',
                                                            year: 'numeric',
                                                            hour: '2-digit',
                                                            minute: '2-digit',
                                                            hour12: false,
                                                            timeZone: 'Asia/Jakarta'
                                                        })
                                                        : '-'
                                                }
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            `;
                        }
                    },

                    // RESI
                    {
                        data: 'no_resi',
                        name: 'no_resi',
                        orderable: false,
                        searchable: true,
                        className: 'px-3 py-2',
                        render: function(data, type, row) {
                            return `
                                <div
                                    class="d-flex flex-column align-items-start"
                                    style="line-height: 1.25;">

                                    <span class="resi-main">
                                        <i class="fa-solid fa-barcode text-secondary me-1" style="font-size: 11px;"></i>
                                        ${data ?? '-'}
                                    </span>

                                    <div class="d-flex align-items-center gap-2 mt-1 flex-wrap">
                                        <span
                                            class="d-inline-flex align-items-center gap-1 px-2 py-1 rounded-pill"
                                            style="
                                                font-size: 10px;
                                                font-weight: 600;
                                                color: #e8590c;
                                                background: rgba(253, 126, 20, 0.15);
                                                border: 1px solid rgba(253, 126, 20, 0.35);
                                            "
                                        >
                                            <i class="fa-solid fa-clock"></i>
                                            ${row.status ?? '-'}
                                        </span>
                                        <span
                                            class="d-inline-flex align-items-center gap-1 px-2 py-1 rounded-pill"
                                            style="
                                                font-size: 10px;
                                                font-weight: 600;
                                                color: #6c757d;
                                                background: #f8f9fa;
                                                border: 1px solid #dee2e6;
                                            "
                                        >
                                            <i class="fa-solid fa-store"></i>
                                            ${row.toko?.nama_toko ?? '-'}
                                        </span>
                                    </div>
                                </div>
                            `;
                        }
                    },

                    // PRODUK
                    {
                        data: 'pesanan_per_produk',
                        orderable: false,
                        searchable: true,
                        className: 'px-3 py-0 produk-cell',
                        render: function(data, type, row) {
                            let html = '';
                            const barang = Array.isArray(row.pesanan_per_produk) ?
                                row.pesanan_per_produk : [];

                            barang.forEach(function(item, index) {
                                const border = index > 0 ? 'border-top' : '';
                                html += `
                                    <div class="product-row-sync produk-item ${border} py-2">
                                        <div class="d-flex align-items-center flex-wrap gap-2 w-100">
                                            <span class="product-sku d-inline-flex align-items-center fw-semibold text-dark">
                                                <i
                                                    class="fa-solid fa-cube text-primary me-1"
                                                    style="font-size:10px;">
                                                </i>
                                                ${item.sku ?? '-'}
                                            </span>
                                            <span class="text-muted">•</span>
                                            <span class="product-name text-secondary">
                                                ${
                                                    item.nama_produk
                                                        ? item.nama_produk
                                                            .split(' ')
                                                            .reduce((hasil, kata, index) => {
                                                                return hasil +
                                                                    kata +
                                                                    ((index + 1) % 5 === 0
                                                                        ? '<br>'
                                                                        : ' ');
                                                            }, '')
                                                        : '-'
                                                }
                                            </span>
                                            ${
                                                item.variasi
                                                    ? `
                                                                                                                                                                                                                            <span
                                                                                                                                                                                                                                class="badge bg-light text-dark border fw-normal"
                                                                                                                                                                                                                                style="font-size:10px;">
                                                                                                                                                                                                                                ${item.variasi}
                                                                                                                                                                                                                            </span>
                                                                                                                                                                                                                        `
                                                    : ''
                                            }

                                        </div>

                                    </div>
                                `;
                            });

                            return html;
                        }
                    },

                    // JUMLAH
                    {
                        data: 'pesanan_per_produk',
                        orderable: false,
                        searchable: false,
                        className: 'px-2 py-0 text-center jumlah-cell',
                        render: function(data, type, row) {
                            let html = '';
                            const barang = Array.isArray(row.pesanan_per_produk) ?
                                row.pesanan_per_produk : [];

                            barang.forEach(function(item, index) {
                                const border = index > 0 ? 'border-top' : '';
                                html += `
                                    <div
                                        class="product-row-sync jumlah-item ${border}
                                            justify-content-center"
                                    >
                                        <span class="qty-value">
                                            ${item.jumlah ?? 0}
                                        </span>
                                    </div>
                                `;
                            });

                            return html;
                        }
                    },

                    // AKTOR
                    {
                        data: null,
                        name: 'aktor',
                        orderable: false,
                        searchable: false,
                        className: 'px-2 py-0 text-center',
                        render: function(data, type, row) {
                            const produk = row.pesanan_per_produk?.[0];
                            const adminGudang = produk?.mutasi?.gudang?.name ?? '-';
                            const pengambilBarang = produk?.mutasi?.admin_penjualan?.name ?? '-';
                            return `
                                <div class="d-flex flex-column gap-2 py-2">

                                    <div>
                                        <div class="text-muted small mb-1">
                                            Admin Gudang
                                        </div>

                                        <span class="badge bg-primary">
                                            ${adminGudang}
                                        </span>
                                    </div>

                                    <div>
                                        <div class="text-muted small mb-1">
                                            Pengambil Barang
                                        </div>

                                        <span class="badge bg-success">
                                            ${pengambilBarang}
                                        </span>
                                    </div>

                                </div>
                            `;
                        }
                    },
                ],
                drawCallback: function() {
                    requestAnimationFrame(function() {
                        syncProductRows();

                    });

                }
            });

            // Check All
            $(document).on('change', '#checkAll', function() {
                const checked = $(this).prop('checked');

                $('#orderlist tbody .item-checkbox').each(function() {
                    $(this).prop('checked', checked);

                    $(this).closest('tr').toggleClass(
                        'table-active',
                        checked
                    );
                });

                updateButtonPengambil();
                updateStokLive();
            });

            // Klik Row
            $(document).on('click', '#orderlist tbody tr', function(e) {
                // Abaikan kalau klik tombol detail
                if ($(e.target).closest('.btnDetail').length) {
                    return;
                }

                const row = $(this);
                const checkbox = row.find('.item-checkbox');

                if (!checkbox.length) {
                    return;
                }

                // Kalau klik checkbox langsung
                if ($(e.target).is('.item-checkbox')) {
                    row.toggleClass(
                        'table-active',
                        checkbox.prop('checked')
                    );

                    updateButtonPengambil();
                    updateStokLive();

                    return;
                }

                // Klik area row
                const checked = !checkbox.prop('checked');

                checkbox.prop('checked', checked);

                row.toggleClass(
                    'table-active',
                    checked
                );

                updateButtonPengambil();
                updateStokLive();
            });

            // Logic jika filter ganti maka hapus checklist
            function resetChecklist() {
                // Hapus semua checkbox item
                $('.item-checkbox').prop('checked', false);

                // Hapus check all
                $('#checkAll').prop('checked', false);

                // Hapus highlight row
                $('#orderlist tbody tr').removeClass('table-active');

                // Update tombol
                updateButtonPengambil();

                // Update card stok live kalau kamu pakai
                if (typeof updateStokLive === 'function') {
                    updateStokLive();
                }
            }
        });
    </script>
@endpush
