@extends('layouts.app')

@section('content')
<div class="bg-white p-3 p-md-4 rounded shadow-sm w-100">

  <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
    <div>
      <h5 class="mb-1 fw-semibold">Riwayat Transaksi Iklan</h5>
    
      <div class="d-flex flex-wrap align-items-center gap-2 text-muted small">
        <span>
          {{ number_format($iklan->total(), 0, ',', '.') }} transaksi
        </span>
    
        <span class="text-secondary">|</span>
    
        <span>
          Total pembayaran:
          <span class="fw-semibold text-dark">
            Rp{{ number_format($totalPembayaran, 0, ',', '.') }}
          </span>
        </span>
      </div>
    </div>

    <button class="btn btn-success btn-sm"
            data-bs-toggle="modal"
            data-bs-target="#tambahTransaksiModal">
      <i class="bi bi-plus-circle"></i> Tambah Transaksi
    </button>
  </div>

  @if(session('success'))
    <div class="alert alert-success py-2" id="alertSuccess">
      {{ session('success') }}
    </div>
  @endif

  <form method="GET" action="{{ route('iklan.index') }}" class="card border-0 shadow-sm mb-3">
    <div class="card-body">
      <div class="row g-3 align-items-end">

        <div class="col-12 col-md-3">
          <label class="form-label small text-muted">Cari</label>
          <input type="text" name="search" class="form-control form-control-sm"
                 value="{{ request('search') }}"
                 placeholder="No iklan, toko, marketplace, metode">
        </div>

        <div class="col-12 col-md-2">
          <label class="form-label small text-muted">Marketplace</label>
          <select name="marketplace" id="filterMarketplace" class="form-select form-select-sm">
            <option value="">Semua marketplace</option>
          </select>
        </div>

        <div class="col-12 col-md-3">
          <label class="form-label small text-muted">Nama Toko</label>
          <select name="id_toko" id="filterToko" class="form-select form-select-sm">
            <option value="">Semua toko</option>
          </select>
        </div>

        <div class="col-6 col-md-2">
          <label class="form-label small text-muted">Tanggal Dari</label>
          <input type="date" name="tanggal_dari" class="form-control form-control-sm"
                 value="{{ request('tanggal_dari') }}">
        </div>

        <div class="col-6 col-md-2">
          <label class="form-label small text-muted">Tanggal Sampai</label>
          <input type="date" name="tanggal_sampai" class="form-control form-control-sm"
                 value="{{ request('tanggal_sampai') }}">
        </div>

        <div class="col-12 d-flex justify-content-end gap-2">
          <a href="{{ route('iklan.index') }}" class="btn btn-outline-secondary btn-sm">
            Reset
          </a>
          <button type="submit" class="btn btn-primary btn-sm">
            Filter
          </button>
        </div>

      </div>
    </div>
  </form>

  <div class="table-responsive">
    <table class="table table-sm table-hover align-middle">
      <thead class="table-light">
        <tr>
          <th class="text-center">No. Iklan</th>
          <th class="text-center">Tanggal</th>
          <th>Nama Toko</th>
          <th class="text-center">Marketplace</th>
          <th class="text-end">Jumlah Pembayaran</th>
          <th class="text-end">Saldo</th>
          <th class="text-center">Metode</th>
          <th class="text-center">Aksi</th>
        </tr>
      </thead>

      <tbody>
        @forelse ($iklan as $data)
          @php
            $method = $data->metode_pembayaran;
            $methodBadge = match (strtolower($method)) {
              'saldo penjualan' => 'info',
              'transfer bank', 'trasfer bank' => 'primary',
              default => 'secondary'
            };
          @endphp

          <tr>
            <td class="text-center fw-semibold">{{ $data->no_iklan }}</td>
            <td class="text-center">
              {{ \Carbon\Carbon::parse($data->tanggal)->format('d/m/Y') }}
            </td>
            <td>{{ $data->toko->nama_toko ?? '-' }}</td>
            <td class="text-center">{{ $data->toko->marketplace ?? '-' }}</td>
            <td class="text-end">
              Rp{{ number_format($data->jumlah_pembayaran, 0, ',', '.') }}
            </td>
            <td class="text-end">
              Rp{{ number_format($data->saldo, 0, ',', '.') }}
            </td>
            <td class="text-center">
              <span class="badge rounded-pill bg-{{ $methodBadge }}">
                {{ $method }}
              </span>
            </td>
            <td class="text-center">
              <div class="d-inline-flex gap-1">
                <button class="btn btn-outline-primary btn-sm"
                        data-bs-toggle="modal"
                        data-bs-target="#editTransaksiModal{{ $data->no_iklan }}">
                  Edit
                </button>

                <form action="{{ route('iklan.destroy', $data->no_iklan) }}"
                      method="POST"
                      onsubmit="return confirm('Yakin ingin menghapus transaksi ini?')">
                  @csrf
                  @method('DELETE')
                  <button class="btn btn-outline-danger btn-sm">
                    Hapus
                  </button>
                </form>
              </div>
            </td>
          </tr>

          <div class="modal fade" id="editTransaksiModal{{ $data->no_iklan }}" tabindex="-1">
            <div class="modal-dialog">
              <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white">
                  <h5 class="modal-title">Edit Transaksi</h5>
                  <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <form action="{{ route('iklan.update', $data->no_iklan) }}" method="POST">
                  @csrf
                  @method('PUT')

                  <div class="modal-body">
                    <div class="mb-3">
                      <label class="form-label">No. Iklan</label>
                      <input type="text" class="form-control" value="{{ $data->no_iklan }}" readonly>
                    </div>

                    <div class="mb-3">
                      <label class="form-label">Tanggal</label>
                      <input type="date" name="tanggal" class="form-control"
                             value="{{ $data->tanggal }}" required>
                    </div>

                    <div class="mb-3">
                      <label class="form-label">Nama Toko</label>
                      <select name="id_toko" class="form-select" required>
                        <option value="">Pilih Toko</option>
                        <option value="{{ $data->id_toko }}" selected>
                          {{ $data->toko->nama_toko ?? '-' }} [{{ $data->toko->marketplace ?? '-' }}]
                        </option>
                      </select>
                    </div>

                    <div class="row g-2">
                      <div class="col-12 col-md-6">
                        <label class="form-label">Jumlah Pembayaran</label>
                        <input type="number" name="jumlah_pembayaran"
                               class="form-control no-dot"
                               value="{{ $data->jumlah_pembayaran }}" required>
                      </div>

                      <div class="col-12 col-md-6">
                        <label class="form-label">Saldo</label>
                        <input type="number" name="saldo"
                               class="form-control no-dot"
                               value="{{ $data->saldo }}" required>
                      </div>
                    </div>

                    <div class="mt-3">
                      <label class="form-label">Metode Pembayaran</label>
                      <select name="metode_pembayaran" class="form-select" required>
                        <option value="">Pilih Metode</option>
                        @foreach (['Saldo Penjualan', 'Transfer Bank'] as $metode)
                          <option value="{{ $metode }}"
                            {{ $data->metode_pembayaran === $metode ? 'selected' : '' }}>
                            {{ $metode }}
                          </option>
                        @endforeach
                      </select>
                    </div>
                  </div>

                  <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                      Batal
                    </button>
                    <button type="submit" class="btn btn-primary">
                      Perbarui
                    </button>
                  </div>
                </form>
              </div>
            </div>
          </div>
        @empty
          <tr>
            <td colspan="8" class="text-center text-muted py-4">
              Data iklan tidak ditemukan.
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div class="mt-3">
    {{ $iklan->links() }}
  </div>
