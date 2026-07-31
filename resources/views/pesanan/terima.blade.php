@extends('layouts.app')

@push('styles')
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
  <style>
    .table thead th { font-size: .85rem; letter-spacing: .2px; }
    .table tbody td { vertical-align: middle; }
  </style>
@endpush

@section('content')

  <div class="bg-white p-3 p-md-4 rounded shadow-sm w-100">

    {{-- Header --}}
    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
      <div class="d-flex align-items-center gap-2">
        <span class="badge bg-success-subtle text-success fw-semibold px-2 py-1">✅</span>
        <h5 class="mb-0">Pesanan Diterima</h5>
      </div>

      <div class="text-muted small">
        {{ number_format($jumlahPesanan, 0, ',', '.') }} pesanan
        <span class="mx-1">•</span>
        Total keuntungan (halaman ini):
        <span class="fw-semibold">Rp{{ number_format($totalKeuntungan, 0, ',', '.') }}</span>
        <span class="mx-1">•</span>
        Total selisih (halaman ini):
        <span class="fw-semibold">Rp{{ number_format($totalSelisih, 0, ',', '.') }}</span>
      </div>
    </div>

    {{-- Filter (auto submit) --}}
    <form id="filterForm" method="GET" action="{{ route('pesanan.terima') }}" class="mb-3">
      <div class="card border-0 shadow-sm">
        <div class="card-body py-3">
          <div class="row g-3 align-items-end">

            {{-- Cari --}}
            <div class="col-12 col-md-4 col-lg-3">
              <label class="form-label mb-1 small text-muted">Cari</label>
              <div class="input-group input-group-sm">
                <span class="input-group-text bg-white border-end-0">
                  <i class="bi bi-search text-muted"></i>
                </span>
                <input type="text"
                       name="no_pesanan"
                       class="form-control form-control-sm border-start-0"
                       placeholder="No. pesanan / No. resi"
                       value="{{ request('no_pesanan') }}">
              </div>
            </div>

            {{-- Toko --}}
            <div class="col-12 col-md-3 col-lg-3">
              <label class="form-label mb-1 small text-muted">Toko</label>
              <select name="id_toko" class="form-select form-select-sm">
                <option value="">Semua toko</option>
                @foreach ($daftarToko as $tk)
                  <option value="{{ $tk->id_toko }}"
                    {{ (string) $tk->id_toko === (string) request('id_toko') ? 'selected' : '' }}>
                    {{ $tk->nama_toko }} [{{ $tk->marketplace }}]
                  </option>
                @endforeach
              </select>
            </div>

            {{-- Urut (BARU: tanggal / selisih) --}}
            <div class="col-12 col-md-2 col-lg-2">
              <label class="form-label mb-1 small text-muted">Urut</label>
              @php $sort = (string) request('sort', 'tanggal'); @endphp
              <select name="sort" class="form-select form-select-sm">
                <option value="tanggal" {{ $sort === 'tanggal' ? 'selected' : '' }}>Tanggal</option>
                <option value="selisih" {{ $sort === 'selisih' ? 'selected' : '' }}>Selisih</option>
              </select>
            </div>

            {{-- Arah (BARU: asc/desc) --}}
            <div class="col-12 col-md-2 col-lg-2">
              <label class="form-label mb-1 small text-muted">Arah</label>
              @php $dir = (string) request('dir', 'desc'); @endphp
              <select name="dir" class="form-select form-select-sm">
                <option value="desc" {{ $dir === 'desc' ? 'selected' : '' }}>Desc</option>
                <option value="asc"  {{ $dir === 'asc'  ? 'selected' : '' }}>Asc</option>
              </select>
            </div>

            {{-- Tanggal --}}
            <div class="col-12 col-md-3 col-lg-2">
              <label class="form-label mb-1 small text-muted">Tanggal</label>
              <div class="input-group input-group-sm">
                <span class="input-group-text bg-white border-end-0">
                  <i class="bi bi-calendar-event text-muted"></i>
                </span>
                <input type="text"
                       id="daterange"
                       name="tanggal"
                       class="form-control form-control-sm border-start-0"
                       value="{{ (string) request('tanggal', '') }}"
                       placeholder="Rentang tanggal"
                       readonly>
              </div>
            </div>

            {{-- Reset --}}
            <div class="col-12 col-md-2 col-lg-2 text-md-end">
              <a href="{{ route('pesanan.terima') }}" class="btn btn-outline-secondary btn-sm w-100">
                Reset
              </a>
            </div>

          </div>
        </div>
      </div>
    </form>

    {{-- Empty state --}}
    @if($pesanan->isEmpty())
      <div class="alert alert-info mb-0">Tidak ada data pesanan.</div>
    @endif

    {{-- Tabel --}}
    @if($pesanan->isNotEmpty())
      <div class="table-responsive mt-3">
        <table class="table table-sm table-hover align-middle" style="width:100%">
          <thead class="table-light">
            <tr>
              <th class="text-center" style="min-width:110px">Tanggal</th>
              <th class="text-center" style="min-width:160px">No. Pesanan</th>
              <th class="text-center" style="min-width:160px">No. Resi</th>
              <th class="text-end" style="min-width:110px">Total Harga</th>
              <th class="text-end" style="min-width:110px">Total HPP</th>
              <th class="text-end" style="min-width:110px">Biaya Admin</th>
              <th class="text-end" style="min-width:110px">Keuntungan</th>
              <th class="text-end" style="min-width:110px">Selisih</th>
              <th class="text-center" style="min-width:140px">Nama Toko</th>
              <th class="text-center" style="min-width:120px">Aksi</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($pesanan as $p)
              @php
                $hppRow = (float) ($p->total_hpp_calc ?? 0);
                $selisihRow = (float) ($p->selisih ?? 0);
              @endphp

              <tr>
                <td class="text-center">{{ \Carbon\Carbon::parse($p->tanggal)->format('d/m/Y') }}</td>
                <td class="text-center fw-semibold">{{ $p->no_pesanan }}</td>
                <td class="text-center fw-semibold">{{ $p->no_resi }}</td>

                <td class="text-end">
                  Rp{{ number_format((float) ($p->total_harga ?? 0), 0, ',', '.') }}
                </td>
                <td class="text-end">
                  Rp{{ number_format($hppRow, 0, ',', '.') }}
                </td>
                <td class="text-end">
                  Rp{{ number_format((float) ($p->total_admin ?? 0), 0, ',', '.') }}
                </td>
                <td class="text-end fw-semibold">
                  Rp{{ number_format((float) ($p->keuntungan ?? 0), 0, ',', '.') }}
                </td>
                <td class="text-end {{ $selisihRow < 0 ? 'text-danger' : 'text-success' }}">
                  Rp{{ number_format($selisihRow, 0, ',', '.') }}
                </td>
                <td class="text-center fw-semibold">
                  {{ $p->toko->nama_toko ?? '—' }}
                </td>
                <td class="text-center">
                  <a href="{{ route('pesanan.show', $p->no_pesanan) }}" target="_blank"
                     class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-file-earmark-text me-1"></i> Rincian
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
          @if($pesanan->total())
            Menampilkan {{ $pesanan->firstItem() }}–{{ $pesanan->lastItem() }} dari {{ $pesanan->total() }} pesanan
          @endif
        </div>

        <nav>
          {{ $pesanan->withQueryString()->onEachSide(1)->links('vendor.pagination.bootstrap-5-compact') }}
        </nav>
      </div>
    @endif

  </div>
