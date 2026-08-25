@extends('layouts.app')
@section('content')
    <main class="flex-grow-1 overflow-auto p-3 p-lg-4">

        <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mb-4">
            <div>
                <h1 class="h3 fw-bold text-dark mb-1">
                    Daftar Produk Custom
                </h1>
                <span class="text-muted">
                    <i class="fa-solid fa-pen-ruler"></i>
                    Produk Custom
                </span>
                <span class="mx-2 text-secondary">/</span>
                <span class="fw-semibold text-primary">
                    Semua Produk
                </span>
            </div>
        </div>

        <!-- Inventory Stock Table Section -->
        <section id="stockTableSection" class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
            <!-- Table Header Bar -->
            <div
                class="card-header bg-white border-bottom p-3 p-md-4 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                <div>
                    <h2 class="h5 fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                        <i class="fa-solid fa-pen-ruler"></i>
                        Produk Custom
                    </h2>
                    <p class="text-muted small mb-0">
                        Menampilkan semua produk custom.
                    </p>
                </div>

                <!-- Controls: Filters & Table Search -->
                <div class="d-flex flex-nowrap align-items-center gap-2">
                    <!-- Table Search Input -->
                    <div class="input-group input-group-sm" style="max-width: 240px;">
                        <span class="input-group-text bg-light border-end-0">
                            <i class="fa-solid fa-magnifying-glass text-muted"></i>
                        </span>
                        <input type="text" id="searchTable" placeholder="Cari data"
                            class="form-control form-control-sm border-start-0 bg-light" />
                    </div>

                    <div class="input-group input-group-sm" style="width: 280px;">
                        <span class="input-group-text">
                            <i class="fa-solid fa-calendar-days"></i>
                        </span>
                        <div id="reportrange" class="form-control d-flex align-items-center justify-content-between"
                            style="cursor: pointer;">
                            <span>Pilih periode</span>
                            <i class="fa-solid fa-caret-down ms-2"></i>
                        </div>
                        <button type="button" class="btn btn-outline-secondary" id="resetDate">

                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>

                    <select id="per_page" class="form-select form-select-sm" style="width: auto;">
                        <option value="10" selected>10</option>
                        <option value="20">20</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
            </div>

            <table class="table table-hover align-middle mb-0 text-nowrap" id="orderlist">
                <thead>
                    <tr>
                        <th width="50">No</th>
                        <th>Produk Custom</th>
                        <th>Detail Produk</th>
                        <th class="text-center">Qty</th>
                        <th class="text-center">Status Pesanan</th>
                        <th>Tanggal</th>
                        <th>Aksi</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($data as $item)
                        <tr data-start="{{ $item['tanggal_awal']->format('Y-m-d') }}"
                            data-end="{{ $item['tanggal_akhir']->format('Y-m-d') }}">
                            <td class="text-center">
                                <span class="text-muted fw-semibold">
                                    {{ $loop->iteration }}
                                </span>
                            </td>
                            <td>
                                <div class="fw-semibold text-dark mb-1">
                                    {{ \Illuminate\Support\Str::words($item['nama_produk'], 3, '...') }}
                                </div>

                                <div class="d-flex align-items-center gap-2 mt-1">
                                    <span class="text-muted small fw-semibold">
                                        SKU :
                                    </span>

                                    <span class="badge bg-light text-primary border px-2 py-1">
                                        <i class="fa-solid fa-barcode me-1"></i>
                                        {{ $item['sku'] }}
                                    </span>
                                </div>
                            </td>

                            <td>
                                <div class="text-dark">
                                    {{ $item['variasi'] ?: '-' }}
                                </div>
                            </td>

                            <td class="text-center">
                                <span class="badge rounded-pill bg-primary px-3 py-2">
                                    {{ number_format($item['qty']) }} items
                                </span>
                            </td>

                            <td>
                                <div class="d-flex flex-column gap-1">
                                    <div class="d-flex align-items-center justify-content-between gap-3">
                                        <span class="text-muted">
                                            <i class="fa-solid fa-clock text-warning me-1"></i>
                                            Diproses
                                        </span>
                                        <span class="badge bg-warning text-dark">
                                            {{ $item['diproses'] }}
                                        </span>
                                    </div>
                                    <div class="d-flex align-items-center justify-content-between gap-3">
                                        <span class="text-muted">
                                            <i class="fa-solid fa-truck text-primary me-1"></i>
                                            Dikirim
                                        </span>
                                        <span class="badge bg-primary">
                                            {{ $item['kirim'] }}
                                        </span>
                                    </div>
                                    <div class="d-flex align-items-center justify-content-between gap-3">
                                        <span class="text-muted">
                                            <i class="fa-solid fa-circle-check text-success me-1"></i>
                                            Selesai
                                        </span>
                                        <span class="badge bg-success">
                                            {{ $item['selesai'] }}
                                        </span>
                                    </div>
                                    <div class="d-flex align-items-center justify-content-between gap-3">
                                        <span class="text-muted">
                                            <i class="fa-solid fa-rotate-left text-danger me-1"></i>
                                            Retur
                                        </span>
                                        <span class="badge bg-danger">
                                            {{ $item['retur'] }}
                                        </span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="bg-light border rounded p-2 text-center">
                                        <i class="fa-regular fa-calendar text-primary"></i>
                                    </div>
                                    <div>
                                        <div class="fw-semibold">
                                            {{ $item['tanggal_awal']->translatedFormat('j') }}
                                            -
                                            {{ $item['tanggal_akhir']->translatedFormat('j F Y') }}
                                        </div>

                                        <small class="text-muted">
                                            Periode bulanan
                                        </small>
                                    </div>

                                </div>
                            </td>
                            <td>
                                <button type="button" class="btn btn-sm btn-outline-primary btn-view"
                                    data-bs-toggle="modal" data-bs-target="#modalDetailProduk"
                                    data-sku="{{ $item['sku'] }}" data-nama="{{ $item['nama_produk'] }}"
                                    data-variasi="{{ $item['variasi'] }}" data-bulan="{{ $item['bulan'] }}"
                                    data-tahun="{{ $item['tahun'] }}" data-detail='@json($item['data'])'>
                                    <i class="fa-solid fa-eye me-1"></i>
                                    Detail
                                </button>
                            </td>
                        </tr>
                    @empty
                        <script>
                            nodata();
                        </script>
                    @endforelse
                </tbody>
            </table>
        </section>
    </main>

    <!-- Footer -->
    @include('layouts.footer')

    <div class="modal fade" id="modalDetailProduk" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">

                <div class="modal-header">
                    <div>
                        <h5 class="modal-title mb-1">
                            Detail Produk Custom
                        </h5>

                        <div class="text-muted small">
                            <span id="detailNamaProduk">-</span>
                            <span class="mx-1">•</span>
                            <span id="detailSku">-</span>
                        </div>
                    </div>

                    <button type="button" class="btn-close" data-bs-dismiss="modal">
                    </button>
                </div>

                <div class="modal-body">

                    {{-- Informasi Produk --}}
                    <div class="row g-3 mb-4">

                        <div class="col-md-4">
                            <div class="border rounded-3 p-3 h-100">
                                <div class="text-muted small mb-1">
                                    SKU
                                </div>

                                <div class="fw-semibold" id="infoSku">
                                    -
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="border rounded-3 p-3 h-100">
                                <div class="text-muted small mb-1">
                                    Variasi
                                </div>

                                <div class="fw-semibold" id="infoVariasi">
                                    -
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="border rounded-3 p-3 h-100">
                                <div class="text-muted small mb-1">
                                    Periode
                                </div>

                                <div class="fw-semibold" id="infoPeriode">
                                    -
                                </div>
                            </div>
                        </div>

                    </div>

                    {{-- Table Detail --}}
                    <div class="table-responsive">
                        <table class="table table-hover align-middle text-nowrap">
                            <thead class="table-light">
                                <tr>
                                    <th width="50">No</th>
                                    <th>No Pesanan</th>
                                    <th>Pembeli</th>
                                    <th>Produk</th>
                                    <th>Variasi</th>
                                    <th class="text-center">Qty</th>
                                    <th>Status</th>
                                    <th>Tanggal</th>
                                </tr>
                            </thead>

                            <tbody id="detailProdukBody">
                            </tbody>
                        </table>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                        Tutup
                    </button>
                </div>

            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        #orderlist tbody tr {
            transition: all .15s ease;
        }

        #orderlist tbody tr:hover {
            background-color: #f8f9fa;
        }

        #orderlist td {
            padding-top: 14px;
            padding-bottom: 14px;
        }

        #orderlist .badge {
            font-weight: 500;
        }

        #orderlist th {
            font-size: 13px;
            font-weight: 600;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: .3px;
        }
    </style>
