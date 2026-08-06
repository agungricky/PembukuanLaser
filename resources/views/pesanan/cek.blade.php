@extends('layouts.app')

@push('styles')
    <style>
        .table thead th {
            font-size: .85rem;
            letter-spacing: .2px;
        }

        .table tbody td {
            vertical-align: middle;
        }
    </style>
@endpush

@section('content')
    <div class="bg-white p-3 p-md-4 rounded shadow-sm w-100">

        {{-- Header --}}
        <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-warning text-dark fw-semibold px-2 py-1">
                    ⚠️
                </span>
                <h5 class="mb-0">
                    Pesanan Cek
                </h5>
            </div>

            <div class="text-muted small">
                {{ number_format($jumlahPesanan, 0, ',', '.') }}
                pesanan perlu dicek
            </div>
        </div>

        {{-- Filter --}}
        <form id="filterForm" method="GET" action="{{ route('pesanan.cek') }}" class="mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="row g-3 align-items-end">
                        <div class="col-lg-3">
                            <label class="form-label small text-muted mb-1">
                                Cari
                            </label>
                            <input type="text" class="form-control form-control-sm" name="no_pesanan"
                                placeholder="No Pesanan / Resi" value="{{ request('no_pesanan') }}">
                        </div>

                        <div class="col-lg-3">
                            <label class="form-label small text-muted mb-1">
                                Toko
                            </label>
                            <select id="id_toko" name="id_toko" class="form-select form-select-sm">
                                <option value="">
                                    Semua Toko
                                </option>
                                @foreach ($daftarToko as $toko)
                                    <option value="{{ $toko->id_toko }}" @selected(request('id_toko') == $toko->id_toko)>
                                        {{ $toko->nama_toko }} ({{ $toko->marketplace }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-lg-2">
                            <label class="form-label small text-muted mb-1">
                                Status
                            </label>
                            <select name="status" class="form-select form-select-sm">
                                <option value="">Semua</option>
                                <option value="proses">Proses</option>
                                <option value="kirim">Kirim</option>
                                <option value="selesai">Selesai</option>
                                <option value="affiliate">Affiliate</option>
                                <option value="pengiriman gagal">Pengiriman Gagal</option>
                                <option value="pengembalian">Pengembalian</option>
                                <option value="batal">Batal</option>
                            </select>
                        </div>
                        <div class="col-lg-2">
                            <label class="form-label small text-muted mb-1">Tanggal</label>
                            <input type="text" id="daterange" name="tanggal" readonly
                                class="form-control form-control-sm" value="{{ request('tanggal') }}">
                        </div>

                        <div class="col-lg-1">
                            <button type="submit" class="btn btn-primary btn-sm w-100">
                                Filter
                            </button>
                        </div>

                        <div class="col-lg-1">
                            <a href="{{ route('pesanan.cek') }}" class="btn btn-outline-secondary btn-sm w-100">
                                Reset
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover table-sm align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Tanggal</th>
                        <th>No Pesanan</th>
                        <th>No Resi</th>
                        <th>Status</th>
                        <th>Toko</th>
                        <th>Pembeli</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody id="tableCek">
                    @forelse($pesanan as $item)
                        @php
                            $badge = [
                                'proses' => 'warning',
                                'kirim' => 'info',
                                'selesai' => 'success',
                                'affiliate' => 'primary',
                                'pengiriman gagal' => 'danger',
                                'pengembalian' => 'danger',
                                'batal' => 'dark',
                            ];
                        @endphp

                        <tr>
                            <td>
                                {{ \Carbon\Carbon::parse($item->tanggal)->format('d/m/Y') }}
                            </td>
                            <td>
                                <strong>
                                    {{ $item->no_pesanan }}
                                </strong>
                            </td>
                            <td>
                                {{ $item->no_resi }}
                            </td>
                            <td>
                                <span class="badge bg-{{ $badge[$item->status] ?? 'secondary' }}">
                                    {{ ucfirst($item->status) }}
                                </span>
                            </td>
                            <td>
                                {{ $item->toko->nama_toko ?? '-' }}
                            </td>
                            <td>
                                {{ $item->nama_pembeli }}
                            </td>
                            <td>
                                <div class="d-flex gap-1 justify-content-center">
                                    <a href="{{ route('pesanan.show', $item->no_pesanan) }}" target="_blank"
                                        class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i>
                                    </a>

                                    <button class="btn btn-sm btn-success cek" data-id="{{ $item->no_pesanan }}">
                                        <i class="bi bi-check-lg"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty

                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                Tidak ada pesanan yang perlu dicek.
                            </td>
                        </tr>
                    @endforelse

                </tbody>
            </table>
        </div>
        <div class="mt-3">
            {{ $pesanan->withQueryString()->links('vendor.pagination.bootstrap-5-compact') }}
        </div>
    </div>

    {{-- Modal --}}
    <div class="modal fade" id="modalKesalahan" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title">Apa yang terjadi?</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div class="row">

                        <div class="col-6 border-end">
                            <table class="table table-borderless table-sm mb-0">
                                <tr>
                                    <th width="170">Nomor Pesanan</th>
                                    <td>: <span id="nomor_pesanan">-</span></td>
                                </tr>
                                <tr>
                                    <th>Kode Resi</th>
                                    <td>: <span id="kode_resi">-</span></td>
                                </tr>
                                <tr>
                                    <th>Nama Pemesan</th>
                                    <td>: <span id="nama_pemesan">-</span></td>
                                </tr>
                                <tr>
                                    <th>Tanggal</th>
                                    <td>: <span id="tanggal">-</span></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-6">
                            <form id="formKesalahan">
                                @csrf
                                <div class="row">
                                    <div class="col-12">
                                        <label class="form-label">Status Pesanan</label>
                                        <select class="form-select" name="status" data-placeholder="Pilih Status Pesanan"
                                            id="status">
                                            <option value="pengiriman gagal">Pengiriman Gagal</option>
                                            <option value="pengembalian">Pengembalian</option>
                                        </select>
                                    </div>

                                    <div class="col-12 mt-3">
                                        <label class="form-label">Pencairan</label>
                                        <input type="text" class="form-control" id="pencairan" name="pencairan"
                                            placeholder="Masukan Pencairan">
                                    </div>

                                    <div class="col-12 mt-3 d-none" id="divKesalahan">
                                        <label class="form-label">Jenis Kesalahan</label>
                                        <select class="form-select" id="idKesalahan" name="idKesalahan"
                                            data-placeholder="Pilih Jenis Kesalahan">
                                            {{-- <option></option> --}}

                                            @if ($roleKesalahan && $roleKesalahan->count())
                                                @foreach ($roleKesalahan as $item)
                                                    <option value="{{ $item->id }}">
                                                        {{ $item->jenis_kesalahan }} - {{ $item->divisi }}
                                                    </option>
                                                @endforeach
                                            @else
                                                <option value=""></option>
                                            @endif
                                        </select>
                                        <input type="hidden" id="no_pesanan" name="no_pesanan">
                                    </div>

                                    <div class="col-12 mt-3 d-none" id="divKeterangan">
                                        <label class="form-label">Keterangan</label>
                                        <input type="text" class="form-control" id="notes" name="notes"
                                            placeholder="Masukkan Keterangan">
                                    </div>
                                </div>
                            </form>
                        </div>

                    </div>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button class="btn btn-primary" id="simpan">Simpan</button>
                </div>

            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(function() {
            $('#daterange').daterangepicker({
                locale: {
                    format: 'YYYY-MM-DD',
                    separator: ' s.d '
                },
                autoUpdateInput: false
            });

            $('#daterange').on('apply.daterangepicker', function(ev, picker) {
                $(this).val(
                    picker.startDate.format('YYYY-MM-DD') + ' s.d ' +
                    picker.endDate.format('YYYY-MM-DD')
                );

                $('#filterForm').submit();
            });

            $('#id_toko').select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: $('#id_toko').data('placeholder'),
                allowClear: true
            });

            $('#idKesalahan').select2({
                theme: 'bootstrap-5',
                width: '100%',
                dropdownParent: $('#modalKesalahan'),
                placeholder: 'Pilih Jenis Kesalahan',
                allowClear: true,
                minimumResultsForSearch: 0
            });

            // Filter toko
            $('#id_toko').on('change', function() {
                form.submit();
            });

            $('#status').on('change', function() {
                let status = $(this).val();
                if (status == "pengembalian") {
                    $('#divKesalahan').removeClass('d-none');
                    $('#divKeterangan').removeClass('d-none');
                } else {
                    $('#divKesalahan').addClass('d-none');
                    $('#divKeterangan').addClass('d-none');
                }
            });

            $(document).on('click', '.cek', function() {
                const id = $(this).data('id');

                $.ajax({
                    type: "GET",
                    url: `pesanan/pesanan-detail/${id}`,
                    dataType: "JSON",
                    success: function(response) {
                        $('#nomor_pesanan').text(response.no_pesanan);
                        $('#kode_resi').text(response.no_resi);
                        $('#nama_pemesan').text(response.nama_pembeli);
                        const tanggal = new Date(response.tanggal);
                        $('#tanggal').text(tanggal.toLocaleDateString('id-ID'));
                        $('#no_pesanan').val(response.no_pesanan);
                    }
                });
                $('#formKesalahan')[0].reset();
                $('#idKesalahan').val(null).trigger('change');
                $('#modalKesalahan').modal('show');
            });

            $('#pencairan').on('input', function() {
                let value = $(this).val();
                let isNegative = value.startsWith('-');
                let angka = value.replace(/\D/g, '');
                let rupiah = angka ? new Intl.NumberFormat('id-ID').format(angka) : '';
                if (isNegative) {
                    rupiah = '-' + rupiah;
                }

                $(this).val(rupiah);
            });

            $('#simpan').on('click', function() {
                let form = $('#formKesalahan');
                let data = form.serializeArray();

                data.forEach(function(item) {
                    if (item.name === 'pencairan') {
                        let isNegative = item.value.startsWith('-');
                        let angka = item.value.replace(/\D/g, '');

                        item.value = (isNegative ? '-' : '') + angka;
                    }
                });

                data = $.param(data);

                $.ajax({
                    type: "POST",
                    url: "{{ route('kesalahan.store') }}",
                    data: data,
                    dataType: "json",
                    success: function(response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil',
                            text: response.message,
                            timer: 1000,
                            showConfirmButton: false
                        }).then(() => {
                            $('#modalKesalahan').modal('hide');
                            $('#tableCek').load(location.href + ' #tableCek > *');
                        });
                    },
                    error: function(xhr, status, error) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal',
                            text: 'Terjadi kesalahan saat menyimpan data.',
                        });
                    }
                });

            });
        });
    </script>
@endpush
