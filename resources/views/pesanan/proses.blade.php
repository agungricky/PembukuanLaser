@extends('layouts.app')

@section('content')
  <div class="bg-white p-3 p-md-4 rounded shadow-sm w-100">

    {{-- Header + Toolbar --}}
    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
      <div class="d-flex align-items-center gap-2">
        <span class="badge bg-primary-subtle text-primary fw-semibold px-2 py-1">⏳</span>
        <h5 class="mb-0">Pesanan Diproses</h5>
      </div>

      <div class="d-flex align-items-center gap-3">
        @php
          $from = $pesanan->firstItem() ?? 0;
          $to   = $pesanan->lastItem() ?? 0;
          $tot  = $pesanan->total();
        @endphp

        <div class="text-muted small">
          {{ $tot ? "{$from}–{$to} dari {$tot}" : '0' }} pesanan
        </div>

        <button id="btnUbahStatus"
                class="btn btn-primary btn-sm d-flex align-items-center gap-2"
                data-bs-toggle="modal"
                data-bs-target="#ubahStatusModal"
                disabled>
          <i class="bi bi-arrow-repeat"></i>
          <span>Ubah Status</span>
        </button>
      </div>
    </div>

    {{-- Filter --}}
    <form method="GET" action="{{ route('pesanan.proses') }}" class="mb-3 filter-bar">
      <div class="card border-0 shadow-sm">
        <div class="card-body py-3">
          <div class="row g-3 align-items-end">

            {{-- Search --}}
            <div class="col-12 col-md-4">
              <label class="form-label mb-1 small text-muted">Cari</label>
              <div class="input-group input-group-sm">
                <span class="input-group-text bg-white border-end-0">
                  <i class="bi bi-search text-muted"></i>
                </span>
                <input type="text"
                       class="form-control form-control-sm border-start-0"
                       name="no_pesanan"
                       value="{{ request('no_pesanan') }}"
                       placeholder="No. pesanan / No. resi">
              </div>
            </div>

            {{-- Toko --}}
            <div class="col-12 col-md-3">
              <label class="form-label mb-1 small text-muted">Toko</label>
              <select name="id_toko" class="form-select form-select-sm">
                <option value="">Semua toko</option>
                @foreach ($daftarToko as $tk)
                  <option value="{{ $tk->id_toko }}"
                    {{ (string)$tk->id_toko === request('id_toko') ? 'selected' : '' }}>
                    {{ $tk->nama_toko }} [{{ $tk->marketplace }}]
                  </option>
                @endforeach
              </select>
            </div>

            {{-- Tanggal --}}
            <div class="col-12 col-md-3">
              <label class="form-label mb-1 small text-muted">Tanggal</label>
              <div class="input-group input-group-sm">
                <span class="input-group-text bg-white border-end-0">
                  <i class="bi bi-calendar-event text-muted"></i>
                </span>
                <input type="text"
                       id="daterange"
                       name="tanggal"
                       class="form-control form-control-sm border-start-0"
                       value="{{ request('tanggal') }}"
                       placeholder="Tanggal"
                       readonly>
              </div>
            </div>

            {{-- Per Page --}}
            <div class="col-6 col-md-1">
              <label class="form-label mb-1 small text-muted">Per Page</label>
              <select name="per_page" class="form-select form-select-sm">
                @foreach ($allowed ?? [10, 20, 50, 100] as $opt)
                  <option value="{{ $opt }}" {{ (int) request('per_page', $perPage ?? 20) === $opt ? 'selected' : '' }}>
                    {{ $opt }}
                  </option>
                @endforeach
              </select>
            </div>

            {{-- Reset --}}
            <div class="col-6 col-md-1 text-md-end">
              <a href="{{ route('pesanan.proses') }}"
                 class="btn btn-outline-secondary btn-sm w-100">
                Reset
              </a>
            </div>

          </div>
        </div>
      </div>
    </form>

    {{-- Empty State --}}
    @if ($pesanan->isEmpty())
      <div class="alert alert-info mb-0">Tidak ada data pesanan.</div>
    @endif

    {{-- Tabel --}}
    @if ($pesanan->isNotEmpty())
      <div class="table-responsive mt-3">
        <table class="table table-sm table-hover align-middle" style="width:100%">
          <thead class="table-light">
            <tr>
              <th class="text-center" style="width:44px">
                <input type="checkbox" id="check-all" class="form-check-input m-0">
              </th>
              <th class="text-center" style="min-width:110px">Tanggal</th>
              <th class="text-center" style="min-width:150px">No. Pesanan</th>
              <th class="text-center" style="min-width:150px">No. Resi</th>
              <th class="text-end" style="min-width:120px">Total Harga</th>
              <th class="text-center" style="min-width:150px">Nama Toko</th>
              <th class="text-center" style="min-width:180px">Pengiriman</th>
              <th class="text-center" style="min-width:140px">Aksi</th>
            </tr>
          </thead>

          <tbody>
            @foreach ($pesanan as $item)
              <tr>
                <td class="text-center">
                  <input type="checkbox"
                         name="selected[]"
                         value="{{ $item->no_pesanan }}"
                         class="form-check-input m-0 row-checkbox">
                </td>

                <td class="text-center">
                  {{ \Carbon\Carbon::parse($item->tanggal)->format('d/m/Y') }}
                </td>

                <td class="text-center fw-semibold">
                  {{ $item->no_pesanan }}
                </td>

                <td class="text-center fw-semibold">
                  {{ $item->no_resi ?: '—' }}
                </td>

                <td class="text-end fw-semibold">
                  Rp{{ number_format($item->total_harga ?? 0, 0, ',', '.') }}
                </td>

                <td class="text-center fw-semibold">
                  {{ $item->toko->nama_toko ?? '—' }}
                </td>

                <td class="text-center">
                  <div class="d-flex flex-column align-items-center">
                    <span class="badge rounded-pill bg-info-subtle text-info mb-1">
                      {{ $item->kurir ?: '—' }}
                    </span>
                    <small class="text-muted">
                      Resi: {{ $item->no_resi ?: '—' }}
                    </small>
                  </div>
                </td>

                <td class="text-center">
                  <a href="{{ route('pesanan.show', $item->no_pesanan) }}"
                     target="_blank"
                     class="btn btn-outline-primary btn-sm">
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
          @if ($pesanan->total())
            Menampilkan {{ $pesanan->firstItem() }}–{{ $pesanan->lastItem() }} dari {{ $pesanan->total() }} pesanan
            <span class="mx-1">•</span>
            Total item halaman ini: <strong>{{ number_format($total ?? 0, 0, ',', '.') }}</strong>
          @endif
        </div>

        <nav>
          {{ $pesanan->withQueryString()->onEachSide(1)->links('vendor.pagination.bootstrap-5-compact') }}
        </nav>
      </div>
    @endif

  </div>

  {{-- Modal Ubah Status --}}
  <div class="modal fade" id="ubahStatusModal" tabindex="-1" aria-labelledby="ubahStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="ubahStatusModalLabel">Ubah Status Pesanan</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
        </div>

        <div class="modal-body">
          <div class="mb-3">
            <label for="statusPesanan" class="form-label">Pilih Status Baru</label>
            <select class="form-select" id="statusPesanan">
              <option value="">-- Pilih Status --</option>
              <option value="kirim">Dikirim</option>
              <option value="batal">Batal</option>
            </select>
          </div>

          <div class="mb-3 d-none" id="notesContainer">
            <label for="notes" class="form-label">Catatan Pembatalan</label>
            <textarea class="form-control" id="notes" rows="3" placeholder="Tulis alasan pembatalan..."></textarea>
          </div>

          <div id="error-message" class="alert alert-danger d-none mb-0"></div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="button" class="btn btn-primary d-inline-flex align-items-center gap-2" id="btn-simpan-status">
            <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
            <span>Simpan</span>
          </button>
        </div>
      </div>
    </div>
  </div>
