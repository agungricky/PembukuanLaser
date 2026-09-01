@extends('layouts.app')

@section('content')

<div class="container-fluid">

    <div class="mb-4">

        <h4 class="fw-bold mb-1">
            Import Hasil Editor
        </h4>

        <div class="text-muted small">
            Upload Excel Antrian Editor setelah proses Editor dan VBA Corel selesai
        </div>

    </div>

    @include('editor.partials.alert')

    <div class="row">

        <div class="col-xl-7">

            <div class="card border-0 shadow-sm">

                <div class="card-header bg-white py-3">

                    <strong>
                        Upload Excel Editor
                    </strong>

                </div>

                <div class="card-body p-4">

                    <div class="alert alert-light border">

                        <div class="fw-semibold mb-2">
                            STATUS REQUEST
                        </div>

                        <div class="small mb-1">

                            <strong>Kosong</strong>
                            = Normal, langsung dikunci

                        </div>

                        <div class="small mb-1">

                            <strong>RANDOM</strong>
                            = Random, langsung dikunci

                        </div>

                        <div class="small">

                            <strong>MENUNGGU</strong>
                            = Belum ada request customer

                        </div>

                    </div>

                    <form
                        action="{{ route('editor.import') }}"
                        method="POST"
                        enctype="multipart/form-data"
                        id="formImportEditor">

                        @csrf

                        <div class="mb-3">

                            <label
                                for="fileEditor"
                                class="form-label fw-semibold">

                                File Excel Antrian Editor

                            </label>

                            <input
                                type="file"
                                name="file_editor"
                                id="fileEditor"
                                class="form-control @error('file_editor') is-invalid @enderror"
                                accept=".xlsx,.xls,.xlsm"
                                required>

                            @error('file_editor')

                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>

                            @enderror

                            <div class="form-text">
                                XLSX, XLS atau XLSM. Maksimal 20 MB.
                            </div>

                        </div>

                        <div
                            class="alert alert-info py-2 mb-3"
                            id="importInfo"
                            style="display:none;">

                            <div class="d-flex align-items-center gap-2">

                                <span
                                    class="spinner-border spinner-border-sm"
                                    role="status">
                                </span>

                                <div class="small">
                                    Sedang memproses hasil Editor. Tunggu sampai halaman berpindah ke Riwayat Antrian Editor.
                                </div>

                            </div>

                        </div>

                        <button
                            type="submit"
                            class="btn btn-primary"
                            id="btnImportEditor">

                            <i class="bi bi-upload me-1"></i>
                            Import Hasil Editor

                        </button>

                    </form>

                </div>

            </div>

        </div>

    </div>

</div>

@endsection

@push('scripts')

<script>
$(function () {

    let isSubmitting = false;

    $('#formImportEditor').on(
        'submit',
        function (event) {

            if (isSubmitting) {
                event.preventDefault();

                return false;
            }

            const fileInput =
                $('#fileEditor')[0];

            if (
                !fileInput ||
                !fileInput.files ||
                fileInput.files.length === 0
            ) {
                event.preventDefault();

                $('#fileEditor').trigger(
                    'focus'
                );

                return false;
            }

            isSubmitting = true;

            $('#btnImportEditor')
                .prop(
                    'disabled',
                    true
                )
                .html(`
                    <span
                        class="spinner-border spinner-border-sm me-2"
                        role="status">
                    </span>
                    Mengimport...
                `);

            $('#importInfo').show();
        }
    );

});
</script>

@endpush
