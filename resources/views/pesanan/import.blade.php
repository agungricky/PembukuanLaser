@extends('layouts.app')

@section('content')
    <div class="bg-white p-4 rounded shadow-sm w-100">

        {{-- HEADER --}}
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0 d-flex align-items-center gap-2">
                <i class="bi bi-upload text-success"></i>
                Import Data Pesanan
            </h5>

            <a href="{{ route('pesanan.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i>
                Kembali
            </a>
        </div>


        {{-- FORM IMPORT --}}
        <form action="{{ route('pesanan.preview') }}" method="POST" enctype="multipart/form-data" id="formImportPesanan">
            @csrf
            <div class="row g-3">

                {{-- TANGGAL --}}
                <div class="col-md-4">
                    <label class="form-label">Tanggal Import</label>
                    <input type="date" class="form-control" name="tanggal_import" id="tanggalImport"
                        value="{{ now()->format('Y-m-d') }}" required>
                </div>

                {{-- MARKETPLACE --}}
                <div class="col-md-4">
                    <label class="form-label">Marketplace</label>
                    <select name="marketplace" id="marketplace" class="form-select" required>
                        <option value="">Pilih Marketplace</option>
                        <option value="Shopee">Shopee</option>
                        <option value="TikTok">TikTok</option>
                    </select>
                </div>

                {{-- TOKO --}}
                <div class="col-md-4">
                    <label class="form-label">Nama Toko</label>
                    <select class="form-select" id="namaToko" name="id_toko" data-placeholder="Pilih Toko" required>
                        <option value="">Pilih Marketplace dulu</option>
                    </select>
                </div>

                {{-- USER --}}
                <div class="col-md-6">
                    <label class="form-label">Nama Pengguna</label>
                    <input type="text" class="form-control" id="namaUser" name="nama_user"
                        value="{{ auth()->user()->name }}" readonly style="background-color:#f5f5f5;">
                </div>

                {{-- FILE --}}
                <div class="col-md-6">
                    <label class="form-label">Upload File Excel</label>
                    <input class="form-control" type="file" name="file" id="fileUpload" accept=".xlsx,.xls" required>
                </div>
            </div>

            <div class="mt-3">
                <button type="submit" class="btn btn-success" id="btnPreview">
                    <i class="bi bi-eye me-1"></i>
                    Preview Data
                </button>
            </div>
        </form>

        <hr class="my-4">

        {{-- PREVIEW HASIL BACA IMPORT EXCELL --}}
        <div id="previewArea" style="display:none;">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="mb-1">Tinjau & Edit Data Pesanan</h5>
                    <small class="text-muted" id="previewInfo"></small>
                </div>

                <button type="button" class="btn btn-primary" id="btnSimpanSemua">
                    <i class="bi bi-save me-1"></i>
                    Simpan Semua
                </button>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">

                    <thead class="table-light text-center">
                        <tr>
                            <th style="width:70px;">No.</th>
                            <th style="min-width:180px;">No. Pesanan</th>
                            <th style="min-width:250px;">Nama Produk</th>
                            <th style="min-width:180px;">Variasi</th>
                            <th style="width:100px;">Jumlah</th>
                            <th style="width:140px;">HPP</th>
                            <th style="width:140px;">Harga</th>
                        </tr>
                    </thead>
                    <tbody id="previewTbody"></tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
@push('styles')
    <style>
        #previewTbody input[readonly] {
            background-color: #f8f9fa;
        }

        .select2-container {
            width: 100% !important;
        }

        .table td {
            vertical-align: middle;
        }
    </style>
