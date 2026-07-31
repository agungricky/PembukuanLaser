@extends('layouts.app')

@section('content')

  @push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
    <style>
    .page-title {
    font-size: 1.75rem;
    font-weight: 700
    }

    .summary-card {
    background: #fff;
    border: 1px solid #edf1f5;
    border-radius: 14px;
    box-shadow: 0 1px 2px rgba(0, 0, 0, .04);
    text-align: center;
    transition: .2s
    }

    .summary-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 18px rgba(0, 0, 0, .08)
    }

    .summary-label {
    color: #6b7280;
    font-weight: 600
    }

    .summary-value {
    font-size: 1.6rem;
    font-weight: 800;
    letter-spacing: -.02em
    }

    .table thead th {
    white-space: nowrap
    }

    .chart-wrap {
    min-height: 360px
    }
    </style>
  @endpush

  <div class="container-fluid py-3">
    {{-- Header + filter --}}
    {{-- Header + filter --}}
    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
    <h1 class="page-title mb-0">📦 Performa Produk</h1>

    <div class="ms-auto d-flex flex-wrap align-items-center gap-2">
      <form method="GET" action="{{ route('produk') }}" id="filterForm" class="d-flex align-items-center gap-2">
      {{-- Date range --}}
      <input type="text" id="daterange" class="form-control daterange-input" placeholder="Pilih Periode"
        autocomplete="off" readonly>
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
      <a href="{{ route('produk') }}" class="btn btn-outline-secondary">Reset</a>

      {{-- === Download Excel === --}}
      <a href="{{ request()->fullUrlWithQuery(['download' => 'xlsx']) }}" class="btn btn-outline-secondary">
        Download
      </a>
      </form>
    </div>
    </div>

    {{-- Summary --}}
    <div class="row g-3 mb-4">
    <div class="col-6 col-xl-3 d-flex">
      <div class="summary-card flex-fill p-4">
      <div class="summary-label mb-1">Omzet</div>
      <div class="summary-value">Rp{{ number_format($summary['omzet'], 0, ',', '.') }}</div>
      </div>
    </div>
    <div class="col-6 col-xl-3 d-flex">
      <div class="summary-card flex-fill p-4">
      <div class="summary-label mb-1">Produk Aktif</div>
      <div class="summary-value">{{ number_format($summary['produk_aktif'], 0, ',', '.') }}</div>
      </div>
    </div>
    <div class="col-6 col-xl-3 d-flex">
      <div class="summary-card flex-fill p-4">
      <div class="summary-label mb-1">Item Terjual</div>
      <div class="summary-value">{{ number_format($summary['qty'], 0, ',', '.') }}</div>
      </div>
    </div>
    <div class="col-6 col-xl-3 d-flex">
      <div class="summary-card flex-fill p-4">
      <div class="summary-label mb-1">Rata-rata Harga</div>
      <div class="summary-value">Rp{{ number_format($summary['avg_price'], 0, ',', '.') }}</div>
      </div>
    </div>
    </div>

    {{-- Chart: Top 10 Produk berdasarkan Jumlah Terjual --}}
    <div class="card shadow-sm border-0">
    <div class="card-header bg-body fw-semibold">
      Top 10 • Produk berdasarkan Jumlah Terjual
      @if(($storeName ?? '') !== '') <small class="text-muted"> — Toko: {{ e($storeName) }}</small> @endif
    </div>
    <div class="card-body">
      <div style="height: {{ max(420, 36 * max(1, count($chart['labels'] ?? []))) }}px;">
      <canvas id="barTopQty" aria-label="Bar Top 10 Jumlah Terjual"></canvas>
      </div>
      <small class="text-muted d-block mt-2">Klik salah satu batang untuk melihat detail variasi produk.</small>
    </div>
    </div>
  </div>

  {{-- Modal detail per-produk --}}
  <div class="modal fade" id="productDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-primary text-white">
      <h5 class="modal-title" id="productDetailTitle">Detail Produk</h5>
      <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
      <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0" id="detailTable">
        <thead class="table-light">
          <tr>
          <th>Variasi</th>
          <th class="text-center" style="width:140px">Jumlah Terjual</th>
          <th class="text-end" style="width:160px">Total Penjualan</th>
          <th class="text-end" style="width:140px">Harga Satuan (maks)</th>
          </tr>
        </thead>
        <tbody></tbody>
        </table>
      </div>
      <div id="detailEmpty" class="text-center text-muted py-4 d-none">Tidak ada detail untuk produk ini.</div>
      </div>
      <div class="modal-footer">
      <small class="text-muted me-auto">
        Periode: {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} s.d
        {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}
      </small>
      <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
    </div>
  </div>
