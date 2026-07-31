@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />

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

                            <select name="id_toko" class="form-select form-select-sm">

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

                                <option value="">
                                    Semua
                                </option>

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

                            <label class="form-label small text-muted mb-1">
                                Tanggal
                            </label>

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

                <tbody>

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

                                    <a href="{{ route('pesanan.show', $item->no_pesanan) }}"
                                        target="_blank" class="btn btn-sm btn-outline-primary">

                                        <i class="bi bi-eye"></i>

                                    </a>

                                    <form action="{{ route('pesanan.cek.selesai', $item->no_pesanan) }}" method="POST">

                                        @csrf

                                        <button class="btn btn-sm btn-success">

                                            <i class="bi bi-check-lg"></i>

                                        </button>

                                    </form>

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
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>

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

                    picker.startDate.format('YYYY-MM-DD')

                    +
                    ' s.d ' +

                    picker.endDate.format('YYYY-MM-DD')

                );

                $('#filterForm').submit();

            });

        });
    </script>
@endpush