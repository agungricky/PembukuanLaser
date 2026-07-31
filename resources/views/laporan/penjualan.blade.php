@extends('layouts.app')

@push('styles')
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" rel="stylesheet" />
@endpush

@section('content')
  <div class="container-lg py-3">

    {{-- ===== FILTER + TITLE ===== --}}
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-header bg-white d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
          <i class="bi bi-graph-up-arrow text-primary fs-4"></i>
          <h1 class="h5 mb-0">Penjualan</h1>
        </div>

        <a href="{{ request()->fullUrlWithQuery(['download' => 'xlsx']) }}" class="btn btn-outline-secondary">
          <i class="bi bi-download me-1"></i> Download
        </a>
      </div>

      <div class="card-body">
        <form method="GET" action="{{ route('penjualan') }}" id="filterForm">
          <div class="row g-3 align-items-end">

            {{-- Toko --}}
            <div class="col-12 col-md-4">
              <label for="id_toko" class="form-label mb-1">Toko</label>
              <select class="form-select" name="id_toko" id="id_toko" data-placeholder="Pilih Toko">
                <option value=""
                  @if(($rawToko ?? '') === '' || !isset($rawToko)) selected @endif>
                  Semua Toko
                </option>

                <option value="all_shopee"
                  @if(($rawToko ?? '') === 'all_shopee') selected @endif>
                  Semua Toko Shopee
                </option>

                <option value="all_tiktok"
                  @if(($rawToko ?? '') === 'all_tiktok') selected @endif>
                  Semua Toko TikTok
                </option>

                @foreach($daftarToko as $t)
                  <option value="{{ $t->id_toko }}"
                    {{ (string) $t->id_toko === (string) $idToko ? 'selected' : '' }}>
                    {{ $t->nama_toko }} — {{ strtoupper($t->marketplace) }}
                  </option>
                @endforeach
              </select>
            </div>

            {{-- Periode --}}
            <div class="col-12 col-md-5">
              <label class="form-label mb-1" for="daterange">Periode</label>
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-calendar-event"></i></span>
                <input type="text" id="daterange" class="form-control" placeholder="Pilih Periode" autocomplete="off" readonly>
              </div>
              <input type="hidden" name="start_date" id="start_date" value="{{ e($startDate) }}">
              <input type="hidden" name="end_date" id="end_date" value="{{ e($endDate) }}">
            </div>

            {{-- Aksi --}}
            <div class="col-12 col-md-3 text-md-end">
              <div class="d-grid d-md-inline-flex gap-2">
                <button type="submit" class="btn btn-primary">Terapkan</button>
                <a href="{{ route('penjualan') }}" class="btn btn-outline-secondary">Reset</a>
              </div>
            </div>

          </div>
        </form>
      </div>
    </div>

    {{-- ===== KPI ===== --}}
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-body">
        <div class="row g-3">
          @foreach ($metrics as $m)
            @php
              $type     = $m['type'] ?? 'number';
              $decimals = $m['decimals'] ?? ($type === 'percent' ? 2 : 0);

              $val = $m['value'] ?? 0;

              $formatted = $type === 'currency'
                ? 'Rp' . number_format((float) $val, 0, ',', '.')
                : number_format((float) $val, $decimals, ',', '.');

              $chg    = $m['change'] ?? null;
              $isUp   = is_numeric($chg) && $chg > 0;
              $isDown = is_numeric($chg) && $chg < 0;

              $good = $m['good'] ?? 'up';

              if (is_null($chg)) {
                $badgeClass = 'text-bg-light';
                $badgeText  = '—';
              } elseif ($chg == 0) {
                $badgeClass = 'text-bg-secondary';
                $badgeText  = '0,00%';
              } else {
                $isPositiveTrend = ($good === 'down') ? $isDown : $isUp;
                $badgeClass = $isPositiveTrend ? 'text-bg-success' : 'text-bg-danger';
                $arrow      = $isUp ? '▲ ' : '▼ ';
                $badgeText  = $arrow . number_format(abs((float) $chg), 2, ',', '.') . '%';
              }
            @endphp

            <div class="col-12 col-sm-6 col-xl-3">
              <div class="border rounded-3 p-3 h-100">
                <div class="d-flex justify-content-between align-items-start">
                  <span class="text-muted fw-semibold small">{{ $m['label'] }}</span>
                  <span class="badge {{ $badgeClass }} fw-semibold">{{ $badgeText }}</span>
                </div>
                <div class="fs-4 fw-bold mt-1">{{ $formatted }}</div>
              </div>
            </div>
          @endforeach
        </div>
      </div>
    </div>
    
    {{-- ===== RINGKASAN STATUS ===== --}}
    <div class="card border-0 shadow-sm mt-4">
    
        <div class="card-header bg-white fw-semibold">
    
            Ringkasan Status Pesanan
    
        </div>
    
        <div class="card-body p-0">
    
            <div class="table-responsive">
    
                <table class="table table-hover table-bordered mb-0 align-middle">
    
                    <thead class="table-light">
    
                        <tr>
    
                            <th>Status</th>
    
                            <th class="text-end">Jumlah</th>
    
                            <th class="text-end">% Pesanan</th>
    
                            <th class="text-end">Penjualan</th>
    
                            <th class="text-end">Admin</th>
    
                            <th class="text-end">Pencairan</th>
    
                            <th class="text-end">HPP</th>
    
                            <th class="text-end">Selisih</th>
    
                        </tr>
    
                    </thead>
    
                    <tbody>
    
                        @foreach($statusSummary as $row)
    
                        <tr>
    
                            <td>{{ ucfirst($row->status) }}</td>
    
                            <td class="text-end">
    
                                {{ number_format($row->jumlah_pesanan) }}
    
                            </td>
    
                            <td class="text-end">
    
                                {{ number_format($row->persentase,2) }}%
    
                            </td>
    
                            <td class="text-end">
    
                                Rp{{ number_format($row->penjualan,0,',','.') }}
    
                            </td>
    
                            <td class="text-end">
    
                                Rp{{ number_format($row->admin,0,',','.') }}
    
                            </td>
    
                            <td class="text-end">
    
                                Rp{{ number_format($row->pencairan,0,',','.') }}
    
                            </td>
    
                            <td class="text-end">
    
                                Rp{{ number_format($row->hpp,0,',','.') }}
    
                            </td>
    
                            <td class="text-end fw-bold">
    
                                Rp{{ number_format($row->selisih,0,',','.') }}
    
                            </td>
    
                        </tr>
    
                        @endforeach
    
                    </tbody>
    
                    <tfoot class="table-secondary fw-bold">
    
                        <tr>
    
                            <td>TOTAL</td>
    
                            <td class="text-end">
    
                                {{ number_format($totalStatus['jumlah_pesanan']) }}
    
                            </td>
    
                            <td class="text-end">
    
                                100%
    
                            </td>
    
                            <td class="text-end">
    
                                Rp{{ number_format($totalStatus['penjualan'],0,',','.') }}
    
                            </td>
    
                            <td class="text-end">
    
                                Rp{{ number_format($totalStatus['admin'],0,',','.') }}
    
                            </td>
    
                            <td class="text-end">
    
                                Rp{{ number_format($totalStatus['pencairan'],0,',','.') }}
    
                            </td>
    
                            <td class="text-end">
    
                                Rp{{ number_format($totalStatus['hpp'],0,',','.') }}
    
                            </td>
    
                            <td class="text-end">
    
                                Rp{{ number_format($totalStatus['selisih'],0,',','.') }}
    
                            </td>
    
                        </tr>
    
                    </tfoot>
    
                </table>
    
            </div>
    
        </div>
    
    </div>

    {{-- ===== CHART (Tren) ===== --}}
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white fw-semibold">Tren Penjualan & Pesanan</div>
      <div class="card-body">
        <div style="height:360px">
          <canvas id="salesOrdersChart" aria-label="Grafik tren penjualan dan pesanan"></canvas>
        </div>
      </div>
    </div>

    {{-- ===== STATUS PIE ===== --}}
    <div class="card border-0 shadow-sm mt-4">
      <div class="card-header bg-white fw-semibold">Distribusi Status Pesanan</div>
      <div class="card-body">
        <div style="max-width:420px; margin:auto;">
          <canvas id="statusPieChart" aria-label="Pie status pesanan"></canvas>
        </div>
      </div>
    </div>

  </div>
