@extends('layouts.app')

@section('content')

<div class="container-fluid">

    {{-- HEADER --}}
    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>
            <h4 class="mb-1">
                Editor
            </h4>

            <div class="text-muted small">
                Pekerjaan Editor & VBA Corel
            </div>
        </div>

    </div>


    {{-- ALERT --}}
    @if(session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif

    @if(session('import_errors') && count(session('import_errors')))
        <div class="alert alert-warning">
            <div class="fw-semibold mb-2">
                Data yang dilewati:
            </div>

            @foreach(session('import_errors') as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif


    {{-- CARD --}}
    <div class="row g-3 mb-4">

        <div class="col-md-4">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-body">

                    <div class="text-muted small mb-1">
                        Belum Dikerjakan
                    </div>

                    <div class="fs-3 fw-bold">
                        {{ number_format($totalBelumEditor) }}
                    </div>

                    <div class="text-muted small">
                        item
                    </div>

                </div>

            </div>

        </div>


        <div class="col-md-4">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-body">

                    <div class="text-muted small mb-1">
                        Sudah Import Editor
                    </div>

                    <div class="fs-3 fw-bold text-success">
                        {{ number_format($totalSelesaiEditor) }}
                    </div>

                    <div class="text-muted small">
                        item
                    </div>

                </div>

            </div>

        </div>

    </div>


    {{-- DOWNLOAD --}}
    <div class="card border-0 shadow-sm">

        <div class="card-header bg-white">
            <strong>
                Pekerjaan Plat
            </strong>
        </div>

        <div class="card-body">

            <p class="text-muted">
                Download data pesanan yang akan dikerjakan
                menggunakan Excel dan VBA Corel.
            </p>

            <a
                href="{{ route('editor.download.plat') }}"
                class="btn btn-success"
            >
                <i class="bi bi-file-earmark-excel me-1"></i>

                Download Excel Editor
            </a>

        </div>

    </div>

    <div class="card border-0 shadow-sm mt-3">
        <div class="card-header bg-white">
            <strong>Import Hasil Editor</strong>
        </div>

        <div class="card-body">

            <form action="{{ route('editor.import') }}"
                method="POST"
                enctype="multipart/form-data">

                @csrf

                <div class="mb-3">
                    <label class="form-label">
                        File Excel Hasil Editor
                    </label>

                    <input type="file"
                        name="file_editor"
                        class="form-control"
                        accept=".xlsx,.xls,.xlsm"
                        required>
                </div>

                @error('file_editor')
                    <div class="alert alert-danger py-2">
                        {{ $message }}
                    </div>
                @enderror

                <button type="submit"
                    class="btn btn-primary">

                    <i class="fa-solid fa-file-arrow-up me-1"></i>
                    Import Hasil Editor
                </button>

            </form>

        </div>
    </div>

</div>

@endsection
