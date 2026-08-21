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
            Data Bermasalah
        </div>

        @foreach(session('import_errors') as $error)
            <div class="small mb-1">
                {{ $error }}
            </div>
        @endforeach

        <button type="button"
            class="btn-close"
            data-bs-dismiss="alert">
        </button>

    </div>
@endif
