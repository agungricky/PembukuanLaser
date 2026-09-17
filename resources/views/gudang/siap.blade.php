@extends('layouts.app')
@section('content')
    <main class="flex-grow-1 overflow-auto p-3 p-lg-4">
        <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mb-4">
            <div>
                <h1 class="h3 fw-bold text-dark mb-1">Produk Perlu Disiapkan</h1>
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

                    <button type="button" class="btn btn-success btn-sm text-nowrap" id="btnDisiapkan">
                        <i class="fa-solid fa-circle-check me-1"></i>
                        Tandai Sudah Disiapkan
                    </button>
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
                            <th scope="col" class="py-3 px-4 text-center">Stok</th>
                            <th scope="col" class="py-3 px-4 text-center" id="filteron">Status Stok</th>
                            <th scope="col" class="py-3 px-4 text-center">Detail Pesanan</th>
                        </tr>
                    </thead>
                    <tbody id="stockTableBody"></tbody>
                </table>
                <div id="loadingData" class="text-center py-3" style="display: none;">
                    <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
                    <span class="ms-2">Memuat data...</span>
                </div>
            </div>
        </section>
    </main>

    @include('layouts.footer')

    <!-- Modal Detail -->
    <div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow">

                <div class="modal-header">
                    <div>
                        <h5 class="modal-title fw-bold">Detail Kebutuhan Terhadap Pesanan</h5>
                        <small class="text-muted" id="detailSku"></small>
                    </div>

                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="table-responsive">
                        <table id="detailTable" class="table table-hover align-middle w-100">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 50px;">No</th>
                                    <th>Pesanan</th>
                                    <th>Produk</th>
                                    <th class="text-center">Qty</th>
                                    <th>Pengiriman</th>
                                    <th class="text-center">Status</th>
                                    <th>Tanggal Order</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>

                            <tbody id="detailTableBody"></tbody>
                        </table>
                    </div>
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
    </style>
@endpush

