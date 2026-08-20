@extends('layouts.app')
@section('content')
    <main class="flex-grow-1 overflow-auto p-3 p-lg-4">

        <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mb-4">
            <div>
                <h1 class="h3 fw-bold text-dark mb-1">
                    Management Kategori
                </h1>
                <span class="text-muted">
                    <i class="fa-solid fa-tags"></i>
                    Kategori
                </span>
                <span class="mx-2 text-secondary">/</span>
                <span class="fw-semibold text-primary">
                    Semua Kategori Produk
                </span>
            </div>

            <div>
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal"
                    data-bs-target="#tambahKategoriModal">

                    <i class="fa-solid fa-plus me-1"></i>
                    Tambah Kategori
                </button>
            </div>
        </div>

        <!-- Inventory Stock Table Section -->
        <section id="stockTableSection" class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
            <!-- Table Header Bar -->
            <div
                class="card-header bg-white border-bottom p-3 p-md-4 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                <div>
                    <h2 class="h5 fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                        <i class="fa-solid fa-tags"></i>
                        Kategori Produk
                    </h2>
                    <p class="text-muted small mb-0">
                        Menampilkan Semua Daftar Kategori.
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
                        <option value="10" selected>10</option>
                        <option value="20">20</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
            </div>

            <table class="table table-hover align-middle mb-0 text-nowrap" id="orderlist">
                <thead class="table-light">
                    <tr>
                        <th scope="col" class="py-3 px-4 text-center">No</th>
                        <th scope="col" class="py-3 px-4 text-center">Nama Kategori</th>
                        <th scope="col" class="py-3 px-4 text-center">jumlah Produk</th>
                        <th scope="col" class="py-3 px-4 text-center">Produk Aktif</th>
                        <th scope="col" class="py-3 px-4 text-center">Produk Nonaktif</th>
                        <th scope="col" class="py-3 px-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $no = 1;
                    @endphp
                    @foreach ($kategori as $item)
                        <tr class="category-row">

                            <td class="py-3 px-4 text-center text-muted fw-semibold">
                                {{ $no++ }}
                            </td>

                            <td class="py-3 px-4">
                                <div class="d-flex align-items-center gap-3">

                                    <div class="category-icon">
                                        <i class="fa-solid fa-layer-group"></i>
                                    </div>

                                    <div>
                                        <div class="fw-bold text-dark">
                                            {{ $item->nama_kategori }}
                                        </div>

                                        <small class="text-muted">
                                            Kategori Produk
                                        </small>
                                    </div>

                                </div>
                            </td>

                            <td class="py-3 px-4 text-center">
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2">
                                    {{ $item->jumlah_produk }}
                                </span>
                            </td>

                            <td class="py-3 px-4 text-center">
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2">
                                    <i class="fa-solid fa-circle-check me-1"></i>
                                    {{ $item->produk_aktif }}
                                </span>
                            </td>

                            <td class="py-3 px-4 text-center">
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-3 py-2">
                                    <i class="fa-solid fa-circle-xmark me-1"></i>
                                    {{ $item->produk_nonaktif }}
                                </span>
                            </td>

                            <td class="py-3 px-4 text-center">
                                <button type="button" class="btn btn-outline-primary btn-sm rounded-3 btnViewKategori"
                                    data-id="{{ $item->id }}">
                                    <i class="fa-solid fa-eye me-1"></i>
                                    View
                                </button>

                                <button type="button" class="btn btn-outline-warning btn-sm rounded-3 btnEditKategori"
                                    data-id="{{ $item->id }}">
                                    <i class="fa-solid fa-pen-to-square me-1"></i>
                                    Edit
                                </button>

                                <button type="button" class="btn btn-outline-danger btn-sm rounded-3 btnDeleteKategori"
                                    data-id="{{ $item->id }}" data-nama="{{ $item->nama_kategori }}">
                                    <i class="fa-solid fa-trash me-1"></i>
                                    Hapus
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>
    </main>

    <!-- Footer -->
    @include('layouts.footer')

    <!-- Modal: view -->
    <div class="modal fade" id="modal" tabindex="-1" aria-labelledby="Modal" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden">

                <div class="modal-header bg-light border-bottom px-4 py-3">
                    <div id="modalheader"></div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                {{-- Body --}}
                <div class="modal-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 text-nowrap" id="produkKategoriTable">
                            <thead class="table-light">
                                <tr>
                                    <th class="py-3 px-4 text-center" style="width: 60px;">No</th>
                                    <th class="py-3 px-4">SKU</th>
                                    <th class="py-3 px-4">Nama Produk</th>
                                    <th class="py-3 px-4">Variasi</th>
                                    <th class="py-3 px-4 text-end">HPP</th>
                                    <th class="py-3 px-4 text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody id="produkKategoriBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Edit --}}
    <div class="modal fade" id="modalEditKategori" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">

                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fa-solid fa-pen-to-square me-2"></i>
                        Edit Kategori
                    </h5>

                    <button type="button" class="btn-close" data-bs-dismiss="modal">
                    </button>
                </div>

                <form id="formEditKategori">
                    @csrf

                    <input type="hidden" id="edit_kategori_id">

                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">
                                Nama Kategori
                                <span class="text-danger">*</span>
                            </label>

                            <input type="text" name="nama_kategori" id="edit_nama_kategori" class="form-control"
                                required>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            Batal
                        </button>

                        <button type="submit" class="btn btn-primary">
                            <i class="fa-solid fa-save me-1"></i>
                            Simpan Perubahan
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>

    {{-- Modal Add --}}
    <div class="modal fade" id="tambahKategoriModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fa-solid fa-tags me-2"></i>
                        Tambah Kategori
                    </h5>

                    <button type="button" class="btn-close" data-bs-dismiss="modal">
                    </button>
                </div>

                <form id="formTambahKategori">
                    @csrf

                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">
                                Nama Kategori
                                <span class="text-danger">*</span>
                            </label>

                            <input type="text" name="nama_kategori" id="nama_kategori" class="form-control"
                                maxlength="255" placeholder="Contoh: Gantungan Kunci" required>

                            <div class="invalid-feedback" id="error_nama_kategori">
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                            Batal
                        </button>
                        <button type="submit" class="btn btn-primary" id="btnSimpanKategori">

                            <i class="fa-solid fa-save me-1"></i>
                            Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        #modal .dt-length {
            margin-left: 16px;
        }

        #modal .dt-search {
            display: flex !important;
            align-items: center;
            justify-content: flex-end;
            padding: 0px 12px;
            gap: 6px;
        }

        #modal .dt-search label {
            font-size: 11px;
            color: #6c757d;
            margin: 0;
        }

        #modal .dt-search input {
            width: 160px !important;
            height: 30px;
            padding: 4px 9px;
            font-size: 11px;
            border: 1px solid #dee2e6;
            border-radius: 7px;
            background: #fff;
            outline: none;
            margin-left: 4px !important;
        }

        #modal .dt-search input:focus {
            border-color: #86b7fe;
            box-shadow: 0 0 0 2px rgba(13, 110, 253, 0.08);
        }

        .category-row {
            transition: all 0.2s ease;
        }

        .category-row:hover {
            background-color: #f8f9fa;
        }

        .category-icon {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(13, 110, 253, 0.1);
            color: #0d6efd;
            font-size: 14px;
        }

        .category-row .badge {
            min-width: 70px;
            font-size: 12px;
            font-weight: 600;
        }

        .btnViewKategori {
            min-width: 80px;
            font-weight: 600;
        }
    </style>
