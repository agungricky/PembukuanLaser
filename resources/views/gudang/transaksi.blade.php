@extends('layouts.app')
@section('content')
    <main class="flex-grow-1 overflow-auto p-3 p-lg-4">
        <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mb-4">
            <div>
                <h1 class="h3 fw-bold text-dark mb-1" id="headertitle"></h1>
                <p class="text-muted small mb-0" id="headersub"></p>
            </div>
        </div>


        <!-- Inventory Stock Table Section -->
        <section id="stockTableSection" class="card border-0 shadow-sm rounded-4 overflow-hidden mb-0 pb-0">
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

                    <select id="per_page" class="form-select form-select-sm" style="width: auto;">
                        <option value="10">10</option>
                        <option value="20" selected>20</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>

                    @if ($id === 'siapkan')
                        <button type="button" class="btn btn-success btn-sm text-nowrap" id="btnDisiapkan">
                            <i class="fa-solid fa-circle-check me-1"></i>
                            Tandai Sudah Disiapkan
                        </button>
                    @elseif ($id === 'siap')
                        <button type="button" class="btn btn-primary btn-sm text-nowrap" id="btnSiap">
                            <i class="fa-solid fa-circle-check me-1"></i>
                            Tandai Sudah Diambil
                        </button>
                    @endif
                </div>
            </div>

            <!-- Table Container -->
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 text-nowrap" id="orderlist">
                    <thead class="table-light">
                        <tr>
                            <th class="py-3 px-4 text-center" data-dt-order="disable">
                                <input type="checkbox" class="form-check-input" id="checkAll"
                                    style="width: 18px; height: 18px;">
                            </th>
                            <th scope="col" class="py-3 px-4">Nama Produk</th>
                            <th scope="col" class="py-3 px-4 text-center">Variasi</th>
                            <th scope="col" class="py-3 px-4 text-center">Hpp</th>
                            @if ($id !== 'diambil')
                                <th scope="col" class="py-3 px-4 text-center">
                                    Stok
                                </th>
                            @endif

                            @if ($id === 'diambil')
                                <th scope="col" class="py-3 px-4 text-center">
                                    Tanggal Disiapkan
                                </th>
                            @endif

                            <th scope="col" class="py-3 px-4 text-center" id="filteron">Status Stok</th>
                            <th scope="col" class="py-3 px-4 text-center">Detail Pesanan</th>
                        </tr>
                    </thead>
                    <tbody id="stockTableBody"></tbody>
                </table>
            </div>
        </section>
    </main>

    <!-- Modal Detail -->
    <div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow">

                <div class="modal-header">
                    <div>
                        <h5 class="modal-title fw-bold">
                            Detail Kebuthan Terhadap Pesanan
                        </h5>
                        <small class="text-muted" id="detailSku"></small>
                    </div>

                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="table-responsive">
                        <table id="detailTable" class="table table-hover align-middle w-100">
                            <thead class="table-light">
                                <tr>
                                    <th>No</th>
                                    <th>No Pesanan</th>
                                    <th>Nama Customer</th>
                                    <th>Produk</th>
                                    <th class="text-center">Jumlah</th>
                                    <th class="text-center">Status</th>
                                    <th>Tanggal</th>
                                </tr>
                            </thead>

                            <tbody id="detailTableBody"></tbody>
                        </table>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            let filter = "{{ $id }}";
            let selected = [];

            let table = new DataTable('#orderlist', {
                pageLength: 10,
                searching: true,
                lengthChange: false,
                autoWidth: false
            });

            $.ajax({
                type: "GET",
                url: "{{ route('showdata.json', ':filter') }}".replace(':filter', filter),
                dataType: "JSON",
                success: function(response) {
                    let html = '';
                    let no = 1;

                    if (filter === "siapkan") {
                        $.each(response, function(index, item) {
                            html += `
                                    <tr>
                                       <td class="py-3 px-4 text-center">
                                            <input type="checkbox"
                                                class="form-check-input item-checkbox"
                                                value="${item.id}">
                                        </td>
                                        <td class="py-3 px-4 text-start">
                                            <div class="d-flex flex-column">
                                                <span class="fw-semibold text-dark sku">
                                                    ${item.produk?.nama_produk ?? '-'}
                                                </span>

                                                <span class="text-muted" style="font-size: 11px;">
                                                   SKU : ${item.produk?.sku ?? '-'}
                                                </span>
                                            </div>
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
                                            <div class="d-flex flex-column gap-1 align-items-center">
                                                <div class="d-flex align-items-center gap-1">
                                                    <i class="fa-solid fa-box-open text-danger" style="font-size: 11px;"></i>
                                                    <span class="text-muted" style="font-size: 11px;">
                                                        Kebutuhan :
                                                    </span>
                                                    <span class="fw-bold text-danger">
                                                        ${item.kebutuhan ?? 0}
                                                    </span>
                                                </div>

                                                <div class="d-flex align-items-center gap-1">
                                                    <i class="fa-solid fa-boxes-stacked text-primary" style="font-size: 11px;"></i>
                                                    <span class="text-muted" style="font-size: 11px;">
                                                        Tersedia :
                                                    </span>
                                                    <span class="fw-bold text-primary">
                                                        ${item.produk?.stok_produk?.jumlah_tersedia ?? 0}
                                                    </span>
                                                </div>
                                            </div>
                                        </td>

                                        <td class="py-3 px-4 text-center">
                                            ${
                                                (item.produk?.stok_produk?.jumlah_tersedia ?? 0) >= (item.kebutuhan ?? 0)
                                                ? `
                                                                                                                                                                    <span class="badge bg-success">Tersedia</span>
                                                                                                                                                                `
                                                : `
                                                                                                                                                                    <span class="badge bg-danger">Kurang</span>
                                                                                                                                                                    <input type="hidden" class="status-stok" value="kurang">
                                                                                                                                                                `
                                            }
                                        </td>
                                        <td class="py-3 px-4 text-center">
                                            <button type="button"
                                                class="btn btn-sm btn-outline-primary btnDetail"
                                                data-sku="${item.produk?.sku ?? ''}"
                                                title="Lihat Detail"
                                                style="width: 34px; height: 34px; padding: 0;">
                                                <i class="fa-solid fa-eye"></i>
                                            </button>
                                        </td>
                                    </tr>
                            `;
                        });

                        $('#headertitle').text("Produk Perlu Disiapkan");
                        $('#headersub').html(`
                                <span class="text-muted">
                                    <i class="fa-solid fa-arrow-right-arrow-left"></i>
                                    Transaksi
                                </span>
                                <span class="mx-2 text-secondary">/</span>
                                <span class="fw-semibold text-primary">
                                    Perlu Disiapkan
                                </span>
                            `);
                    } else if (filter === "siap" || filter === "diambil") {
                        $.each(response, function(index, item) {
                            html += `
                            <tr>
                                <td class="py-3 px-4 text-center">
                                    <input type="checkbox"
                                        class="form-check-input item-checkbox"
                                        value="${item.id}">
                                </td>

                                <td class="py-3 px-4 text-start">
                                    <div class="d-flex flex-column">
                                        <span class="fw-semibold text-dark sku">
                                            ${item.stok_produk?.produk?.nama_produk ?? '-'}
                                        </span>

                                        <span class="text-muted" style="font-size: 11px;">
                                           SKU : ${item.stok_produk?.sku_id ?? '-'}
                                        </span>
                                    </div>
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


                                ${filter === 'diambil'
                                    ? `
                                                                                    <td class="py-3 px-4 text-center">
                                                                                        <span class="fw-bold text-primary">
                                                                                            ${
                                                                                                item.created_at
                                                                                                    ? new Date(item.created_at)
                                                                                                        .toLocaleDateString('id-ID')
                                                                                                    : '-'
                                                                                            }
                                                                                        </span>
                                                                                    </td>
                                                                                `
                                    : `
                                                                                    <td class="py-3 px-4 text-center">
                                                                                        <span class="fw-bold text-primary">
                                                                                            ${item.stok_produk?.jumlah_tersedia ?? 0}
                                                                                        </span>
                                                                                    </td>
                                                                                `
                                }


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
                            $('#headertitle').text("Produk Siap Diambil");
                            $('#headersub').html(`
                                <span class="text-muted">
                                    <i class="fa-solid fa-arrow-right-arrow-left"></i>
                                    Transaksi
                                </span>
                                <span class="mx-2 text-secondary">/</span>
                                <span class="fw-semibold text-primary">
                                    Siap Diambil
                                </span>
                            `);
                        } else {
                            $('#filteron').text("Tanggal Diambil");
                            $('#headertitle').text("Produk Sudah Diambil");
                            $('#headersub').html(`
                                <span class="text-muted">
                                    <i class="fa-solid fa-arrow-right-arrow-left"></i>
                                    Transaksi
                                </span>
                                <span class="mx-2 text-secondary">/</span>
                                <span class="fw-semibold text-primary">
                                    Sudah Diambil
                                </span>
                            `);
                        }
                    }

                    if ($.fn.DataTable.isDataTable('#orderlist')) {
                        table.destroy();
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

            $(document).ready(function() {
                $('#checkAll').prop('checked', false);
                $('.item-checkbox').prop('checked', false);
            });

            // Check All
            $(document).on('change', '#checkAll', function() {
                const checked = $(this).prop('checked');
                $('#orderlist tbody tr').each(function() {
                    const status = $(this).find('.status-stok').val();
                    const checkbox = $(this).find('.item-checkbox');

                    if (!checkbox.length) {
                        return;
                    }

                    if (status === 'kurang') {
                        checkbox.prop('checked', false);
                        $(this).removeClass('table-active');
                        return;
                    }

                    checkbox.prop('checked', checked);
                    $(this).toggleClass(
                        'table-active',
                        checked
                    );
                });
            });

            // Klik Row
            $(document).on('click', '#orderlist tbody tr', function(e) {

                // Jangan jalankan kalau klik tombol detail
                if ($(e.target).closest('.btnDetail').length) {
                    return;
                }

                const status = $(this).find('.status-stok').val();
                const checkbox = $(this).find('.item-checkbox');

                if (!checkbox.length) {
                    return;
                }

                if (status === 'kurang') {
                    checkbox.prop('checked', false);
                    $(this).removeClass('table-active');
                    return;
                }

                if ($(e.target).is('.item-checkbox')) {
                    $(this).toggleClass(
                        'table-active',
                        checkbox.prop('checked')
                    );

                    return;
                }

                const checked = !checkbox.prop('checked');
                checkbox.prop('checked', checked);
                $(this).toggleClass(
                    'table-active',
                    checked
                );
            });

            // Fitur Halaman Disiapkan
            $('#btnDisiapkan').on('click', function() {
                const selected = $('.item-checkbox:checked');
                if (selected.length === 0) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Belum Ada Barang',
                        text: 'Silakan pilih barang terlebih dahulu.'
                    });
                    return;
                }

                const selectedSku = selected.map(function() {
                    return $(this)
                        .closest('tr')
                        .find('.sku')
                        .text()
                        .trim();
                }).get();

                console.log(selectedSku);

                Swal.fire({
                    title: 'Konfirmasi',
                    text: `Tandai ${selected.length} barang sebagai sudah disiapkan?`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Proses',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            type: "POST",
                            url: "{{ route('transaksi.store') }}",
                            data: {
                                _token: "{{ csrf_token() }}",
                                sku: selectedSku
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

            // Fitur Halaman Siap
            $('#btnSiap').on('click', function() {
                const selected = $('.item-checkbox:checked');
                if (selected.length === 0) {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Belum Ada Barang',
                        text: 'Silakan pilih barang terlebih dahulu.'
                    });
                    return;
                }

                const selectedSku = selected.map(function() {
                    return $(this).val();
                }).get();

                Swal.fire({
                    title: 'Konfirmasi',
                    text: `Tandai ${selected.length} barang sudah di ambil?`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, Proses',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            type: "POST",
                            url: "{{ route('transaksi.updatestatus') }}",
                            data: {
                                _token: "{{ csrf_token() }}",
                                sku: selectedSku
                            },
                            dataType: "JSON",
                            success: function(response) {
                                console.log(response);
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

            let detailTable = null;
            $('#stockTableBody').on('click', '.btnDetail', function(e) {
                e.stopPropagation();
                const sku = $(this).data('sku');

                if (!sku || String(sku).trim() === '') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Data tidak valid',
                        text: 'Data pesanan tidak valid.'
                    });

                    return;
                }

                $('#detailSku').html(`
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-muted fw-semibold">SKU Produk :</span>
                        <span class="fw-bold text-primary">${sku}</span>
                    </div>
                `);

                $('#detailTableBody').empty();

                $.ajax({
                    type: "GET",
                    url: "{{ route('kebutuhan.detailpesanan', ':filter') }}".replace(':filter', filter),
                    dataType: "JSON",
                    success: function(response) {
                        $.each(data, function(index, item) {
                            $('#detailTableBody').append(`
                                <tr>
                                    <td>${index + 1}</td>

                                    <td>
                                        <span class="fw-semibold">
                                            ${item.no_pesanan}
                                        </span>
                                    </td>

                                    <td>
                                        ${item.customer}
                                    </td>

                                    <td>
                                        ${item.produk}
                                    </td>

                                    <td class="text-center">
                                        <span class="fw-bold">
                                            ${item.jumlah}
                                        </span>
                                    </td>

                                    <td class="text-center">
                                        <span class="badge bg-primary-subtle text-primary">
                                            ${item.status}
                                        </span>
                                    </td>

                                    <td>
                                        ${item.tanggal}
                                    </td>
                                </tr>
                            `);
                        });
                    }
                });

                // Masukkan ke tbody


                // Hancurkan DataTable lama jika ada
                if ($.fn.DataTable.isDataTable('#detailTable')) {
                    $('#detailTable').DataTable().destroy();
                }

                // Buat DataTable
                detailTable = $('#detailTable').DataTable({
                    pageLength: 10,
                    lengthChange: false,
                    searching: true,
                    ordering: true,
                    autoWidth: false,
                    responsive: true,
                    language: {
                        search: '',
                        searchPlaceholder: 'Cari data...',
                        emptyTable: 'Tidak ada data pesanan'
                    },
                    columnDefs: [{
                        targets: 0,
                        orderable: false,
                        searchable: false
                    }]
                });

                // Buka modal
                const modal = new bootstrap.Modal(
                    document.getElementById('detailModal')
                );

                modal.show();
            });
        });
    </script>
@endpush