@push('scripts')
    <script>
        $(document).ready(function() {
            let filter = "{{ $page }}";
            let selected = [];
            let table = null;
            let cekDetail = [];

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

            // Menghilangkan Checkbox Setelah Halaman Refresh
            $('#checkAll').prop('checked', false);
            $('.item-checkbox').prop('checked', false);

            $.ajax({
                type: "GET",
                url: "{{ route('showdata.json', ':filter') }}".replace(':filter', filter),
                dataType: "JSON",

                // ============ Loading Sebelum ada data ============== //
                beforeSend: function() {
                    if ($.fn.DataTable.isDataTable('#orderlist')) {
                        $('#orderlist').DataTable().destroy();
                    }

                    const jumlahKolom = $('#orderlist thead th').length;
                    const kolomTengah = Math.floor(jumlahKolom / 2);

                    let tdLoading = '';

                    for (let i = 0; i < jumlahKolom; i++) {
                        if (i === kolomTengah) {
                            tdLoading += `
                            <td class="text-center py-3">
                                <div class="d-flex align-items-center justify-content-center gap-2">
                                    <div
                                        class="spinner-border spinner-border-sm text-primary"
                                        role="status"
                                        style="width: 16px; height: 16px;">
                                    </div>

                                    <span class="text-muted" style="font-size: 12px;">
                                        Memuat...
                                    </span>
                                </div>
                            </td>
                        `;
                        } else {
                            tdLoading += `<td></td>`;
                        }
                    }

                    $('#stockTableBody').html(`
                        <tr class="loading-row">
                            ${tdLoading}
                        </tr>
                    `);
                },

                success: function(response) {
                    let html = '';

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
                                                <span class="fw-semibold text-dark d-inline-block"
                                                    style="max-width: 180px; white-space: normal; word-break: break-word;">
                                                    ${item.produk?.nama_produk ?? '-'}
                                                </span>

                                                <span class="badge bg-light text-secondary border fw-normal"
                                                    style="font-size: 10px; letter-spacing: .3px;">
                                                    SKU: <span class="sku">${item.produk?.sku ?? '-'}</span>
                                                </span>
                                            </div>
                                        </td>
                                        <td class="py-3 px-4 text-start">
                                            ${(item.produk?.variasi ?? '-').replace(/,\s*/g, '<br>')}
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
                                                (item.produk?.stok_produk?.jumlah_tersedia ?? 0) >= (item.kebutuhan ?? 0) ? 
                                                `
                                                                                        <span class="badge bg-success">Tersedia</span>
                                                                                    ` 
                                                : 
                                                `
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

                    if ($.fn.DataTable.isDataTable('#orderlist')) {
                        $('#orderlist').DataTable().destroy();
                        table = null;
                    }

                    $('#stockTableBody').html(html);
                    table = new DataTable('#orderlist', {
                        pageLength: 10,
                        searching: true,
                        lengthChange: false,
                        autoWidth: false
                    });

                    table.search($('#searchTable').val()).draw();
                },

                error: function(xhr) {
                    console.log(xhr.responseText);
                }
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

                // Kalau klik area row
                const checked = !checkbox.prop('checked');
                checkbox.prop('checked', checked);
                $(this).toggleClass(
                    'table-active',
                    checked
                );
            });

            // Submit Barang Disiapkan
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

            // Button View Detail
            let detailTable = null;
            $('#stockTableBody').on('click', '.btnDetail', function(e) {
                e.stopPropagation();

                let sku = $(this).data('sku');
                if (sku == null || String(sku).trim() === '') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Data tidak valid',
                        text: 'Data pesanan tidak valid.'
                    });
                    return;
                }

                $('#detailSku').html(`
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-muted fw-semibold">
                            SKU Produk :
                        </span>

                        <span class="badge bg-primary-subtle text-primary fs-6">
                            ${sku}
                        </span>
                    </div>
                `);

                if ($.fn.DataTable.isDataTable('#detailTable')) {
                    $('#detailTable').DataTable().destroy();
                    detailTable = null;
                }

                bootstrap.Modal
                    .getOrCreateInstance(document.getElementById('detailModal'))
                    .show();

                $.ajax({
                    type: "GET",
                    url: "{{ route('kebutuhan.detailpesanan', ['filter' => ':filter', 'sku' => ':sku']) }}"
                        .replace(':filter', filter)
                        .replace(':sku', sku),
                    dataType: "JSON",

                    // Loading Sebelum Data Muncul
                    beforeSend: function() {
                        $('#detailTableBody').html(`
                            <tr>
                                <td></td>
                                <td></td>
                                <td></td>

                                <td class="text-center py-4" colspan="2">
                                    <div class="d-flex align-items-center justify-content-center gap-2">

                                        <div class="spinner-border spinner-border-sm text-primary"
                                            role="status">
                                        </div>

                                        <span class="text-muted" style="font-size: 12px;">
                                            Memuat data...
                                        </span>

                                    </div>
                                </td>

                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                        `);
                    },

                    success: function(response) {
                        $('#detailTableBody').empty();
                        $.each(response, function(index, item) {
                            const pesanan = item.pesanan ?? {};
                            const namaPembeli = pesanan.nama_pembeli ?? '-';
                            const username = pesanan.username ?? '-';
                            const kurir = pesanan.kurir ?? '-';
                            const noResi = pesanan.no_resi ?? '-';
                            const status = pesanan.status ?? '-';
                            const tanggal = pesanan.tanggal ?
                                new Date(pesanan.tanggal).toLocaleDateString('id-ID', {
                                    day: '2-digit',
                                    month: 'short',
                                    year: 'numeric'
                                }) : '-';
                            let statusBadge = 'bg-secondary-subtle text-secondary';

                            if (status === 'proses') {
                                statusBadge = 'bg-warning-subtle text-warning';
                            } else if (status === 'selesai') {
                                statusBadge = 'bg-success-subtle text-success';
                            } else if (status === 'batal') {
                                statusBadge = 'bg-danger-subtle text-danger';
                            } else if (status === 'packing') {
                                statusBadge = 'bg-info-subtle text-info';
                            }

                            $('#detailTableBody').append(`
                                <tr>
                                    <td class="text-center">
                                        <span class="fw-bold text-muted">
                                            ${index + 1}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark">
                                            ${item.pesanan?.no_pesanan ?? '-'}
                                        </div>

                                        <div class="small mt-1">
                                            <i class="fa-solid fa-user text-muted me-1"></i>
                                            ${item.pesanan?.nama_pembeli ?? '-'}
                                        </div>
                                        <div class="text-muted" style="font-size: 11px;">
                                            @${item.pesanan?.username}
                                        </div>
                                    </td>
                                    <td style="max-width: 300px;">
                                        <div class="fw-semibold text-dark">
                                            ${item.produk.nama_produk ?? '-'}
                                        </div>
                                        <div class="d-flex gap-2 mt-1 flex-wrap">
                                            <span class="badge bg-light text-dark border">
                                                ${item.produk.variasi ?? '-'}
                                            </span>
                                            <span class="badge bg-primary-subtle text-primary">
                                                ${item.sku ?? '-'}
                                            </span>
                                        </div>
                                        <div class="text-muted mt-1" style="font-size: 11px;">
                                            Rp ${Number(item.harga ?? 0).toLocaleString('id-ID')}
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge rounded-pill bg-primary fs-6 px-3">
                                            ${item.jumlah ?? 0}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="fw-semibold">
                                            ${kurir}
                                        </div>

                                        <div class="text-muted mt-1" style="font-size: 11px;">
                                            <i class="fa-solid fa-barcode me-1"></i>
                                            ${noResi}
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge ${statusBadge} text-uppercase">
                                            ${status}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="fw-semibold">
                                            ${tanggal}
                                        </div>
                                        <hr class="m-0 p-0">
                                        <div class="text-muted mt-1" style="font-size: 11px;">
                                            <i class="fa-solid fa-store me-1"></i>
                                            ${item.pesanan.toko.nama_toko ?? '-'}
                                        </div>
                                    </td>
                                    <td>
                                        <button type="button"
                                            class="btn btn-success btn-sm btn-selesai"
                                            data-no-pesanan="${pesanan.no_pesanan}"
                                            ${cekDetail.includes(pesanan.no_pesanan) ? 'disabled' : ''}>
                                            Selesai
                                        </button>
                                    </td>
                                </tr>
                            `);
                        });

                        detailTable = $('#detailTable').DataTable({
                            pageLength: 10,
                            lengthChange: true,
                            searching: true,
                            ordering: true,
                            autoWidth: false,
                            responsive: true,

                            language: {
                                search: '',
                                searchPlaceholder: 'Cari pesanan...',
                                emptyTable: 'Tidak ada data pesanan',
                                zeroRecords: 'Data tidak ditemukan',
                                info: 'Menampilkan _START_ - _END_ dari _TOTAL_ pesanan',
                                infoEmpty: 'Tidak ada data'
                            },

                            columnDefs: [{
                                targets: 0,
                                orderable: false,
                                searchable: true
                            }]
                        });
                    },

                    error: function(xhr) {
                         Swal.fire({
                            icon: 'error',
                            title: 'Gagal',
                            text: 'Gagal mengambil detail pesanan.'
                        });

                        $('#detailTableBody').html(`
                            <tr>
                                <td colspan="8" class="text-center py-4 text-danger">
                                    Gagal memuat data.
                                </td>
                            </tr>
                        `);

                        console.log(xhr.responseText);
                    }
                });
            });

            $(document).on('click', '.btn-selesai', function() {
                const noPesanan = String($(this).data('no-pesanan')).trim();
                if (!cekDetail.includes(noPesanan)) {
                    cekDetail.push(noPesanan);
                }
                $(this).prop('disabled', true);
            });

            $(document).on('hidden.bs.modal', '#detailModal', function() {
                if (cekDetail.length > 0) {

                    $.ajax({
                        type: "POST",
                        url: "{{ route('transaksi.update.selesai') }}",
                        data: {
                            no_pesanan: cekDetail,
                            _token: "{{ csrf_token() }}"
                        },
                        dataType: "json",
                        success: function(response) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Data Berhasil Diubah',
                                text: 'Data telah berubah. Kami akan memperbarui jumlah kebutuhan.',
                                confirmButtonText: 'OK'
                            }).then(() => {
                                cekDetail = [];
                                location.reload();
                            });
                        },
                        error: function(xhr) {
                            console.log(xhr.responseText);
                        }
                    });

                }
            });

        });
    </script>
@endpush
