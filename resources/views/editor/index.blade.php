@extends('layouts.app')

@section('content')

<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1 fw-bold">
                Editor
            </h4>

            <div class="text-muted small">
                Pekerjaan Editor & VBA Corel
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="bi bi-check-circle me-1"></i>
            {{ session('success') }}

            <button type="button"
                class="btn-close"
                data-bs-dismiss="alert">
            </button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="bi bi-exclamation-circle me-1"></i>
            {{ session('error') }}

            <button type="button"
                class="btn-close"
                data-bs-dismiss="alert">
            </button>
        </div>
    @endif

    @if(session('import_errors') && count(session('import_errors')))
        <div class="alert alert-warning alert-dismissible fade show">

            <div class="fw-semibold mb-2">
                <i class="bi bi-exclamation-triangle me-1"></i>
                Data yang dilewati
            </div>

            <div class="small">
                @foreach(session('import_errors') as $error)
                    <div class="mb-1">
                        {{ $error }}
                    </div>
                @endforeach
            </div>

            <button type="button"
                class="btn-close"
                data-bs-dismiss="alert">
            </button>

        </div>
    @endif

    <div class="row g-3 mb-4">

        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">

                    <div class="d-flex justify-content-between align-items-center">

                        <div>
                            <div class="text-muted small mb-1">
                                Belum Dikerjakan
                            </div>

                            <div class="fs-3 fw-bold">
                                {{ number_format($totalBelumEditor ?? 0, 0, ',', '.') }}
                            </div>

                            <div class="text-muted small">
                                item
                            </div>
                        </div>

                        <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                            <i class="bi bi-hourglass-split"></i>
                        </div>

                    </div>

                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">

                    <div class="d-flex justify-content-between align-items-center">

                        <div>
                            <div class="text-muted small mb-1">
                                Sudah Import Editor
                            </div>

                            <div class="fs-3 fw-bold text-success">
                                {{ number_format($totalSelesaiEditor ?? 0, 0, ',', '.') }}
                            </div>

                            <div class="text-muted small">
                                item
                            </div>
                        </div>

                        <div class="stat-icon bg-success bg-opacity-10 text-success">
                            <i class="bi bi-check-circle"></i>
                        </div>

                    </div>

                </div>
            </div>
        </div>

    </div>

    <div class="row g-3">

        <div class="col-lg-6">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-header bg-white border-bottom py-3">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-file-earmark-excel text-success"></i>

                        <strong>
                            Download Pekerjaan Editor
                        </strong>
                    </div>
                </div>

                <div class="card-body p-4">

                    <div class="text-muted mb-4">
                        Download data pesanan yang akan dikerjakan menggunakan Excel dan VBA Corel.
                    </div>

                    <a href="{{ route('editor.download.plat') }}"
                        class="btn btn-success">

                        <i class="bi bi-download me-1"></i>
                        Download Excel Editor
                    </a>

                </div>

            </div>

        </div>

        <div class="col-lg-6">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-header bg-white border-bottom py-3">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-file-earmark-arrow-up text-primary"></i>

                        <strong>
                            Import Hasil Editor
                        </strong>
                    </div>
                </div>

                <div class="card-body p-4">

                    <form action="{{ route('editor.import') }}"
                        method="POST"
                        enctype="multipart/form-data"
                        id="formImportEditor">

                        @csrf

                        <div class="mb-3">

                            <label for="fileEditor"
                                class="form-label fw-semibold">

                                File Excel Hasil Editor
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
                                Format file: XLSX, XLS, atau XLSM. Maksimal 20 MB.
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

@push('styles')

<style>
    .stat-icon {
        width: 52px;
        height: 52px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        flex-shrink: 0;
    }
</style>

@endpush

@push('scripts')

<script>
$(function () {

    $('#formImportEditor').on('submit', function () {

        const fileInput = $('#fileEditor');
        const btn = $('#btnImportEditor');

        if (!fileInput[0].files.length) {
            return false;
        }

        btn
            .prop('disabled', true)
            .html(`
                <span class="spinner-border spinner-border-sm me-2"></span>
                Mengimport...
            `);

    });

});
</script>

@endpush
