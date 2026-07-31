@extends('layouts.app')

@section('content')
<div class="bg-white p-4 rounded shadow-sm w-100">

  <div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 d-flex align-items-center gap-2">
      <i class="bi bi-upload text-success"></i>
      Import Data Pesanan
    </h5>

    <a href="{{ route('pesanan.index') }}" class="btn btn-outline-secondary btn-sm">
      Kembali
    </a>
  </div>

  <form action="{{ route('pesanan.preview') }}" method="POST" enctype="multipart/form-data" id="formImportPesanan">
    @csrf

    <div class="row g-3">
      <div class="col-md-4">
        <label class="form-label">Marketplace</label>
        <select name="marketplace" id="marketplace" class="form-select" required>
          <option value="">Pilih Marketplace</option>
          <option value="Shopee">Shopee</option>
          <option value="TikTok">TikTok</option>
        </select>
      </div>

      <div class="col-md-4">
        <label class="form-label">Tanggal Import</label>
        <input type="date" class="form-control" name="tanggal_import" id="tanggalImport"
          value="{{ now()->format('Y-m-d') }}" required>
      </div>

      <div class="col-md-4">
        <label class="form-label">Nama Toko</label>
        <select class="form-select" id="namaToko" name="id_toko" required>
          <option value="">Pilih Marketplace dulu</option>
        </select>
      </div>

      <div class="col-md-6">
        <label class="form-label">Nama Pengguna</label>
        <input type="text" class="form-control" id="namaUser" name="nama_user"
          value="{{ auth()->user()->name }}" readonly style="background-color:#f5f5f5;">
      </div>

      <div class="col-md-6">
        <label class="form-label">Upload File Excel</label>
        <input class="form-control" type="file" name="file" id="fileUpload" accept=".xlsx,.xls" required>
      </div>
    </div>

    <div class="mt-3">
      <button type="submit" class="btn btn-success" id="btnPreview">
        <i class="bi bi-eye me-1"></i> Preview Data
      </button>
    </div>
  </form>

  <hr>

  <div id="previewArea" style="display:none;">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h5 class="mb-0">
        Tinjau & Edit Data Pesanan
      </h5>

      <button type="button" class="btn btn-primary" id="btnSimpanSemua">
        <i class="bi bi-save me-1"></i> Simpan Semua
      </button>
    </div>

    <div class="table-responsive">
      <table class="table table-bordered align-middle">
        <thead class="table-light text-center">
          <tr>
            <th>No.</th>
            <th>No. Pesanan</th>
            <th>Nama Produk</th>
            <th>Variasi</th>
            <th>Jumlah</th>
            <th>HPP</th>
            <th>Harga</th>
          </tr>
        </thead>
        <tbody id="previewTbody"></tbody>
      </table>
    </div>
  </div>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>