@endsection

@push('styles')
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css">

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

@push('scripts')
  <script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/moment.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>

  <script>
    $(function () {
      const form = document.querySelector('.filter-bar');

      function debounce(fn, delay) {
        let timer = null;
        return function (...args) {
          clearTimeout(timer);
          timer = setTimeout(() => fn.apply(this, args), delay);
        };
      }

      // Daterangepicker
      $('#daterange').daterangepicker({
        locale: {
          format: 'YYYY-MM-DD',
          separator: ' s.d ',
          applyLabel: 'Terapkan',
          cancelLabel: 'Batal',
          customRangeLabel: 'Custom',
          daysOfWeek: ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'],
          monthNames: ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
          firstDay: 1
        },
        autoUpdateInput: false,
        opens: 'left',
      });

      $('#daterange').on('apply.daterangepicker', function (ev, picker) {
        $(this).val(
          picker.startDate.format('YYYY-MM-DD') +
          ' s.d ' +
          picker.endDate.format('YYYY-MM-DD')
        );

        form.submit();
      });

      $('#daterange').on('cancel.daterangepicker', function () {
        $(this).val('');
        form.submit();
      });

      const oldRange = $('#daterange').val();
      if (oldRange) {
        const dates = oldRange.split(' s.d ');
        if (dates.length === 2) {
          $('#daterange').data('daterangepicker').setStartDate(moment(dates[0], 'YYYY-MM-DD'));
          $('#daterange').data('daterangepicker').setEndDate(moment(dates[1], 'YYYY-MM-DD'));
        }
      }

      // Search otomatis
      const searchInput = form.querySelector('[name="no_pesanan"]');
      if (searchInput) {
        searchInput.addEventListener('input', debounce(function () {
          form.submit();
        }, 300));
      }

      // Submit otomatis toko & per page
      ['id_toko', 'per_page'].forEach(function (name) {
        const el = form.querySelector('[name="' + name + '"]');
        if (!el) return;

        el.addEventListener('change', function () {
          form.submit();
        });
      });

      // Checkbox master
      $('#check-all').on('click', function () {
        $('.row-checkbox').prop('checked', this.checked);
        toggleButton();
      });

      $('body').on('change', '.row-checkbox', function () {
        const all = $('.row-checkbox').length;
        const checked = $('.row-checkbox:checked').length;

        $('#check-all').prop('checked', all > 0 && all === checked);
        toggleButton();
      });

      function toggleButton() {
        $('#btnUbahStatus').prop('disabled', $('.row-checkbox:checked').length === 0);
      }

      // Tampil/hidden notes saat batal
      $('#statusPesanan').on('change', function () {
        $('#notesContainer').toggleClass('d-none', this.value !== 'batal');
      });

      // Simpan status bulk
      $('#btn-simpan-status').on('click', function () {
        const $btn = $(this);
        const status = $('#statusPesanan').val();
        const notes = $('#notes').val();

        const selected = $('.row-checkbox:checked').map(function () {
          return $(this).val();
        }).get();

        if (!status) {
          alert('Silakan pilih status terlebih dahulu.');
          return;
        }

        if (selected.length === 0) {
          alert('Silakan pilih minimal satu pesanan.');
          return;
        }

        $btn.prop('disabled', true);
        $btn.find('.spinner-border').removeClass('d-none');

        $.ajax({
          url: "{{ route('pesanan.ubahStatus') }}",
          method: "POST",
          data: {
            _token: "{{ csrf_token() }}",
            selected: selected,
            status: status,
            notes: status === 'batal' ? notes : null
          },
          success: function () {
            location.reload();
          },
          error: function (xhr) {
            $('#error-message')
              .removeClass('d-none')
              .text('Gagal mengubah status: ' + (xhr.responseText || 'Unknown error'));
          },
          complete: function () {
            $btn.prop('disabled', false);
            $btn.find('.spinner-border').addClass('d-none');
          }
        });
      });
    });
  </script>
@endpush