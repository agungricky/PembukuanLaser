@extends('layouts.app')
@section('content')
    <main class="flex-grow-1 overflow-auto p-3 p-lg-4">
        <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mb-4">
            <div>
                <h1 class="h3 fw-bold text-dark mb-1" id="headertitle">SEDANG DIKERJAKAN</h1>
                <span class="text-muted">
                    <i class="fa-solid fa-gear"></i>
                    Penugasan
                </span>
                <span class="mx-2 text-secondary">/</span>
                <span class="fw-semibold text-primary">
                    Pesanan Reguler
                </span>
            </div>
        </div>

        <!-- Inventory Stock Table Section -->
        <section id="stockTableSection" class="card border-0 shadow-sm rounded-4 overflow-hidden mb-0 pb-0">
            <div
                class="card-header bg-white border-bottom p-3 p-md-4 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                <div>
                    <h2 class="h5 fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                        <i class="fa-solid fa-boxes-stacked text-primary"></i>
                        Daftar Pesanan
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

                    <button class="btn btn-success btn-sm text-nowrap" id="exportExcel">
                        <i class="fa-solid fa-file-excel me-1"></i>
                        Export Excel
                    </button>
                    <button type="button" class="btn btn-success btn-sm text-nowrap" id="btnSelesaikanTugas">

                        <i class="fa-solid fa-circle-check me-1"></i>
                        Selesaikan Tugas
                    </button>
                </div>
            </div>

            <!-- Table Container -->
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-nowrap" id="orderlist">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" class="py-3 px-4 text-center" id="headerCheckAll" style="cursor: pointer;">
                                <input type="checkbox" id="checkAllSelesai">
                                <span class="ms-1">Tandai Selesai</span>
                            </th>
                            <th scope="col" class="py-3 px-4">Nama Produk</th>
                            <th scope="col" class="py-3 px-4 text-center">Variasi</th>
                            <th scope="col" class="py-3 px-4 text-center">Pesanan</th>
                            <th scope="col" class="py-3 px-4 text-center">Kebutuhan Produksi</th>
                            <th scope="col" class="py-3 px-4 text-center">Aksi</th>
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

            let page = "{{ $page }}";
            let table = null;
            let skuDipilih = [];
            let exporter_id = null;

            // Data Table
            table = $('#orderlist').DataTable({
                ajax: {
                    type: "GET",
                    url: "{{ route('penugasan.task.json', ':page') }}"
                        .replace(':page', page),
                    dataSrc: function(response) {
                        exporter_id = response.exporter;
                        if (!response.success || !response.data) {
                            return [];
                        }

                        return Object.entries(response.data).map(
                            function([sku, items]) {
                                return {
                                    sku: items.sku,
                                    nama_produk: items.nama_produk ?? '-',
                                    variasi: items.variasi ?? '-',
                                    jumlah_pesanan: items.jumlah_pesanan ?? 0,
                                    stok: items.stok ?? 0,
                                    kebutuhan_produksi: items.kebutuhan_produksi ?? 0,
                                    exporter_id: response.exporter
                                };

                            }
                        );
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
                        data: 'sku',
                        orderable: false,
                        searchable: false,
                        className: 'text-center',
                        render: function(data, type, row, meta) {
                            const checked = skuDipilih.includes(row.sku) ? 'checked' : '';
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
                                <div
                                    class="fw-semibold text-dark"
                                    style="
                                        font-size:14px;
                                        line-height:1.4;
                                    "
                                >
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
                            const variasiFormat = variasi.split(' ').reduce(function(hasil, kata,
                                index) {
                                if (index > 0 && index % 3 === 0) {
                                    hasil += '<br>';
                                }

                                hasil += (index % 3 === 0 ? '' : ' ') + kata;
                                return hasil;
                            }, '');

                            return `
                                <div
                                    style="
                                        font-size:12px;
                                        font-weight:500;
                                        color:#495057;
                                        line-height:1.5;
                                    "
                                >
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
                            console.log(row)
                            if (row.source_type === 'stok') {
                                return '';
                            }
                            return `
                                <div class="small">
                                    <div class="d-flex justify-content-between gap-3 mb-1">
                                        <span class="text-muted">Pesanan Masuk</span>
                                        <span class="fw-semibold text-primary">
                                            ${row.jumlah_pesanan}
                                        </span>
                                    </div>
                                    <div class="d-flex justify-content-between gap-3 mb-1">
                                        <span class="text-muted">
                                            Ketersediaan Stok
                                        </span>
                                        <span class="fw-semibold text-success">
                                            ${row.stok}
                                        </span>
                                    </div>
                                </div>
                            `;
                        }
                    },
                    {
                        data: 'kebutuhan_produksi',
                        orderable: true,
                        searchable: false,
                        className: 'text-center',
                        render: function(data, type, row) {
                            const kebutuhanProduksi = parseInt(row.kebutuhan_produksi ?? 0);
                            return `
                                <div class="fw-bold fs-6">
                                    ${kebutuhanProduksi} pcs
                                </div>
                            `;
                        }
                    },
                    {
                        data: 'sku',
                        orderable: false,
                        searchable: false,
                        className: 'text-center',
                        render: function(data, type, row) {
                            return `
                                <button type="button" class="btn btn-sm btn-outline-danger btn-batalkan" 
                                data-sku="${row.sku}" data-exporter="${row.exporter_id}">
                                    <i class="fa-solid fa-xmark me-1"></i>
                                    Batalkan
                                </button>
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
                },

                initComplete: function() {
                    updateButtonSelesai();
                }
            });

            // Logic On Off Button selesai
            function updateButtonSelesai() {
                const totalData = table.rows().data().length;
                const totalDipilih = skuDipilih.length;
                const semuaTerpilih = totalData > 0 && totalDipilih === totalData;

                $('#btnSelesaikanTugas').prop('disabled', !semuaTerpilih);
                $('#checkAllSelesai').prop('checked', semuaTerpilih);
            }

            // Penanganan Checkbox -------------> 
            $('#orderlist tbody').on('click', 'tr',
                function(e) {
                    if ($(e.target).closest('.item-checkbox, button').length) {
                        return;
                    }

                    const checkbox = $(this).find('.item-checkbox');
                    if (!checkbox.length) {
                        return;
                    }

                    checkbox.prop('checked', !checkbox.prop('checked'));
                    checkbox.trigger('change');
                }
            );

            $('#orderlist tbody').on('change', '.item-checkbox',
                function() {
                    const sku = $(this).val();
                    const row = $(this).closest('tr');
                    if ($(this).is(':checked')) {
                        if (!skuDipilih.includes(sku)) {
                            skuDipilih.push(sku);
                        }

                        row.addClass('row-selected');
                    } else {
                        skuDipilih = skuDipilih.filter(item => item !== sku);
                        row.removeClass('row-selected');
                    }

                    updateButtonSelesai();
                }
            );

            $(document).on('change', '#checkAllSelesai',
                function() {
                    const checked = $(this).prop('checked');
                    if (checked) {
                        skuDipilih = table
                            .rows()
                            .data()
                            .toArray()
                            .map(
                                item => item.sku
                            );

                    } else {
                        skuDipilih = [];
                    }
                    table.rows().invalidate().draw(false);
                    updateButtonSelesai();
                }
            );

            $(document).on('click', '#headerCheckAll', function(e) {
                if ($(e.target).is('#checkAllSelesai')) {
                    return;
                }
                const checkbox = $('#checkAllSelesai');
                checkbox.prop('checked', !checkbox.prop('checked'));
                checkbox.trigger('change');
            });
            // <-------------- Penanganan Checkbox

            table.on('draw', function() {
                updateButtonSelesai();
            });

            // Button Batalkan
            $(document).on('click', '.btn-batalkan', function(e) {
                e.stopPropagation();
                const sku = $(this).data('sku');
                const exporter_id = $(this).data('exporter')

                Swal.fire({
                    title: 'Batalkan Pengerjaan?',
                    text: 'Apakah Anda yakin ingin membatalkan pengerjaan ini? Data akan di keluarkan dari antrean produksi.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Batalkan',
                    cancelButtonText: 'Tidak',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            type: "POST",
                            url: "{{ route('penugasan.task.cancel', ['page' => $page]) }}",
                            data: {
                                _token: "{{ csrf_token() }}",
                                sku: sku,
                                exporter_id: exporter_id
                            },
                            dataType: "json",
                            success: function(response) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Berhasil',
                                    text: response.message ??
                                        'Pengerjaan berhasil dibatalkan dan data dikembalikan ke antrean.',
                                    confirmButtonText: 'OK'
                                }).then(() => {
                                    table.ajax.reload(null, false);
                                });
                            }
                        });
                    }
                });


            });

            // Button Selesaikan Tugas
            $(document).on('click', '#btnSelesaikanTugas', function() {
                if ($(this).prop('disabled')) {
                    return;
                }

                Swal.fire({
                    icon: 'warning',
                    title: 'Selesaikan Produksi?',
                    text: 'Pastikan semua pekerjaan produksi sudah benar-benar selesai.',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Selesaikan',
                    cancelButtonText: 'Batal',
                    reverseButtons: true
                }).then((result) => {

                    if (result.isConfirmed) {

                        $.ajax({
                            type: "POST",
                            url: "{{ route('penugasan.done', ':id') }}".replace(':id',
                                exporter_id),
                            data: {
                                _token: "{{ csrf_token() }}"
                            },
                            dataType: "json",
                            beforeSend: function() {
                                Swal.fire({
                                    title: 'Memproses...',
                                    text: 'Sedang menyelesaikan produksi',
                                    allowOutsideClick: false,
                                    didOpen: () => {
                                        Swal.showLoading();
                                    }
                                });
                            },
                            success: function(response) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Berhasil',
                                    text: response.message ??
                                        'Produksi berhasil diselesaikan.',
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
                                        'Terjadi kesalahan saat menyelesaikan produksi.'
                                });
                            }
                        });

                    }

                });
            });

            // Export Excell
            $('#exportExcel').on('click', function(e) {
                e.preventDefault();
                $.ajax({
                    url: "{{ route('penugasan.export', ['page' => $page]) }}",
                    type: "GET",
                    xhrFields: {
                        responseType: 'blob'
                    },
                    beforeSend: function() {
                        Swal.fire({
                            title: 'Memproses...',
                            text: 'Sedang membuat file Excel',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });
                    },
                    success: function(response, status, xhr) {
                        Swal.close();
                        let filename = 'penugasan.xlsx';
                        const disposition = xhr.getResponseHeader('Content-Disposition');
                        if (disposition) {
                            const match = disposition.match(/filename="?([^"]+)"?/);
                            if (match && match[1]) {
                                filename = match[1];
                            }
                        }

                        const url = window.URL.createObjectURL(response);
                        const a = document.createElement('a');
                        a.href = url;
                        a.download = filename;
                        document.body.appendChild(a);
                        a.click();
                        a.remove();
                        window.URL.revokeObjectURL(url);
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil',
                            text: 'File Excel berhasil diexport',
                            timer: 1500,
                            showConfirmButton: false
                        });
                    },
                    error: function(xhr) {
                        let message = 'Tidak terdapat tugas aktif yang dapat di export.';
                        if (xhr.response instanceof Blob) {
                            const reader = new FileReader();
                            reader.onload = function() {
                                try {
                                    const response = JSON.parse(reader.result);
                                    message = response.message ?? message;
                                } catch (e) {
                                    message = reader.result || message;
                                }

                                Swal.fire({
                                    icon: 'error',
                                    title: 'Export Gagal',
                                    text: message
                                });
                            };

                            reader.readAsText(xhr.response);
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Export Gagal',
                                text: xhr.responseJSON?.message ?? message
                            });
                        }
                    }
                });
            });

        });
    </script>
@endpush
