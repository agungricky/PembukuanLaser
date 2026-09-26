@extends('layouts.app')
@section('content')
    <main class="flex-grow-1 overflow-auto p-3 p-lg-4">
        <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mb-4">
            <div>
                <h1 class="h3 fw-bold text-dark mb-1">Produk Reguler</h1>
                <p class="text-muted small mb-0">
                    <span class="text-muted">
                        <i class="fa-solid fa-arrow-right-arrow-left"></i>
                        Transaksi
                    </span>
                    <span class="mx-2 text-secondary">/</span>
                    <span class="fw-semibold text-primary">
                        Perlu Disiapkan
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
                <span class="badge bg-primary ms-1">7 Hari Terakhir</span>
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
                <div class="d-flex flex-column align-items-center gap-2">
                    <div class="d-flex justify-content-center align-items-center gap-2 flex-nowrap">
                        <div class="input-group input-group-sm" style="width: 240px;">
                            <span class="input-group-text bg-light border-end-0">
                                <i class="fa-solid fa-magnifying-glass text-muted"></i>
                            </span>

                            <input type="text" id="searchTable" placeholder="Search"
                                class="form-control form-control-sm border-start-0 bg-light" />
                        </div>

                        <select id="filterKategori" data-placeholder="Pilih Kategori" class="form-select form-select-sm"
                            style="width: 140px;">
                            <option value="">Semua Kategori</option>
                            @foreach ($kategori as $item)
                                <option value="{{ $item->id }}">{{ $item->nama_kategori }}</option>
                            @endforeach
                        </select>

                        <select id="filterMarketplace" class="form-select form-select-sm" style="width: 140px;">
                            <option value="">Semua Toko</option>
                            <option value="Shopee">Shopee</option>
                            <option value="TikTok">TikTok</option>
                        </select>

                        <select id="per_page" class="form-select form-select-sm" style="width: 80px;">
                            <option value="10" selected>10</option>
                            <option value="20">20</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>

                    <div class="d-flex justify-content-end align-items-center gap-2 w-100">
                        <button type="button" id="cetakResi" class="btn btn-danger btn-sm text-nowrap">
                            <i class="fa-solid fa-print me-1"></i>
                            Cetak Resi
                        </button>
                        <button type="button" class="btn btn-primary btn-sm text-nowrap" data-bs-toggle="modal"
                            data-bs-target="#pengambilModal" data-role="pegawai" id="btnPengambilModal" disabled>
                            <i class="fa-solid fa-circle-check me-1"></i>
                            Tandai Sudah Diambil
                        </button>
                    </div>
                </div>
            </div>
            <!-- Table Container -->
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="orderlist">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center px-2" data-dt-order="disable" style="width: 55px;">

                                <div class="dropdown">
                                    <button class="btn btn-sm btn-light border dropdown-toggle" type="button"
                                        data-bs-toggle="dropdown" style="padding: 2px 7px;" title="Pilih pesanan">

                                        <i class="fa-solid fa-check-double"></i>
                                    </button>

                                    <ul class="dropdown-menu">
                                        <li>
                                            <button class="dropdown-item pilih-checkbox" data-type="all">
                                                <i class="fa-solid fa-check-double me-2 text-primary"></i>
                                                Pilih Semua
                                            </button>
                                        </li>

                                        <li>
                                            <button class="dropdown-item pilih-checkbox" data-type="tersedia">
                                                <i class="fa-solid fa-circle-check me-2 text-success"></i>
                                                Pilih Tersedia
                                            </button>
                                        </li>

                                        <li>
                                            <button class="dropdown-item pilih-checkbox" data-type="kurang">
                                                <i class="fa-solid fa-triangle-exclamation me-2 text-danger"></i>
                                                Pilih Tidak Tersedia
                                            </button>
                                        </li>

                                        <li>
                                            <hr class="dropdown-divider">
                                        </li>

                                        <li>
                                            <button class="dropdown-item pilih-checkbox" data-type="clear">
                                                <i class="fa-solid fa-xmark me-2 text-secondary"></i>
                                                Kosongkan Pilihan
                                            </button>
                                        </li>
                                    </ul>
                                </div>

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
                                <i class="fa-solid fa-boxes-stacked me-1"></i>
                                Stok
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

    {{-- Modal Export --}}
    <div class="modal fade" id="modalAlasanExport" tabindex="-1" aria-labelledby="modalAlasanExportLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title fw-bold" id="modalAlasanExportLabel">
                            Alasan Export Resi
                        </h5>
                        <div class="text-muted" style="font-size: 12px;">
                            Pilih alasan sebelum melakukan export resi.
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal">
                    </button>
                </div>

                <div class="modal-body">
                    <label for="alasanExport" class="form-label fw-semibold">
                        Alasan Export
                        <span class="text-danger">*</span>
                    </label>
                    <select id="alasanExport" class="form-select">
                        <option value="">
                            -- Pilih Alasan --
                        </option>
                        <option value="Cetak Resi Pertama">
                            Cetak Resi Pertama
                        </option>
                        <option value="Cetak Ulang Resi">
                            Pengecekan Resi
                        </option>
                        <option value="Resi Hilang">
                            Resi Hilang
                        </option>
                        <option value="Resi Barang Terlambat">
                            Resi Barang Terlamabat
                        </option>
                        <option value="Resi Barang Urgent">
                            Resi Barang URGENT
                        </option>
                    </select>
                    <div class="invalid-feedback">
                        Alasan export wajib dipilih.
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                        Batal
                    </button>
                    <button type="button" class="btn btn-primary" id="btnProsesExport">
                        <i class="fa-solid fa-file-export me-1"></i>
                        Export Resi
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Pengambil Barang -->
    <div class="modal fade" id="pengambilModal" tabindex="-1" aria-labelledby="pengambilModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title fw-bold" id="pengambilModalLabel">
                            Pengambil Barang
                        </h5>
                        <small class="text-muted">
                            Pilih orang yang mengambil barang dari gudang
                        </small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="pengambil_id" class="form-label fw-semibold">
                            Nama Pengambil
                        </label>
                        <select class="form-select" id="pengambil_id" name="pengambil_id" required>
                            <option>-- Pilih Pengambil --</option>
                        </select>
                        <span id="userpengambilbarang" class="text-danger" style="font-size: 11px; font-weight: 400;">
                        </span>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                        Batal
                    </button>
                    <button type="button" class="btn btn-primary" id="btnSiap">
                        <i class="ri-save-line me-1"></i>
                        Simpan
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection
@push('styles')
    <style>
        #detailModal .dt-length {
            margin-left: 16px;
            margin-top: 0 !important;
            padding-top: 0 !important;
        }

        #detailModal .dt-search {
            display: flex !important;
            align-items: center;
            justify-content: flex-end;
            padding: 0 12px !important;
            margin: 0 !important;
            gap: 6px;
        }

        #detailModal .dt-search label {
            font-size: 11px;
            color: #6c757d;
            margin: 0 !important;
            padding: 0 !important;
        }

        #detailModal .dt-search input {
            width: 160px !important;
            height: 30px;
            padding: 4px 9px;
            font-size: 11px;
            border: 1px solid #dee2e6;
            border-radius: 7px;
            background: #fff;
            outline: none;
            margin: 0 !important;
        }

        #detailModal .dt-search input:focus {
            border-color: #86b7fe;
            box-shadow: 0 0 0 2px rgba(13, 110, 253, 0.08);
        }

        #detailModal .dt-layout-row:first-child {
            align-items: center !important;
            margin: 0 !important;
            padding-top: 0 !important;
        }

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

        .status-stok {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 5px 8px;
            border-radius: 5px;
            font-size: 11px;
            font-weight: 600;
            white-space: nowrap;
        }

        .stok-tersedia {
            color: #198754;
            background: #eaf7f0;
            border: 1px solid #badbcc;
        }

        .stok-kurang {
            color: #dc3545;
            background: #fdecef;
            border: 1px solid #f5c2c7;
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

        .stok-live {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            color: #198754;
            background: #eaf7f0;
            border: 1px solid #badbcc;
            white-space: nowrap;
        }

        .stok-live-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #198754;
        }

        .stok-table thead th {
            padding-top: 12px !important;
            padding-bottom: 12px !important;
            font-size: 12px;
            font-weight: 700;
            color: #6c757d;
            white-space: nowrap;
            vertical-align: middle;
        }

        .stok-table tbody td {
            padding-top: 12px !important;
            padding-bottom: 12px !important;
            vertical-align: middle;
        }

        .stok-sku {
            font-size: 14px;
            font-weight: 700;
            color: #212529;
        }

        .stok-produk {
            font-size: 14px;
            font-weight: 600;
            color: #343a40;
        }

        .stok-value {
            font-size: 17px;
            font-weight: 700;
            color: #0d6efd;
        }

        .kebutuhan-value {
            font-size: 17px;
            font-weight: 700;
            color: #dc3545;
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

            $('#filterKategori').select2({
                theme: 'bootstrap-5',
                width: '30%',
                placeholder: $('#filterKategori').data('placeholder'),
                allowClear: true
            });

            
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

            $('#filterKategori').on('change', function() {
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
                        d.kategori = $('#filterKategori').val();
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
                        data: 'input_at',
                        name: 'input_at',
                        orderable: true,
                        searchable: true,
                        className: 'px-3 py-2',
                        render: function(data, type, row) {
                            const batasKirim = row.batas_kirim_at ?
                                new Date(row.batas_kirim_at) :
                                null;

                            const terlambat = batasKirim && new Date() > batasKirim;
                            return `
                                <div class="d-flex flex-column gap-1">

                                    <div class="d-flex align-items-center gap-2">
                                        <i class="fa-solid fa-receipt text-primary"
                                            style="font-size: 12px;"></i>

                                        <span class="fw-bold text-dark"
                                            style="font-size: 14px;">
                                            ${row.no_pesanan ?? '-'}
                                        </span>
                                    </div>

                                    <div class="d-flex flex-column">
                                        <div class="d-flex align-items-center gap-2 text-nowrap">

                                            <span class="text-muted fw-semibold"
                                                style="font-size: 10px;">
                                                <i class="fa-solid fa-file-import me-1"></i>
                                                Tanggal Import
                                            </span>

                                            <span class="fw-semibold text-primary"
                                                style="font-size: 11px;">

                                                ${
                                                    data
                                                        ? new Date(data)
                                                            .toLocaleString('id-ID', {
                                                                day: '2-digit',
                                                                month: 'short',
                                                                year: 'numeric',
                                                                hour: '2-digit',
                                                                minute: '2-digit',
                                                                hour12: false
                                                            })
                                                        : '-'
                                                }

                                            </span>

                                        </div>
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
                                                    row.batas_kirim_at
                                                        ? new Date(row.batas_kirim_at)
                                                            .toLocaleString('id-ID', {
                                                                day: '2-digit',
                                                                month: 'short',
                                                                year: 'numeric',
                                                                hour: '2-digit',
                                                                minute: '2-digit',
                                                                hour12: false
                                                            })
                                                        : '-'
                                                }

                                            </span>

                                        </div>
                                    </div>

                                    ${
                                        terlambat
                                            ? `
                                                                                                                                                                                                                                                                <div class="mt-1">
                                                                                                                                                                                                                                                                    <span
                                                                                                                                                                                                                                                                        class="badge bg-danger text-white"
                                                                                                                                                                                                                                                                        style="font-size: 9px;">

                                                                                                                                                                                                                                                                        <i class="fa-solid fa-triangle-exclamation me-1"></i>
                                                                                                                                                                                                                                                                        Terlambat

                                                                                                                                                                                                                                                                    </span>
                                                                                                                                                                                                                                                                </div>
                                                                                                                                                                                                                                                            `
                                            : ''
                                    }
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

                                        <i
                                            class="fa-solid fa-barcode text-secondary me-1"
                                            style="font-size: 11px;">
                                        </i>

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

                    // STOK
                    {
                        data: 'pesanan_per_produk',
                        name: 'status_stok',
                        orderable: true,
                        searchable: false,
                        className: 'px-2 py-0 text-center stok-cell',
                        render: function(data, type, row) {
                            let html = '';
                            const barang = Array.isArray(row.pesanan_per_produk) ?
                                row.pesanan_per_produk : [];

                            barang.forEach(function(item, index) {
                                const border = index > 0 ? 'border-top' : '';
                                const sku = String(item.sku ?? '')
                                    .trim()
                                    .toUpperCase();

                                // SKU karakter terakhir C
                                const skuCustom = sku.endsWith('C');

                                // Tersedia jika stok tersedia ATAU SKU custom
                                const tersedia = item.tersedia === true || skuCustom;

                                html += `
                                    <div
                                        class="product-row-sync stok-item ${border}
                                            justify-content-center"
                                    >

                                        ${
                                            tersedia
                                                ? `
                                                                                                                    <span class="status-stok stok-tersedia">
                                                                                                                        <i class="fa-solid fa-circle-check"></i>
                                                                                                                        Tersedia
                                                                                                                    </span>
                                                                                                                `
                                                : `
                                                                                                                    <span class="status-stok stok-kurang">
                                                                                                                        <i class="fa-solid fa-triangle-exclamation"></i>
                                                                                                                        Kurang
                                                                                                                    </span>
                                                                                                                `
                                        }

                                    </div>
                                `;
                            });

                            return html;
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
            $(document).on('click', '.pilih-checkbox', function() {
                const type = $(this).data('type');
                $('#orderlist tbody tr').each(function() {

                    const row = $(this);
                    const checkbox = row.find('.item-checkbox');

                    if (!checkbox.length) {
                        return;
                    }

                    // Ambil data row dari DataTable
                    const rowData = table.row(this).data();

                    const barang = Array.isArray(rowData?.pesanan_per_produk) ?
                        rowData.pesanan_per_produk : [];

                    // CEK APAKAH ADA SKU NON-C YANG STOKNYA KURANG
                    const adaKurangNonCustom = barang.some(function(item) {

                        const sku = String(item.sku ?? '')
                            .trim()
                            .toUpperCase();

                        const skuCustom = sku.endsWith('C');

                        // SKU berakhiran C tetap dianggap tersedia
                        if (skuCustom) {
                            return false;
                        }

                        // SKU biasa harus tersedia
                        return item.tersedia !== true;
                    });


                    // STATUS PESANAN
                    const tersedia =
                        barang.length > 0 &&
                        !adaKurangNonCustom;

                    let checked = false;

                    // PILIH SEMUA
                    if (type === 'all') {
                        checked = true;
                    }

                    // PILIH YANG TERSEDIA
                    else if (type === 'tersedia') {
                        checked = tersedia;
                    }

                    // PILIH YANG TIDAK TERSEDIA
                    else if (type === 'kurang') {
                        checked = !tersedia;
                    }

                    // KOSONGKAN
                    else if (type === 'clear') {
                        checked = false;
                    }

                    checkbox.prop('checked', checked);

                    row.toggleClass(
                        'table-active',
                        checked
                    );
                });

                updateButtonPengambil();
                updateStokLive();

            });

            // Klik Row
            $(document).on('click', '#orderlist tbody tr', function(e) {
                const row = $(this);
                const checkbox = row.find('.item-checkbox');

                if (!checkbox.length) {
                    return;
                }

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

            // Tabel Stok Live
            function updateStokLive() {
                const rekap = {};
                $('#orderlist tbody tr').each(function() {
                    const row = $(this);
                    const checkbox = row.find('.item-checkbox');

                    // Hanya hitung row yang dicentang
                    if (!checkbox.prop('checked')) {
                        return;
                    }

                    const rowData = table.row(this).data();

                    if (!rowData) {
                        return;
                    }

                    const barang = Array.isArray(rowData.pesanan_per_produk) ?
                        rowData.pesanan_per_produk : [];

                    barang.forEach(function(item) {
                        const sku = item.sku ?? '-';
                        if (!rekap[sku]) {
                            rekap[sku] = {
                                sku: sku,
                                nama_produk: item.nama_produk ?? '-',
                                variasi: item.variasi,
                                stok_awal: Number(item.stok_total ?? 0),
                                kebutuhan: 0
                            };
                        }


                        // Tambahkan qty pesanan
                        rekap[sku].kebutuhan += Number(
                            item.jumlah ?? 0
                        );

                    });

                });

                const data = Object.values(rekap);
                kebutuhan = Object.values(rekap);

                // Kalau tidak ada pesanan yang dipilih
                if (data.length === 0) {
                    $('#stokCard').addClass('d-none');
                    $('#stokLiveBody').empty();
                    return;
                }

                let html = '';
                data.forEach(function(item) {
                    // Stok setelah dikurangi semua kebutuhan terpilih
                    const stokSisa = Math.max(
                        0,
                        item.stok_awal - item.kebutuhan
                    );

                    const stokAwal = Math.max(
                        0,
                        item.stok_awal
                    );

                    html += `
                        <tr>
                            <td class="px-4">
                                <span class="stok-sku">
                                    ${item.sku}
                                </span>
                            </td>
                            <td class="px-4 text-start">
                                <span class="fw-semibold text-dark" style="font-size: 12px;">
                                    ${item.nama_produk ?? '-'}
                                </span>

                                <span class="text-muted mx-1" style="font-size: 12px;">
                                    •
                                </span>

                                <span class="text-secondary" style="font-size: 12px;">
                                    ${item.variasi ?? '-'}
                                </span>
                            </td>
                            <td class="px-4 text-center">
                                <span class="stok-value">
                                    ${stokSisa}
                                </span>
                                <div
                                    class="text-muted mt-1"
                                    style="font-size:10px;"
                                >
                                    Awal ${stokAwal}
                                </div>
                            </td>
                            <td class="px-4 text-center">
                                <span class="kebutuhan-value">
                                    ${item.kebutuhan}
                                </span>
                            </td>
                        </tr>
                    `;
                });

                $('#stokLiveBody').html(html);
                $('#stokCard').removeClass('d-none');
            }

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

            // Cetak Resi
            let pesananExport = [];
            let kebutuhanExport = [];

            // Buka Modal Cetak Resi
            $('#cetakResi').on('click', function() {
                const selected = $('.item-checkbox:checked');
                if (selected.length === 0) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Belum Ada Barang',
                        text: 'Silakan pilih barang terlebih dahulu.'
                    });
                    return;
                }

                // Ambil semua no pesanan yang dicentang
                pesananExport = selected
                    .map(function() {
                        return String($(this).attr('data-no-pesanan'));
                    })
                    .get();

                // Ambil kebutuhan saat ini
                kebutuhanExport = kebutuhan;

                // Reset pilihan alasan
                $('#alasanExport')
                    .val('')
                    .removeClass('is-invalid');

                // Tampilkan jumlah pesanan jika diperlukan
                $('#jumlahPesananExport').text(
                    pesananExport.length
                );

                // Buka modal
                const modal = new bootstrap.Modal(
                    document.getElementById('modalAlasanExport')
                );

                modal.show();

            });

            // Proses Cetak Resi
            $('#btnProsesExport').on('click', function() {
                const alasanExport = $('#alasanExport').val();
                // Validasi alasan
                if (!alasanExport) {
                    $('#alasanExport')
                        .addClass('is-invalid')
                        .focus();

                    return;
                }

                $('#alasanExport').removeClass('is-invalid');
                const button = $(this);

                // Disable tombol saat proses
                button
                    .prop('disabled', true)
                    .html(`
                        <span class="spinner-border spinner-border-sm me-1"></span>
                        Memproses...
                    `);

                $.ajax({
                    type: "POST",
                    url: "{{ route('transaksi.cetak-resi') }}",
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },

                    contentType: "application/json",
                    dataType: "json",
                    data: JSON.stringify({
                        pesanan: pesananExport,
                        kebutuhan: kebutuhanExport,
                        alasan_export: alasanExport
                    }),

                    beforeSend: function() {
                        Swal.fire({
                            title: 'Memproses Resi',
                            html: `
                                <div class="text-muted">
                                    Sedang menggabungkan beberapa resi...
                                </div>
                            `,
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                            showConfirmButton: false,

                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });
                    },
                    success: function(response) {
                        const modalEl = document.getElementById('modalAlasanExport');
                        const modal = bootstrap.Modal.getInstance(modalEl);

                        modal?.hide();
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil',
                            text: response.message ??
                                'Resi berhasil diproses.'
                        });

                        if (response.success && response.preview_url) {
                            window.open(
                                response.preview_url,
                                '_blank'
                            );

                            return;
                        }
                    },
                    error: function(xhr) {
                        const response = xhr.responseJSON;
                        if (xhr.status === 422 && response?.data) {
                            const rows = response.data
                                .map((item, index) => `
                                    <tr>
                                        <td>${index + 1}</td>
                                        <td>${item ?? '-'}</td>
                                        ${index === 0 ? `
                                                                <td rowspan="${response.data.length}" class="text-center align-middle">
                                                                    <button
                                                                        type="button"
                                                                        id="copy-pesanan"
                                                                        class="btn btn-primary btn-sm">
                                                                        Copy Semua No. Pesanan
                                                                    </button>
                                                                </td>
                                                            ` : ''}
                                    </tr>
                                `)
                                .join('');

                            Swal.fire({
                                icon: 'warning',
                                title: 'Pemberitahuan',
                                width: 700,

                                html: `
                                    <div class="text-center mb-3">
                                        ${response.message ?? 'Beberapa pesanan belum memiliki resi.'}
                                    </div>

                                    <div class="table-responsive">
                                        <table
                                            id="table-pesanan-belum-resi"
                                            class="table table-bordered table-striped table-sm">
                                            <thead>
                                                <tr>
                                                    <th style="width:60px;">No</th>
                                                    <th>No. Pesanan</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                ${rows}
                                            </tbody>
                                        </table>
                                    </div>
                                `,

                                confirmButtonText: 'OK',
                                didOpen: () => {
                                    $('#copy-pesanan').on('click', function() {
                                        let nomorPesanan = [];
                                        $('#table-pesanan-belum-resi tbody tr')
                                            .each(function() {
                                                let nomor = $(this)
                                                    .find('td:eq(1)')
                                                    .text()
                                                    .trim();
                                                if (nomor) {
                                                    nomorPesanan.push(
                                                        nomor);
                                                }

                                            });

                                        let text = nomorPesanan.join('\n');
                                        if (!text) {
                                            alert(
                                                'Data nomor pesanan kosong'
                                            );
                                            return;
                                        }

                                        // Event copy sementara
                                        const copyHandler = function(e) {
                                            e.preventDefault();
                                            e.clipboardData.setData(
                                                'text/plain',
                                                text
                                            );

                                        };

                                        document.addEventListener('copy',
                                            copyHandler);

                                        const berhasil = document
                                            .execCommand('copy');

                                        document.removeEventListener('copy',
                                            copyHandler);

                                        if (berhasil) {
                                            $(this)
                                                .removeClass('btn-primary')
                                                .addClass('btn-success')
                                                .text('Berhasil Dicopy');
                                        } else {
                                            alert('Gagal copy');
                                        }
                                    });
                                }
                            });

                            return;
                        }

                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal',
                            text: response?.message ??
                                'Terjadi kesalahan saat memproses resi.'
                        });
                    },
                    complete: function() {
                        button
                            .prop('disabled', false)
                            .html(`
                                <i class="fa-solid fa-file-export me-1"></i>
                                Export Resi
                            `);
                    }
                });

            });

            $('#pengambil_id').select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: 'Pilih Pengambil',
                allowClear: true,
                dropdownParent: $('#pengambilModal')
            });

            // Menangani Pengambilan User Sesuai Role
            $('#pengambilModal').on('shown.bs.modal', function(event) {
                const button = $(event.relatedTarget);
                const role = button.data('role');
                pengambilbarang = null;
                $('#pengambil_id').html(`
                    <option value="">Memuat data...</option>
                `);
                $.ajax({
                    type: "GET",
                    url: "{{ route('user.data', ':role') }}".replace(':role', role),
                    dataType: "json",
                    success: function(response) {
                        let option = `
                                <option value="">
                                    -- Pilih Pengambil --
                                </option>
                            `;
                        $.each(response, function(index, user) {
                            option += `
                                <option value="${user.id}">
                                    ${user.name}
                                </option>
                            `;
                        });
                        $('#pengambil_id').html(option);
                    },
                    error: function(xhr) {
                        console.log(xhr.responseText);
                        $('#pengambil_id').html(`
                            <option value="">
                                Gagal memuat data
                            </option>
                        `);
                    }
                });
            });

            // Mengambil Field Pengambil Barang
            $('#pengambil_id').on('change', function() {
                pengambilbarang = $(this).val() || null;
            });

            function updateButtonPengambil() {
                const adaYangDipilih = $('.item-checkbox:checked').length > 0;
                $('#btnPengambilModal').prop('disabled', !adaYangDipilih);
            }

            // Submit halaman Siap
            $('#btnSiap').on('click', function() {
                const list = $('.item-checkbox:checked');
                const kebutuhanPesanan = kebutuhan;

                if (list.length === 0) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Belum Ada Barang',
                        text: 'Silakan pilih barang terlebih dahulu.'
                    });
                    return;
                }

                const noPesanan = list.map(function() {
                    return $(this).data('no-pesanan');
                }).get();

                if (pengambilbarang == null) {
                    $('#userpengambilbarang').html('Pengambil barang tidak boleh kosong');
                } else {
                    $.ajax({
                        type: "POST",
                        url: "{{ route('transaksi.updatestatus') }}",
                        data: {
                            _token: "{{ csrf_token() }}",
                            no_pesanan: noPesanan,
                            pengambil_barang: pengambilbarang,
                            kebutuhan: kebutuhanPesanan
                        },
                        dataType: "JSON",
                        success: function(response) {
                            if (response.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Berhasil',
                                    text: response.message,
                                    timer: 1800,
                                    showConfirmButton: false
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Gagal',
                                    text: response.message ??
                                        'Terjadi kesalahan.'
                                });
                            }
                        },
                        error: function(xhr) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal',
                                text: xhr.responseJSON?.message ??
                                    'Terjadi kesalahan saat memproses data.'
                            });
                        }
                    });
                }
            });
        });
    </script>
@endpush
