@extends('layouts.app')
@section('content')
    <main class="flex-grow-1 overflow-auto p-3 p-lg-4">
        <!-- Dashboard Title & Pulse Badge -->
        <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mb-4">

            <div>
                <h1 class="h3 fw-bold text-dark mb-1">
                    PERLU DIKIRIM HARI INI
                </h1>

                <span class="text-muted">
                    <i class="fa-solid fa-box"></i>
                    Pesanan
                </span>

                <span class="mx-2 text-secondary">/</span>

                <span class="fw-semibold text-primary">
                    Perlu diKirim
                </span>
            </div>
        </div>

        <!-- Inventory Stock Table Section -->
        <section id="stockTableSection" class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
            <div
                class="card-header bg-white border-bottom p-3 p-md-4 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                <div>
                    <h2 class="h5 fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                        <i class="fa-solid fa-box"></i>
                        Daftar Semua Pesanan
                    </h2>
                    <p class="text-muted small mb-0">
                        Menampilkan Semua Pesanan.
                    </p>
                </div>

                <!-- Controls: Filters & Table Search -->
                <div class="d-flex flex-nowrap align-items-center gap-2">

                    <div class="input-group input-group-sm" style="max-width: 240px;">
                        <span class="input-group-text bg-light border-end-0">
                            <i class="fa-solid fa-magnifying-glass text-muted"></i>
                        </span>

                        <input type="text" id="searchTable" placeholder="Cari SKU / Produk..."
                            class="form-control form-control-sm border-start-0 bg-light">
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
                <table class="table table-hover align-middle mb-0 text-nowrap" id="orderlist">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" style="min-width: 150px;">
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-light border-0 fw-semibold dropdown-toggle" type="button"
                                        id="dropdownStatusKirim" data-bs-toggle="dropdown" data-bs-auto-close="true"
                                        aria-expanded="false">

                                        <i id="iconFilterKirim" class="fa-solid fa-filter me-1 text-secondary"></i>

                                        <span id="labelFilterKirim">
                                            Batas Kirim
                                        </span>
                                    </button>

                                    <ul class="dropdown-menu shadow-sm border-0" aria-labelledby="dropdownStatusKirim">

                                        <li>
                                            <button type="button" class="dropdown-item filter-status-kirim active"
                                                data-status="">

                                                <i class="fa-solid fa-list me-2 text-secondary"></i>
                                                Semua
                                            </button>
                                        </li>

                                        <li>
                                            <button type="button" class="dropdown-item filter-status-kirim"
                                                data-status="aman">

                                                <i class="fa-solid fa-circle-check me-2 text-success"></i>
                                                Aman
                                            </button>
                                        </li>

                                        <li>
                                            <button type="button" class="dropdown-item filter-status-kirim"
                                                data-status="terlambat">

                                                <i class="fa-solid fa-circle-exclamation me-2 text-danger"></i>
                                                Terlambat
                                            </button>
                                        </li>

                                        <li>
                                            <button type="button" class="dropdown-item filter-status-kirim"
                                                data-status="berisiko">

                                                <i class="fa-solid fa-triangle-exclamation me-2 text-warning"></i>
                                                Berisiko
                                            </button>
                                        </li>

                                    </ul>
                                </div>
                            </th>

                            <th class="text-center" style="min-width:160px">
                                No. Pesanan
                            </th>

                            <th class="text-end" style="min-width:120px">
                                Total Pesanan
                            </th>

                            <th class="text-center" style="min-width:160px">
                                Nama Toko
                            </th>

                            <th class="text-center" style="min-width:180px">
                                Pengiriman
                            </th>

                            <th class="text-center" style="min-width:150px">
                                Aksi
                            </th>
                        </tr>
                    </thead>
                    <tbody id="produkBody">

                    </tbody>
                </table>
            </div>
        </section>
    </main>

    <!-- Footer -->
    @include('layouts.footer')
