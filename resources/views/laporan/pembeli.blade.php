@extends('layouts.app')

@section('content')

  @push('styles')
    {{-- Chart.js donut membutuhkan sedikit style agar tampak rapih --}}
    <style>
    .page-title { font-size: 1.75rem; font-weight: 700 }

    .summary-card {
      border: 1px solid #edf1f5; border-radius: 14px; padding: 14px 16px;
      background: #fff; box-shadow: 0 1px 2px rgba(0, 0, 0, .04); text-align: center
    }
    .summary-label { color: #6b7280; font-weight: 600; font-size: .9rem }
    .summary-value { font-size: 1.45rem; font-weight: 800; color: #111827; letter-spacing: -.02em }
    .daterange-input { height: 40px }
    .donut-wrap { max-width: 340px; margin: auto }

    .summary-card { background:#fff; border:1px solid #e5e7eb; border-radius:14px;
      box-shadow:0 2px 8px rgba(0, 0, 0, 0.04); text-align:center; transition: transform .2s ease, box-shadow .2s ease; }
    .summary-card:hover { transform: translateY(-3px); box-shadow: 0 4px 16px rgba(0, 0, 0, 0.08); }
    .summary-label { font-size: 1rem; color: #6b7280; margin-bottom: .4rem; }
    .summary-value { font-size: 2rem; font-weight: 700; color: #111827; }
    </style>
  @endpush

  <div class="container-fluid py-3">

    {{-- Header + Filter --}}
    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
      <h1 class="page-title mb-0">👥 Detail Pembeli</h1>

      <div class="ms-auto d-flex flex-wrap align-items-center gap-2">
        <form method="GET" action="{{ route('pembeli') }}" id="filterForm" class="d-flex align-items-center gap-2">
          {{-- Date range (single input) --}}
          <input type="text" id="daterange" class="form-control daterange-input" placeholder="Pilih Periode" autocomplete="off" readonly>
          <input type="hidden" name="start_date" id="start_date" value="{{ e($startDate) }}">
          <input type="hidden" name="end_date" id="end_date" value="{{ e($endDate) }}">

          {{-- Pilihan Toko --}}
            <select name="store_id" class="form-select" style="width: 220px">
              <option value="">Semua Toko</option>
              @foreach(($stores ?? []) as $id => $store)
                {{-- anggap $store adalah object dengan nama_toko dan marketplace --}}
                <option value="{{ $id }}" {{ (string)($storeId ?? '') === (string)$id ? 'selected' : '' }}>
                  {{ $store->nama_toko }} - {{ strtoupper($store->marketplace) }}
                </option>
              @endforeach
            </select>

          <button type="submit" class="btn btn-primary">Terapkan</button>
          <a href="{{ route('pembeli') }}" class="btn btn-outline-secondary">Reset</a>
        </form>
      </div>
    </div>

    {{-- Ringkasan + Donut --}}
    <div class="row g-3 align-items-stretch mb-4">
      <div class="col-12 col-xl-8">
        <div class="row h-100 g-3">
          <div class="col-6 d-flex">
            <div class="summary-card flex-fill p-4">
              <div class="summary-label">Pembeli Baru</div>
              <div class="summary-value">{{ $summary['pembeli_baru'] }}</div>
            </div>
          </div>
          <div class="col-6 d-flex">
            <div class="summary-card flex-fill p-4">
              <div class="summary-label">Pembeli Lama</div>
              <div class="summary-value">{{ $summary['pembeli_lama'] }}</div>
            </div>
          </div>
          <div class="col-6 d-flex">
            <div class="summary-card flex-fill p-4">
              <div class="summary-label">Repeat (%)</div>
              <div class="summary-value">{{ number_format($summary['repeat_rate'], 2, ',', '.') }}%</div>
            </div>
          </div>
          <div class="col-6 d-flex">
            <div class="summary-card flex-fill p-4">
              <div class="summary-label">Total Pembeli (Unik)</div>
              <div class="summary-value">{{ $summary['total_pembeli'] }}</div>
            </div>
          </div>
        </div>
      </div>

      {{-- Donut Chart --}}
      <div class="col-12 col-xl-4 d-flex">
        <div class="card shadow-sm border-0 flex-fill">
          <div class="card-header bg-body fw-semibold">Komposisi Pembeli</div>
          <div class="card-body d-flex align-items-center">
            <canvas id="buyersDonut" class="w-100" aria-label="Donut pembeli baru vs lama"></canvas>
          </div>
        </div>
      </div>
    </div>

    {{-- Tabel Pembeli --}}
    <div class="card shadow-sm border-0">
      <div class="card-header bg-body d-flex justify-content-between align-items-center">
        <strong>Daftar Pembeli</strong>
        <span class="text-muted small">
          Periode: {{ \Carbon\Carbon::parse($startDate)->format('d-m-Y') }} s.d
          {{ \Carbon\Carbon::parse($endDate)->format('d-m-Y') }}
        </span>
      </div>

      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>Nama Pembeli</th>
              <th class="text-center" style="width:140px">Total Pesanan</th>
              <th class="text-end" style="width:180px">Total Penjualan</th>
              <th class="text-center" style="width:160px">Pesanan Terakhir</th>
            </tr>
          </thead>
          <tbody>
            @forelse ($buyers as $buyer)
              <tr>
                <td>{{ $buyer->username ?? '-' }}</td>
                <td class="text-center">{{ number_format($buyer->total_pesanan, 0, ',', '.') }}</td>
                <td class="text-end">Rp{{ number_format($buyer->total_penjualan ?? 0, 0, ',', '.') }}</td>
                <td class="text-center">
                  {{ $buyer->last_order ? \Carbon\Carbon::parse($buyer->last_order)->format('d-m-Y') : '-' }}
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="4" class="text-center py-4 text-muted">Tidak ada data pembeli untuk periode ini.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
@endsection

@push('scripts')
  {{-- daterangepicker deps --}}
  <script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/min/moment.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
  {{-- Chart.js --}}
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

  <script>
    (function () {
      const start = @json($startDate);
      const end   = @json($endDate);

      $('#daterange').daterangepicker({
        startDate: moment(start, 'YYYY-MM-DD'),
        endDate: moment(end, 'YYYY-MM-DD'),
        locale: {
          format: 'DD-MM-YYYY', separator: ' s.d ',
          applyLabel: 'Terapkan', cancelLabel: 'Batal',
          daysOfWeek: ['Min','Sen','Sel','Rab','Kam','Jum','Sab'],
          monthNames: ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'],
          firstDay: 1
        },
        autoUpdateInput: false,
        opens: 'left'
      });

      if (start && end) {
        $('#daterange').val('Periode Data ' + moment(start, 'YYYY-MM-DD').format('DD-MM-YYYY') +
          ' s.d ' + moment(end, 'YYYY-MM-DD').format('DD-MM-YYYY'));
      }

      $('#daterange').on('apply.daterangepicker', function (ev, picker) {
        $(this).val('Periode Data ' + picker.startDate.format('DD-MM-YYYY') + ' s.d ' + picker.endDate.format('DD-MM-YYYY'));
        $('#start_date').val(picker.startDate.format('YYYY-MM-DD'));
        $('#end_date').val(picker.endDate.format('YYYY-MM-DD'));
      });

      $('#daterange').on('cancel.daterangepicker', function () {
        $(this).val('');
        $('#start_date').val('');
        $('#end_date').val('');
      });
    })();

    (function () {
      const ctx = document.getElementById('buyersDonut')?.getContext('2d');
      if (!ctx) return;

      const labels = @json($chart['labels']);
      const data   = @json($chart['values']);

      new Chart(ctx, {
        type: 'doughnut',
        data: {
          labels,
          datasets: [{
            data,
            borderWidth: 0,
            backgroundColor: ['rgba(99,102,241,0.9)','rgba(255,107,107,0.9)'],
            hoverBackgroundColor: ['rgba(99,102,241,1)','rgba(255,107,107,1)']
          }]
        },
        options: {
          responsive: true,
          plugins: {
            legend: { position: 'bottom' },
            tooltip: {
              callbacks: {
                label: function (ctx) {
                  const total = ctx.dataset.data.reduce((a, b) => a + b, 0) || 1;
                  const val   = ctx.raw;
                  const pct   = (val / total * 100).toFixed(1);
                  return `${ctx.label}: ${val} (${pct}%)`;
                }
              }
            }
          },
          cutout: '60%'
        }
      });
    })();
  </script>
@endpush