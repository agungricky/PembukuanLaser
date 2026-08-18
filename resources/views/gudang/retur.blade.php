@extends('layouts.app')
@section('content')
    <main class="flex-grow-1 overflow-auto p-3 p-lg-4">

        <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mb-4">
            <div>
                <h1 class="h3 fw-bold text-dark mb-1">
                    Daftar Barang Retur
                </h1>
                <span class="text-muted">
                    <i class="fa-solid fa-vial"></i>
                    Barang Retur
                </span>
                <span class="mx-2 text-secondary">/</span>
                <span class="fw-semibold text-primary">
                    Semua Barang Retur
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
                        Barang Retur
                    </h2>
                    <p class="text-muted small mb-0">
                        Menampilkan Semua Daftar Barang Retur.
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
                        data-bs-target="#modalRetur">
                        <i class="fa-solid fa-rotate-left me-1"></i>
                        Tambah Stok Barang Retur
                    </button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Pesanan</th>
                            <th>Pembeli</th>
                            <th>Pengiriman</th>
                            <th>Status</th>
                            <th>Keuangan</th>
                            <th>Produk</th>
                            <th>SKU / Variasi</th>
                            <th class="text-center">Jumlah</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($pesanan as $item)
                            @php
                                $perproduk = $item->pesanan_per_produk->count();
                            @endphp

                            @foreach ($item->pesanan_per_produk as $produk)
                                <tr>
                                    {{-- DATA PESANAN: hanya tampil di row pertama --}}
                                    @if ($loop->first)
                                        <!-- PESANAN -->
                                        <td rowspan="{{ $perproduk }}" class="px-3">
                                            <div class="fw-semibold text-dark">
                                                {{ $item->no_pesanan ?? '-' }}
                                            </div>

                                            <div class="text-muted mt-1" style="font-size: 11px;">
                                                <i class="fa-regular fa-calendar me-1"></i>
                                                {{ $item->tanggal ? \Carbon\Carbon::parse($item->tanggal)->locale('id')->translatedFormat('d F Y') : '-' }}
                                            </div>

                                            <div class="text-muted mt-1" style="font-size: 11px;">
                                                <i class="fa-solid fa-user-gear me-1"></i>
                                                Input : {{ $item->user->name ?? '-' }}
                                            </div>
                                        </td>

                                        <!-- PEMBELI -->
                                        <td rowspan="{{ $perproduk }}" class="px-3">
                                            <div class="fw-semibold text-dark">
                                                {{ \Illuminate\Support\Str::limit($item->nama_pembeli ?? '-', 10, '...') }}
                                            </div>

                                            <div class="text-muted" style="font-size: 11px;">
                                                <i class="fa-regular fa-user me-1"></i>
                                                {{ $item->username ?? '-' }}
                                            </div>
                                        </td>

                                        <!-- PENGIRIMAN -->
                                        <td rowspan="{{ $perproduk }}" class="px-3">
                                            <div class="fw-semibold text-dark" style="font-size: 12px;">
                                                {{ $item->no_resi ?? '-' }}
                                            </div>

                                            <div class="text-muted mt-1" style="font-size: 11px;">
                                                <i class="fa-solid fa-truck me-1"></i>
                                                {{ $item->kurir ?? '-' }}
                                            </div>
                                        </td>

                                        <!-- STATUS -->
                                        <td rowspan="{{ $perproduk }}" class="px-3 text-center">
                                            <span class="badge bg-warning-subtle text-warning-emphasis px-2 py-1">
                                                {{ $item->status ?? '-' }}
                                            </span>

                                            <div class="text-muted mt-1" style="font-size: 10px;">
                                                <i class="fa-solid fa-store me-1"></i>
                                                {{ $item->toko->nama_toko ?? '-' }}
                                            </div>
                                        </td>

                                        <!-- KEUANGAN -->
                                        <td rowspan="{{ $perproduk }}" class="px-3">
                                            <div class="text-muted" style="font-size: 10px;">
                                                Total HPP
                                            </div>

                                            <div class="fw-semibold text-dark text-nowrap">
                                                Rp {{ number_format($item->total_hpp ?? 0, 0, ',', '.') }}
                                            </div>

                                            <div class="text-muted mt-2" style="font-size: 10px;">
                                                Pencairan
                                            </div>

                                            <div class="fw-semibold text-danger text-nowrap">
                                                Rp {{ number_format($item->pencairan ?? 0, 0, ',', '.') }}
                                            </div>
                                        </td>
                                    @endif


                                    <!-- PRODUK -->
                                    <td class="px-3 border-start">
                                        <div class="fw-normal text-dark" style="font-size: 12px; line-height: 1.2;">
                                            {{ $produk->nama_produk ?? '-' }}
                                        </div>
                                    </td>

                                    <!-- SKU + VARIASI -->
                                    <td class="px-3">
                                        <div class="fw-semibold" style="font-size: 12px;">
                                            {{ $produk->sku ?? '-' }}
                                        </div>

                                        <div class="text-muted" style="font-size: 11px;">
                                            {{ $produk->variasi ?? '-' }}
                                        </div>
                                    </td>

                                    <!-- JUMLAH -->
                                    <td class="text-center">
                                        <div class="d-flex flex-column">
                                            <div>
                                                <div class="text-muted" style="font-size: 9px;">
                                                    Pesanan
                                                </div>
                                                <div class="fw-semibold text-dark" style="font-size: 13px;">
                                                    {{ $produk->jumlah ?? 0 }}
                                                </div>
                                            </div>

                                            <div class="mt-1">
                                                <div class="text-muted" style="font-size: 9px;">
                                                    Masuk Stok
                                                </div>
                                                <div class="fw-semibold text-success" style="font-size: 13px;">
                                                    {{ $produk->retur->diterima ?? 0 }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>

                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>

        </section>
    </main>

    <!-- Footer -->
    @include('layouts.footer')

    {{-- Modal Retur --}}
    <div class="modal fade" id="modalRetur" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow">

                <div class="modal-header border-0 pb-0">
                    <div>
                        <h5 class="modal-title fw-bold">
                            Penerimaan Barang Retur
                        </h5>
                        <small class="text-muted">
                            Masukkan nomor pesanan untuk mencari produk retur.
                        </small>
                    </div>

                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">

                    <!-- NOMOR PESANAN -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold mb-1">
                            Nomor Pesanan
                        </label>

                        <div class="input-group">
                            <span class="input-group-text bg-light">
                                <i class="fa-solid fa-receipt text-muted"></i>
                            </span>

                            <input type="text" id="no_pesanan_retur" class="form-control"
                                placeholder="Masukkan nomor pesanan...">

                            <button type="button" class="btn btn-primary" id="btnCariPesanan">
                                <i class="fa-solid fa-magnifying-glass me-1"></i>
                                Cari
                            </button>
                        </div>
                    </div>

                    <!-- LOADING -->
                    <div id="loadingRetur" class="text-center py-4 d-none">
                        <div class="spinner-border spinner-border-sm text-primary"></div>
                        <div class="text-muted mt-2" style="font-size: 12px;">
                            Mencari pesanan...
                        </div>
                    </div>

                    <!-- DATA PESANAN -->
                    <div id="detailPesananRetur" class="d-none">

                        <div class="bg-light rounded-3 px-3 py-2 mb-3">
                            <div class="row g-2">

                                <div class="col-md-3 border-end">
                                    <small class="text-muted">
                                        Nomor Pesanan
                                    </small>
                                    <div class="fw-semibold" id="detailNoPesanan">
                                        -
                                    </div>
                                </div>

                                <div class="col-md-6 border-end">
                                    <small class="text-muted">
                                        Pembeli
                                    </small>
                                    <div class="fw-semibold" id="detailPembeli">
                                        -
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <small class="text-muted">
                                        Toko
                                    </small>
                                    <div class="fw-semibold" id="detailToko">
                                        -
                                    </div>
                                </div>

                            </div>
                        </div>

                        <form id="FormRetur">
                            @csrf

                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Produk</th>
                                            <th>SKU / Variasi</th>
                                            <th class="text-center">
                                                Pesanan
                                            </th>
                                            <th style="width: 140px;">
                                                Diterima
                                            </th>
                                        </tr>
                                    </thead>

                                    <tbody id="tableProdukRetur">
                                    </tbody>
                                </table>
                            </div>

                        </form>

                    </div>

                    <!-- PESAN TIDAK DITEMUKAN -->
                    <div id="returNotFound" class="text-center py-4 d-none">

                        <i class="fa-solid fa-circle-exclamation text-warning fs-3"></i>

                        <div class="fw-semibold mt-2" id="message">
                            Pesanan tidak ditemukan
                        </div>

                        <small class="text-muted">
                            Periksa kembali nomor pesanan.
                        </small>
                    </div>

                </div>

                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                        Batal
                    </button>

                    <button type="submit" class="btn btn-primary" form="FormRetur" id="btnSimpanRetur" disabled>
                        <i class="fa-solid fa-box-open me-1"></i>
                        Simpan Barang Diterima
                    </button>
                </div>

            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $('#modalRetur').on('shown.bs.modal', function() {
            $('#no_pesanan_retur').val('');
            $('#tableProdukRetur').html('');
            $('#detailPesananRetur').addClass('d-none');
            $('#returNotFound').addClass('d-none');
            $('#loadingRetur').addClass('d-none');
            $('#btnSimpanRetur').prop('disabled', true);
        });

        $('#btnCariPesanan').on('click', function() {
            const noPesanan = $('#no_pesanan_retur').val().trim();

            if (!noPesanan) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Nomor Pesanan',
                    text: 'Masukkan nomor pesanan terlebih dahulu.'
                });

                return;
            }

            $('#detailPesananRetur').addClass('d-none');
            $('#returNotFound').addClass('d-none');
            $('#loadingRetur').removeClass('d-none');
            $('#btnSimpanRetur').prop('disabled', true);

            $.ajax({
                type: 'GET',
                url: "{{ route('gudang.retur.json', ':no_pesanan') }}".replace(':no_pesanan', noPesanan),
                dataType: 'JSON',
                success: function(response) {
                    console.log(response);
                    $('#loadingRetur').addClass('d-none');

                    const pesanan = response.data;
                    $('#detailNoPesanan').text(pesanan[0].no_pesanan ?? '-');
                    $('#detailPembeli').text(pesanan[0].pesanan.nama_pembeli ?? '-');
                    $('#detailToko').text(pesanan[0].pesanan.toko.nama_toko ?? '-');

                    let html = '';
                    pesanan.forEach(function(item) {
                        html += `
                            <tr class="align-middle">

                                <!-- PRODUK -->
                                <td class="py-2 px-3">
                                    <div class="fw-semibold text-dark"
                                        style="font-size: 12px; line-height: 1.25;">
                                        ${item.nama_produk ?? '-'}
                                    </div>
                                </td>

                                <!-- VARIASI + SKU -->
                                <td class="py-2 px-3">
                                    <div class="fw-semibold text-dark"
                                        style="font-size: 12px;">
                                        ${item.variasi ?? '-'}
                                    </div>

                                    <div class="text-muted mt-1"
                                        style="font-size: 10px; line-height: 1;">
                                        <i class="fa-solid fa-barcode me-1"></i>
                                        ${item.sku ?? '-'}
                                    </div>
                                </td>

                                <!-- JUMLAH PESANAN -->
                                <td class="py-2 px-3 text-center">
                                    <span class="badge bg-primary-subtle text-primary rounded-pill px-3 py-2">
                                        ${item.jumlah ?? 0}
                                    </span>
                                </td>

                                <!-- DITERIMA -->
                                <td class="py-2 px-3" style="width: 130px;">

                                    <input type="hidden"
                                        name="produk[${item.id_per_produk}][per_produk_id]"
                                        value="${item.id_per_produk}">

                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text bg-light border-end-0">
                                            <i class="fa-solid fa-box-open text-muted"></i>
                                        </span>

                                        <input type="number"
                                            name="produk[${item.id_per_produk}][diterima]"
                                            class="form-control border-start-0 input-diterima text-center fw-semibold"
                                            min="0"
                                            max="${item.jumlah ?? 0}"
                                            value="0">
                                    </div>

                                </td>

                            </tr>
                        `;
                    });

                    $('#tableProdukRetur').html(html);
                    $('#detailPesananRetur').removeClass('d-none');
                    $('#btnSimpanRetur').prop('disabled', false);
                },

                error: function(xhr) {
                    const message = xhr.responseJSON?.message || 'Data tidak ditemukan.';
                    
                    $('#loadingRetur').addClass('d-none');
                    $('#returNotFound')
                        .removeClass('d-none')
                        .text(message);
                    $('#btnSimpanRetur').prop('disabled', true);
                    $('#tableProdukRetur').html('');
                }
            });
        });

        // Tekan enter cari pesanan
        $('#no_pesanan_retur').on('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();

                $('#btnCariPesanan').click();
            }
        });

        $('#FormRetur').on('submit', function(e) {
            e.preventDefault();

            const data = $(this).serialize();

            $.ajax({
                type: 'POST',
                url: "{{ route('gudang.retur.create') }}",
                data: data,
                dataType: 'JSON',
                beforeSend: function() {
                    $('#btnSimpanRetur')
                        .prop('disabled', true)
                        .html(`
                    <span class="spinner-border spinner-border-sm me-1"></span>
                    Menyimpan...
                `);
                },

                success: function(response) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: response.message ?? 'Data retur berhasil disimpan.',
                        timer: 1800,
                        showConfirmButton: false
                    });

                    $('#FormRetur')[0].reset();
                    $('#tableProdukRetur').html('');
                    $('#detailPesananRetur').addClass('d-none');

                    $('#modalRetur').modal('hide');
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
                    $('#btnSimpanRetur')
                        .prop('disabled', false)
                        .html(`
                    <i class="fa-solid fa-box-open me-1"></i>
                    Simpan Retur
                `);
                }
            });
        });
    </script>
@endpush
