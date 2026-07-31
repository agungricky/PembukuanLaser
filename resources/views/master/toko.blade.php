@extends('layouts.app')

@section('content')
<div class="bg-white p-3 p-md-4 rounded shadow-sm w-100">

  <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-3">
    <div>
      <h5 class="mb-1 fw-semibold">Daftar Toko</h5>

      <div class="d-flex flex-wrap align-items-center gap-3 text-muted small">
        <span>
          <i class="bi bi-shop me-1"></i>
          {{ number_format($toko->total(), 0, ',', '.') }} toko
        </span>
      </div>
    </div>

    <button class="btn btn-success btn-sm d-flex align-items-center gap-1"
            data-bs-toggle="modal"
            data-bs-target="#tambahTokoModal">
      <i class="bi bi-plus-circle"></i>
      <span>Tambah Toko</span>
    </button>
  </div>

  @if(session('success'))
    <div class="alert alert-success d-flex align-items-center py-2" id="alertSuccess">
      <i class="bi bi-check-circle-fill me-2"></i>
      <div>{{ session('success') }}</div>
    </div>
  @endif

  @if($errors->any())
    <div class="alert alert-danger py-2">
      <ul class="mb-0">
        @foreach($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <form method="GET" action="{{ route('toko.index') }}" class="card border-0 shadow-sm mb-3">
    <div class="card-body">
      <div class="row g-2 align-items-end">
        <div class="col-12 col-md-4">
          <label class="form-label small text-muted">Cari Toko</label>
          <input type="text"
                 name="search"
                 class="form-control form-control-sm"
                 value="{{ request('search') }}"
                 placeholder="Cari nama toko, marketplace, biaya">
        </div>

        <div class="col-12 col-md-auto">
          <button type="submit" class="btn btn-primary btn-sm">
            Search
          </button>

          <a href="{{ route('toko.index') }}" class="btn btn-outline-secondary btn-sm">
            Reset
          </a>
        </div>
      </div>
    </div>
  </form>

  <div class="table-responsive">
    <table class="table table-sm table-hover align-middle">
      <thead class="table-light">
        <tr>
          <th class="text-start" style="min-width:220px">Nama Toko</th>
          <th class="text-center" style="min-width:130px">Marketplace</th>
          <th class="text-end" style="min-width:140px">Biaya Admin</th>
          <th class="text-end" style="min-width:160px">Biaya Tambahan</th>
          <th class="text-center" style="min-width:160px">Aksi</th>
        </tr>
      </thead>

      <tbody>
        @forelse($toko as $t)
          @php
            $badge = [
              'Shopee' => 'warning',
              'Tiktok' => 'dark',
            ][$t->marketplace] ?? 'secondary';
          @endphp

          <tr>
            <td class="text-start fw-semibold">{{ $t->nama_toko }}</td>

            <td class="text-center">
              <span class="badge rounded-pill bg-{{ $badge }}">
                {{ $t->marketplace }}
              </span>
            </td>

            <td class="text-end">
              {{ rtrim(rtrim(number_format($t->biaya_admin, 2, ',', '.'), '0'), ',') }}%
            </td>

            <td class="text-end">
              Rp{{ number_format($t->biaya_tambahan ?? 0, 0, ',', '.') }}
            </td>

            <td class="text-center">
              <div class="d-inline-flex gap-1">
                <button class="btn btn-outline-primary btn-sm"
                        onclick="editToko({{ $t->id_toko }})"
                        data-bs-toggle="modal"
                        data-bs-target="#editTokoModal">
                  <i class="bi bi-pencil"></i> Edit
                </button>

                <form action="{{ route('toko.destroy', $t->id_toko) }}"
                      method="POST"
                      class="d-inline"
                      onsubmit="return confirm('Yakin ingin menghapus toko ini?')">
                  @csrf
                  @method('DELETE')

                  <button type="submit" class="btn btn-outline-danger btn-sm">
                    <i class="bi bi-trash"></i> Hapus
                  </button>
                </form>
              </div>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="5" class="text-center text-muted py-4">
              Data toko tidak ditemukan.
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <div class="mt-3">
    {{ $toko->links() }}
  </div>

  <div class="small text-muted mt-2">
    *Biaya admin ditulis dalam persen, contoh: 12,5%.
  </div>
</div>

<div class="modal fade" id="tambahTokoModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title">Tambah Toko</h5>
        <button type="button"
                class="btn-close btn-close-white"
                data-bs-dismiss="modal"></button>
      </div>

      <form action="{{ route('toko.store') }}" method="POST">
        @csrf

        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Nama Toko</label>
            <input type="text"
                   name="nama_toko"
                   class="form-control"
                   value="{{ old('nama_toko') }}"
                   required>
          </div>

          <div class="mb-3">
            <label class="form-label">Marketplace</label>
            <select class="form-select" name="marketplace" required>
              <option value="">Pilih Marketplace</option>
              <option value="Shopee" {{ old('marketplace') == 'Shopee' ? 'selected' : '' }}>Shopee</option>
              <option value="Tiktok" {{ old('marketplace') == 'Tiktok' ? 'selected' : '' }}>Tiktok</option>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label">Biaya Admin (%)</label>
            <input type="number"
                   step="0.01"
                   min="0"
                   name="biaya_admin"
                   class="form-control"
                   value="{{ old('biaya_admin') }}"
                   required>
          </div>

          <div class="mb-3">
            <label class="form-label">Biaya Tambahan (Rp)</label>
            <input type="number"
                   step="1"
                   min="0"
                   name="biaya_tambahan"
                   class="form-control"
                   value="{{ old('biaya_tambahan', 0) }}">
          </div>
        </div>

        <div class="modal-footer">
          <button type="button"
                  class="btn btn-outline-secondary"
                  data-bs-dismiss="modal">
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

<div class="modal fade" id="editTokoModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title">Edit Toko</h5>
        <button type="button"
                class="btn-close btn-close-white"
                data-bs-dismiss="modal"></button>
      </div>

      <form id="formEditToko" method="POST">
        @csrf
        @method('PUT')

        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Nama Toko</label>
            <input type="text"
                   class="form-control"
                   id="editNamaToko"
                   name="nama_toko"
                   required>
          </div>

          <div class="mb-3">
            <label class="form-label">Marketplace</label>
            <select class="form-select" id="editMarketplace" name="marketplace" required>
              <option value="">Pilih Marketplace</option>
              <option value="Shopee">Shopee</option>
              <option value="Tiktok">Tiktok</option>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label">Biaya Admin (%)</label>
            <input type="number"
                   step="0.01"
                   min="0"
                   class="form-control"
                   id="editBiayaAdmin"
                   name="biaya_admin"
                   required>
          </div>

          <div class="mb-3">
            <label class="form-label">Biaya Tambahan (Rp)</label>
            <input type="number"
                   step="1"
                   min="0"
                   class="form-control"
                   id="editBiayaTambahan"
                   name="biaya_tambahan">
          </div>
        </div>

        <div class="modal-footer">
          <button type="button"
                  class="btn btn-outline-secondary"
                  data-bs-dismiss="modal">
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
@endsection

@push('styles')
<style>
  table thead th {
    font-size: .85rem;
    letter-spacing: .2px;
    white-space: nowrap;
  }

  table tbody td {
    vertical-align: middle;
  }
</style>
@endpush

@push('scripts')
<script>
  function editToko(id) {
    fetch(`/toko/${id}`)
      .then(response => response.json())
      .then(data => {
        document.getElementById('editNamaToko').value = data.nama_toko ?? '';
        document.getElementById('editMarketplace').value = data.marketplace ?? '';
        document.getElementById('editBiayaAdmin').value = data.biaya_admin ?? 0;
        document.getElementById('editBiayaTambahan').value = data.biaya_tambahan ?? 0;
        document.getElementById('formEditToko').action = `/toko/${data.id_toko}`;
      })
      .catch(() => {
        alert('Gagal mengambil data toko.');
      });
  }

  document.addEventListener('DOMContentLoaded', function () {
    setTimeout(() => {
      document.getElementById('alertSuccess')?.remove();
    }, 3000);
  });
</script>
@endpush