@endpush

@push('scripts')
    <script>
        $(document).ready(function() {

            const table = new DataTable('#orderlist', {
                pageLength: 10,
                searching: true,
                lengthChange: false,
                autoWidth: false
            });

            $('#per_page').val(10);
            $('#per_page').on('change', function() {
                table.page.len(parseInt(this.value)).draw();
            });

            $('#searchTable').on('input', function() {
                table.search(this.value).draw();
            });

            $(document).on('click', '.btnViewKategori', function() {
                const id = $(this).data('id');

                $.ajax({
                    type: "GET",
                    url: "{{ route('gudang.kategori.json', ':id') }}".replace(':id', id),
                    dataType: "JSON",
                    success: function(response) {
                        console.log(response);
                        // Header modal
                        $('#modalheader').html(`
                            <div>
                                <h5 class="modal-title fw-bold mb-0">
                                    <i class="bi bi-caret-right-fill"></i> Detail Kategori : ${response?.kategori?.nama_kategori}
                                </h5>
                                <small class="text-muted ms-3">
                                    &ensp; Daftar produk sesuai kategori
                                </small>
                            </div>
                        `);

                        if ($.fn.DataTable.isDataTable('#produkKategoriTable')) {
                            $('#produkKategoriTable').DataTable().destroy();
                        }

                        let rows = '';
                        $.each(response.data, function(index, item) {

                            const status = item.status === 'aktif' ?
                                `<span class="badge bg-success-subtle text-success">Aktif</span>` :
                                `<span class="badge bg-danger-subtle text-danger">Nonaktif</span>`;
                            rows += `
                                        <tr>
                                            <td class="text-center">
                                                ${index + 1}
                                            </td>

                                            <td class="fw-semibold text-primary">
                                                ${item.sku}
                                            </td>

                                            <td>
                                                ${item.nama_produk}
                                            </td>

                                            <td>
                                                ${item.variasi ?? '-'}
                                            </td>

                                            <td class="text-end">
                                                Rp ${Number(item.hpp).toLocaleString('id-ID')}
                                            </td>

                                            <td class="text-center">
                                                ${status}
                                            </td>
                                        </tr>
                                `;
                        });

                        $('#produkKategoriBody').html(rows);
                        $('#produkKategoriTable').DataTable({
                            pageLength: 10,
                            searching: true,
                            lengthChange: true,
                            autoWidth: true
                        });

                        const modal = bootstrap.Modal.getOrCreateInstance(
                            document.getElementById('modal')
                        );

                        modal.show();
                    }
                });
            });

            $('.btnEditKategori').on('click', function() {
                let id = $(this).data('id');

                $.ajax({
                    type: "GET",
                    url: "{{ route('kategori.edit', ':id') }}".replace(':id', id),
                    dataType: "json",

                    success: function(response) {
                        $('#edit_kategori_id').val(response.id);
                        $('#edit_nama_kategori').val(response.nama_kategori);

                        let modal = new bootstrap.Modal(
                            document.getElementById('modalEditKategori')
                        );

                        modal.show();
                    },

                    error: function(xhr) {
                        console.log(xhr.responseJSON);
                    }
                });
            });

            $('#formEditKategori').on('submit', function(e) {
                e.preventDefault();

                let id = $('#edit_kategori_id').val();

                $.ajax({
                    type: "PATCH",
                    url: "{{ route('kategori.update', ':id') }}".replace(':id', id),
                    data: $(this).serialize(),
                    dataType: "json",

                    success: function(response) {
                        bootstrap.Modal
                            .getInstance(document.getElementById('modalEditKategori'))
                            .hide();

                        Swal.fire({
                            icon: 'success',
                            title: response.message ?? 'Kategori berhasil diperbarui.',
                            showConfirmButton: false,
                            timer: 1000
                        }).then(() => {
                            location.reload();
                        });
                    },

                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal',
                            text: xhr.responseJSON?.message ?? 'Terjadi kesalahan.'
                        });
                    }
                });
            });

            $('#formTambahKategori').on('submit', function(e) {
                e.preventDefault();

                let form = $(this);
                let button = $('#btnSimpanKategori');

                // reset error
                $('#nama_kategori').removeClass('is-invalid');
                $('#error_nama_kategori').text('');

                button.prop('disabled', true).html(`
                    <span class="spinner-border spinner-border-sm me-1"></span>
                    Menyimpan...
                `);

                $.ajax({
                    type: "POST",
                    url: "{{ route('kategori.store') }}",
                    data: form.serialize(),
                    dataType: "json",
                    success: function(response) {
                        $('#formTambahKategori')[0].reset();

                        bootstrap.Modal
                            .getInstance(document.getElementById('tambahKategoriModal'))
                            .hide();

                        Swal.fire({
                            icon: 'success',
                            title: response.message ?? 'Kategori berhasil ditambahkan.',
                            showConfirmButton: false,
                            timer: 1000
                        }).then(() => {
                            location.reload();
                        });
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            let errors = xhr.responseJSON.errors;
                            if (errors.nama_kategori) {
                                $('#nama_kategori').addClass('is-invalid');
                                $('#error_nama_kategori').text(
                                    errors.nama_kategori[0]
                                );
                            }
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal',
                                text: xhr.responseJSON?.message ??
                                    'Terjadi kesalahan saat menyimpan kategori.'
                            });

                        }
                    },
                    complete: function() {
                        button.prop('disabled', false).html(`
                            <i class="fa-solid fa-save me-1"></i>
                            Simpan
                        `);
                    }
                });

            });

            $('.btnDeleteKategori').on('click', function() {

                let id = $(this).data('id');
                let nama = $(this).data('nama');

                Swal.fire({
                    title: 'Hapus Kategori?',
                    html: `
                        Kategori <b>${nama}</b> akan dihapus.
                        <br>
                        <small class="text-muted">
                            Data masih dapat dipulihkan karena menggunakan soft delete.
                        </small>
                    `,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: '<i class="fa-solid fa-trash me-1"></i> Ya, Hapus',
                    cancelButtonText: 'Batal',
                    confirmButtonColor: '#dc3545'
                }).then((result) => {

                    if (result.isConfirmed) {

                        $.ajax({
                            type: "DELETE",
                            url: "{{ route('kategori.destroy', ':id') }}"
                                .replace(':id', id),

                            data: {
                                _token: "{{ csrf_token() }}"
                            },

                            dataType: "json",

                            success: function(response) {

                                Swal.fire({
                                    icon: 'success',
                                    title: response.message ??
                                        'Kategori berhasil dihapus.',
                                    showConfirmButton: false,
                                    timer: 1000
                                }).then(() => {
                                    location.reload();
                                });

                            },

                            error: function(xhr) {

                                Swal.fire({
                                    icon: 'error',
                                    title: 'Gagal',
                                    text: xhr.responseJSON?.message ??
                                        'Kategori gagal dihapus.'
                                });

                            }
                        });

                    }

                });

            });
        });
    </script>
@endpush
