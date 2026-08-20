@extends('layouts.app')

@section('content')

<div class="bg-white p-4 rounded shadow-sm w-100">

    <div class="d-flex justify-content-between align-items-center mb-3">

        <h5 class="mb-0 d-flex align-items-center gap-2">
            <i class="bi bi-file-earmark-pdf text-danger"></i>
            Import PDF Resi
        </h5>

        <a href="{{ route('pesanan.index') }}"
            class="btn btn-outline-secondary btn-sm">

            <i class="bi bi-arrow-left me-1"></i>
            Kembali

        </a>

    </div>


    @if(session('success'))

        <div class="alert alert-success">
            <i class="bi bi-check-circle me-1"></i>
            {{ session('success') }}
        </div>

    @endif


    @if(session('error'))

        <div class="alert alert-danger">
            <i class="bi bi-exclamation-circle me-1"></i>
            {{ session('error') }}
        </div>

    @endif


    @if(
        session('import_errors') &&
        count(session('import_errors'))
    )

        <div class="alert alert-warning">

            <div class="fw-semibold mb-2">
                Data yang dilewati
            </div>

            @foreach(
                session('import_errors')
                as $error
            )

                <div class="small">
                    {{ $error }}
                </div>

            @endforeach

        </div>

    @endif


    <form action="{{ route('resi.preview') }}"
        method="POST"
        enctype="multipart/form-data"
        id="formImportResi">

        @csrf

        <div class="row g-3">

            <div class="col-md-4">

                <label class="form-label">
                    Tanggal Import
                </label>

                <input type="date"
                    class="form-control"
                    value="{{ now()->format('Y-m-d') }}"
                    readonly
                    style="background-color:#f5f5f5;">

            </div>


            <div class="col-md-4">

                <label class="form-label">
                    Marketplace
                </label>

                <select name="marketplace"
                    id="marketplace"
                    class="form-select"
                    required>

                    <option value="">
                        Pilih Marketplace
                    </option>

                    <option value="Shopee"
                        {{ old('marketplace', $selectedMarketplace ?? '') === 'Shopee' ? 'selected' : '' }}>

                        Shopee

                    </option>

                    <option value="Tiktok"
                        {{ old('marketplace', $selectedMarketplace ?? '') === 'Tiktok' ? 'selected' : '' }}>

                        TikTok

                    </option>

                </select>

                @error('marketplace')

                    <div class="text-danger small mt-1">
                        {{ $message }}
                    </div>

                @enderror

            </div>


            <div class="col-md-4">

                <label class="form-label">
                    Nama Toko
                </label>

                <select name="id_toko"
                    id="namaToko"
                    class="form-select"
                    data-placeholder="Pilih Toko"
                    required>

                    <option value="">
                        Pilih Marketplace dulu
                    </option>

                </select>

                @error('id_toko')

                    <div class="text-danger small mt-1">
                        {{ $message }}
                    </div>

                @enderror

            </div>


            <div class="col-md-6">

                <label class="form-label">
                    Nama Pengguna
                </label>

                <input type="text"
                    class="form-control"
                    value="{{ auth()->user()->name }}"
                    readonly
                    style="background-color:#f5f5f5;">

            </div>


            <div class="col-md-6">

                <label class="form-label">
                    Upload File PDF
                </label>

                <input type="file"
                    class="form-control"
                    name="file_resi"
                    id="fileUpload"
                    accept=".pdf,application/pdf"
                    required>

                @error('file_resi')

                    <div class="text-danger small mt-1">
                        {{ $message }}
                    </div>

                @enderror

            </div>

        </div>


        <div id="marketplaceInfo"
            class="mt-3"
            style="display:none;">
        </div>


        <div class="mt-3">

            <button type="submit"
                class="btn btn-success"
                id="btnPreview">

                <i class="bi bi-eye me-1"></i>
                Preview Data

            </button>

        </div>

    </form>


    @if(isset($preview))

        <hr class="my-4">

        @php
            $total = count($preview);

            $matched = collect($preview)
                ->where('status', 'matched')
                ->count();

            $existing = collect($preview)
                ->where('status', 'existing')
                ->count();

            $notFound = collect($preview)
                ->where('status', 'not_found')
                ->count();

            $unreadable = collect($preview)
                ->where('status', 'unreadable')
                ->count();
        @endphp


        <form action="{{ route('resi.store') }}"
            method="POST"
            id="formSimpanResi">

            @csrf


            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">

                <div>

                    <h5 class="mb-1">
                        Tinjau & Cocokkan Data Resi
                    </h5>

                    <div class="d-flex align-items-center gap-2">

                        <small class="text-muted">
                            {{ $total }} halaman PDF
                        </small>

                        @if(
                            ($selectedMarketplace ?? '') === 'Tiktok'
                        )

                            <span class="badge bg-dark">
                                TikTok
                            </span>

                        @elseif(
                            ($selectedMarketplace ?? '') === 'Shopee'
                        )

                            <span class="badge bg-warning text-dark">
                                Shopee
                            </span>

                        @endif

                    </div>

                </div>


                <button type="submit"
                    class="btn btn-primary"
                    id="btnSimpanSemua">

                    <i class="bi bi-save me-1"></i>
                    Simpan Semua

                </button>

            </div>


            <div class="row g-2 mb-3">

                <div class="col-md">

                    <div class="border rounded p-3 h-100">

                        <div class="text-muted small">
                            Total Halaman
                        </div>

                        <div class="fw-bold fs-5">
                            {{ $total }}
                        </div>

                    </div>

                </div>


                <div class="col-md">

                    <div class="border rounded p-3 h-100">

                        <div class="text-muted small">
                            Ditemukan
                        </div>

                        <div class="fw-bold fs-5 text-success">
                            {{ $matched }}
                        </div>

                    </div>

                </div>


                <div class="col-md">

                    <div class="border rounded p-3 h-100">

                        <div class="text-muted small">
                            Sudah Ada
                        </div>

                        <div class="fw-bold fs-5 text-primary">
                            {{ $existing }}
                        </div>

                    </div>

                </div>


                <div class="col-md">

                    <div class="border rounded p-3 h-100">

                        <div class="text-muted small">
                            Tidak Ditemukan
                        </div>

                        <div class="fw-bold fs-5 text-warning">
                            {{ $notFound }}
                        </div>

                    </div>

                </div>


                <div class="col-md">

                    <div class="border rounded p-3 h-100">

                        <div class="text-muted small">
                            Tidak Terbaca
                        </div>

                        <div class="fw-bold fs-5 text-danger">
                            {{ $unreadable }}
                        </div>

                    </div>

                </div>

            </div>


            <div class="table-responsive">

                <table class="table table-bordered table-hover align-middle mb-0">

                    <thead class="table-light text-center">

                        <tr>

                            <th style="width:70px;">
                                No.
                            </th>

                            <th style="width:100px;">
                                Halaman
                            </th>

                            <th style="min-width:220px;">
                                No. Pesanan
                            </th>

                            <th style="min-width:220px;">
                                No. Resi
                            </th>

                            <th style="width:180px;">
                                Status
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                        @foreach(
                            $preview
                            as $index => $item
                        )

                            <tr>

                                <td class="text-center">
                                    {{ $index + 1 }}
                                </td>


                                <td class="text-center fw-semibold">

                                    {{ $item['halaman'] }}

                                    <input type="hidden"
                                        name="pages[{{ $index }}][halaman]"
                                        value="{{ $item['halaman'] }}">

                                </td>


                                <td>

                                    <input type="text"
                                        name="pages[{{ $index }}][no_pesanan]"
                                        class="form-control form-control-sm fw-semibold"
                                        value="{{ $item['no_pesanan'] }}"
                                        placeholder="No. Pesanan"
                                        autocomplete="off">

                                </td>


                                <td>

                                    <input type="text"
                                        name="pages[{{ $index }}][no_resi]"
                                        class="form-control form-control-sm"
                                        value="{{ $item['no_resi'] }}"
                                        placeholder="No. Resi"
                                        autocomplete="off">

                                </td>


                                <td class="text-center">

                                    @if(
                                        $item['status'] ===
                                        'matched'
                                    )

                                        <span class="badge bg-success">

                                            <i class="bi bi-check-circle me-1"></i>

                                            Ditemukan

                                        </span>


                                    @elseif(
                                        $item['status'] ===
                                        'existing'
                                    )

                                        <span class="badge bg-primary">

                                            <i class="bi bi-link-45deg me-1"></i>

                                            Sudah Ada

                                        </span>


                                    @elseif(
                                        $item['status'] ===
                                        'not_found'
                                    )

                                        <span class="badge bg-warning text-dark">

                                            <i class="bi bi-exclamation-triangle me-1"></i>

                                            Tidak Ditemukan

                                        </span>


                                    @else

                                        <span class="badge bg-danger">

                                            <i class="bi bi-x-circle me-1"></i>

                                            Tidak Terbaca

                                        </span>

                                    @endif

                                </td>

                            </tr>

                        @endforeach

                    </tbody>

                </table>

            </div>

        </form>

    @endif