</div>

<div class="modal fade" id="tambahTransaksiModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title">Tambah Transaksi</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <form action="{{ route('iklan.store') }}" method="POST">
        @csrf

        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">No. Iklan / Pesanan</label>
            <input type="text" name="no_iklan" class="form-control" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Tanggal</label>
            <input type="date" name="tanggal" class="form-control" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Marketplace</label>
            <select id="modalMarketplace" class="form-select" required>
              <option value="">Pilih Marketplace</option>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label">Nama Toko</label>
            <select name="id_toko" id="modalToko" class="form-select" required>
              <option value="">Pilih Toko</option>
            </select>
          </div>

          <div class="row g-2">
            <div class="col-12 col-md-6">
              <label class="form-label">Jumlah Pembayaran</label>
              <input type="number" name="jumlah_pembayaran" class="form-control no-dot" required>
            </div>

            <div class="col-12 col-md-6">
              <label class="form-label">Saldo</label>
              <input type="number" name="saldo" class="form-control no-dot" required>
            </div>
          </div>

          <div class="mt-3">
            <label class="form-label">Metode Pembayaran</label>
            <select name="metode_pembayaran" class="form-select" required>
              <option value="">Pilih Metode</option>
              <option value="Saldo Penjualan">Saldo Penjualan</option>
              <option value="Transfer Bank">Transfer Bank</option>
            </select>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
            Batal
          </button>
          <button type="submit" class="btn btn-success">
            Simpan
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@push('styles')
<style>
  table thead th {
    font-size: .85rem;
    white-space: nowrap;
  }

  table tbody td {
    vertical-align: middle;
  }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const selectedMarketplace = @json(request('marketplace'));
    const selectedToko = @json(request('id_toko'));

    const filterMarketplace = document.getElementById('filterMarketplace');
    const filterToko = document.getElementById('filterToko');

    const modalMarketplace = document.getElementById('modalMarketplace');
    const modalToko = document.getElementById('modalToko');

    loadMarketplace(filterMarketplace, selectedMarketplace, function () {
        loadToko(filterToko, selectedMarketplace, selectedToko, true);
    });

    loadMarketplace(modalMarketplace);

    filterMarketplace.addEventListener('change', function () {
        loadToko(filterToko, this.value, null, true);
    });

    modalMarketplace.addEventListener('change', function () {
        loadToko(modalToko, this.value, null, false);
    });

    function loadMarketplace(selectElement, selected = null, callback = null) {
        fetch("{{ route('ajax.marketplace') }}")
            .then(response => response.json())
            .then(data => {
                selectElement.innerHTML = selectElement.id === 'filterMarketplace'
                    ? '<option value="">Semua marketplace</option>'
                    : '<option value="">Pilih Marketplace</option>';

                data.forEach(mp => {
                    let option = document.createElement('option');
                    option.value = mp;
                    option.textContent = mp;

                    if (selected && selected === mp) {
                        option.selected = true;
                    }

                    selectElement.appendChild(option);
                });

                if (callback) callback();
            });
    }

    function loadToko(selectElement, marketplace = null, selected = null, isFilter = false) {
        let url = "{{ route('ajax.toko.marketplace') }}";

        if (marketplace) {
            url += '?marketplace=' + encodeURIComponent(marketplace);
        }

        fetch(url)
            .then(response => response.json())
            .then(data => {
                selectElement.innerHTML = isFilter
                    ? '<option value="">Semua toko</option>'
                    : '<option value="">Pilih Toko</option>';

                data.forEach(toko => {
                    let option = document.createElement('option');
                    option.value = toko.id_toko;
                    option.textContent = toko.nama_toko;

                    if (selected && selected == toko.id_toko) {
                        option.selected = true;
                    }

                    selectElement.appendChild(option);
                });
            });
    }

    setTimeout(() => {
        document.getElementById('alertSuccess')?.remove();
    }, 3000);

    document.querySelectorAll('.no-dot').forEach(function(input) {
        input.addEventListener('input', function() {
            this.value = this.value.replace(/\./g, '');
        });
    });
});
</script>
@endpush