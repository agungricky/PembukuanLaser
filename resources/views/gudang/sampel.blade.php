@extends('layouts.app')
@section('content')
    <main class="flex-grow-1 overflow-auto p-3 p-lg-4">

        <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mb-4">
            <div>
                <h1 class="h3 fw-bold text-dark mb-1">
                    Daftar Barang Sampel
                </h1>
                <span class="text-muted">
                    <i class="fa-solid fa-vial"></i>
                    Barang Sampel
                </span>
                <span class="mx-2 text-secondary">/</span>
                <span class="fw-semibold text-primary">
                    Semua Barang Sampel
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
                        <i class="fa-solid fa-vial"></i>
                        Barang Sampel
                    </h2>
                    <p class="text-muted small mb-0">
                        Menampilkan Semua Daftar barang Sampel.
                    </p>
                </div>

                <!-- Controls: Filters & Table Search -->
                <div class="d-flex flex-nowrap align-items-center gap-2">
                    <!-- Table Search Input -->
                    <div class="input-group input-group-sm" style="max-width: 240px;">
                        <span class="input-group-text bg-light border-end-0">
                            <i class="fa-solid fa-magnifying-glass text-muted"></i>
                        </span>
                        <input type="text" id="searchTable" placeholder="Cari data"
                            class="form-control form-control-sm border-start-0 bg-light" />
                    </div>

                    <select id="per_page" class="form-select form-select-sm" style="width: auto;">
                        <option value="10" selected>10</option>
                        <option value="20">20</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>

                    <button type="button" class="btn btn-primary text-nowrap" data-bs-toggle="modal"
                        data-bs-target="#modalPermintaanSampel" id="btnmodal">

                        <i class="fa-solid fa-flask me-1"></i>
                        Tambah Barang Sampel
                    </button>
                </div>
            </div>

            <table class="table table-hover align-middle mb-0 text-nowrap" id="orderlist">
                <thead class="table-light">
                    <tr>
                        <th scope="col" class="py-3 px-4 text-center">No</th>
                        <th scope="col" class="py-3 px-4 text-center">Nama Produk</th>
                        <th scope="col" class="py-3 px-4 text-center">Qty</th>
                        <th scope="col" class="py-3 px-4 text-center">Jenis Mutasi</th>
                        <th scope="col" class="py-3 px-4 text-center">Petugas Gudang</th>
                        <th scope="col" class="py-3 px-4 text-center">Peminta Sampel</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $no = 1;
                    @endphp
                    @foreach ($produk as $item)
                        <tr class="category-row">

                            <td class="py-3 px-4 text-center text-muted fw-semibold">
                                {{ $no++ }}
                            </td>

                            <td class="py-3 px-4">
                                <div>
                                    <div class="fw-bold text-dark">
                                        {{ $item->stok_produk->produk->nama_produk ?? '-' }}
                                    </div>

                                    <div class="d-flex align-items-center gap-2 text-muted"
                                        style="font-size: 13px; line-height: 1.1;">

                                        <span>
                                            SKU: {{ $item->stok_produk->produk->sku ?? '-' }}
                                        </span>

                                        <span style="font-size: 15px;">|</span>

                                        <span>
                                            Kategori :
                                            {{ $item->stok_produk->produk->kategori->nama_kategori ?? '-' }}
                                        </span>

                                    </div>
                                </div>
                            </td>

                            <td class="py-3 px-4 text-center">
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2">
                                    {{ $item->jumlah }}
                                </span>
                            </td>

                            <td class="py-3 px-4 text-center">
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-2">
                                    <i class="fa-solid fa-circle-check me-1"></i>
                                    {{ $item->jenis_mutasi }}
                                </span>
                            </td>

                            <td class="py-0 px-4 text-center">
                                <div class="d-flex align-items-start justify-content-start gap-2 pb-1">
                                    <div class="d-flex align-items-center justify-content-center rounded-circle bg-primary-subtle text-primary"
                                        style="width: 28px; height: 28px; font-size: 12px;">
                                        <i class="fa-solid fa-user"></i>
                                    </div>

                                    <span class="fw-semibold text-dark" style="font-size: 14px;">
                                        {{ $item->user->name ?? '-' }}
                                    </span>
                                </div>

                                <div class="text-muted d-flex align-items-center justify-content-center gap-2"
                                    style="font-size: 13px; line-height: 1.1;">

                                    <span>
                                        <i class="fa-regular fa-calendar me-1"></i>
                                        {{ $item->updated_at ? $item->updated_at->format('d/m/Y') : '-' }}
                                    </span>

                                    <span>
                                        <i class="fa-regular fa-clock me-1"></i>
                                        {{ $item->updated_at ? $item->updated_at->format('H:i') : '-' }}
                                    </span>

                                </div>
                            </td>

                            <td class="py-0 px-4 text-center">
                                <div class="d-flex align-items-start justify-content-start gap-2 pb-1">
                                    <div class="d-flex align-items-center justify-content-center rounded-circle bg-primary-subtle text-primary"
                                        style="width: 28px; height: 28px; font-size: 12px;">
                                        <i class="fa-solid fa-user"></i>
                                    </div>

                                    <span class="fw-semibold text-dark" style="font-size: 14px;">
                                        {{ $item->ambilBarang->name ?? '-' }}
                                    </span>
                                </div>

                                <div class="text-muted d-flex align-items-center justify-content-center gap-2"
                                    style="font-size: 13px; line-height: 1.1;">

                                    <span>
                                        <i class="fa-regular fa-calendar me-1"></i>
                                        {{ $item->updated_at ? $item->updated_at->format('d/m/Y') : '-' }}
                                    </span>

                                    <span>
                                        <i class="fa-regular fa-clock me-1"></i>
                                        {{ $item->updated_at ? $item->updated_at->format('H:i') : '-' }}
                                    </span>

                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>
    </main>

    <!-- Footer -->
    @include('layouts.footer')

    <!-- Modal Permintaan Sampel -->
    <div class="modal fade" id="modalPermintaanSampel" tabindex="-1" aria-labelledby="modalPermintaanSampelLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">

                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title fw-bold" id="modalPermintaanSampelLabel">
                            Permintaan Sampel
                        </h5>
                        <small class="text-muted">
                            Pilih produk dan masukkan nama peminta sampel.
                        </small>
                    </div>

                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <form id="Form">
                    @csrf
                    <div class="modal-body pt-4">
                        <!-- Produk -->
                        <div class="mb-3">
                            <label for="produk_id" class="form-label fw-semibold mb-0">
                                Produk
                            </label>
                            <select name="produk_id" id="produk_id" class="form-select" required>
                                <option value="">Pilih Produk</option>
                                @foreach ($allproduk as $item)
                                    <option value="{{ $item->sku }}" data-sku="{{ $item->sku }}"
                                        data-kategori="{{ $item->kategori->nama_kategori ?? '-' }}"
                                        data-variasi="{{ $item->variasi ?? '-' }}">

                                        {{ $item->nama_produk }}
                                        | {{ $item->variasi }}
                                        | {{ $item->sku }}
                                        | {{ $item->kategori->nama_kategori ?? '-' }}

                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Nama Peminta Sampel -->
                        <div class="mb-3">
                            <label for="nama_peminta" class="form-label fw-semibold mb-0">
                                Nama Peminta Sampel
                            </label>

                            <div class="input-group">
                                <select name="nama_peminta" id="nama_peminta" class="form-select border-start-0"
                                    required>

                                    <option value="" selected disabled>
                                        Pilih Peminta Sampel
                                    </option>

                                    @foreach ($user as $user)
                                        <option value="{{ $user->id }}">
                                            {{ $user->name }}
                                        </option>
                                    @endforeach

                                </select>
                            </div>
                        </div>

                        <div class="mb-2">
                            <label for="jumlah" class="form-label fw-semibold mb-0">
                                Jumlah
                            </label>

                            <div class="input-group">
                                <span class="input-group-text bg-light">
                                    <i class="fa-solid fa-note-sticky text-muted"></i>
                                </span>

                                <input type="number" name="jumlah" id="jumlah" min="1" value="1"
                                    class="form-control" placeholder="Masukkan Jumlah Barang">
                            </div>
                        </div>

                    </div>

                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                            Batal
                        </button>

                        <button type="button" class="btn btn-primary px-4" id="btnsubmit">
                            <i class="fa-solid fa-paper-plane me-1"></i>
                            Tambahkan Barang Sampel
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
@endsection

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

            function formatProduk(data) {
                if (!data.id) {
                    return data.text;
                }

                const $option = $(data.element);
                const sku = $option.data('sku');
                const kategori = $option.data('kategori');
                const nama = data.text.split('|')[0].trim();
                const variasi = $option.data('variasi');

                return $(`
                    <div style="padding: 3px 0;">
                       <div class="fw-semibold text-dark">
                            ${nama}
                            <span class="text-muted fw-normal" style="font-size: 11px;">
                                • ${variasi}
                            </span>
                        </div>

                        <div class="text-muted" style="font-size: 10px;">
                            <i class="fa-solid fa-barcode me-1"></i>
                            ${sku}

                            <span class="mx-1">|</span>

                            <i class="fa-solid fa-layer-group me-1"></i>
                            ${kategori}
                        </div>
                    </div>
                `);
            }

            $('#produk_id').select2({
                dropdownParent: $('#modalPermintaanSampel'),
                placeholder: 'Cari nama produk atau SKU...',
                allowClear: true,
                width: '100%',
                templateResult: formatProduk
            });

            $('#nama_peminta').select2({
                dropdownParent: $('#modalPermintaanSampel'),
                placeholder: 'Cari nama peminta...',
                allowClear: true,
                width: '100%'
            });

            $('#jumlah').on('keydown', function(e) {
                const allowedKeys = [
                    'Backspace',
                    'Delete',
                    'Tab',
                    'ArrowLeft',
                    'ArrowRight',
                    'Home',
                    'End'
                ];

                if (allowedKeys.includes(e.key)) {
                    return;
                }

                if (!/^[0-9]$/.test(e.key)) {
                    e.preventDefault();
                }
            });

            $('#jumlah').on('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '');
            });

            $('#btnsubmit').on('click', function(e) {
                e.preventDefault();

                const data = $('#Form').serialize();

                $.ajax({
                    type: "POST",
                    url: "{{ route('gudang.sampel.create') }}",
                    data: data,
                    dataType: "JSON",
                    beforeSend: function() {
                        $('#btnsubmit').prop('disabled', true);
                    },

                    success: function(response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil',
                            text: response.message ??
                                'Permintaan sampel berhasil dibuat.',
                            timer: 1800,
                            showConfirmButton: false
                        });

                        $('#Form')[0].reset();
                        $('#produk_id').val(null).trigger('change');
                        $('#nama_peminta').val(null).trigger('change');
                        $('#jumlah').val(null).trigger('change');
                        $('#modalPermintaanSampel').modal('hide');

                        location.reload();
                    },

                    error: function(xhr) {
                        let message = xhr.responseJSON?.message ??
                            'Terjadi kesalahan. Silakan coba kembali.';

                        if (xhr.status === 422 && xhr.responseJSON?.errors) {
                            message = Object.values(xhr.responseJSON.errors)
                                .flat()
                                .join('<br>');
                        }

                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal',
                            html: message,
                            confirmButtonText: 'OK'
                        });
                    },

                    complete: function() {
                        $('#btnsubmit').prop('disabled', false);
                    }
                });
            });

            $('#btnmodal').on('click', function() {
                $('#Form')[0].reset();
                $('#produk_id').val(null).trigger('change');
                $('#nama_peminta').val(null).trigger('change');
                $('#jumlah').val('1');
            });
        });
    </script>
@endpush
