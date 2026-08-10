@extends('layouts.app')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

@section('content')
    <main class="flex-grow-1 overflow-auto p-3 p-lg-4">

        <!-- Dashboard Title & Pulse Badge -->
        <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mb-4">
            <div>
                <h1 class="h3 fw-bold text-dark mb-1">
                    Produk & Kelola Stok
                </h1>
                <span class="text-muted">
                    <i class="fa-solid fa-box"></i>
                    Produk
                </span>
                <span class="mx-2 text-secondary">/</span>
                <span class="fw-semibold text-primary">
                    Semua Produk & Kelola Stok
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
                        <i class="fa-solid fa-box"></i>
                        Daftar Semua Produk
                    </h2>
                    <p class="text-muted small mb-0">
                        Menampilkan Semua Produk & Kelola Stok.
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
                            <th scope="col" class="py-3 px-4 text-center">Kategori</th>
                            <th scope="col" class="py-3 px-4 text-center">Hpp</th>
                            <th scope="col" class="py-3 px-4 text-center">Tersedia</th>
                            <th scope="col" class="py-3 px-4 text-center">Status Stok</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $no = 1;
                        @endphp
                        @foreach ($produk as $item)
                            <tr class="{{ $item->status == 'nonaktif' ? 'row-nonactive' : '' }}">
                                <td class="py-3 px-4 text-center">{{ $no++ }}</td>

                                <td class="py-3 px-4 text-start">
                                    <div class="fw-bold text-success fs-6">
                                        {{ $item->nama_produk }}
                                    </div>

                                    <small class="text-muted">Sku : {{ $item?->sku }}</small>
                                </td>

                                <td class="py-3 px-4 text-center">
                                    {{ $item->variasi }}
                                </td>

                                <td class="py-3 px-4 text-center">{{ $item->kategori->nama_kategori ?? '' }}</td>

                                <td class="py-3 px-4 text-center">
                                    <div class="fw-bold text-success fs-6">
                                        Rp {{ number_format($item->hpp, 0, ',', '.') }}
                                    </div>

                                    <small class="text-muted">/ Item</small>
                                </td>

                                @php
                                    $stok = $item->stok_produk->jumlah_tersedia ?? 0;
                                @endphp

                                <td class="py-3 px-4 text-center">
                                    <span
                                        class="badge 
                                         {{ $stok < 5 ? 'bg-danger' : ($stok == 5 ? 'bg-warning text-dark' : 'bg-success') }}">
                                        {{ $stok }}
                                    </span>
                                </td>

                                <td class="py-3 px-4 text-center">
                                    <button type="button" class="btn btn-success btn-sm addstok"
                                        data-sku="{{ $item->sku }}" {{ $item->status === 'aktif' ? '' : 'disabled' }}>
                                        <i class="fa-solid fa-plus"></i>
                                    </button>

                                    <button type="button"
                                        class="btn btn-warning btn-sm editstok 
                                        {{ $item->status == 'aktif' ? '' : 'disabled' }}"
                                        data-sku="{{ $item->sku }}">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

    </main>

    </div>

    <!-- Footer -->
    <footer class="bg-white border-top py-3 text-center text-muted small">
        <div class="container-fluid px-4 d-flex flex-column flex-sm-row align-items-center justify-content-between gap-2">
            <p class="mb-0">© 2026 StockFlow WMS. Pure Bootstrap 5.3 Framework & Modern JavaScript.</p>
            <button id="footerBtnCodeModal" class="btn btn-link text-primary p-0 text-decoration-none fw-semibold small">
                Lihat Kode Pure HTML & CSS / Bootstrap 5
            </button>
        </div>
    </footer>

    <!-- Modal: Tambah Stok (Bootstrap 5 Modal) -->
    <div class="modal fade" id="addStockModal" tabindex="-1" aria-labelledby="addStockModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden">
                <div class="modal-header bg-light">
                    <div class="d-flex align-items-center gap-2">
                        <div class="bg-primary text-white rounded-3 p-2 d-flex align-items-center justify-content-center"
                            style="width: 32px; height: 32px;">
                            <i class="fa-solid fa-plus"></i>
                        </div>
                        <div>
                            <h5 class="modal-title fw-bold text-dark fs-6" id="addStockModalLabel">Tambah Stok Produk Baru
                            </h5>
                            <p class="text-muted mb-0" style="font-size: 11px;">Tambahkan Stok Masuk</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <form id="addStockForm">
                    @csrf
                    <div class="modal-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-dark mb-1">SKU Produk *</label>
                                <input type="text" id="addSku" name="sku" required
                                    placeholder="Contoh: KRM-SM-009" class="form-control form-control-sm" lock />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-dark mb-1">Kategori *</label>
                                <input type="text" id="addCategory" required name="kategori"
                                    placeholder="Pakaian, Elektronik..." class="form-control form-control-sm" lock />
                            </div>
                        </div>

                        <div class="row g-3 mt-1">
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-dark mb-1">Nama Produk *</label>
                                <input type="text" id="nama_produk" required name="nama_produk"
                                    placeholder="masukan Produk" class="form-control form-control-sm" lock />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-dark mb-1">Jumlah ditambahkan *</label>
                                <input type="number" id="addjumlah" name="jumlah" required value="1"
                                    min="1" class="form-control form-control-sm" />
                            </div>
                        </div>

                        <input type="hidden" id="checking" name="checking">
                    </div>

                    <div class="modal-footer bg-light border-top p-3">
                        <button type="button" class="btn btn-outline-secondary btn-sm rounded-3 fw-semibold"
                            data-bs-dismiss="modal">
                            Batal
                        </button>
                        <button type="button" class="btn btn-primary btn-sm rounded-3 fw-semibold px-4 btnSimpan">
                            <i class="fa-solid fa-floppy-disk me-1"></i>
                            Simpan Produk
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- <!-- Modal: Edit Stok (Bootstrap 5 Modal) -->
    <div class="modal fade" id="editStockModal" tabindex="-1" aria-labelledby="editStockModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content rounded-4 border-0 shadow-lg overflow-hidden">
                <div class="modal-header bg-light">
                    <div class="d-flex align-items-center gap-2">
                        <div class="bg-warning text-white rounded-3 p-2 d-flex align-items-center justify-content-center"
                            style="width: 32px; height: 32px;">
                            <i class="fa-solid fa-pen-to-square text-white"></i>
                        </div>
                        <div>
                            <h5 class="modal-title fw-bold text-dark fs-6" id="addStockModalLabel">Edit Stok Produk
                            </h5>
                            <p class="text-muted mb-0" style="font-size: 11px;">Mohon berikan keterangan yang valid alasan
                                edit data</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <form id="editStockForm">
                    <div class="modal-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-dark mb-1">SKU Produk *</label>
                                <input type="text" id="sku" nama="sku" required
                                    placeholder="Contoh: KRM-SM-009" class="form-control form-control-sm" lock />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-dark mb-1">Kategori *</label>
                                <input type="text" id="kategori" name="kategori" required
                                    placeholder="Pakaian, Elektronik..." class="form-control form-control-sm" lock />
                            </div>
                        </div>

                        <div class="row g-3 mt-1">
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-dark mb-1">Nama Produk *</label>
                                <input type="text" id="nama_produkk" name="nama_produk" required
                                    placeholder="masukan Produk" class="form-control form-control-sm" lock />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-dark mb-1">Stok Saat ini *</label>
                                <input type="number" id="addjumlah" name="jumlah" required min="0"
                                    class="form-control form-control-sm" />
                            </div>
                        </div>

                        <div class="mt-3">
                            <label class="form-label fw-bold small text-dark mb-1">Keterangan *</label>
                            <input type="text" id="keterangan" name="keterangan" required
                                placeholder="Nama lengkap produk..." class="form-control form-control-sm" />
                        </div>
                        <input type="hidden" id="checking" name="checking">

                    </div>

                    <div class="modal-footer bg-light border-top p-3">
                        <button type="button" class="btn btn-outline-secondary btn-sm rounded-3 fw-semibold"
                            data-bs-dismiss="modal">
                            Batal
                        </button>
                        <button type="submit" class="btn btn-primary btn-sm rounded-3 fw-semibold px-4">
                            <i class="fa-solid fa-floppy-disk me-1"></i>
                            Simpan Produk
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div> --}}
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

            $('.addstok').on('click', function() {
                const modal = new bootstrap.Modal(document.getElementById('addStockModal'));
                modal.show();
            });

            $('.editstok').on('click', function() {
                const modal = new bootstrap.Modal(document.getElementById('editStockModal'));
                modal.show();
            });

            // addstok
            $(document).on('click', '.addstok', function() {
                const sku = $(this).data('sku');
                $('#addStockForm')[0].reset();

                $.ajax({
                    type: "GET",
                    url: "{{ route('gudang.produk.json', ':sku') }}".replace(':sku', sku),
                    dataType: "JSON",
                    success: function(response) {
                        $('#addSku').val(response.sku);
                        $('#addCategory').val(
                            response?.kategori?.nama_kategori ?? '-'
                        );
                        $('#nama_produk').val(response.nama_produk);
                        $('#checking').val("add");

                        const modal = bootstrap.Modal.getOrCreateInstance(
                            document.getElementById('addStockModal')
                        );

                        modal.show();
                    }
                });
            });

            // Update stok
            $(document).on('click', '.editstok', function() {
                const sku = $(this).data('sku');
                $('#editStockForm')[0].reset();

                $.ajax({
                    type: "GET",
                    url: "{{ route('gudang.produk.json', ':sku') }}".replace(':sku', sku),
                    dataType: "JSON",
                    success: function(response) {
                        $('#sku').val(response.sku);
                        $('#kategori').val(response?.kategori?.nama_kategori ?? '-');
                        $('#nama_produkk').val(response.nama_produk);
                        $('#stok').val(response?.stok_produk?.jumlah_tersedia ?? '0');
                        $('#checking').val("update");

                        const modal = bootstrap.Modal.getOrCreateInstance(
                            document.getElementById('editStockModal')
                        );

                        modal.show();
                    }
                });
            });

            $(document).on('click', '.btnSimpan', function() {
                const sku = $('#sku').val();
                const jumlah = $('#addjumlah').val();
                const keterangan = $('#keterangan').val();
                const checking = $('#checking').val();

                console.log(sku);


                if (checking === "add" && jumlah == "0") {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Data Belum Lengkap',
                        text: 'jumlah stok tidak boleh 0.'
                    });
                    return;
                }

                $.ajax({
                    type: "PATCH",
                    url: "{{ route('update.stok') }}",
                    data: {
                        _token: "{{ csrf_token() }}",
                        sku: sku,
                        jumlah: jumlah,
                        keterangan: keterangan,
                        checking: checking
                    },
                    dataType: "JSON",
                    success: function(response) {
                        console.log(response);
                        // if (response.success) {

                        //     Swal.fire({
                        //         icon: 'success',
                        //         title: 'Berhasil',
                        //         text: response.message,
                        //         timer: 1800,
                        //         showConfirmButton: false
                        //     }).then(() => {

                        //         $('#addStockForm')[0].reset();

                        //         const modal = bootstrap.Modal.getInstance(
                        //             document.getElementById('addStockModal')
                        //         );

                        //         modal?.hide();

                        //         location.reload();
                        //     });

                        // } else {

                        //     Swal.fire({
                        //         icon: 'error',
                        //         title: 'Gagal',
                        //         text: response.message
                        //     });
                        // }
                    },

                    error: function(xhr) {

                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal',
                            text: xhr.responseJSON?.message ??
                                'Terjadi kesalahan saat menambahkan stok.'
                        });

                    }
                });
            });

        });
    </script>
@endpush