@endsection
@push('scripts')
    <script>
        $(document).ready(function() {

            let filterStatusKirim = '';
            let table = $('#orderlist').DataTable({
                ajax: {
                    type: "GET",
                    url: "{{ route('pesanan.perludikirim.json') }}",
                    dataSrc: function(response) {
                        if (!response.success || !response.data) {
                            return [];
                        }

                        return response.data.map(function(items) {
                            return {
                                tanggal: items.batas_kirim_at ?? null,
                                status_kirim: items.status_kirim ?? null,
                                no_pesanan: items.no_pesanan ?? '-',
                                no_resi: items.no_resi ?? '-',
                                total_pesanan: items.total_harga ?? 0,
                                toko: items.toko?.nama_toko ?? '-',
                                pengiriman: items.kurir ?? '-'
                            };

                        });
                    }
                },

                order: [],
                searching: true,
                lengthChange: false,
                pageLength: 10,
                dom: `
                    rt
                    <"d-flex justify-content-between align-items-center px-3 py-2"
                        i
                        p
                    >
                `,
                columns: [{
                        data: 'tanggal',
                        orderable: false,
                        searchable: true,
                        className: 'text-center',
                        render: function(data, type, row, meta) {
                            if (!data) {
                                return `
                                    <div class="d-flex flex-column align-items-center">
                                        <span class="text-muted mb-1" style="font-size:12px;">
                                            Belum Import Resi
                                        </span>
                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle
                                            rounded-pill px-3 py-1">
                                            <i class="fa-solid fa-triangle-exclamation me-1"></i>
                                            Berisiko
                                        </span>
                                    </div>
                                `;
                            }

                            const tanggal = new Date(data);
                            if (isNaN(tanggal.getTime())) {
                                return `
                                    <div class="d-flex flex-column align-items-center">
                                        <span class="text-muted mb-1" style="font-size:12px;">
                                            Belum Import Resi
                                        </span>
                                        <span class="badge bg-warning-subtle text-warning border border-warning-subtle
                                            rounded-pill px-3 py-1">
                                            <i class="fa-solid fa-triangle-exclamation me-1"></i>
                                            Berisiko
                                        </span>
                                    </div>
                                `;
                            }

                            const tanggalFormat =
                                new Intl.DateTimeFormat('id-ID', {
                                    timeZone: 'Asia/Jakarta',
                                    day: '2-digit',
                                    month: 'short',
                                    year: 'numeric'
                                }).format(tanggal);

                            const jamFormat =
                                new Intl.DateTimeFormat('id-ID', {
                                    timeZone: 'Asia/Jakarta',
                                    hour: '2-digit',
                                    minute: '2-digit',
                                    hour12: false
                                }).format(tanggal);

                            let statusBadge = '';
                            if (row.status_kirim === 'terlambat') {
                                statusBadge = `
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill
                                        px-3 py-1 mt-1">
                                        <i class="fa-solid fa-circle-exclamation me-1"></i>
                                        Terlambat
                                    </span>
                                `;
                            } else {
                                statusBadge = `
                                    <span class="badge bg-success-subtle text-success border
                                        border-success-subtle rounded-pill px-3 py-1 mt-1">
                                        <i class="fa-solid fa-circle-check me-1"></i>
                                        Aman
                                    </span>
                                `;
                            }

                            return `
                                <div class="d-flex flex-column align-items-center">
                                    <div class="fw-bold text-dark" style="font-size:13px;">
                                        ${tanggalFormat}
                                    </div>
                                    <small class="text-muted" style="font-size:12px;">
                                        <i class="fa-regular fa-clock me-1"></i>
                                        ${jamFormat} WIB
                                    </small>
                                    ${statusBadge}
                                </div>
                            `;
                        }
                    },
                    {
                        data: 'no_pesanan',
                        name: 'no_pesanan',
                        orderable: false,
                        searchable: true,
                        className: 'text-center',
                        render: function(data, type, row) {
                            if (!data) {
                                return `
                                    <span class="text-muted"> - </span>
                                `;
                            }

                            return `
                                <div class="d-flex flex-column align-items-center">
                                    <span class="fw-semibold text-dark">
                                        ${data}
                                    </span>
                                    <small class="text-muted">
                                        <i class="fa-solid fa-receipt me-1"></i>
                                        No. Pesanan
                                    </small>
                                </div>
                            `;
                        }
                    },
                    {
                        data: 'total_pesanan',
                        name: 'total_pesanan',
                        orderable: false,
                        searchable: false,
                        className: 'text-center',
                        render: function(data, type, row) {
                            const total = Number(data ?? 0).toLocaleString('id-ID');
                            return `
                                <div class="d-flex flex-column align-items-center">
                                    <span class="fw-bold text-dark fs-6">Rp ${total}</span>
                                    <small class="text-muted">Total Pesanan</small>
                                </div>
                            `;
                        }
                    },
                    {
                        data: 'toko',
                        orderable: false,
                        searchable: true,
                        className: 'text-center',
                        render: function(data, type, row) {
                            if (!data) {
                                return `
                                    <span class="text-muted">
                                        -
                                    </span>
                                `;
                            }

                            return `
                                <div class="d-flex align-items-center justify-content-center gap-2">
                                    <i class="fa-solid fa-store text-primary"></i>

                                    <span class="fw-semibold text-dark">
                                        ${data}
                                    </span>

                                    <small class="text-muted">
                                        Marketplace
                                    </small>
                                </div>
                            `;
                        }
                    },
                    {
                        data: 'pengiriman',
                        orderable: false,
                        searchable: true,
                        className: 'text-center',
                        render: function(data, type, row) {
                            const pengiriman = data ?? '-';
                            const noResi = row.no_resi ?? '-';

                            return `
                                <div class="d-flex flex-column align-items-center">
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 rounded-pill">
                                        <i class="fa-solid fa-truck-fast me-1"></i>
                                        ${pengiriman}
                                    </span>
                                    <small class="text-muted mt-1">
                                        <i class="fa-solid fa-barcode me-1"></i>
                                        ${noResi}
                                    </small>
                                </div>
                            `;
                        }
                    },
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        className: 'text-center',
                        render: function(data, type, row) {
                            const url = "{{ route('pesanan.show', ':no_pesanan') }}".replace(':no_pesanan', row.no_pesanan);

                            return `
                                <a href="${url}" target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-file-earmark-text me-1"></i>
                                    Rincian
                                </a>
                            `;
                        }
                    }

                ]

            });

            $.fn.dataTable.ext.search.push(
                function(settings, searchData, dataIndex) {
                    if (!filterStatusKirim) {
                        return true;
                    }
                    const rowData = table.row(dataIndex).data();
                    if (!rowData) {
                        return true;
                    }

                    if (filterStatusKirim === 'berisiko') {
                        return !rowData.tanggal;
                    }

                    if (filterStatusKirim === 'terlambat') {
                        return (
                            rowData.status_kirim === 'terlambat'
                        );
                    }

                    if (filterStatusKirim === 'aman') {
                        return (!!rowData.tanggal && rowData.status_kirim !== 'terlambat');
                    }

                    return true;
                }
            );

            $(document).on('click', '.filter-status-kirim', function(e) {
                    e.preventDefault();
                    filterStatusKirim = $(this).data('status') ?? '';
                    $('.filter-status-kirim').removeClass('active');
                    $(this).addClass('active');

                    const icon = $('#iconFilterKirim');
                    icon.removeClass(
                        'fa-filter ' +
                        'fa-circle-check ' +
                        'fa-circle-exclamation ' +
                        'fa-triangle-exclamation ' +
                        'text-secondary ' +
                        'text-success ' +
                        'text-danger ' +
                        'text-warning'
                    );

                    if (!filterStatusKirim) {
                        $('#labelFilterKirim').text('Batas Kirim');
                        icon.addClass(
                            'fa-filter text-secondary'
                        );
                    }


                    else if (filterStatusKirim === 'aman') {
                        $('#labelFilterKirim')
                            .text('Aman');

                        icon.addClass(
                            'fa-circle-check text-success'
                        );
                    }

                    else if (filterStatusKirim === 'terlambat') {
                        $('#labelFilterKirim').text('Terlambat');
                        icon.addClass('fa-circle-exclamation text-danger');
                    }

                    else if (filterStatusKirim === 'berisiko') {
                        $('#labelFilterKirim').text('Berisiko');
                        icon.addClass('fa-triangle-exclamation text-warning');
                    }

                    table.draw();
                }
            );

            $('#searchTable').on('input', function() {
                    table
                        .search(this.value)
                        .draw();
                }
            );

            $('#per_page').on('change', function() {
                    table
                        .page
                        .len(parseInt(this.value))
                        .draw();
                }
            );

        });
    </script>
@endpush
