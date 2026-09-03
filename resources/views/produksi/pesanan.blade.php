@extends('layouts.app')
@section('content')
    <main class="flex-grow-1 overflow-auto p-3 p-lg-4">
        <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mb-4">
            <div>
                <h1 class="h3 fw-bold text-dark mb-1" id="headertitle">PESANAN PRODUK REGULER</h1>
                <span class="text-muted">
                    <i class="fa-solid fa-arrow-right-arrow-left"></i>
                    Produksi
                </span>
                <span class="mx-2 text-secondary">/</span>
                <span class="fw-semibold text-primary">
                    Pesanan Reguler
                </span>
            </div>
        </div>

        <div class="alert alert-primary d-flex align-items-start gap-2 mb-3" role="alert">
            <i class="fa-solid fa-circle-info mt-1"></i>
            <div>
                <strong>Informasi Kebutuhan Produksi</strong>
                <div class="small mt-1">
                    Kolom <strong>Kebutuhan Produksi</strong> merupakan total kebutuhan dari
                    <strong>pesanan masuk</strong> -
                    <strong>stok yang tersedia</strong>.
                </div>
            </div>
        </div>

        <!-- Inventory Stock Table Section -->
        <section id="stockTableSection" class="card border-0 shadow-sm rounded-4 overflow-hidden mb-0 pb-0">
            <div
                class="card-header bg-white border-bottom p-3 p-md-4 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                <div>
                    <h2 class="h5 fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                        <i class="fa-solid fa-boxes-stacked text-primary"></i>
                        Daftar Pesanan {{ Str::ucfirst($produksi) }}
                    </h2>
                    <p class="text-muted small mb-0">
                        Menampilkan semua pesanan masuk.
                    </p>
                </div>

                <!-- Controls: Filters & Table Search -->
                <div class="d-flex flex-nowrap align-items-center gap-2">
                    <div class="input-group input-group-sm" style="max-width: 240px;">
                        <span class="input-group-text bg-light border-end-0">
                            <i class="fa-solid fa-magnifying-glass text-muted"></i>
                        </span>
                        <input type="text" id="searchTable" placeholder="Cari SKU / Produk..."
                            class="form-control form-control-sm border-start-0 bg-light" />
                    </div>

                    <select id="per_page" class="form-select form-select-sm" style="width: auto;">
                        <option value="10" selected>10</option>
                        <option value="20">20</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>

                    <button type="button" class="btn btn-primary btn-sm text-nowrap" data-bs-toggle="modal"
                        data-bs-target="#pengambilModal" data-role="pegawai" id="btnPengambilModal">
                        <i class="fa-solid fa-file-import me-1"></i>
                        Import Excell
                    </button>
                    <button type="button" class="btn btn-success btn-sm text-nowrap" id="exportExcel">
                        <i class="fa-solid fa-file-excel me-1"></i>
                        Export Excel
                    </button>
                </div>
            </div>

            <!-- Table Container -->
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-nowrap" id="orderlist">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" class="py-3 px-4">Pilih Jobdesk</th>
                            <th scope="col" class="py-3 px-4">Nama Produk</th>
                            <th scope="col" class="py-3 px-4 text-center">Variasi</th>
                            <th scope="col" class="py-3 px-4 text-center">Pesanan</th>
                            <th scope="col" class="py-3 px-4 text-center">Kebutuhan Produksi</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </section>
    </main>

    @include('layouts.footer')
@endsection

@push('styles')
    <style>
        #orderlist .item-checkbox {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: #0d6efd;
        }

        #orderlist tbody tr {
            cursor: pointer;
            transition: background-color 0.15s ease;
        }

        #orderlist tbody tr:hover {
            background-color: #f5f8ff;
        }

        #orderlist tbody tr.row-selected {
            background-color: #e7f1ff !important;
        }

        #orderlist tbody tr.row-selected td {
            background-color: #e7f1ff !important;
        }
    </style>
@endpush

