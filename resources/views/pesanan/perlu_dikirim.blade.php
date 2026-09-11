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
                            <th class="text-center" style="min-width:110px">Batas Kirim</th>
                            <th class="text-center" style="min-width:160px">No. Pesanan</th>
                            <th class="text-end" style="min-width:120px">Total Pesanan</th>
                            <th class="text-center" style="min-width:160px">Nama Toko</th>
                            <th class="text-center" style="min-width:180px">Pengiriman</th>
                            <th class="text-center" style="min-width:150px">Aksi</th>
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

            $('#searchTable').on('input', function() {
                table
                    .search(this.value)
                    .draw();
            });

            $('#per_page').on('change', function() {
                table
                    .page
                    .len(parseInt(this.value))
                    .draw();
            });

            // Data Table
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
                                tanggal: items.batas_kirim_at ?? '-',
                                no_pesanan: items.no_pesanan ?? '-',
                                no_resi: items.no_resi ?? '-',
                                total_pesanan: items.total_harga ?? 0,
                                toko: items.toko?.nama_toko ?? '-',
                                pengiriman: items.kurir ?? '-',
                                status_kirim: items.status_kirim ?? '-'
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
                        orderable: true,
                        searchable: true,
                        className: 'text-center',
                        render: function(data, type, row, meta) {
                            console.log(row);
                            if (!data) {
                                return '<span class="text-muted">-</span>';
                            }

                            const tanggal = new Date(data);
                            let statusBadge = '';

                            // Supaya sorting DataTables tetap berdasarkan tanggal asli
                            if (type === 'sort' || type === 'type') {
                                return tanggal.getTime();
                            }

                            const tanggalFormat = new Intl.DateTimeFormat('id-ID', {
                                timeZone: 'Asia/Jakarta',
                                day: '2-digit',
                                month: 'short',
                                year: 'numeric'
                            }).format(tanggal);

                            const jamFormat = new Intl.DateTimeFormat('id-ID', {
                                timeZone: 'Asia/Jakarta',
                                hour: '2-digit',
                                minute: '2-digit',
                                hour12: false
                            }).format(tanggal);

                            if (row.status_kirim === 'terlambat') {
                                statusBadge = `
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill px-3 py-1 mt-1">
                                        <i class="fa-solid fa-triangle-exclamation me-1"></i>
                                        Terlambat
                                    </span>
                                `;
                            } else {
                                statusBadge = `
                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1 mt-1">
                                        <i class="fa-solid fa-circle-check me-1"></i>
                                        Aman
                                    </span>
                                `;
                            }

                            return `
                                <div class="d-flex flex-column align-items-center">

                                    <div class="text-center">
                                        <div class="text-dark fw-bold" style="font-size: 13px;">
                                            ${tanggalFormat}
                                        </div>

                                        <small class="text-muted" style="font-size: 12px;">
                                            <i class="fa-regular fa-clock me-1"></i>
                                            ${jamFormat} WIB
                                        </small>
                                    </div>

                                    ${statusBadge}

                                </div>
                            `;
                        }
                    },
                    {
                        data: 'no_pesanan',
                        name: 'no_pesanan',
                        className: 'text-center',
                        render: function(data, type, row) {
                            if (!data) {
                                return '<span class="text-muted">-</span>';
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
                        className: 'text-center',
                        render: function(data, type, row) {

                            if (type === 'sort' || type === 'type') {
                                return parseFloat(data ?? 0);
                            }

                            const total = Number(data ?? 0).toLocaleString('id-ID');

                            return `
                                <div class="d-flex flex-column align-items-center">
                                    <span class="fw-bold text-dark fs-6">
                                        Rp ${total}
                                    </span>
                                    <small class="text-muted">
                                        Total Pesanan
                                    </small>
                                </div>
                            `;
                        }
                    },
                    {
                        data: 'toko',
                        orderable: true,
                        searchable: true,
                        className: 'text-center',
                        render: function(data, type, row) {

                            if (!data) {
                                return '<span class="text-muted">-</span>';
                            }

                        return `
                            <div class="text-center">
                                <div class="fw-semibold text-dark">
                                    <i class="fa-solid fa-store text-primary me-1"></i>
                                    ${data}
                                </div>
                                <small class="text-muted">
                                    Marketplace
                                </small>
                            </div>
                        `;
                        }
                    },
                    {
                        data: 'pengiriman',
                        orderable: true,
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

                            const url = "{{ route('pesanan.show', ':no_pesanan') }}"
                                .replace(':no_pesanan', row.no_pesanan);

                            const warning = row.status_cek
                                ? `<i class="bi bi-exclamation-circle-fill text-warning ms-1"></i>`
                                : '';

                            return `
                                <a href="${url}"
                                    target="_blank"
                                    class="btn btn-sm btn-outline-primary">

                                    <i class="bi bi-file-earmark-text me-1"></i>
                                    Rincian
                                    ${warning}

                                </a>
                            `;
                        }
                    }
                ],
            });
        });
    </script>
@endpush