</div>

@endsection


@push('styles')

<style>
    .select2-container {
        width: 100% !important;
    }

    .table td {
        vertical-align: middle;
    }
</style>

@endpush


@push('scripts')

<script>
$(function () {

    const selectedToko =
        "{{ old('id_toko', $selectedToko ?? '') }}";


    $('#namaToko').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: 'Pilih Toko',
        allowClear: true
    });


    function marketplaceInfo(
        marketplace
    ) {

        const container =
            $('#marketplaceInfo');

        if (!marketplace) {

            container
                .hide()
                .empty();

            return;
        }


        if (marketplace === 'Tiktok') {

            container
                .html(`
                    <div class="alert alert-info py-2 mb-0">

                        <i class="bi bi-info-circle me-1"></i>

                        TikTok:
                        sistem membaca
                        <strong>Order Id</strong>
                        sebagai No. Pesanan dan nomor tracking sebagai No. Resi.

                    </div>
                `)
                .show();

            return;
        }


        container
            .html(`
                <div class="alert alert-info py-2 mb-0">

                    <i class="bi bi-info-circle me-1"></i>

                    Shopee:
                    sistem membaca No. Pesanan dan No. Resi dari setiap halaman PDF.

                </div>
            `)
            .show();

    }


    function loadToko(
        marketplace,
        idToko = ''
    ) {

        const selectToko =
            $('#namaToko');

        selectToko
            .empty()
            .append(
                '<option value="">Memuat toko...</option>'
            )
            .trigger(
                'change.select2'
            );


        if (!marketplace) {

            selectToko
                .empty()
                .append(
                    '<option value="">Pilih Marketplace dulu</option>'
                )
                .trigger(
                    'change.select2'
                );

            return;
        }


        const url =
            "{{ route('pesanan.getToko', ':market') }}"
                .replace(
                    ':market',
                    encodeURIComponent(
                        marketplace
                    )
                );


        $.ajax({

            url: url,

            method: 'GET',

            dataType: 'json',

            success: function(response) {

                selectToko.empty();

                selectToko.append(
                    '<option value="">Pilih Toko</option>'
                );


                if (
                    !Array.isArray(response) ||
                    response.length === 0
                ) {

                    selectToko.append(
                        '<option value="">Tidak ada toko</option>'
                    );

                    selectToko.trigger(
                        'change.select2'
                    );

                    return;
                }


                response.forEach(
                    function(toko) {

                        const option =
                            $('<option>', {
                                value:
                                    toko.id_toko,

                                text:
                                    toko.nama_toko
                            });


                        if (
                            idToko &&
                            String(
                                toko.id_toko
                            ) ===
                            String(
                                idToko
                            )
                        ) {
                            option.prop(
                                'selected',
                                true
                            );
                        }


                        selectToko.append(
                            option
                        );

                    }
                );


                selectToko.trigger(
                    'change.select2'
                );

            },


            error: function(xhr) {

                console.error(
                    'Gagal mengambil toko:',
                    xhr.responseText
                );


                selectToko
                    .empty()
                    .append(
                        '<option value="">Gagal memuat toko</option>'
                    )
                    .trigger(
                        'change.select2'
                    );

            }

        });

    }


    $('#marketplace').on(
        'change',
        function () {

            const marketplace =
                $(this).val();

            loadToko(
                marketplace
            );

            marketplaceInfo(
                marketplace
            );

        }
    );


    const marketplaceAwal =
        $('#marketplace').val();


    if (marketplaceAwal) {

        loadToko(
            marketplaceAwal,
            selectedToko
        );

        marketplaceInfo(
            marketplaceAwal
        );

    }


    $('#formImportResi').on(
        'submit',
        function () {

            const btn =
                $('#btnPreview');


            if (
                !$('#marketplace').val()
            ) {

                alert(
                    'Pilih marketplace terlebih dahulu.'
                );

                return false;
            }


            if (
                !$('#namaToko').val()
            ) {

                alert(
                    'Pilih nama toko terlebih dahulu.'
                );

                return false;
            }


            if (
                !$('#fileUpload')[0]
                    .files
                    .length
            ) {

                alert(
                    'Pilih file PDF terlebih dahulu.'
                );

                return false;
            }


            btn
                .prop(
                    'disabled',
                    true
                )
                .html(`
                    <span class="spinner-border spinner-border-sm me-2"></span>
                    Membaca PDF...
                `);

        }
    );


    $('#formSimpanResi').on(
        'submit',
        function () {

            const btn =
                $('#btnSimpanSemua');

            btn
                .prop(
                    'disabled',
                    true
                )
                .html(`
                    <span class="spinner-border spinner-border-sm me-2"></span>
                    Menyimpan...
                `);

        }
    );

});
</script>

@endpush
