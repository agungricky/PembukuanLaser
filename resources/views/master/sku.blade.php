@extends('layouts.app')

@section('content')
<div class="bg-white p-3 p-md-4 rounded shadow-sm w-100">

    {{-- HEADER --}}
    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
        <div>
            <h5 class="mb-1 fw-semibold">Daftar SKU Produk</h5>

            <div class="d-flex flex-wrap align-items-center gap-3 text-muted small">
                <span>
                    <i class="bi bi-box-seam me-1"></i>
                    {{ number_format($produk->total(), 0, ',', '.') }} SKU
                </span>
            </div>
        </div>

        <button
            type="button"
            class="btn btn-success btn-sm d-flex align-items-center gap-1"
            data-bs-toggle="modal"
            data-bs-target="#tambahProdukModal"
        >
            <i class="bi bi-plus-circle"></i>
            <span>Tambah Produk</span>
        </button>
    </div>

    {{-- ALERT SUKSES --}}
    @if(session('success'))
        <div
            class="alert alert-success d-flex align-items-center py-2"
            id="alertSuccess"
        >
            <i class="bi bi-check-circle-fill me-2"></i>
            <div>{{ session('success') }}</div>
        </div>
    @endif

    {{-- ALERT ERROR --}}
    @if($errors->any())
        <div class="alert alert-danger py-2">
            <div class="fw-semibold mb-1">
                Data belum dapat disimpan:
            </div>

            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- FILTER --}}
    <form
        method="GET"
        action="{{ route('sku.index') }}"
        class="card border-0 shadow-sm mb-3"
    >
        <div class="card-body">
            <div class="row g-2 align-items-end">

                <div class="col-12 col-md-6">
                    <label class="form-label small text-muted">
                        Cari Produk
                    </label>

                    <input
                        type="text"
                        name="search"
                        class="form-control form-control-sm"
                        value="{{ request('search') }}"
                        placeholder="Cari SKU, nama produk, variasi, atau HPP"
                    >
                </div>

                <div class="col-12 col-md-auto">
                    <button
                        type="submit"
                        class="btn btn-primary btn-sm"
                    >
                        <i class="bi bi-search me-1"></i>
                        Filter
                    </button>

                    <a
                        href="{{ route('sku.index') }}"
                        class="btn btn-outline-secondary btn-sm"
                    >
                        Reset
                    </a>
                </div>

            </div>
        </div>
    </form>

    {{-- TABEL --}}
    <div class="table-responsive">
        <table class="table table-sm table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th class="text-center" style="min-width: 120px;">
                        SKU
                    </th>

                    <th style="min-width: 220px;">
                        Nama Produk
                    </th>

                    <th style="min-width: 180px;">
                        Variasi
                    </th>

                    <th class="text-end" style="min-width: 140px;">
                        HPP
                    </th>

                    <th class="text-center" style="min-width: 170px;">
                        Aksi
                    </th>
                </tr>
            </thead>

            <tbody>
                @forelse($produk as $row)
                    @php
                        $modalId = 'editProdukModal' . md5($row->sku);
                    @endphp

                    <tr>
                        <td class="text-center fw-semibold">
                            {{ $row->sku }}
                        </td>

                        <td>
                            @if($row->nama_produk)
                                {{ $row->nama_produk }}
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>

                        <td>
                            @if($row->variasi)
                                {{ $row->variasi }}
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>

                        <td class="text-end">
                            Rp{{ number_format((float) $row->hpp, 0, ',', '.') }}
                        </td>

                        <td class="text-center">
                            <div class="d-inline-flex gap-1">

                                <button
                                    type="button"
                                    class="btn btn-outline-primary btn-sm"
                                    data-bs-toggle="modal"
                                    data-bs-target="#{{ $modalId }}"
                                >
                                    <i class="bi bi-pencil"></i>
                                    Edit
                                </button>

                                <form
                                    action="{{ route('sku.destroy', ['sku' => $row->sku]) }}"
                                    method="POST"
                                    class="d-inline"
                                    onsubmit="return confirm('Yakin ingin menghapus SKU {{ addslashes($row->sku) }}?')"
                                >
                                    @csrf
                                    @method('DELETE')

                                    <input
                                        type="hidden"
                                        name="search"
                                        value="{{ request('search') }}"
                                    >

                                    <input
                                        type="hidden"
                                        name="page"
                                        value="{{ request('page') }}"
                                    >

                                    <button
                                        type="submit"
                                        class="btn btn-outline-danger btn-sm"
                                    >
                                        <i class="bi bi-trash"></i>
                                        Hapus
                                    </button>
                                </form>

                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td
                            colspan="5"
                            class="text-center text-muted py-4"
                        >
                            Data SKU produk belum tersedia.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- PAGINATION --}}
    @if($produk->hasPages())
        <div class="mt-3">
            {{ $produk->links() }}
        </div>
    @endif
</div>

