@extends('layouts.app')
@section('content')
    <main class="flex-grow-1 overflow-auto p-3 p-lg-4">
        <!-- Dashboard Title & Pulse Badge -->
        <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mb-4">

            <div>
                <h1 class="h3 fw-bold text-dark mb-1">
                    SKU PRODUK
                </h1>

                <span class="text-muted">
                    <i class="fa-solid fa-box"></i>
                    Master Data
                </span>

                <span class="mx-2 text-secondary">/</span>

                <span class="fw-semibold text-primary">
                    SKU PRODUK
                </span>
            </div>

            <div>
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal"
                    data-bs-target="#tambahProdukModal">
                    <i class="fa-solid fa-plus me-1"></i>
                    Tambah Produk
                </button>
            </div>

        </div>

        <!-- Inventory Stock Table Section -->
        <section id="stockTableSection" class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
            <div
                class="card-header bg-white border-bottom p-3 p-md-4 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                <div>
                    <h2 class="h5 fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                        <i class="fa-solid fa-box"></i>
                        Daftar Semua Produk
                    </h2>
                    <p class="text-muted small mb-0">
                        Menampilkan Semua Produk.
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
                        <option value="10">10</option>
                        <option value="20" selected>20</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>

                    <button type="button" class="btn btn-light border text-success btn-sm text-nowrap"
                        data-bs-toggle="modal" data-bs-target="#importExcelModal">
                        <i class="fa-solid fa-file-arrow-up me-1"></i>
                        Import Excel
                    </button>

                    <button type="button" class="btn btn-light border text-success btn-sm text-nowrap"
                        data-bs-toggle="modal" data-bs-target="#exportExcelModal">
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
                            <th scope="col" class="py-3 px-4 text-center">No</th>
                            <th scope="col" class="py-3 px-4 text-center">Nama Produk</th>
                            <th scope="col" class="py-3 px-4 text-center">Variasi</th>
                            <th scope="col" class="py-3 px-4 text-center">Hpp</th>
                            <th scope="col" class="py-3 px-4 text-center">Tersedia</th>
                            <th scope="col" class="py-3 px-4 text-center">Aksi</th>
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

    {{-- Produk IMPORT --}}
    <div class="modal fade" id="importExcelModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">

                <form id="formImportProduk" action="{{ route('produk.import') }}" method="POST"
                    enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <h6 class="modal-title">
                            Import Produk Custom
                        </h6>

                        <button type="button" class="btn-close" data-bs-dismiss="modal">
                        </button>
                    </div>

                    <div class="modal-body">
                        <label class="form-label small">
                            Pilih File Excel
                        </label>

                        <input type="file" id="fileImport" name="file" class="form-control" accept=".xlsx,.xls,.csv"
                            required>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                            Batal
                        </button>

                        <button type="submit" class="btn btn-success">
                            <i class="fa-solid fa-file-import me-1"></i>
                            Import
                        </button>
                    </div>

                </form>

            </div>
        </div>
    </div>

    {{-- Produk EXPORT --}}
    <div class="modal fade" id="exportExcelModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">

                <form action="{{ route('produk.export') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fa-solid fa-file-excel text-success me-2"></i>
                            Export Excel
                        </h5>

                        <button type="button" class="btn-close" data-bs-dismiss="modal">
                        </button>
                    </div>

                    <div class="modal-body">

                        <div class="mb-3">
                            <label class="form-label">
                                Pilih Data
                            </label>

                            <select name="filter" class="form-select">
                                <option value="all" selected>Semua Kategori</option>

                                @foreach ($kategori as $item)
                                    <option value="{{ $item->id }}">
                                        {{ $item->nama_kategori }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">
                            Batal
                        </button>

                        <button type="submit" class="btn btn-success">
                            <i class="fa-solid fa-file-excel me-1"></i>
                            Export
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>

    {{-- Modal Tambah --}}
    <div class="modal fade" id="tambahProdukModal" tabindex="-1" aria-labelledby="tambahProdukModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content border-0 shadow">

                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="tambahProdukModalLabel">
                        Tambah Produk
                    </h5>

                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Tutup"></button>
                </div>

                <form id="formaddsku">
                    @csrf

                    <div class="modal-body">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    SKU
                                    <span class="text-danger">*</span>
                                </label>

                                <input type="text" name="sku" id="sku"
                                    class="form-control @error('sku') is-invalid @enderror" value="{{ old('sku') }}"
                                    maxlength="50" placeholder="Contoh: LBL" required>

                                @error('sku')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    Produk Custom ?
                                    <span class="text-danger">*</span>
                                </label>

                                <select name="custom" id="custom" class="form-select" required>
                                    <option value="T">Bukan Custom</option>
                                    <option value="Y">Custom</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">
                                Nama Produk
                            </label>

                            <input type="text" name="nama_produk"
                                class="form-control @error('nama_produk') is-invalid @enderror"
                                value="{{ old('nama_produk') }}" maxlength="255" placeholder="Boleh dikosongkan">

                            @error('nama_produk')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror

                            <div class="form-text">
                                Nama produk tidak wajib diisi.
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    Variasi
                                </label>

                                <input type="text" name="variasi"
                                    class="form-control @error('variasi') is-invalid @enderror"
                                    value="{{ old('variasi') }}" maxlength="255" placeholder="Contoh: Cowo A - Kuning">

                                @error('variasi')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror

                                <div class="form-text">
                                    Variasi produk tidak wajib diisi.
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">
                                    Kategori
                                    <span class="text-danger">*</span>
                                </label>

                                <select name="kategori_id" id="kategori_id"
                                    class="form-select @error('kategori') is-invalid @enderror" required>
                                    @foreach ($kategori as $item)
                                        <option value="{{ $item->id }}">{{ $item->nama_kategori }}</option>
                                    @endforeach
                                </select>

                                @error('kategori')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">
                                HPP
                                <span class="text-danger">*</span>
                            </label>

                            <div class="input-group">
                                <span class="input-group-text">Rp</span>

                                <input type="text" id="hpp_display"
                                    class="form-control @error('hpp') is-invalid @enderror" placeholder="Rp 0"
                                    autocomplete="off" required>

                                <input type="hidden" name="hpp" id="hpp" value="{{ old('hpp') }}">

                                @error('hpp')
                                    <div class="invalid-feedback">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                        </div>

                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            Batal
                        </button>

                        <button type="button" class="btn btn-success" id="viewstore">
                            <i class="bi bi-save me-1"></i>
                            Simpan
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editProdukModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content border-0 shadow">

                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Edit Produk</h5>

                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal">
                    </button>
                </div>

                <form id="formEditProduk" method="POST">
                    @csrf
                    @method('PATCH')

                    <div class="modal-body">

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">SKU</label>
                                <input type="text" id="edit_sku" class="form-control" lock>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Status</label>
                                <select name="status" id="edit_status" class="form-select">
                                    <option value="aktif">Aktif</option>
                                    <option value="nonaktif">Nonaktif</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Nama Produk</label>

                            <input type="text" name="nama_produk" id="edit_nama_produk" class="form-control"
                                maxlength="255">
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Variasi</label>

                                <input type="text" name="variasi" id="edit_variasi" class="form-control"
                                    maxlength="255">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label">Kategori</label>

                                <select name="kategori_id" id="edit_kategori" class="form-select">
                                    @foreach ($kategori as $item)
                                        <option value="{{ $item->id }}">
                                            {{ $item->nama_kategori }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">HPP</label>

                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="text" id="edit_hpp_display" class="form-control" autocomplete="off"
                                    required>
                                <input type="hidden" name="hpp" id="edit_hpp">
                            </div>
                        </div>

                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            Batal
                        </button>

                        <button type="submit" id="btnupdate" class="btn btn-primary">
                            <i class="fa-solid fa-save me-1"></i>
                            Perbarui
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .row-nonactive td:not(.action-column) {
            text-decoration: line-through;
            opacity: 0.5;
        }

        .row-nonactive .action-column {
            opacity: 1;
            text-decoration: none;
        }
    </style>
@endpush

@push('scripts')
    <script>
        $(document).ready(function() {

            $('#kategori').select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: 'Pilih Kategori',
                allowClear: true,
                dropdownParent: $('#tambahProdukModal')
            });

            $('#edit_kategori').select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: 'Pilih Kategori',
                allowClear: true,
                dropdownParent: $('#formEditProduk')
            });

            // ============= Data Tables ================== //
            const table = new DataTable('#orderlist', {
                pageLength: 10,
                searching: true,
                lengthChange: false,
                autoWidth: false
            });

            function loadSkuTable() {

                if ($.fn.DataTable.isDataTable('#orderlist')) {
                    $('#orderlist').DataTable().destroy();
                }

                let table = $('#orderlist').DataTable({
                    ajax: {
                        url: "{{ route('sku.json') }}",
                        dataSrc: ''
                    },

                    pageLength: 10,
                    lengthChange: false,
                    lengthMenu: [10, 20, 25, 50, 100],
                    searching: true,
                    autoWidth: false,

                    columns: [
                        {
                            data: null,
                            className: 'text-center',
                            render: function(data, type, row, meta) {
                                return meta.row + 1;
                            }
                        },
                        {
                            data: null,
                            render: function(data, type, row) {

                                let words = (row.nama_produk ?? '')
                                    .trim()
                                    .split(/\s+/);

                                let nama = '';

                                for (let i = 0; i < words.length; i += 3) {
                                    nama += words.slice(i, i + 3).join(' ') + '<br>';
                                }

                                return `
                                    <div class="fw-bold text-success fs-6">
                                        ${nama}
                                    </div>

                                    <small class="text-muted">
                                        Sku : ${row.sku ?? ''}
                                    </small>
                                `;
                            }
                        },
                        {
                            data: null,
                            render: function(data, type, row) {

                                let kategori = row.kategori ?
                                    row.kategori.nama_kategori :
                                    '';

                                return `
                                    <div class="fw-semibold">
                                        ${row.variasi ?? ''}
                                    </div>

                                    <small class="text-muted">
                                        Kategori : ${kategori}
                                    </small>
                                `;
                            }
                        },
                        {
                            data: 'hpp',
                            className: 'text-center',
                            render: function(data) {
                                let hpp = new Intl.NumberFormat('id-ID')
                                    .format(data ?? 0);

                                return `
                                    <div class="fw-bold text-success fs-6">
                                        Rp ${hpp}
                                    </div>

                                    <small class="text-muted">
                                        / Item
                                    </small>
                                `;
                            }
                        },
                        {
                            data: null,
                            className: 'text-center',
                            render: function(data, type, row) {

                                let stok = row.stok_produk ?
                                    row.stok_produk.jumlah_tersedia :
                                    0;

                                let badge = '';

                                if (stok < 5) {
                                    badge = 'bg-danger';
                                } else if (stok == 5) {
                                    badge = 'bg-warning text-dark';
                                } else {
                                    badge = 'bg-success';
                                }

                                return `
                                    <span class="badge ${badge}">
                                        ${stok}
                                    </span>
                                `;
                            }
                        },
                        {
                            data: null,
                            orderable: false,
                            searchable: false,
                            className: 'text-center action-column',
                            render: function(data, type, row) {
                                return `
                                    <button
                                        type="button"
                                        class="btn btn-warning btn-sm btnedit"
                                        data-sku="${row.sku}"
                                        data-btn="edit"
                                    >
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>
                                `;
                            }
                        }
                    ],

                    createdRow: function(row, data) {
                        if (data.status === 'nonaktif') {
                            $(row).addClass('row-nonactive');
                        }
                    }
                });


                $('#per_page').val(10);
                $('#per_page')
                    .off('change')
                    .on('change', function() {
                        table.page.len(parseInt(this.value, 10)).draw();
                    });

                $('#searchTable')
                    .off('input')
                    .on('input', function() {
                        table.search(this.value).draw();
                    });
            }

            loadSkuTable();

            // ============= ================== //
            $(document).on('input', '#jumlah_add, #jumlah_edit', function() {
                this.value = this.value.replace(/\D/g, '');
            });

            // ============= Select 2 Form Kategori ================== //
            $('#exportFilter').select2({
                dropdownParent: $('#exportExcelModal'),
                placeholder: 'Pilih kategori...',
                allowClear: true,
                width: '100%'
            });

            // ============= IMPORT ================== //
            $('#formImportProduk').on('submit', function(e) {
                e.preventDefault();

                const formData = new FormData(this);
                $.ajax({
                    type: 'POST',
                    url: "{{ route('produk.import') }}",
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (!response.status) {
                            Swal.fire({
                                icon: 'info',
                                title: 'Tidak Ada Perubahan',
                                text: response.message
                            });

                            return;
                        }

                        let daftar = '';
                        response.data.forEach(function(item) {

                            const lama = Number(item.hpp_lama)
                                .toLocaleString('id-ID');

                            const baru = Number(item.hpp_baru)
                                .toLocaleString('id-ID');

                            daftar += `
                                <tr>
                                    <td class="text-start">${item.sku}</td>
                                    <td class="text-start">${item.nama_produk}</td>
                                    <td class="text-end">Rp ${lama}</td>
                                    <td class="text-end fw-bold text-success">
                                        Rp ${baru}
                                    </td>
                                </tr>
                            `;
                        });

                        Swal.fire({
                            title: `${response.jumlah} Produk Akan Diubah`,
                            html: `
                                <div style="max-height:350px; overflow-y:auto;">
                                    <table class="table table-sm table-bordered mb-0">
                                        <thead style="position: sticky; top: 0; background: white; z-index: 2;">
                                            <tr>
                                                <th>SKU</th>
                                                <th>Produk</th>
                                                <th>HPP Lama</th>
                                                <th>HPP Baru</th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            ${daftar}
                                        </tbody>
                                    </table>
                                </div>

                                <div class="mt-3">
                                    Yakin ingin memperbarui data tersebut?
                                </div>
                            `,
                            icon: 'question',
                            showCancelButton: true,
                            confirmButtonText: 'Ya, Update',
                            cancelButtonText: 'Tidak',
                            width: '850px'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                konfirmasiImport(response.token);
                            }

                        });
                    },
                    error: function(xhr, status, error) {
                        console.log('XHR:', xhr);
                        console.log('Status HTTP:', xhr.status);
                        console.log('Status:', status);
                        console.log('Error:', error);
                        console.log('Response JSON:', xhr.responseJSON);
                        console.log('Response Text:', xhr.responseText);

                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal Membaca Excel',
                            text: xhr.responseJSON?.message ?? error ??
                                'Terjadi kesalahan.'
                        });
                    }
                });
            });

            function konfirmasiImport(token) {
                Swal.fire({
                    title: 'Memperbarui Data...',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                $.ajax({
                    type: 'POST',
                    url: "{{ route('produk.import.confirm') }}",
                    data: {
                        _token: "{{ csrf_token() }}",
                        token: token
                    },
                    success: function(response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil',
                            text: response.message,
                            timer: 1800,
                            showConfirmButton: false
                        }).then(() => {
                            table.ajax.reload(null, false);
                            const modalElement = document.getElementById('importExcelModal');
                            const modal = bootstrap.Modal.getInstance(modalElement);

                            if (modal) {
                                modal.hide();
                            }

                            location.reload();

                        });
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Update Gagal',
                            text: xhr.responseJSON?.message ?? 'Semua perubahan dibatalkan.'
                        });

                    }
                });
            }

            // Format Rupiah
            function formatRupiah(angka) {
                return new Intl.NumberFormat('id-ID', {
                    style: 'currency',
                    currency: 'IDR',
                    minimumFractionDigits: 0
                }).format(angka);
            }

            $('#hpp_display').on('input', function() {
                let angka = $(this).val().replace(/\D/g, '');
                $('#hpp').val(angka);
                if (angka) {
                    $(this).val(formatRupiah(angka));
                } else {
                    $(this).val('');
                    $('#hpp').val('');
                }

            });

            // ============= Replace inputan field SKU ================== //
            $('#sku').on('input', function() {
                this.value = this.value
                    .toUpperCase()
                    .replace(/[^A-Z]/g, '');
            });

            // ============= Create Sku ========================= //
            $('#viewstore').on('click', function() {
                let form = $('#formaddsku').serialize();

                $.ajax({
                    type: "POST",
                    url: "{{ route('sku.viewstore') }}",
                    data: form,
                    dataType: "json",
                    success: function(response) {

                        Swal.fire({
                            title: 'Konfirmasi Data',
                            width: 11200,
                            padding: '1.5rem',
                            html: `
                                    <div class="row g-3 text-start">

                                        <div class="col-12">
                                            <div class="border rounded-3 p-3 h-100 bg-light">

                                                <div class="d-flex align-items-center mb-3">
                                                    <div class="d-flex align-items-center justify-content-center rounded-circle bg-secondary bg-opacity-10 me-2"
                                                        style="width:32px; height:32px;">
                                                        <i class="fa-solid fa-clock-rotate-left text-secondary"></i>
                                                    </div>

                                                    <div>
                                                        <div class="fw-bold" style="font-size:14px;">
                                                            Data Terakhir
                                                        </div>
                                                        <small class="text-muted">
                                                            SKU terakhir ditemukan
                                                        </small>
                                                    </div>
                                                </div>

                                                <div class="d-flex text-nowrap border-top pt-3" style="font-size:13px;">

                                                    <div class="pe-3 border-end" style="width:25%;">
                                                        <div class="text-muted mb-1" style="font-size:12px;">
                                                            <i class="fa-solid fa-barcode me-1"></i>
                                                            SKU
                                                        </div>

                                                        <div class="fw-bold">
                                                            ${response.terakhir.sku ?? '-'}
                                                        </div>
                                                    </div>

                                                    <div class="px-3 border-end" style="width:45%;">
                                                        <div class="text-muted mb-1" style="font-size:12px;">
                                                            <i class="fa-solid fa-box me-1"></i>
                                                            Produk
                                                        </div>

                                                        <div class="fw-semibold">
                                                            ${response.terakhir.nama_produk ?? '-'}
                                                        </div>
                                                    </div>

                                                    <div class="ps-3" style="width:30%;">
                                                        <div class="text-muted mb-1" style="font-size:12px;">
                                                            <i class="fa-solid fa-layer-group me-1"></i>
                                                            Variasi
                                                        </div>

                                                        <div class="fw-semibold">
                                                            ${response.terakhir.variasi ?? '-'}
                                                        </div>
                                                    </div>

                                                </div>
                                            </div>
                                        </div>


                                        <div class="col-12">
                                            <div class="border border-success border-opacity-25 rounded-3 p-3 h-100">

                                                <div class="d-flex align-items-center mb-3">
                                                    <div class="d-flex align-items-center justify-content-center rounded-circle bg-success bg-opacity-10 me-2"
                                                        style="width:32px; height:32px;">
                                                        <i class="fa-solid fa-circle-plus text-success"></i>
                                                    </div>

                                                    <div>
                                                        <div class="fw-bold" style="font-size:14px;">
                                                            Data Baru
                                                        </div>
                                                        <small class="text-muted">
                                                            SKU yang akan dibuat
                                                        </small>
                                                    </div>
                                                </div>

                                                <div class="d-flex text-nowrap border-top pt-3" style="font-size:13px;">

                                                    <div class="pe-3 border-end" style="width:25%;">
                                                        <div class="text-muted mb-1" style="font-size:12px;">
                                                            <i class="fa-solid fa-barcode me-1"></i>
                                                            SKU
                                                        </div>

                                                        <div class="fw-bold text-success">
                                                            ${response.baru.sku ?? '-'}
                                                        </div>
                                                    </div>

                                                    <div class="px-3 border-end" style="width:45%;">
                                                        <div class="text-muted mb-1" style="font-size:12px;">
                                                            <i class="fa-solid fa-box me-1"></i>
                                                            Produk
                                                        </div>

                                                        <div class="fw-semibold">
                                                            ${response.baru.nama_produk ?? '-'}
                                                        </div>
                                                    </div>

                                                    <div class="ps-3" style="width:30%;">
                                                        <div class="text-muted mb-1" style="font-size:12px;">
                                                            <i class="fa-solid fa-layer-group me-1"></i>
                                                            Variasi
                                                        </div>

                                                        <div class="fw-semibold">
                                                            ${response.baru.variasi ?? '-'}
                                                        </div>
                                                    </div>

                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                `,
                            icon: 'question',
                            width: 650,
                            showCancelButton: true,
                            confirmButtonText: 'Ya, Buat Data',
                            cancelButtonText: 'Batal'
                        }).then((result) => {

                            if (result.isConfirmed) {
                                $.ajax({
                                    type: "POST",
                                    url: "{{ route('sku.store') }}",
                                    data: response.baru,
                                    dataType: "json",
                                    success: function(response) {
                                        Swal.fire({
                                            icon: 'success',
                                            title: 'Berhasil!',
                                            text: response
                                                .message ??
                                                'Data berhasil disimpan.',
                                            confirmButtonText: 'OK'
                                        });

                                        $('#formaddsku')[0].reset();
                                        location.reload();
                                    }
                                });
                            }

                        });
                    }
                });
            });

            $('#edit_hpp_display').on('input', function() {
                let angka = $(this).val().replace(/\D/g, '');
                $('#edit_hpp').val(angka);
                if (angka) {
                    $(this).val(formatRupiah(angka));
                } else {
                    $(this).val('');
                }
            });

            $(document).on('click', '.btnedit', function() {
                let sku = $(this).data('sku');

                $.ajax({
                    type: "GET",
                    url: "{{ route('sku.edit', ':sku') }}".replace(':sku', sku),
                    dataType: "JSON",
                    success: function(response) {
                        $('#edit_sku').val(response.sku);
                        $('#edit_nama_produk').val(response.nama_produk);
                        $('#edit_variasi').val(response.variasi);
                        $('#edit_hpp_display').val(
                            new Intl.NumberFormat('id-ID').format(response.hpp)
                        );
                        $('#edit_status').val(response.status);
                        $('#edit_hpp').val(response.hpp);

                        let modal = new bootstrap.Modal(
                            document.getElementById('editProdukModal')
                        );

                        $('#edit_kategori')
                            .val(response.kategori_id ?? '')
                            .trigger('change');

                        modal.show();
                    },

                    error: function(xhr) {
                        console.log(xhr.responseText);
                    }
                });

            });

            $('#btnupdate').on('click', function() {
                let form = $('#formEditProduk').serialize();

                $('#formEditProduk').on('submit', function(e) {
                    e.preventDefault();

                    let sku = $('#edit_sku').val();

                    $.ajax({
                        type: "PATCH",
                        url: "{{ route('sku.update', ':sku') }}".replace(':sku', sku),
                        data: $(this).serialize(),
                        dataType: "json",
                        success: function(response) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Berhasil',
                                text: response.message ??
                                    'Data berhasil diperbarui.',
                                showConfirmButton: false,
                                timer: 1200
                            }).then(() => {
                                const modal = bootstrap.Modal.getInstance(
                                    document.getElementById(
                                        'editProdukModal')
                                );

                                modal.hide();
                                location.reload();
                            });
                        },
                        error: function(xhr) {
                            console.log(xhr.responseJSON);
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal',
                                text: xhr.responseJSON?.message ??
                                    'Terjadi kesalahan saat memperbarui data.'
                            });
                        }
                    });
                });
            });
        });
    </script>
@endpush