@endpush
@push('scripts')
    <script>
        (function($) {
            'use strict';

            $(function() {

                $('#namaToko').select2({
                    theme: 'bootstrap-5',
                    width: '100%',
                    placeholder: 'Pilih Toko',
                    allowClear: true
                });

                function escapeHtml(value) {
                    if (value === null || value === undefined) {
                        return '';
                    }
                    return String(value)
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;')
                        .replace(/'/g, '&#039;');
                }

                function toNumber(value) {
                    if (value === null || value === undefined || value === '') {
                        return 0;
                    }

                    if (typeof value === 'number') {
                        return value;
                    }

                    let cleaned = String(value).replace(/[^\d.-]/g, '');
                    let number = Number(cleaned);

                    return isNaN(number) ? 0 : number;
                }

                $('#marketplace').on('change', function() {
                    const marketplace = $(this).val();
                    const selectToko = $('#namaToko');

                    selectToko.empty().append('<option value="">Memuat toko...</option>').trigger(
                        'change.select2');
                    if (!marketplace) {
                        selectToko.empty().append('<option value="">Pilih Marketplace dulu</option>')
                            .trigger('change.select2');
                        return;
                    }

                    const url = "{{ route('pesanan.getToko', ':market') }}".replace(':market',
                        encodeURIComponent(marketplace));
                    $.ajax({
                        url: url,
                        method: 'GET',
                        dataType: 'json',
                        success: function(response) {
                            selectToko.empty();
                            selectToko.append(
                                '<option value="">Pilih Toko</option>'
                            );

                            if (!Array.isArray(response) || response.length === 0) {
                                selectToko.append(
                                    '<option value="">Tidak ada toko</option>');
                                selectToko.trigger('change.select2');
                                return;
                            }

                            response.forEach(function(toko) {
                                selectToko.append(
                                    $('<option>', {
                                        value: toko.id_toko,
                                        text: toko.nama_toko
                                    })
                                );

                            });

                            selectToko.trigger('change.select2');
                        },
                        error: function(xhr) {
                            console.error(
                                'Gagal mengambil toko:',
                                xhr.responseText
                            );

                            selectToko
                                .empty()
                                .append(
                                    '<option value="">Gagal memuat toko</option>'
                                )
                                .trigger('change.select2');
                        }
                    });
                });

                function tampilkanPreview() {
                    $('#previewTbody').empty();
                    $.ajax({
                        url: "{{ route('pesanan.getPreview') }}",
                        method: 'GET',
                        dataType: 'json',
                        success: function(response) {
                            if (response.status !== 'success') {
                                alert(response.message || 'Gagal mengambil preview.');
                                return;
                            }

                            const tbody = $('#previewTbody');
                            tbody.empty();
                            let totalItem = 0;

                            (response.data || []).forEach(
                                function(item, indexPesanan) {
                                    const produkList = Array.isArray(item.produk_detail) ? item
                                        .produk_detail : [];
                                    produkList.forEach(
                                        function(produk, indexProduk) {
                                            totalItem++;
                                            const sku = produk['sku'] ?? produk['__sku'] ??
                                                produk['SKU Induk'] ?? produk['SKU'] ?? '';
                                            const namaProduk = produk['Nama Produk'] ??
                                                produk['nama_produk'] ?? '';
                                            const variasi = produk['Nama Variasi'] ??
                                                produk['variasi'] ?? '';
                                            const jumlah = parseInt(produk['Jumlah'] ??
                                                produk['jumlah'] ?? 1, 10) || 1;
                                            const hpp = toNumber(produk['HPP'] ?? produk[
                                                'hpp'] ?? 0);
                                            const harga = toNumber(produk['Harga'] ??
                                                produk['harga'] ?? 0);
                                            const nomor = (indexPesanan + 1) + '.' + (
                                                indexProduk + 1);
                                            const custom = produk['custom'] ?? 0;
                                            const row = `
                                                <tr class="preview-row">
                                                    <td class="text-center">
                                                        ${nomor}
                                                        <input type="hidden" class="field-no-resi" value="${escapeHtml(item.no_resi || '')}" >
                                                        <input type="hidden" class="field-kurir" value="${escapeHtml(item.kurir || '')}" >
                                                        <input type="hidden" class="field-pembeli" value="${escapeHtml(item.nama_pembeli || '')}">
                                                        <input type="hidden" class="field-username" value="${escapeHtml(item.username || '')}">
                                                        <input type="hidden" class="field-sku" value="${escapeHtml(sku)}">
                                                        <input type="hidden" class="field-custom" value="${custom}">
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control form-control-sm field-no-pesanan"
                                                            value="${escapeHtml(item.no_pesanan || '')}" readonly >
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control form-control-sm field-nama-produk"
                                                            value="${escapeHtml(namaProduk)}" readonly >
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control form-control-sm field-variasi"
                                                            value="${escapeHtml(variasi)}" readonly >
                                                    </td>
                                                    <td>
                                                        <input type="number" class="form-control form-control-sm text-center field-jumlah"
                                                            value="${jumlah}" min="1" readonly >
                                                    </td>
                                                    <td>
                                                        <input type="number" step="0.01" min="0" class="form-control form-control-sm field-hpp"
                                                            value="${hpp}" >
                                                    </td>
                                                    <td>
                                                        <input type="number" step="0.01" min="0" class="form-control form-control-sm field-harga"
                                                            value="${harga}" readonly >
                                                    </td>
                                                </tr>
                                            `;
                                            tbody.append(row);
                                        }
                                    );
                                }
                            );

                            if (totalItem === 0) {
                                tbody.html(`
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">
                                            Tidak ada data pesanan.
                                        </td>
                                    </tr>
                                `);

                                $('#previewInfo').text('0 item');
                                $('#btnSimpanSemua').prop('disabled', true);
                            } else {
                                $('#previewInfo').text(totalItem + ' item siap ditinjau');
                                $('#btnSimpanSemua').prop('disabled', false);
                            }

                            $('#previewArea').show();
                        },
                        error: function(xhr) {
                            console.error('Preview error:', xhr.responseText);
                            alert(xhr.responseJSON?.message || 'Gagal mengambil data preview.');
                        }
                    });
                }

                $('#formImportPesanan').on('submit', function(e) {
                    e.preventDefault();
                    const form = $(this);
                    const btn = $('#btnPreview');
                    const originalButtonHtml = btn.html();

                    if (!$('#marketplace').val()) {
                        alert('Pilih marketplace terlebih dahulu.');
                        return;
                    }

                    if (!$('#namaToko').val()) {
                        alert('Pilih nama toko terlebih dahulu.');
                        return;
                    }

                    if (!$('#fileUpload')[0].files.length) {
                        alert('Pilih file Excel terlebih dahulu.');
                        return;
                    }

                    btn.prop('disabled', true).html(`
                                <span
                                    class="spinner-border spinner-border-sm me-2"
                                ></span>
                                Membaca Excel...
                            `);

                    $('#previewArea').hide();
                    $('#previewTbody').empty();
                    $.ajax({
                        url: form.attr('action'),
                        method: 'POST',
                        data: new FormData(this),
                        contentType: false,
                        processData: false,
                        success: function(response) {
                            if (response.status === 'success') {
                                tampilkanPreview();
                                return;
                            }
                            alert(response.message || 'Gagal membaca data Excel.');
                        },
                        error: function(xhr) {
                            console.error('Upload error:', xhr.responseText);
                            let message = 'Gagal mengunggah file.';
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                message = xhr.responseJSON.message;
                            }

                            alert(message);
                        },
                        complete: function() {
                            btn.prop('disabled', false).html(originalButtonHtml);
                        }
                    });

                });

                $('#btnSimpanSemua').on('click', function() {
                    const btn = $(this);
                    const originalButtonHtml = btn.html();
                    const pesananMap = {};

                    $('#previewTbody .preview-row').each(function() {
                        const row = $(this);
                        const noPesanan = $.trim(row.find('.field-no-pesanan').val());

                        if (!noPesanan) {
                            return;
                        }

                        if (!pesananMap[noPesanan]) {
                            pesananMap[noPesanan] = {
                                no_pesanan: noPesanan,
                                no_resi: $.trim(row.find('.field-no-resi').val() || ''),
                                kurir: $.trim(row.find('.field-kurir').val() || ''),
                                nama_pembeli: $.trim(row.find('.field-pembeli').val() ||
                                    ''),
                                username: $.trim(row.find('.field-username').val() || ''),
                                produk: []
                            };
                        }

                        pesananMap[noPesanan].produk.push({
                            nama_produk: $.trim(row.find('.field-nama-produk').val() ||
                                ''),
                            variasi: $.trim(row.find('.field-variasi').val() || ''),
                            jumlah: parseInt(row.find('.field-jumlah').val(), 10) || 1,
                            hpp: parseFloat(row.find('.field-hpp').val()) || 0,
                            harga: parseFloat(row.find('.field-harga').val()) || 0,
                            sku: $.trim(row.find('.field-sku').val() || ''),
                            custom: parseInt(row.find('.field-custom').val(), 10) || 0
                        });

                    });

                    const pesanan = Object.values(pesananMap);
                    if (pesanan.length === 0) {
                        alert('Tidak ada data untuk disimpan.');
                        return;
                    }

                    btn.prop('disabled', true)
                        .html(`
                                <span
                                    class="spinner-border spinner-border-sm me-2"
                                ></span>
                                Menyimpan...
                            `);

                    $.ajax({
                        url: "{{ route('pesanan.simpanImport') }}",
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': "{{ csrf_token() }}"
                        },
                        contentType: 'application/json; charset=utf-8',
                        processData: false,
                        data: JSON.stringify({
                            pesanan: pesanan,
                            tanggal_import: $('#tanggalImport').val(),
                            id_toko: $('#namaToko').val(),
                            nama_user: $('#namaUser').val()
                        }),
                        success: function(response) {
                            if (response.status !== 'success') {
                                alert(response.message || 'Gagal menyimpan data.');
                                return;
                            }

                            const berhasil = response.imported || 0;
                            const dilewati = response.skipped_count || 0;
                            Swal.fire({
                                icon: 'success',
                                title: 'Import selesai!',
                                html: `
                                    Berhasil: <b>${berhasil} Pesanan</b><br>
                                    Dilewati: <b>${dilewati} Pesanan</b>
                                `,
                                confirmButtonText: 'OK'
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    window.location.href =
                                        "{{ route('pesanan.index') }}";
                                }
                            });
                        },

                        error: function(xhr) {
                            console.error('Simpan error:', xhr.responseText);
                            let message = 'Terjadi kesalahan saat menyimpan.';

                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                message = xhr.responseJSON.message;
                            }

                            Swal.fire({
                                icon: 'error',
                                title: 'Gagal!',
                                text: message,
                                confirmButtonText: 'OK',
                                confirmButtonColor: '#0d6efd'
                            });
                        },
                        complete: function() {
                            btn.prop('disabled', false).html(originalButtonHtml);
                        }
                    });
                });
            });
        })(jQuery);
    </script>
@endpush