{{-- MODAL EDIT --}}
@foreach($produk as $row)
    @php
        $modalId = 'editProdukModal' . md5($row->sku);
    @endphp

    <div
        class="modal fade"
        id="{{ $modalId }}"
        tabindex="-1"
        aria-labelledby="{{ $modalId }}Label"
        aria-hidden="true"
    >
        <div class="modal-dialog">
            <div class="modal-content border-0 shadow">

                <div class="modal-header bg-primary text-white">
                    <h5
                        class="modal-title"
                        id="{{ $modalId }}Label"
                    >
                        Edit Produk
                    </h5>

                    <button
                        type="button"
                        class="btn-close btn-close-white"
                        data-bs-dismiss="modal"
                        aria-label="Tutup"
                    ></button>
                </div>

                <form
                    action="{{ route('sku.update', ['sku' => $row->sku]) }}"
                    method="POST"
                >
                    @csrf
                    @method('PUT')

                    <input
                        type="hidden"
                        name="search"
                        value="{{ request('search') }}"
                    >

                    <input
                        type="hidden"
                        name="page"
                        value="{{ request('page') }}"
                    >

                    <div class="modal-body">

                        <div class="mb-3">
                            <label class="form-label">
                                SKU
                            </label>

                            <input
                                type="text"
                                class="form-control"
                                value="{{ $row->sku }}"
                                readonly
                            >
                        </div>

                        <div class="mb-3">
                            <label class="form-label">
                                Nama Produk
                            </label>

                            <input
                                type="text"
                                name="nama_produk"
                                class="form-control"
                                value="{{ $row->nama_produk }}"
                                maxlength="255"
                                placeholder="Boleh dikosongkan"
                            >

                            <div class="form-text">
                                Nama produk tidak wajib diisi.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">
                                Variasi
                            </label>

                            <input
                                type="text"
                                name="variasi"
                                class="form-control"
                                value="{{ $row->variasi }}"
                                maxlength="255"
                                placeholder="Boleh dikosongkan"
                            >

                            <div class="form-text">
                                Variasi produk tidak wajib diisi.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">
                                HPP
                            </label>

                            <div class="input-group">
                                <span class="input-group-text">Rp</span>

                                <input
                                    type="number"
                                    name="hpp"
                                    class="form-control"
                                    value="{{ (float) $row->hpp }}"
                                    min="0"
                                    max="99999999.99"
                                    step="0.01"
                                    required
                                >
                            </div>
                        </div>

                    </div>

                    <div class="modal-footer">
                        <button
                            type="button"
                            class="btn btn-outline-secondary"
                            data-bs-dismiss="modal"
                        >
                            Batal
                        </button>

                        <button
                            type="submit"
                            class="btn btn-primary"
                        >
                            <i class="bi bi-save me-1"></i>
                            Perbarui
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
@endforeach

{{-- MODAL TAMBAH --}}
<div
    class="modal fade"
    id="tambahProdukModal"
    tabindex="-1"
    aria-labelledby="tambahProdukModalLabel"
    aria-hidden="true"
>
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">

            <div class="modal-header bg-success text-white">
                <h5
                    class="modal-title"
                    id="tambahProdukModalLabel"
                >
                    Tambah Produk
                </h5>

                <button
                    type="button"
                    class="btn-close btn-close-white"
                    data-bs-dismiss="modal"
                    aria-label="Tutup"
                ></button>
            </div>

            <form
                action="{{ route('sku.store') }}"
                method="POST"
            >
                @csrf

                <div class="modal-body">

                    <div class="mb-3">
                        <label class="form-label">
                            SKU
                            <span class="text-danger">*</span>
                        </label>

                        <input
                            type="text"
                            name="sku"
                            class="form-control @error('sku') is-invalid @enderror"
                            value="{{ old('sku') }}"
                            maxlength="50"
                            placeholder="Contoh: LBL001"
                            required
                        >

                        @error('sku')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label">
                            Nama Produk
                        </label>

                        <input
                            type="text"
                            name="nama_produk"
                            class="form-control @error('nama_produk') is-invalid @enderror"
                            value="{{ old('nama_produk') }}"
                            maxlength="255"
                            placeholder="Boleh dikosongkan"
                        >

                        @error('nama_produk')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror

                        <div class="form-text">
                            Nama produk tidak wajib diisi.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">
                            Variasi
                        </label>

                        <input
                            type="text"
                            name="variasi"
                            class="form-control @error('variasi') is-invalid @enderror"
                            value="{{ old('variasi') }}"
                            maxlength="255"
                            placeholder="Contoh: Cowo A - Kuning"
                        >

                        @error('variasi')
                            <div class="invalid-feedback">
                                {{ $message }}
                            </div>
                        @enderror

                        <div class="form-text">
                            Variasi produk tidak wajib diisi.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">
                            HPP
                            <span class="text-danger">*</span>
                        </label>

                        <div class="input-group">
                            <span class="input-group-text">Rp</span>

                            <input
                                type="number"
                                name="hpp"
                                class="form-control @error('hpp') is-invalid @enderror"
                                value="{{ old('hpp') }}"
                                min="0"
                                max="99999999.99"
                                step="0.01"
                                placeholder="0"
                                required
                            >

                            @error('hpp')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>

                </div>

                <div class="modal-footer">
                    <button
                        type="button"
                        class="btn btn-outline-secondary"
                        data-bs-dismiss="modal"
                    >
                        Batal
                    </button>

                    <button
                        type="submit"
                        class="btn btn-success"
                    >
                        <i class="bi bi-save me-1"></i>
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
    table thead th {
        font-size: 0.85rem;
        letter-spacing: 0.2px;
        white-space: nowrap;
    }

    table tbody td {
        vertical-align: middle;
    }

    .table > :not(caption) > * > * {
        padding-top: 0.65rem;
        padding-bottom: 0.65rem;
    }
</style>
@endpush

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const alertSuccess = document.getElementById('alertSuccess');

        if (alertSuccess) {
            setTimeout(function () {
                alertSuccess.remove();
            }, 3000);
        }

        @if(
            $errors->has('sku') ||
            $errors->has('nama_produk') ||
            $errors->has('variasi') ||
            $errors->has('hpp')
        )
            const tambahModalElement =
                document.getElementById('tambahProdukModal');

            if (tambahModalElement) {
                const tambahModal =
                    bootstrap.Modal.getOrCreateInstance(tambahModalElement);

                tambahModal.show();
            }
        @endif
    });
</script>
@endpush