@extends('layouts.app')
@section('content')
    <main class="flex-grow-1 overflow-auto p-3 p-lg-4">
        <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mb-4">
            <div>
                <h1 class="h3 fw-bold text-dark mb-1">
                    Semua Pesanan Masuk
                </h1>
                <p class="text-muted small mb-0">
                    Hanya dapat melihat semua pesanan ( Perlu Disiapkan, Siap Diambil, dan Sudah Diambil ).
                </p>
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

                    <!-- Status Select Filter -->
                    <select id="statusSelectFilter" class="form-select form-select-sm" style="width: auto;">
                        <option value="siapkan" selected>Perlu Disiapkan</option>
                        <option value="siap">Siap Diambil</option>
                        <option value="diambil">Sudah Diambil</option>
                    </select>

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
                            <th scope="col" class="py-3 px-4">Sku</th>
                            <th scope="col" class="py-3 px-4">Nama Produk</th>
                            <th scope="col" class="py-3 px-4 text-center">Variasi</th>
                            <th scope="col" class="py-3 px-4 text-center">Hpp</th>
                            <th scope="col" class="py-3 px-4 text-center">Kebutuhan</th>
                            <th scope="col" class="py-3 px-4 text-center">Tersedia</th>
                            <th scope="col" class="py-3 px-4 text-center" id="filteron">Status Stok</th>
                        </tr>
                    </thead>
                    <tbody id="stockTableBody"></tbody>
                </table>
            </div>
        </section>
    </main>
@endsection


@push('scripts')
    <script>
        $(document).ready(function() {

            let filter = $('#statusSelectFilter').val();
            let table = null;
            loadPesanan(filter);

            function loadPesanan(filter) {
                $.ajax({
                    type: "GET",
                    url: "{{ route('allpesanan.json', ':filter') }}".replace(':filter', filter),
                    dataType: "JSON",

                    success: function(response) {
                        // Hancurkan DataTable lama sebelum isi tabel diubah
                        if ($.fn.DataTable.isDataTable('#orderlist')) {
                            $('#orderlist').DataTable().destroy();
                        }

                        let html = '';
                        let no = 1;

                        if (filter === "siapkan") {
                            $.each(response, function(index, item) {

                                html += `
                                    <tr>
                                        <td class="py-3 px-4 text-center">
                                            ${no++}
                                        </td>

                                        <td class="py-3 px-4">
                                            ${item.produk?.sku ?? '-'}
                                        </td>

                                        <td class="py-3 px-4 text-start">
                                            ${item.produk?.nama_produk ?? '-'}
                                        </td>

                                        <td class="py-3 px-4 text-center">
                                            ${item.produk?.variasi ?? '-'}
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

                            $('#filteron').text("Status Stok");
                        } else if (filter === "siap" || filter === "diambil") {
                            $.each(response, function(index, item) {
                                html += `
                                    <tr>
                                        <td class="py-3 px-4 text-center">
                                            ${no++}
                                        </td>

                                        <td class="py-3 px-4">
                                            ${item.stok_produk?.sku_id ?? '-'}
                                        </td>

                                        <td class="py-3 px-4 text-start">
                                            ${item.stok_produk?.produk?.nama_produk ?? '-'}
                                        </td>

                                        <td class="py-3 px-4 text-center">
                                            ${item.stok_produk?.produk?.variasi ?? '-'}
                                        </td>

                                        <td class="py-3 px-4 text-center">
                                            <div class="fw-bold text-success fs-6">
                                                ${Number(item.stok_produk?.produk?.hpp ?? 0)
                                                    .toLocaleString('id-ID', {
                                                        style: 'currency',
                                                        currency: 'IDR',
                                                        minimumFractionDigits: 0
                                                    })}
                                            </div>
                                            <small class="text-muted">/ Item</small>
                                        </td>

                                        <td class="py-3 px-4 text-center">
                                            <span class="fw-bold text-danger">
                                                ${item.jumlah ?? 0}
                                            </span>
                                        </td>

                                        <td class="py-3 px-4 text-center">
                                            <span class="fw-bold text-primary">
                                                ${item.stok_produk?.jumlah_tersedia ?? 0}
                                            </span>
                                        </td>

                                        <td class="py-3 px-4 text-center">
                                            <span class="fw-bold text-success">
                                                ${
                                                    filter === "siap"
                                                        ? (
                                                            item.created_at
                                                                ? new Date(item.created_at)
                                                                    .toLocaleDateString('id-ID')
                                                                : '-'
                                                        )
                                                        : (
                                                            item.updated_at
                                                                ? new Date(item.updated_at)
                                                                    .toLocaleDateString('id-ID')
                                                                : '-'
                                                        )
                                                }
                                            </span>
                                        </td>
                                    </tr>
                                `;
                            });

                            if (filter === "siap") {
                                $('#filteron').text("Tanggal Disiapkan");
                            } else {
                                $('#filteron').text("Tanggal Diambil");
                            }
                        }

                        $('#stockTableBody').html(html);

                        table = new DataTable('#orderlist', {
                            pageLength: 10,
                            searching: true,
                            lengthChange: false,
                            autoWidth: false
                        });
                    },

                    error: function(xhr) {
                        console.log(xhr.responseText);
                    }
                });
            }


            // Cukup dipasang SEKALI
            $('#per_page').val(10);

            $('#per_page').on('change', function() {
                if (table) {
                    table.page.len(parseInt(this.value)).draw();
                }
            });


            $('#searchTable').on('input', function() {
                if (table) {
                    table.search(this.value).draw();
                }
            });


            $('#statusSelectFilter').on('change', function() {
                filter = $(this).val();
                loadPesanan(filter);
            });

        });
    </script>
@endpush