@endsection

@push('scripts')
  {{-- deps --}}
  <script src="https://cdn.jsdelivr.net/npm/moment@2.29.4/min/moment.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

  <script>
    // ===== Select2 Toko =====
    $(function () {
      $('#id_toko').select2({
        theme: 'bootstrap-5',
        width: '100%',
        placeholder: $('#id_toko').data('placeholder'),
        allowClear: true
      });
    });

    // ===== Date Range Picker =====
    (function () {
      const start = @json($startDate);
      const end   = @json($endDate);

      $('#daterange').daterangepicker({
        startDate: moment(start, 'YYYY-MM-DD'),
        endDate:   moment(end, 'YYYY-MM-DD'),
        locale: {
          format: 'DD-MM-YYYY',
          separator: ' s.d ',
          applyLabel: 'Terapkan',
          cancelLabel: 'Batal',
          daysOfWeek: ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'],
          monthNames: ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
          firstDay: 1
        },
        autoUpdateInput: false,
        opens: 'left'
      });

      if (start && end) {
        $('#daterange').val(
          moment(start, 'YYYY-MM-DD').format('DD-MM-YYYY') + ' s.d ' +
          moment(end,   'YYYY-MM-DD').format('DD-MM-YYYY')
        );
      }

      $('#daterange').on('apply.daterangepicker', function (ev, picker) {
        $(this).val(
          picker.startDate.format('DD-MM-YYYY') + ' s.d ' +
          picker.endDate.format('DD-MM-YYYY')
        );
        $('#start_date').val(picker.startDate.format('YYYY-MM-DD'));
        $('#end_date').val(picker.endDate.format('YYYY-MM-DD'));
      });

      // Library menyediakan event cancel; kamu manfaatkan untuk clear + submit (custom behavior). [web:56]
      $('#daterange').on('cancel.daterangepicker', function () {
        $(this).val('');
        $('#start_date').val('');
        $('#end_date').val('');
        $('#filterForm').trigger('submit');
      });
    })();

    // ===== Chart Tren (mixed + multi axis) =====
    (function () {
      const labels  = @json($chart['labels']);
      const sales   = @json($chart['sales']);
      const orders  = @json($chart['orders']);
      const adspend = @json($chart['adspend']);

      const canvas = document.getElementById('salesOrdersChart');
      if (!canvas) return;

      const ctx = canvas.getContext('2d');

      const gradient = (() => {
        const g = ctx.createLinearGradient(0, 0, 0, 300);
        g.addColorStop(0, 'rgba(99,102,241,0.35)');
        g.addColorStop(1, 'rgba(99,102,241,0.05)');
        return g;
      })();

      const fmtIDR = (v) => new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0
      }).format(v);

      const fmtNum = (v) => new Intl.NumberFormat('id-ID').format(v);

      // Mixed chart dataset-per-dataset adalah pola Chart.js [web:17]
      // Multiple axes via yAxisID + options.scales [web:16]
      new Chart(ctx, {
        data: {
          labels,
          datasets: [
            {
              type: 'line',
              label: 'Penjualan',
              data: sales,
              borderColor: 'rgba(99,102,241,1)',
              backgroundColor: gradient,
              borderWidth: 2,
              pointRadius: 0,
              tension: 0.35,
              yAxisID: 'yMoney',
              fill: true
            },
            {
              type: 'bar',
              label: 'Pesanan',
              data: orders,
              borderWidth: 0,
              backgroundColor: 'rgba(15,23,42,0.12)',
              yAxisID: 'yCount',
              borderRadius: 6,
              barPercentage: 0.7,
              categoryPercentage: 0.7
            },
            {
              type: 'line',
              label: 'Biaya Iklan',
              data: adspend,
              borderColor: 'rgba(239,68,68,0.9)',
              borderDash: [6, 6],
              pointRadius: 0,
              borderWidth: 2,
              yAxisID: 'yMoney',
              hidden: false
            }
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          interaction: { mode: 'index', intersect: false },
          plugins: {
            legend: { display: true },
            tooltip: {
              callbacks: {
                label: function (ctx) {
                  const label = ctx.dataset.label || '';
                  const v = ctx.parsed.y;
                  return (ctx.dataset.yAxisID === 'yMoney')
                    ? `${label}: ${fmtIDR(v)}`
                    : `${label}: ${fmtNum(v)}`;
                }
              }
            }
          },
          scales: {
            x: { grid: { display: false } },
            yMoney: {
              position: 'left',
              ticks: { callback: (v) => fmtIDR(v) },
              grid: { color: 'rgba(0,0,0,0.05)' }
            },
            yCount: {
              position: 'right',
              ticks: { callback: (v) => fmtNum(v) },
              grid: { display: false }
            }
          }
        }
      });
    })();

    // ===== Status Pie Chart =====
    (function () {
      const statusLabels = @json($chart['status_labels']);
      const statusValues = @json($chart['status_values']);

      const canvas = document.getElementById('statusPieChart');
      if (!canvas) return;

      const ctxPie = canvas.getContext('2d');

      const colors = [
      'rgba(59,130,246,0.9)',   // Diproses
      'rgba(34,197,94,0.9)',    // Dikirim
      'rgba(99,102,241,0.9)',   // Selesai
      'rgba(245,158,11,0.95)',  // Affiliate - orange
      'rgba(248,113,113,0.9)',  // Pengiriman Gagal
      'rgba(239,68,68,0.9)',    // Batal
      'rgba(107,114,128,0.9)'   // Pengembalian
    ];

      new Chart(ctxPie, {
        type: 'pie',
        data: {
          labels: statusLabels,
          datasets: [{
            data: statusValues,
            backgroundColor: colors,
            borderWidth: 1
          }]
        },
        options: {
          responsive: true,
          plugins: {
            legend: { position: 'bottom' },
            tooltip: {
              callbacks: {
                label: function (ctx) {
                  const label = ctx.label || '';
                  const value = ctx.parsed || 0;
                  const dataArr = ctx.dataset.data || [];
                  const total = dataArr.reduce((a, b) => a + b, 0);
                  const pct = total > 0 ? ((value / total) * 100).toFixed(1) + '%' : '0%';
                  return `${label}: ${value} (${pct})`;
                }
              }
            }
          }
        }
      });
    })();
  </script>
@endpush