@endpush

@push('scripts')
    <script>
        $(document).ready(function() {
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

            $(document).on('click', '.btn-view', function() {
                const sku = $(this).data('sku');
                const nama = $(this).data('nama');
                const variasi = $(this).data('variasi');
                const bulan = $(this).data('bulan');
                const tahun = $(this).data('tahun');

                let detail = $(this).attr('data-detail');

                try {
                    detail = JSON.parse(detail);
                } catch (e) {
                    detail = [];
                }

                $('#detailSku').text(sku);
                $('#detailNamaProduk').text(nama);
                $('#infoSku').text(sku);
                $('#infoVariasi').text(variasi || '-');

                const namaBulan = [
                    '',
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
                ];

                $('#infoPeriode').text(
                    namaBulan[bulan] + ' ' + tahun
                );

                let html = '';

                if (!detail.length) {
                    html = `
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                Tidak ada detail data.
                            </td>
                        </tr>
                    `;
                } else {

                    detail.forEach((item, index) => {

                        const pesanan = item.pesanan ?? {};

                        const tanggal = pesanan.tanggal ?
                            formatTanggal(pesanan.tanggal) :
                            '-';

                        html += `
                            <tr>
                                <td class="text-center text-muted">
                                    ${index + 1}
                                </td>

                                <td>
                                    <div class="fw-semibold">
                                        ${item.no_pesanan ?? '-'}
                                    </div>
                                </td>

                                <td>
                                    <div>
                                        ${pesanan.nama_pembeli ?? '-'}
                                    </div>

                                    <small class="text-muted">
                                        ${pesanan.username ?? ''}
                                    </small>
                                </td>

                                <td>
                                    ${wrapWords(escapeHtml(item.nama_produk ?? '-'), 5)}
                                </td>

                                <td>
                                    ${escapeHtml(item.variasi ?? '-')}
                                </td>

                                <td class="text-center">
                                    <span class="badge bg-light text-dark border">
                                        ${item.jumlah ?? 0}
                                    </span>
                                </td>

                                <td>
                                    ${statusBadge(pesanan.status)}
                                </td>

                                <td>
                                    ${tanggal}
                                </td>
                            </tr>
                        `;
                    });
                }

                $('#detailProdukBody').html(html);

                function wrapWords(text, limit = 5) {
                    if (!text) return '-';

                    const words = text.split(' ');
                    let result = [];

                    for (let i = 0; i < words.length; i += limit) {
                        result.push(words.slice(i, i + limit).join(' '));
                    }

                    return result.join('<br>');
                }

                function statusBadge(status) {

                    switch (status) {

                        case 'proses':
                            return `
                                <span class="badge bg-warning text-dark">
                                    <i class="fa-solid fa-clock me-1"></i>
                                    Diproses
                                </span>
                            `;

                        case 'dikirim':
                            return `
                                <span class="badge bg-primary">
                                    <i class="fa-solid fa-truck me-1"></i>
                                    Dikirim
                                </span>
                            `;

                        case 'retur':
                            return `
                                <span class="badge bg-danger">
                                    <i class="fa-solid fa-rotate-left me-1"></i>
                                    Retur
                                </span>
                            `;

                        case 'selesai':
                            return `
                                <span class="badge bg-success">
                                    <i class="fa-solid fa-circle-check me-1"></i>
                                    Selesai
                                </span>
                            `;

                        default:
                            return `
                                <span class="badge bg-secondary">
                                    ${status ?? '-'}
                                </span>
                            `;
                    }
                }

                function formatTanggal(tanggal) {
                    const date = new Date(tanggal);
                    return date.toLocaleDateString('id-ID', {
                        day: '2-digit',
                        month: 'short',
                        year: 'numeric'
                    });
                }

                function escapeHtml(text) {
                    return $('<div>')
                        .text(text ?? '')
                        .html();
                }
            });

            let filterStartDate = null;
            let filterEndDate = null;


            /*
            |--------------------------------------------------------------------------
            | Filter DataTable berdasarkan periode
            |--------------------------------------------------------------------------
            */
            function filterTableBulan() {
                table.search.fixed('tanggal', function(searchStr, rowData, rowIdx) {
                    // Kalau filter belum dipilih
                    if (!filterStartDate || !filterEndDate) {
                        return true;
                    }

                    const row = table.row(rowIdx).node();

                    if (!row) {
                        return true;
                    }

                    const rowStart = row.getAttribute('data-start');
                    const rowEnd = row.getAttribute('data-end');

                    if (!rowStart || !rowEnd) {
                        return true;
                    }

                    const start = moment(rowStart, 'YYYY-MM-DD');
                    const end = moment(rowEnd, 'YYYY-MM-DD');

                    return (
                        start.isSameOrBefore(filterEndDate, 'day') &&
                        end.isSameOrAfter(filterStartDate, 'day')
                    );
                });

                table.draw();
            }


            /*
            |--------------------------------------------------------------------------
            | Date Range
            |--------------------------------------------------------------------------
            */
            $('#reportrange').daterangepicker({
                startDate: moment().startOf('month'),
                endDate: moment().endOf('month'),
                autoUpdateInput: false,
                ranges: {
                    'Bulan Ini': [
                        moment().startOf('month'),
                        moment().endOf('month')
                    ],

                    'Bulan Lalu': [
                        moment().subtract(1, 'month').startOf('month'),
                        moment().subtract(1, 'month').endOf('month')
                    ],

                    '3 Bulan Terakhir': [
                        moment().subtract(2, 'month').startOf('month'),
                        moment().endOf('month')
                    ],

                    '6 Bulan Terakhir': [
                        moment().subtract(5, 'month').startOf('month'),
                        moment().endOf('month')
                    ]
                },

                locale: {
                    applyLabel: 'Pilih',
                    cancelLabel: 'Batal',
                    customRangeLabel: 'Pilih Periode'
                }

            }, function(start, end) {
                // Karena datamu bulanan,
                // paksa mulai dari awal bulan sampai akhir bulan
                filterStartDate = start.clone().startOf('month');
                filterEndDate = end.clone().endOf('month');
                let text;

                // Kalau bulan awal dan akhir sama
                if (
                    filterStartDate.format('YYYY-MM') ===
                    filterEndDate.format('YYYY-MM')
                ) {

                    text = filterStartDate
                        .clone()
                        .locale('id')
                        .format('MMMM YYYY');

                } else {

                    text =
                        filterStartDate
                        .clone()
                        .locale('id')
                        .format('MMMM YYYY') +
                        ' - ' +
                        filterEndDate
                        .clone()
                        .locale('id')
                        .format('MMMM YYYY');
                }

                $('#reportrange span').text(text);

                filterTableBulan();
            });


            /*
            |--------------------------------------------------------------------------
            | Reset
            |--------------------------------------------------------------------------
            */
            $('#resetDate').on('click', function() {

                filterStartDate = null;
                filterEndDate = null;

                $('#reportrange span').text('Pilih periode');

                // Hapus hanya filter periode
                table.search.fixed('tanggal', null);

                table.draw();
            });

            function nodata() {
                $('#orderlist').DataTable({
                    language: {
                        emptyTable: `
                                        <div class="text-muted py-4">
                                            <i class="fa-solid fa-box-open fa-2x mb-3 opacity-50"></i>

                                            <div class="fw-semibold">
                                                Tidak ada data
                                            </div>

                                            <small>
                                                Data produk custom belum tersedia.
                                            </small>
                                        </div>
                                    `
                    }
                });
            }
        });
    </script>
@endpush
