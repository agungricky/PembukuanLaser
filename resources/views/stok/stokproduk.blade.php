@extends('layouts.app')
@section('content')
    <div class="bg-white p-3 p-md-4 rounded shadow-sm w-100">

        {{-- Header + Toolbar --}}
        <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-primary-subtle text-primary fw-semibold px-2 py-1">
                    <i class="bi bi-box-seam"></i>
                </span>
                <h5 class="mb-0">Stok Produk</h5>
            </div>
        </div>

        {{-- Filter --}}
        <div class="card border-0 shadow-sm">
            <div class="card-body py-3">
                <div class="row g-3 align-items-end">

                    <div class="col-12 col-md-4">
                        <label class="form-label mb-1 small text-muted">Cari</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-white border-end-0">
                                <i class="bi bi-search text-muted"></i>
                            </span>
                            <input type="text" id="searchTable" class="form-control form-control-sm border-start-0"
                                name="nama_produk" value="" placeholder="Nama Produk / SKU">
                        </div>
                    </div>

                    <div class="col-6 col-md-1">
                        <label class="form-label mb-1 small text-muted">Per Page</label>
                        <select id="per_page" name="per_page" class="form-select form-select-sm">
                            @foreach ($allowed ?? [10, 20, 50, 100] as $opt)
                                <option value="{{ $opt }}"
                                    {{ (int) request('per_page', $perPage ?? 20) === $opt ? 'selected' : '' }}>
                                    {{ $opt }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                </div>
            </div>
        </div>

        {{-- Tabel --}}
        @if ($produk->isNotEmpty())
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle" style="width:100%" id="example">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" style="min-width:110px">SKU</th>
                            <th class="text-center" style="min-width:110px">Produk</th>
                            <th class="text-center" style="min-width:110px">Variasi</th>
                            <th class="text-center" style="min-width:110px">Hpp</th>
                            <th class="text-center" style="min-width:170px">Tersedia</th>
                            <th class="text-center" style="min-width:150px">Minimal Stok</th>
                            <th class="text-center" style="min-width:140px">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($produk as $item)
                            <tr>
                                <td class="text-center">
                                    {{ $item->sku }}
                                </td>

                                <td class="text-center fw-semibold">
                                    {{ $item->nama_produk }}
                                </td>

                                <td class="text-center fw-semibold">
                                    {{ $item->variasi ?: '—' }}
                                </td>

                                <td class="text-end fw-semibold">
                                    Rp {{ number_format($item->hpp ?? 0, 0, ',', '.') }}
                                </td>

                                <td class="text-center fw-semibold">
                                    @php
                                        $stok = $item->stok_produk?->jumlah_tersedia ?? 0;
                                    @endphp

                                    @if ($stok < 5)
                                        <span class="badge p-2 bg-danger">{{ $stok }}</span>
                                    @elseif ($stok == 5)
                                        <span class="badge p-2 bg-warning text-dark">{{ $stok }}</span>
                                    @else
                                        <span class="badge p-2 bg-success">{{ $stok }}</span>
                                    @endif
                                </td>

                                <td class="text-center fw-semibold">
                                    {{ $item->stok_produk->min_stok ?? 5 }}
                                </td>

                                <td class="text-center">
                                    <button class="btn btn-outline-primary btn-sm add" data-id="{{ $item->sku }}">
                                        <i class="bi bi-plus-circle-fill"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-warning btn-sm edit"
                                        data-id="{{ $item->sku }}" title="Edit stok">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-5">
                <i class="bi bi-box-seam fs-1 text-muted"></i>
                <p class="text-muted mb-0">Tidak ada data</p>
            </div>
        @endif
    </div>


    {{-- Modal Tambah Stok --}}
    <div class="modal fade" id="modalTambahStok" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form id="formTambahStok">
                @csrf
                <div class="modal-content border-0 shadow">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bi bi-box-seam me-2"></i>
                            Tambah Stok Produk
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label class="form-label text-muted small">
                                    SKU
                                </label>
                                <input type="text" class="form-control" id="sku" name="sku" lock>
                            </div>

                            <div class="col-md-9 mb-3">
                                <label class="form-label text-muted small">
                                    Nama Produk
                                </label>
                                <input type="text" class="form-control" id="nama_produk" lock>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted small">
                                    Stok Saat Ini ( Item )
                                </label>
                                <input type="text" class="form-control text-center fw-bold" id="stok_tersedia" lock>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-muted small">
                                    Tambah Stok
                                </label>
                                <input type="number" id="tambah" min="1" class="form-control text-center"
                                    name="jumlah" placeholder="0" required>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                            Batal
                        </button>

                        <button type="button" id="submit" class="btn btn-primary">
                            <i class="bi bi-plus-circle me-1"></i>
                            Tambah Stok
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="modalEditStok" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form id="formEditStok">
                @csrf
                <div class="modal-content border-0 shadow">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bi bi-box-seam me-2"></i>
                            Edit Stok Produk
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>

                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label class="form-label text-muted small">
                                    SKU
                                </label>
                                <input type="text" class="form-control" id="sku_edit" name="sku_edit" lock>
                            </div>

                            <div class="col-md-9 mb-3">
                                <label class="form-label text-muted small">
                                    Nama Produk
                                </label>
                                <input type="text" class="form-control" id="nama_produk_edit" lock>
                            </div>

                            <div class="col-md-12 mb-3">
                                <label class="form-label text-muted small">
                                    Stok Saat Ini ( Item )
                                </label>
                                <input type="text" class="form-control text-center fw-bold" name="stok_tersedia_edit" id="stok_tersedia_edit">
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                            Batal
                        </button>

                        <button type="button" id="Edit" class="btn btn-primary">
                            <i class="bi bi-pencil-square"></i>
                            Update
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const table = new DataTable('#example', {
            pageLength: 10,
            searching: true,
            lengthChange: false,
            autoWidth: false
        });

        document.getElementById('per_page').value = 10;
        document.getElementById('per_page').addEventListener('change', function() {
            table.page.len(parseInt(this.value)).draw();
        })

        document.getElementById('searchTable').addEventListener('input', function() {
            table.search(this.value).draw();
        });

        document.getElementById('tambah').addEventListener('keydown', function(e) {
            const allowedKeys = [
                'Backspace',
                'Delete',
                'Tab',
                'Escape',
                'Enter',
                'ArrowLeft',
                'ArrowRight',
                'Home',
                'End'
            ];

            if (e.ctrlKey || e.metaKey) return;
            if (allowedKeys.includes(e.key)) return;
            if (!/^[0-9]$/.test(e.key)) {
                e.preventDefault();
            }
        });

        let id = null;
        // Tambah stok
        $(document).on('click', '.add', function() {
            id = $(this).data('id');
            $('#formTambahStok')[0].reset();
            $.ajax({
                type: "GET",
                url: "{{ route('stok-produk.show', ':id') }}".replace(':id', id),
                dataType: "JSON",
                success: function(response) {
                    $('#sku').val(response.sku);
                    $('#nama_produk').val(response.nama_produk);
                    $('#stok_tersedia').val(response.stok_produk?.jumlah_tersedia ?? 0);

                }
            });
            $('#modalTambahStok').modal('show');
        });

        $('#submit').click(function(e) {
            e.preventDefault();
            const formData = $('#formTambahStok').serialize();

            $.ajax({
                type: "PATCH",
                url: "{{ route('tambah.stok', ':id') }}".replace(':id', id),
                data: formData,
                dataType: "JSON",
                success: function(response) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: response.message,
                        timer: 1000,
                        showConfirmButton: false
                    }).then(() => {
                        $('#modalTambahStok').modal('hide');

                        if ($.fn.DataTable.isDataTable('#example')) {
                            $('#example').DataTable().destroy();
                        }

                        $('#example').load(
                            location.href + ' #example > *',
                            function() {
                                // Aktifkan kembali DataTables
                                $('#example').DataTable({
                                    responsive: true,
                                    autoWidth: false
                                });
                            }
                        );
                    });
                },
                error: function(xhr, status, error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: 'Terjadi kesalahan saat menambahkan stok.',
                    });
                }
            });
        });

        // Update Stok 
        $(document).on('click', '.edit', function() {
            id = $(this).data('id');
            $('#formEditStok')[0].reset();
            $.ajax({
                type: "GET",
                url: "{{ route('stok-produk.show', ':id') }}".replace(':id', id),
                dataType: "JSON",
                success: function(response) {
                    $('#sku_edit').val(response.sku);
                    $('#nama_produk_edit').val(response.nama_produk);
                    $('#stok_tersedia_edit').val(response.stok_produk?.jumlah_tersedia ?? 0);

                }
            });
            $('#modalEditStok').modal('show');
        });

        $('#Edit').click(function(e) {
            e.preventDefault();
            const formData = $('#formEditStok').serialize();

            $.ajax({
                type: "PATCH",
                url: "{{ route('stok-produk.update', ':id') }}".replace(':id', id),
                data: formData,
                dataType: "JSON",
                success: function(response) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil',
                        text: response.message,
                        timer: 1000,
                        showConfirmButton: false
                    }).then(() => {
                        $('#modalEditStok').modal('hide');

                        if ($.fn.DataTable.isDataTable('#example')) {
                            $('#example').DataTable().destroy();
                        }

                        $('#example').load(
                            location.href + ' #example > *',
                            function() {
                                $('#example').DataTable({
                                    responsive: true,
                                    autoWidth: false
                                });
                            }
                        );
                    });
                },
                error: function(xhr, status, error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: 'Terjadi kesalahan saat Update stok.',
                    });
                }
            });
        });
    </script>
@endpush