@endsection

@push('scripts')
  <script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/min/moment.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
  <script>
    // ===== Date Range Picker =====
    (function () {
    const start = @json($startDate), end = @json($endDate);
    $('#daterange').daterangepicker({
      startDate: moment(start, 'YYYY-MM-DD'),
      endDate: moment(end, 'YYYY-MM-DD'),
      locale: {
      format: 'DD-MM-YYYY', separator: ' s.d ',
      applyLabel: 'Terapkan', cancelLabel: 'Batal',
      daysOfWeek: ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'],
      monthNames: ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
      firstDay: 1
      },
      autoUpdateInput: false, opens: 'left'
    });
    if (start && end) {
      $('#daterange').val(moment(start, 'YYYY-MM-DD').format('DD-MM-YYYY') +
      ' s.d ' + moment(end, 'YYYY-MM-DD').format('DD-MM-YYYY'));
    }
    $('#daterange').on('apply.daterangepicker', function (ev, picker) {
      $(this).val('Periode Data ' + picker.startDate.format('DD-MM-YYYY') + ' s.d ' + picker.endDate.format('DD-MM-YYYY'));
      $('#start_date').val(picker.startDate.format('YYYY-MM-DD'));
      $('#end_date').val(picker.endDate.format('YYYY-MM-DD'));
    });
    $('#daterange').on('cancel.daterangepicker', function () {
      $(this).val(''); $('#start_date').val(''); $('#end_date').val(''); $('#filterForm').submit();
    });
    })();

    // ===== Chart + Modal Detail =====
    (function () {
    const chartData = @json($chart);
    const details = @json($detailsMap ?? []);
    const fmtNum = v => new Intl.NumberFormat('id-ID').format(v);
    const palette = (n) => {
      const base = ['#0EA5E9', '#22C55E', '#F59E0B', '#EF4444', '#6366F1', '#84CC16', '#E879F9', '#14B8A6', '#A855F7', '#F97316'];
      return Array.from({ length: n }, (_, i) => base[i % base.length]);
    };

    const ctx = document.getElementById('barTopQty').getContext('2d');
    const bar = new Chart(ctx, {
      type: 'bar',
      data: {
      labels: chartData.labels || [],
      datasets: [{
        label: 'Jumlah Terjual',
        data: chartData.data || [],
        backgroundColor: palette((chartData.labels || []).length),
        borderWidth: 0,
        borderRadius: 6,
      }]
      },
      options: {
      indexAxis: 'y',
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { display: false },
        tooltip: { callbacks: { label: (ctx) => ` ${fmtNum(ctx.parsed.x)}` } }
      },
      scales: {
        x: { ticks: { callback: v => fmtNum(v) }, grid: { color: 'rgba(0,0,0,0.06)' } },
        y: { grid: { display: false } }
      }
      }
    });

    function openDetailModal(productName) {
      const rows = details[productName] || [];
      const modalEl = document.getElementById('productDetailModal');
      const titleEl = document.getElementById('productDetailTitle');
      const tbody = document.querySelector('#detailTable tbody');
      const emptyEl = document.getElementById('detailEmpty');

      titleEl.textContent = `Detail Produk: ${productName}`;
      tbody.innerHTML = '';

      if (!rows.length) {
      emptyEl.classList.remove('d-none');
      } else {
      emptyEl.classList.add('d-none');
      const fmtIDR = v => new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', maximumFractionDigits: 0 }).format(v);
      rows.forEach(r => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
      <td>${r.variasi ?? '-'}</td>
      <td class="text-center">${fmtNum(r.total_terjual || 0)}</td>
      <td class="text-end">${fmtIDR(r.total_penjualan || 0)}</td>
      <td class="text-end">${fmtIDR(r.harga_satuan || 0)}</td>
      `;
        tbody.appendChild(tr);
      });
      }

      new bootstrap.Modal(modalEl).show();
    }

    document.getElementById('barTopQty').onclick = function (evt) {
      const points = bar.getElementsAtEventForMode(evt, 'nearest', { intersect: true }, true);
      if (points && points.length) {
      const idx = points[0].index;
      const product = bar.data.labels[idx];
      if (product) { openDetailModal(product); }
      }
    };
    })();
  </script>
@endpush