<script>
(function ($) {
  $(function () {

    $('#marketplace').on('change', function () {
      let market = $(this).val();
      let select = $('#namaToko');

      select.empty().append(`<option value="">Memuat toko...</option>`);

      if (!market) {
        select.empty().append(`<option value="">Pilih Marketplace dulu</option>`);
        return;
      }

      $.get("{{ route('pesanan.getToko', ':market') }}".replace(':market', market), function (res) {
        select.empty().append(`<option value="">Pilih Toko</option>`);

        if (!res || res.length === 0) {
          select.append(`<option value="">Tidak ada toko</option>`);
          return;
        }

        res.forEach(function (t) {
          select.append(`<option value="${t.id_toko}">${t.nama_toko}</option>`);
        });
      }).fail(function () {
        select.empty().append(`<option value="">Gagal memuat toko</option>`);
      });
    });

    function tampilkanPreview() {
      $.get("{{ route('pesanan.getPreview') }}", function (response) {
        if (response.status === 'success') {
          let tbody = $('#previewTbody');
          tbody.empty();

          (response.data || []).forEach(function (item, index) {
            (item.produk_detail || []).forEach(function (produk, i) {
              let hargaNum = 0;

              if (produk['Harga'] != null) {
                hargaNum = String(produk['Harga']).replace(/[^\d]/g, '');
              }

              let skuVal = (
                (produk['sku'] != null ? String(produk['sku']) : '') ||
                (produk['__sku'] != null ? String(produk['__sku']) : '') ||
                (produk['SKU Induk'] != null ? String(produk['SKU Induk']) : '') ||
                (produk['SKU'] != null ? String(produk['SKU']) : '')
              ).trim();

              let hppNum = (
                produk['HPP'] !== undefined &&
                produk['HPP'] !== null &&
                String(produk['HPP']).trim() !== ''
              ) ? Number(produk['HPP']) : 0;

              let baris = `
                <tr>
                  <td class="text-center">${index + 1}.${i + 1}</td>
                  <td><input type="text" class="form-control form-control-sm" value="${item['no_pesanan'] || ''}" readonly></td>

                  <td hidden><input type="hidden" value="${item['no_resi'] || ''}"></td>
                  <td hidden><input type="hidden" value="${item['kurir'] || ''}"></td>
                  <td hidden><input type="hidden" value="${item['nama_pembeli'] || ''}"></td>
                  <td hidden><input type="hidden" value="${item['username'] || ''}"></td>
                  <td hidden><input type="hidden" value="${skuVal}"></td>

                  <td><input type="text" class="form-control form-control-sm" value="${produk['Nama Produk'] || ''}" readonly></td>
                  <td><input type="text" class="form-control form-control-sm" value="${produk['Nama Variasi'] || ''}" readonly></td>
                  <td><input type="number" class="form-control form-control-sm" value="${produk['Jumlah'] || 1}" readonly></td>
                  <td><input type="number" step="0.01" class="form-control form-control-sm" value="${hppNum}"></td>
                  <td><input type="number" class="form-control form-control-sm" value="${hargaNum || 0}" readonly></td>
                </tr>
              `;

              tbody.append(baris);
            });
          });

          $('#previewArea').show();
        }
      });
    }

    $('#formImportPesanan').on('submit', function (e) {
      e.preventDefault();

      const form = $(this);
      const btn = $('#btnPreview');
      const prevHtml = btn.html();

      if (!$('#marketplace').val()) {
        alert('Pilih marketplace dulu.');
        return;
      }

      if (!$('#namaToko').val()) {
        alert('Pilih toko dulu.');
        return;
      }

      if (!$('#fileUpload')[0].files.length) {
        alert('Pilih file Excel dulu.');
        return;
      }

      btn.prop('disabled', true)
        .html('<span class="spinner-border spinner-border-sm me-2"></span>Mengunggah...');

      $('#previewArea').hide();
      $('#previewTbody').empty();

      $.ajax({
        url: form.attr('action'),
        method: 'POST',
        data: new FormData(this),
        contentType: false,
        processData: false,
        success: function () {
          tampilkanPreview();
        },
        error: function (xhr) {
          let msg = 'Gagal mengunggah file.';

          if (xhr.responseJSON?.message) {
            msg = xhr.responseJSON.message;
          }

          alert(msg);
        },
        complete: function () {
          btn.prop('disabled', false).html(prevHtml);
        }
      });
    });

    $('#btnSimpanSemua').on('click', function () {
      let pesanan = [];

      let currentNo = '';
      let currentResi = '';
      let currentKurir = '';
      let currentPembeli = '';
      let currentUsername = '';
      let currentProdukList = [];

      $('#previewTbody tr').each(function () {
        let td = $(this).find('td');

        let noPesanan = td.eq(1).find('input').val();
        let noResi = td.eq(2).find('input').val();
        let kurir = td.eq(3).find('input').val();
        let pembeli = td.eq(4).find('input').val();
        let username = td.eq(5).find('input').val();
        let sku = td.eq(6).find('input').val();
        let namaProduk = td.eq(7).find('input').val();
        let variasi = td.eq(8).find('input').val();
        let jumlah = parseInt(td.eq(9).find('input').val(), 10) || 0;
        let hpp = parseFloat(td.eq(10).find('input').val()) || 0;
        let harga = parseInt(td.eq(11).find('input').val(), 10) || 0;

        if (noPesanan !== currentNo) {
          if (currentNo !== '') {
            pesanan.push({
              no_pesanan: currentNo,
              no_resi: currentResi,
              kurir: currentKurir,
              nama_pembeli: currentPembeli,
              username: currentUsername,
              produk: currentProdukList
            });
          }

          currentNo = noPesanan;
          currentResi = noResi;
          currentKurir = kurir;
          currentPembeli = pembeli;
          currentUsername = username;
          currentProdukList = [];
        }

        currentProdukList.push({
          nama_produk: namaProduk,
          variasi: variasi,
          jumlah: jumlah,
          hpp: hpp,
          harga: harga,
          sku: sku
        });
      });

      if (currentNo !== '') {
        pesanan.push({
          no_pesanan: currentNo,
          no_resi: currentResi,
          kurir: currentKurir,
          nama_pembeli: currentPembeli,
          username: currentUsername,
          produk: currentProdukList
        });
      }

      if (pesanan.length === 0) {
        alert('Tidak ada data untuk disimpan.');
        return;
      }

      $.ajax({
        url: "{{ route('pesanan.simpanImport') }}",
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': "{{ csrf_token() }}"
        },
        data: JSON.stringify({
          pesanan: pesanan,
          tanggal_import: $('#tanggalImport').val(),
          id_toko: $('#namaToko').val(),
          nama_user: $('#namaUser').val()
        }),
        contentType: 'application/json; charset=utf-8',
        processData: false,
        success: function (res) {
          if (res.status === 'success') {
            alert('Data berhasil disimpan!');
            window.location.href = "{{ route('pesanan.index') }}";
          } else {
            alert('Gagal menyimpan data: ' + (res.message || ''));
          }
        },
        error: function (xhr) {
          let msg = 'Terjadi kesalahan saat menyimpan.';

          if (xhr.responseJSON?.message) {
            msg = xhr.responseJSON.message;
          }

          alert(msg);
        }
      });
    });

  });
})(jQuery);
</script>
@endpush