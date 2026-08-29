@extends('layouts.app')
@section('content')
    <main class="flex-grow-1 overflow-auto p-3 p-lg-4">
        <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mb-4">
            <div>
                <h1 class="h3 fw-bold text-dark mb-1" id="headertitle">PESANAN PRODUK {{ Str::upper($produksi) }}</h1>
                @if ($produksi == 'reguler')
                    <span class="text-muted">
                        <i class="fa-solid fa-arrow-right-arrow-left"></i>
                        Produksi
                    </span>
                    <span class="mx-2 text-secondary">/</span>
                    <span class="fw-semibold text-primary">
                        Pesanan Reguler
                    </span>
                @elseif ($produksi == 'custom')
                    <span class="text-muted">
                        <i class="fa-solid fa-arrow-right-arrow-left"></i>
                        Produksi
                    </span>
                    <span class="mx-2 text-secondary">/</span>
                    <span class="fw-semibold text-primary">
                        Pesanan Custom
                    </span>
                @endif
            </div>
        </div>

        @if ($produksi == 'reguler')
            <div class="alert alert-primary d-flex align-items-start gap-2 mb-3" role="alert">
                <i class="fa-solid fa-circle-info mt-1"></i>

                <div>
                    <strong>Informasi Kebutuhan Produksi</strong>
                    <div class="small mt-1">
                        Kolom <strong>Kebutuhan Produksi</strong> merupakan total kebutuhan dari
                        <strong>pesanan yang harus diproduksi</strong> ditambah
                        <strong>kebutuhan stok berdasarkan pesanan 7 hari terakhir</strong>.
                    </div>
                </div>
            </div>
        @endif

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
                        <option value="10">10</option>
                        <option value="20" selected>20</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>

                    <button type="button" class="btn btn-primary btn-sm text-nowrap" data-bs-toggle="modal"
                        data-bs-target="#pengambilModal" data-role="pegawai" id="btnPengambilModal">
                        <i class="fa-solid fa-file-import me-1"></i>
                        Import Excell
                    </button>
                    @if ($produksi === 'reguler')
                        <button type="button" class="btn btn-success btn-sm text-nowrap" id="btnreguler">
                            <i class="fa-solid fa-file-excel me-1"></i>
                            Export Excell
                        </button>
                    @elseif ($produksi === 'custom')
                        <button type="button" class="btn btn-success btn-sm text-nowrap" id="btncustom">
                            <i class="fa-solid fa-file-excel me-1"></i>
                            Export Excell
                        </button>
                    @endif
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
                            <th scope="col" class="py-3 px-4 text-center">Pesanan</th>
                            <th scope="col" class="py-3 px-4 text-center">Kebutuhan Produksi</th>
                        </tr>
                    </thead>
                    <tbody id="stockTableBody"></tbody>
                </table>
            </div>
        </section>
    </main>

    @include('layouts.footer')

    <!-- Modal Detail -->
    {{-- <div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content border-0 shadow">

                <div class="modal-header">
                    <div>
                        <h5 class="modal-title fw-bold" id="modalTitle"></h5>
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
                                </tr>
                            </thead>

                            <tbody id="detailTableBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div> --}}

    <!-- Modal Pengambil Barang -->
    {{-- <div class="modal fade" id="pengambilModal" tabindex="-1" aria-labelledby="pengambilModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">

                <div class="modal-header">
                    <div>
                        <h5 class="modal-title fw-bold" id="pengambilModalLabel">
                            Pengambil Barang
                        </h5>
                        <small class="text-muted">
                            Pilih orang yang mengambil barang dari gudang
                        </small>
                    </div>

                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">
                    </button>
                </div>

                <div class="modal-body">

                    <div class="mb-3">
                        <label for="pengambil_id" class="form-label fw-semibold">
                            Nama Pengambil
                        </label>

                        <select class="form-select" id="pengambil_id" name="pengambil_id" required>
                            <option>-- Pilih Pengambil --</option>
                        </select>
                    </div>

                </div>

                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                        Batal
                    </button>
                    <button type="button" class="btn btn-primary" id="btnSiap">
                        <i class="ri-save-line me-1"></i>
                        Simpan
                    </button>
                </div>

            </div>
        </div>
    </div> --}}
@endsection
{{-- 
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
@endpush --}}

@push('scripts')
    <script>
        $(document).ready(function() {
            let table = new DataTable('#orderlist', {
                pageLength: 10,
                searching: true,
                lengthChange: false,
                autoWidth: false
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

            const id = @json($produksi);
            $.ajax({
                type: "GET",
                url: "{{ route('produksi.pesanan.json', ':produksi') }}".replace(':produksi', id),
                dataType: "json",
                success: function (response) {
                    console.log(response);
                }
            });

        });
    </script>
@endpush
