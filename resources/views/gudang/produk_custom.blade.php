@extends('layouts.app')
@section('content')
    <main class="flex-grow-1 overflow-auto p-3 p-lg-4">

        <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mb-4">
            <div>
                <h1 class="h3 fw-bold text-dark mb-1">
                    Daftar Produk Custom
                </h1>
                <span class="text-muted">
                    <i class="fa-solid fa-pen-ruler"></i>
                    Produk Custom
                </span>
                <span class="mx-2 text-secondary">/</span>
                <span class="fw-semibold text-primary">
                    Semua Produk
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
                        <i class="fa-solid fa-pen-ruler"></i>
                        Produk Custom
                    </h2>
                    <p class="text-muted small mb-0">
                        Halaman ini sementara digunakan hanya untuk record barang custom.
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

                        <i class="fa-solid fa-plus me-1"></i>
                        Tambah Transaksi
                    </button>
                </div>
            </div>

            <table class="table table-hover align-middle mb-0 text-nowrap" id="orderlist">
                <thead>
                    <tr>
                        <th width="50">No</th>
                        <th>Pesanan</th>
                        <th>Produk Custom</th>
                        <th>Detail Produk</th>
                        <th class="text-center">Qty</th>
                        <th>Harga</th>
                        <th class="text-center">Status Pesanan</th>
                    </tr>
                </thead>

                <tbody>
                    @php
                        $no = 1;
                    @endphp

                    @foreach ($pesanan as $item)
                        @foreach ($item->pesanan_per_produk as $produk)
                            @if ($produk->custom == 1)
                                <tr>

                                    {{-- NO --}}
                                    <td class="text-center text-muted">
                                        {{ $no++ }}
                                    </td>


                                    {{-- PESANAN --}}
                                    <td>
                                        <div class="fw-semibold text-dark">
                                            {{ $item->no_pesanan ?? '-' }}
                                        </div>

                                        <div class="text-muted mt-1" style="font-size: 11px;">
                                            <i class="fa-solid fa-user me-1"></i>
                                            {{ $item->nama_pembeli ?? '-' }}
                                        </div>

                                        <div class="text-muted" style="font-size: 10px;">
                                            {{ $item->tanggal ? \Carbon\Carbon::parse($item->tanggal)->locale('id')->translatedFormat('d M Y') : '-' }}
                                        </div>
                                    </td>


                                    {{-- PRODUK CUSTOM --}}
                                    <td>
                                        <div class="fw-semibold text-dark">
                                            {{ \Illuminate\Support\Str::limit($produk->nama_produk ?? '-', 40, '...') }}
                                        </div>

                                        <div class="text-muted mt-1" style="font-size: 11px;">
                                            <i class="fa-solid fa-barcode me-1"></i>
                                            SKU : {{ $produk->sku ?? '-' }}
                                        </div>
                                    </td>


                                    {{-- DETAIL PRODUK --}}
                                    <td>
                                        <div class="fw-semibold text-dark" style="font-size: 12px;">
                                            {{ $produk->variasi ?? '-' }}
                                        </div>

                                        <div class="mt-1">
                                            <span class="badge bg-primary-subtle text-primary">
                                                Custom
                                            </span>
                                        </div>
                                    </td>


                                    {{-- JUMLAH --}}
                                    <td class="text-center">
                                        <span class="fw-semibold">
                                            {{ $produk->jumlah ?? 0 }}
                                        </span>
                                    </td>


                                    {{-- HARGA --}}
                                    <td>
                                        <div class="fw-semibold text-dark">
                                            Rp {{ number_format($produk->harga ?? 0, 0, ',', '.') }}
                                        </div>

                                        <div class="text-muted mt-1" style="font-size: 10px;">
                                            HPP Rp {{ number_format($produk->hpp ?? 0, 0, ',', '.') }}
                                        </div>
                                    </td>


                                    {{-- STATUS PESANAN --}}
                                    <td class="text-center">

                                        @if ($produk->status_pesanan == 0)
                                            <span class="badge bg-warning-subtle text-warning">
                                                <i class="fa-solid fa-clock me-1"></i>
                                                Belum Diproses
                                            </span>
                                        @elseif ($produk->status_pesanan == 1)
                                            <span class="badge bg-success-subtle text-success">
                                                <i class="fa-solid fa-check me-1"></i>
                                                Selesai
                                            </span>
                                        @else
                                            <span class="badge bg-secondary-subtle text-secondary">
                                                {{ $produk->status_pesanan }}
                                            </span>
                                        @endif

                                    </td>
                                </tr>
                            @endif
                        @endforeach
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
                                {{-- @foreach ($allproduk as $item)
                                    <option value="{{ $item->sku }}" data-sku="{{ $item->sku }}"
                                        data-kategori="{{ $item->kategori->nama_kategori ?? '-' }}"
                                        data-variasi="{{ $item->variasi ?? '-' }}">

                                        {{ $item->nama_produk }}
                                        | {{ $item->variasi }}
                                        | {{ $item->sku }}
                                        | {{ $item->kategori->nama_kategori ?? '-' }}

                                    </option>
                                @endforeach --}}
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
                                    {{-- 
                                    @foreach ($user as $user)
                                        <option value="{{ $user->id }}">
                                            {{ $user->name }}
                                        </option>
                                    @endforeach --}}

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
    </script>
@endpush