@endsection

@push('scripts')
  <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>

  <script>
    $(function () {
      const form = document.getElementById('filterForm');

      // daterangepicker [web:21]
      $('#daterange').daterangepicker({
        locale: {
          format: 'YYYY-MM-DD',
          separator: ' s.d ',
          applyLabel: 'Terapkan',
          cancelLabel: 'Batal',
          customRangeLabel: "Custom",
          daysOfWeek: ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'],
          monthNames: ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
          firstDay: 1
        },
        autoUpdateInput: false,
        opens: 'left'
      });

      $('#daterange').on('apply.daterangepicker', function (ev, picker) {
        $(this).val(picker.startDate.format('YYYY-MM-DD') + ' s.d ' + picker.endDate.format('YYYY-MM-DD'));
        form.submit();
      });

      $('#daterange').on('cancel.daterangepicker', function () {
        $(this).val('');
        form.submit();
      });

      // restore old range
      const oldRange = $('#daterange').val();
      if (oldRange) {
        const parts = oldRange.split(' s.d ');
        if (parts.length === 2) {
          const drp = $('#daterange').data('daterangepicker');
          drp.setStartDate(moment(parts[0], 'YYYY-MM-DD'));
          drp.setEndDate(moment(parts[1], 'YYYY-MM-DD'));
        }
      }

      function debounce(fn, delay) {
        let timer = null;
        return function (...args) {
          clearTimeout(timer);
          timer = setTimeout(() => fn.apply(this, args), delay);
        };
      }

      // search otomatis (debounce)
      const searchInput = form.querySelector('[name="no_pesanan"]');
      if (searchInput) {
        const submitSearch = debounce(() => form.submit(), 500);
        searchInput.addEventListener('input', submitSearch);
      }

      // auto submit saat pilih toko / sort / dir
      const tokoSelect = form.querySelector('[name="id_toko"]');
      if (tokoSelect) tokoSelect.addEventListener('change', () => form.submit());

      const sortSelect = form.querySelector('[name="sort"]');
      if (sortSelect) sortSelect.addEventListener('change', () => form.submit());

      const dirSelect = form.querySelector('[name="dir"]');
      if (dirSelect) dirSelect.addEventListener('change', () => form.submit());
    });
  </script>
@endpush