@push('scripts')
    <script>
        $(document).ready(function() {
            let table = null;
            let searchTimer = null;
            let skuDipilih = [];

            table = $('#orderlist').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('produksi.pesanan.json') }}",
                    type: 'GET'
                },
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
                        data: 'sku',
                        orderable: false,
                        searchable: false,
                        className: 'text-center',
                        render: function(data, type, row, meta) {
                            const checked = skuDipilih.includes(row.sku) ?
                                'checked' :
                                '';

                            return `
                                <input
                                    type="checkbox"
                                    class="item-checkbox"
                                    value="${row.sku}"
                                    ${checked}
                                >
                            `;
                        }
                    },
                    {
                        data: 'nama_produk',
                        name: 'nama_produk',
                        render: function(data, type, row) {
                            const namaProduk = data ?? '-';
                            const namaFormat = namaProduk
                                .split(' ')
                                .reduce(function(hasil, kata, index) {

                                    if (index > 0 && index % 4 === 0) {
                                        hasil += '<br>';
                                    }

                                    hasil += (index % 4 === 0 ? '' : ' ') + kata;
                                    return hasil;
                                }, '');

                            return `
                                <div class="fw-semibold text-dark" style="font-size:14px; line-height:1.4;">
                                    ${namaFormat}
                                </div>
                                <div class="mt-1">
                                    <span class="badge bg-light text-secondary border fw-normal">
                                        ${row.sku ?? '-'}
                                    </span>
                                </div>
                            `;
                        }
                    },
                    {
                        data: 'variasi',
                        name: 'variasi',
                        className: 'text-center',
                        render: function(data, type, row) {
                            const variasi = data ?? '-';
                            const variasiFormat = variasi
                                .split(' ')
                                .reduce(function(hasil, kata, index) {

                                    if (index > 0 && index % 3 === 0) {
                                        hasil += '<br>';
                                    }

                                    hasil += (index % 3 === 0 ? '' : ' ') + kata;
                                    return hasil;
                                }, '');

                            return `
                                <div style="font-size: 12px; font-weight: 500; color: #495057; line-height: 1.5;">
                                    ${variasiFormat}
                                </div>
                            `;
                        }
                    },
                    {
                        data: null,
                        orderable: false,
                        searchable: false,
                        render: function(data, type, row) {
                            const pesananMasuk = parseInt(row.pesanan_items ?? 0);
                            const stok = parseInt(row.stok ?? 0);
                            return `
                                <div class="small">
                                    <div class="d-flex justify-content-between gap-3 mb-1">
                                        <span class="text-muted">
                                            Pesanan Masuk
                                        </span>
                                        <span class="fw-semibold text-primary">
                                            ${pesananMasuk}
                                        </span>
                                    </div>
                                    <div class="d-flex justify-content-between gap-3 mb-1">
                                        <span class="text-muted">
                                            Ketersediaan Stok
                                        </span>
                                        <span class="fw-semibold text-success">
                                            ${stok}
                                        </span>
                                    </div>
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
                            const kebutuhanProduksi =
                                parseInt(row.kebutuhan_produksi ?? 0);

                            return `
                                <div class="fw-bold fs-6">
                                    ${kebutuhanProduksi} pcs
                                </div>
                            `;
                        }
                    }
                ],
                rowCallback: function(row, data) {
                    if (skuDipilih.includes(data.sku)) {
                        $(row).addClass('row-selected');
                        $(row).find('.item-checkbox').prop('checked', true);
                    } else {
                        $(row).removeClass('row-selected');
                        $(row).find('.item-checkbox').prop('checked', false);
                    }
                }
            });

            $('#orderlist tbody').on('click', 'tr', function(e) {
                if ($(e.target).is('.item-checkbox')) {
                    return;
                }

                const checkbox = $(this).find('.item-checkbox');
                if (!checkbox.length) {
                    return;
                }
                checkbox.prop(
                    'checked',
                    !checkbox.prop('checked')
                );
                checkbox.trigger('change');
            });

            $('#orderlist tbody').on(
                'change',
                '.item-checkbox',
                function() {
                    const sku = $(this).val();
                    const row = $(this).closest('tr');
                    if ($(this).is(':checked')) {
                        if (!skuDipilih.includes(sku)) {
                            skuDipilih.push(sku);
                        }

                        row.addClass('row-selected');
                    } else {
                        skuDipilih = skuDipilih.filter(
                            item => item !== sku
                        );

                        row.removeClass('row-selected');
                    }
                    // console.log(skuDipilih);
                }
            );

            $(document)
                .off('change.orderlist', '#per_page')
                .on('change.orderlist', '#per_page', function() {
                    if (!table) {
                        return;
                    }
                    const length = parseInt(this.value, 10);
                    table
                        .page
                        .len(length)
                        .draw();

                });

            $(document)
                .off('input.orderlist', '#searchTable')
                .on('input.orderlist', '#searchTable', function() {
                    if (!table) {
                        return;
                    }

                    const keyword = this.value;
                    clearTimeout(searchTimer);
                    searchTimer = setTimeout(function() {
                        table
                            .search(keyword)
                            .draw();

                    }, 350);
                });

            $('#orderlist').on('preXhr.dt', function(e, settings, data) {
                console.log('Request server-side:', {
                    start: data.start,
                    length: data.length,
                    search: data.search.value
                });
            });

            $('.item-checkbox:checked').each(function() {
                skuDipilih.push($(this).val());
            });

            $('#exportExcel').on('click', function() {
                if (skuDipilih.length === 0) {
                    alert('Pilih data terlebih dahulu');
                    return;
                }

                const params = new URLSearchParams();
                skuDipilih.forEach(function(sku) {
                    params.append('sku[]', sku);
                });

                window.location.href = '{{ route('produksi.export') }}?' + params.toString();
            });
        });
    </script>
@endpush
