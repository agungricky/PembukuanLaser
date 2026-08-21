@extends('layouts.app')

@section('content')

<div class="container-fluid">

    <div class="mb-4">
        <h4 class="fw-bold mb-1">
            Import Hasil Editor
        </h4>

        <div class="text-muted small">
            Upload Excel Part setelah proses Editor dan VBA Corel selesai
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

                    <form action="{{ route('editor.import') }}"
                        method="POST"
                        enctype="multipart/form-data"
                        id="formImportEditor">

                        @csrf

                        <div class="mb-3">

                            <label class="form-label fw-semibold">
                                File Excel
                            </label>

                            <input type="file"
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

                        <button type="submit"
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
    $('#formImportEditor').on('submit', function () {
        if (!$('#fileEditor')[0].files.length) {
            return false;
        }

        $('#btnImportEditor')
            .prop('disabled', true)
            .html(`
                <span class="spinner-border spinner-border-sm me-2"></span>
                Mengimport...
            `);
    });
});
</script>

@endpush
