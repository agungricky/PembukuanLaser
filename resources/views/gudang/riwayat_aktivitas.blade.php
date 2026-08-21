@extends('layouts.app')

@section('content')
    <main class="flex-grow-1 overflow-auto p-3 p-lg-4">

        <!-- Dashboard Title & Pulse Badge -->
        <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mb-4">
            <div>
                <h1 class="h3 fw-bold text-dark mb-1">
                    Riwayat Aktivitas Mutasi Gudang
                </h1>
                <span class="text-muted">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                    Riwayat Aktivitas
                </span>
                <span class="mx-2 text-secondary">/</span>
                <span class="fw-semibold text-primary">
                    Riwayat mutasi
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
                        <i class="fa-solid fa-clock-rotate-left"></i>
                        Daftar Semua Aktivitas
                    </h2>
                    <p class="text-muted small mb-0">
                        Menampilkan Semua Aktivitas Mutasi.
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
                </div>
            </div>

            <!-- Table Container -->
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-nowrap" id="orderlist">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" class="py-3 px-4 text-center">No</th>
                            <th scope="col" class="py-3 px-4 text-center">Produk</th>
                            <th scope="col" class="py-3 px-4 text-center">Variasi</th>
                            <th scope="col" class="py-3 px-4 text-center">Hpp</th>
                            <th scope="col" class="py-3 px-4 text-center">Admin Gudang</th>
                            <th scope="col" class="py-3 px-4 text-center">Diambil</th>
                            <th scope="col" class="py-3 px-4 text-center">Aktivitas</th>
                        </tr>
                    </thead>
                    <tbody>
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

            const table = new DataTable('#orderlist', {
                processing: true,
                serverSide: true,

                ajax: {
                    url: "{{ route('gudang.riwayataktivitas.data') }}",
                    type: "GET",
                    dataSrc: function(response) {
                        return response.data;
                    }
                },
                columns: [{
                        data: 'no',
                        name: 'no',
                        orderable: true,
                        searchable: false,
                        className: 'text-center'
                    },
                    {
                        data: null,
                        orderable: false,
                        render: function(data, type, row) {
                            return `
                            <div class="fw-semibold text-dark">
                                ${row.produk ?? '-'}
                            </div>

                            <div class="text-muted" style="font-size: 11px;">
                                SKU: ${row.sku ?? '-'}
                            </div>
                        `;
                        }
                    },

                    // =========================
                    // VARIASI + KATEGORI
                    // =========================
                    {
                        data: null,
                        orderable: false,
                        render: function(data, type, row) {

                            return `
                            <div class="fw-semibold text-start">
                                ${row.variasi ?? '-'}
                            </div>

                            <div class="text-muted text-start" style="font-size: 11px;">
                                Kategori : ${row.kategori ?? '-'}
                            </div>
                        `;
                        }
                    },

                    // =========================
                    // HPP + JUMLAH
                    // =========================
                    {
                        data: null,
                        orderable: false,
                        render: function(data, type, row) {

                            let hpp = Number(row.hpp ?? 0)
                                .toLocaleString('id-ID');

                            return `
                            <div class="text-center">
                                Rp ${hpp}
                            </div>

                            <div class="text-center">
                                <span class="text-darnk" style="font-size: 11px;">Jumlah :</span>
                                <span class="text-primary fw-bold" style="font-size: 11px;">${row.jumlah ?? 0} pcs</span>
                            </div>
                        `;
                        }
                    },

                    {
                        data: null,
                        orderable: false,
                        render: function(data, type, row) {

                            let admin = row.admin_gudang ?? '-';

                            let tanggal = '-';
                            let jam = '-';

                            if (row.created_at) {
                                let date = new Date(row.created_at);

                                tanggal = date.toLocaleDateString('id-ID', {
                                    day: '2-digit',
                                    month: 'short',
                                    year: 'numeric'
                                });

                                jam = date.toLocaleTimeString('id-ID', {
                                    hour: '2-digit',
                                    minute: '2-digit'
                                });
                            }

                            return `
                                <div class="text-center">
                                    <div class="fw-semibold text-dark" style="font-size: 12px;">
                                        ${admin}
                                    </div>

                                    <div class="text-muted mt-1" style="font-size: 10px;">
                                        <i class="fa-regular fa-calendar me-1"></i>
                                        ${tanggal}
                                    </div>

                                    <div class="text-muted" style="font-size: 10px;">
                                        <i class="fa-regular fa-clock me-1"></i>
                                        ${jam}
                                    </div>
                                </div>
                            `;
                        }
                    },

                    {
                        data: null,
                        orderable: false,
                        render: function(data, type, row) {
                            console.log(row)
                            let pengambil = row.pengambil ?? '-';

                            let tanggal = '-';
                            let jam = '-';

                            if (row.created_at) {
                                let date = new Date(row.created_at);

                                tanggal = date.toLocaleDateString('id-ID', {
                                    day: '2-digit',
                                    month: 'short',
                                    year: 'numeric'
                                });

                                jam = date.toLocaleTimeString('id-ID', {
                                    hour: '2-digit',
                                    minute: '2-digit'
                                });
                            }

                            return `
                                <div class="text-center">
                                    <div class="fw-semibold text-dark" style="font-size: 12px;">
                                        ${pengambil}
                                    </div>

                                    <div class="text-muted mt-1" style="font-size: 10px;">
                                        <i class="fa-regular fa-calendar me-1"></i>
                                        ${tanggal}
                                    </div>

                                    <div class="text-muted" style="font-size: 10px;">
                                        <i class="fa-regular fa-clock me-1"></i>
                                        ${jam}
                                    </div>
                                </div>
                            `;
                        }
                    },

                    // =========================
                    // JENIS MUTASI + KETERANGAN
                    // =========================
                    {
                        data: null,
                        orderable: false,
                        className: 'text-center',
                        render: function(data, type, row) {

                            let badge = 'bg-secondary';
                            let icon = '';

                            if (row.jenis_mutasi === 'masuk') {
                                badge = 'bg-success';
                                icon = '🟢';
                            }

                            if (row.jenis_mutasi === 'keluar') {
                                badge = 'bg-danger';
                                icon = '🔴';
                            }

                            return `
                            <span class="badge ${badge} mb-1">
                                ${icon} STOK ${row.jenis_mutasi
                                    ? row.jenis_mutasi.toUpperCase()
                                    : '-'}
                            </span>

                            <div class="text-muted" style="font-size: 11px;">
                                ${row.keterangan ?? '-'}
                            </div>
                        `;
                        }
                    }
                ],

                pageLength: 10,

                searching: true,
                lengthChange: false,
                autoWidth: false,

                layout: {
                    topStart: null,
                    topEnd: null,
                    bottomStart: 'info',
                    bottomEnd: 'paging'
                },

                language: {
                    processing: "Memuat data...",
                    info: "Menampilkan _START_ - _END_ dari _TOTAL_ aktivitas",
                    infoEmpty: "Tidak ada aktivitas",
                    zeroRecords: "Data tidak ditemukan",
                    paginate: {
                        next: "›",
                        previous: "‹"
                    }
                }

            });


            // =========================
            // PER PAGE
            // =========================

            $('#per_page').val(10);

            $('#per_page').on('change', function() {

                table.page
                    .len(parseInt(this.value))
                    .draw();

            });


            // =========================
            // SEARCH
            // =========================

            let searchTimer;

            $('#searchTable').on('input', function() {

                const keyword = this.value;

                clearTimeout(searchTimer);

                searchTimer = setTimeout(function() {

                    table
                        .search(keyword)
                        .draw();

                }, 400);

            });

        });
    </script>
@endpush
