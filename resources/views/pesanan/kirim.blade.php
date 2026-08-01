@extends('layouts.app')

@section('content')
    <div class="bg-white p-3 p-md-4 rounded shadow-sm w-100">

        {{-- Header --}}
        <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">

            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-primary-subtle text-primary fw-semibold px-2 py-1">📦</span>
                <h5 class="mb-0">Pesanan Dikirim</h5>
            </div>

            <div class="d-flex align-items-center gap-3">
                @php
                    $from = $pesanan->firstItem() ?? 0;
                    $to = $pesanan->lastItem() ?? 0;
                    $tot = $pesanan->total();
                @endphp

                <div class="text-muted small">
                    {{ $tot ? "{$from}–{$to} dari {$tot}" : '0' }} pesanan
                    <span class="mx-1">•</span>

                    Total tarik:
                    <span class="fw-semibold">
                        Rp{{ number_format($totalTarik ?? 0, 0, ',', '.') }}
                    </span>
                </div>

                <div class="d-flex gap-2">
                    <button id="btnUbahStatus" class="btn btn-primary btn-sm d-flex align-items-center gap-2"
                        data-bs-toggle="modal" data-bs-target="#ubahStatusModal" disabled>
                        <i class="bi bi-arrow-repeat"></i>
                        <span>Ubah Status</span>
                    </button>

                    <a href="{{ route('kirim.importPage') }}"
                        class="btn btn-success btn-sm d-flex align-items-center gap-2">
                        <i class="bi bi-upload"></i>
                        <span>Import Pencairan</span>
                    </a>
                </div>
            </div>
        </div>

        {{-- Filter --}}
        <form id="filterForm" method="GET" action="{{ route('pesanan.kirim') }}" class="mb-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body py-3">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label small text-muted">
                                Cari
                            </label>

                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white border-end-0">
                                    <i class="bi bi-search"></i>
                                </span>
                                <input type="text" name="q" class="form-control border-start-0"
                                    value="{{ request('q') }}" placeholder="No Pesanan / Resi">
                            </div>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label small text-muted">Toko</label>
                            <select id="id_toko" name="id_toko" class="form-select form-select-sm">
                                <option value="">Semua toko</option>
                                @foreach ($daftarToko as $tk)
                                    <option value="{{ $tk->id_toko }}"
                                        {{ request('id_toko') == $tk->id_toko ? 'selected' : '' }}>
                                        {{ $tk->nama_toko }}
                                        [{{ $tk->marketplace }}]
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label small text-muted">Dari</label>
                            <input type="date" name="min_date" class="form-control form-control-sm" value="{{ request('min_date') }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small text-muted">Sampai</label>
                            <input type="date" name="max_date" class="form-control form-control-sm" value="{{ request('max_date') }}">
                        </div>
                        <div class="col-md-1">
                            <a href="{{ route('pesanan.kirim') }}" class="btn btn-outline-secondary btn-sm w-100">
                                Reset
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        @if ($pesanan->isEmpty())
            <div class="alert alert-info">
                Tidak ada data pesanan.
            </div>
        @endif

        @if ($pesanan->isNotEmpty())
            <div class="table-responsive mt-3">
                <table class="table table-sm table-hover align-middle" style="width:100%">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center" style="width:48px">Pilih</th>
                            <th class="text-center" style="min-width:110px">Tanggal</th>
                            <th class="text-center" style="min-width:160px">
                                No. Pesanan
                            </th>

                            <th class="text-center" style="min-width:160px">
                                No. Resi
                            </th>

                            <th class="text-end" style="min-width:120px">
                                Total Pesanan
                            </th>

                            <th class="text-end" style="min-width:120px">
                                Tarik
                            </th>

                            <th class="text-center" style="min-width:160px">
                                Nama Toko
                            </th>

                            <th class="text-center" style="min-width:180px">
                                Pengiriman
                            </th>

                            <th class="text-center" style="min-width:150px">
                                Aksi
                            </th>
                        </tr>

                    </thead>

                    <tbody>

                        @foreach ($pesanan as $p)
                            <tr>

                                <td class="text-center">

                                    <input type="radio" name="selected" value="{{ $p->no_pesanan }}"
                                        class="form-check-input">

                                </td>

                                <td class="text-center">

                                    {{ \Carbon\Carbon::parse($p->tanggal)->format('d/m/Y') }}

                                </td>

                                <td class="text-center fw-semibold">

                                    {{ $p->no_pesanan }}

                                </td>

                                <td class="text-center fw-semibold">

                                    {{ $p->no_resi }}

                                </td>

                                <td class="text-end fw-semibold">

                                    Rp{{ number_format($p->total_harga ?? 0, 0, ',', '.') }}

                                </td>

                                <td class="text-end">

                                    Rp{{ number_format($p->tarik ?? 0, 0, ',', '.') }}

                                </td>

                                <td class="text-center fw-semibold">

                                    {{ $p->toko->nama_toko ?? '—' }}

                                </td>

                                <td class="text-center">

                                    <div class="d-flex flex-column align-items-center">

                                        <span class="badge rounded-pill bg-info-subtle text-info mb-1">

                                            {{ $p->kurir ?: '—' }}

                                        </span>

                                        <small class="text-muted">

                                            Resi:
                                            {{ $p->no_resi ?: '—' }}

                                        </small>

                                    </div>

                                </td>

                                <td class="text-center">

                                    <a href="{{ route('pesanan.show', $p->no_pesanan) }}" target="_blank"
                                        class="btn btn-sm btn-outline-primary">

                                        <i class="bi bi-file-earmark-text me-1"></i>
                                        Rincian

                                    </a>

                                </td>

                            </tr>
                        @endforeach

                    </tbody>

                </table>

            </div>


            {{-- Pagination --}}
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2 mt-3">

                <div class="text-muted small">

                    @if ($pesanan->total())
                        Menampilkan

                        {{ $pesanan->firstItem() }}
                        –
                        {{ $pesanan->lastItem() }}

                        dari

                        {{ $pesanan->total() }}

                        pesanan
                    @endif

                </div>

                <nav>

                    {{ $pesanan->withQueryString()->onEachSide(1)->links('vendor.pagination.bootstrap-5-compact') }}

                </nav>

            </div>
        @endif

    </div>


    {{-- Modal Ubah Status --}}
    <div class="modal fade" id="ubahStatusModal" tabindex="-1" aria-labelledby="ubahStatusModalLabel"
        aria-hidden="true">

        <div class="modal-dialog">

            <form method="POST" action="{{ route('kirim.ubahStatus') }}">

                @csrf

                <div class="modal-content">

                    <div class="modal-header">

                        <h5 class="modal-title" id="ubahStatusModalLabel">

                            Ubah Status Pesanan

                        </h5>

                        <button type="button" class="btn-close" data-bs-dismiss="modal">
                        </button>

                    </div>


                    <div class="modal-body">

                        <div class="mb-3">

                            <label class="form-label">
                                Status
                            </label>

                            <select class="form-select" id="statusSelect" name="status" required>

                                <option value="">
                                    -- Pilih Status --
                                </option>

                                <option value="selesai">
                                    Diterima
                                </option>

                                <option value="pengiriman gagal">
                                    Pengiriman Gagal
                                </option>

                                <option value="pengembalian">
                                    Pengembalian
                                </option>

                            </select>

                        </div>


                        <div class="mb-3">

                            <label class="form-label">
                                Pencairan (Rp)
                            </label>

                            <input type="text" class="form-control" name="pencairan" id="pencairan"
                                placeholder="Masukkan jumlah pencairan"
                                oninput="
                               this.value=this.value
                               .replace(/\./g,'')
                               .replace(/(?!^-)[^0-9]/g,'')
                               ">

                        </div>


                        <div class="mb-3 d-none" id="notesGroup">

                            <label class="form-label">
                                Catatan / Alasan
                            </label>

                            <textarea class="form-control" name="notes" id="notes" rows="3" placeholder="Tuliskan alasan..."></textarea>

                        </div>


                        <input type="hidden" name="no_pesanan" id="selectedOrderNumber">

                    </div>


                    <div class="modal-footer">

                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">

                            Batal

                        </button>

                        <button type="submit" class="btn btn-primary">

                            Simpan Perubahan

                        </button>

                    </div>

                </div>

            </form>

        </div>

    </div>

@endsection


@push('scripts')
    <script>
        $(function() {
            $('#statusSelect').change(function() {
                ($(this).val() === 'pengembalian')
                ?
                $('#notesGroup').removeClass('d-none')

                    : $('#notesGroup').addClass('d-none');

            });


            $('input[name="selected"]').change(function() {

                $('#selectedOrderNumber').val(
                    $(this).val()
                );

                $('#btnUbahStatus').prop(
                    'disabled',
                    false
                );

            });


            const form = document.getElementById('filterForm');
            ['id_toko', 'min_date', 'max_date']
            .forEach(function(name) {
                const el = form.querySelector(`[name="${name}"]`);

                if (el) {
                    el.addEventListener('change', () => form.submit());
                }
            });

            // $('#id_toko').select2({
            //     theme: 'bootstrap-5',
            //     width: '100%',
            //     placeholder: $('#id_toko').data('placeholder'),
            //     allowClear: true
            // });

        });
    </script>
@endpush
