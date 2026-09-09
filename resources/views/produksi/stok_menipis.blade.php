@extends('layouts.app')
@section('content')
    <main class="flex-grow-1 overflow-auto p-3 p-lg-4">
        <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mb-4">
            <div>
                <h1 class="h3 fw-bold text-dark mb-1" id="headertitle">STOK PERLU DIPRODUKSI</h1>
                <span class="text-muted">
                    <i class="fa-solid fa-tags"></i>
                    Produksi
                </span>
                <span class="mx-2 text-secondary">/</span>
                <span class="fw-semibold text-primary">
                    Stok Menipis
                </span>
            </div>
        </div>

        <!-- Inventory Stock Table Section -->
        <section id="stockTableSection" class="card border-0 shadow-sm rounded-4 overflow-hidden mb-0 pb-0">
            <div
                class="card-header bg-white border-bottom p-3 p-md-4 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                <div>
                    <h2 class="h5 fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                        <i class="fa-solid fa-tags"></i>
                        Daftar Stok Menipis
                    </h2>
                    <p class="text-muted small mb-0">
                        Menampilkan semua stok yang hampir habis.
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

                    <button type="button" class="btn btn-primary btn-sm text-nowrap" id="ambilTugas">
                        <i class="fa-solid fa-user-check me-1"></i>
                        Ambil Tugas
                    </button>
                </div>
            </div>

            <!-- Table Container -->
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-nowrap" id="orderlist">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" class="py-3 px-4">No</th>
                            <th scope="col" class="py-3 px-4">Nama Produk</th>
                            <th scope="col" class="py-3 px-4 text-center">Variasi</th>
                            <th scope="col" class="py-3 px-4 text-center">Stok Tersedia</th>
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

            table = $('#orderlist').DataTable({
                processing: true,
                serverSide: false,

                ajax: {
                    url: "{{ route('stokmenipis.json') }}",
                    type: "GET",
                    dataSrc: "data"
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
                        data: null,
                        render: function(data, type, row) {
                            return `
                                <div>
                                    <div class="fw-semibold text-dark" style="font-size:14px; line-height:1.4;">
                                        ${row.nama_produk ?? '-'}
                                    </div>

                                    <div class="mt-1">
                                        <span class="badge bg-light text-secondary border fw-normal"
                                            style="font-size:11px;">
                                            ${row.sku ?? '-'}
                                        </span>
                                    </div>
                                </div>
                            `;
                        }
                    },
                    {
                        data: 'variasi',
                        defaultContent: '-'
                    },
                    {
                        data: 'stok_produk',
                        className: 'text-center',
                        render: function(data, type, row) {
                            if (!data) {
                                return 0;
                            }

                            return data.jumlah_tersedia ?? 0;
                        }
                    },
                    {
                        data: 'stok_produk',
                        className: 'text-center',
                        render: function(data, type, row) {
                            let kebutuhan = 5;

                            if (data) {
                                kebutuhan = Math.max(
                                    5 - parseInt(data.jumlah_tersedia ?? 0),
                                    0
                                );
                            }

                            return `
                                <div class="d-inline-flex align-items-center gap-2">
                                    <input
                                        type="number"
                                        class="form-control form-control-sm text-center kebutuhan-input"
                                        value="${kebutuhan}"
                                        min="0"
                                        style="width:75px;"
                                        data-sku="${row.sku}"
                                    >

                                    <span class="text-muted" style="font-size:11px;">
                                        pcs
                                    </span>
                                </div>
                            `;
                        }
                    }
                ]
            });

            // Melakukan Checklist
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

            // Mengambil data yang di checklist
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

            // Push data ke variabel
            $('.item-checkbox:checked').each(function() {
                skuDipilih.push($(this).val());
            });

            function getData() {
                let dataKebutuhan = [];

                $('.kebutuhan-input').each(function() {
                    const sku = $(this).data('sku');

                    if (skuDipilih.includes(sku)) {
                        dataKebutuhan.push({
                            sku: sku,
                            jumlah: parseInt($(this).val()) || 0
                        });
                    }
                });

                return dataKebutuhan;
            }

            $('#ambilTugas').on('click', function(e) {
                e.preventDefault();
                const dataKebutuhan = getData();

                $.ajax({
                    type: "POST",
                    url: "{{ route('produksi.ambiltugas') }}",
                    data: {
                        _token: "{{ csrf_token() }}",
                        kebutuhan: dataKebutuhan,
                        source_type: 'stok'
                    },
                    dataType: "json",
                    success: function(response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil',
                            text: response.message ?? 'Tugas berhasil diambil.',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            location.reload();
                        });
                    },

                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal',
                            text: xhr.responseJSON?.message ??
                                'Terjadi kesalahan saat mengambil tugas.'
                        });
                    }
                });
            });

        });
    </script>
@endpush
