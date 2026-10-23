@extends('layouts.app')
@section('content')
    <div class="bg-white p-4 rounded shadow-sm w-100">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="mb-0 d-flex align-items-center gap-2">
                <i class="bi bi-upload text-success"></i>
                Import Pencairan
            </h5>

            <a href="{{ route('pesanan.kirim') }}" class="btn btn-outline-secondary btn-sm">
                Kembali
            </a>
        </div>

        <form action="{{ route('kirim.previewPencairan') }}" method="POST" enctype="multipart/form-data"
            id="formImportPencairan">
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

                <div class="col-md-8">
                    <label class="form-label">File Excel / CSV</label>
                    <input class="form-control" type="file" name="file" id="filePencairan" accept=".xlsx,.xls,.csv"
                        required>
                    <div class="form-text">
                        Format didukung: .xlsx, .xls, .csv
                    </div>
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
                <h5 class="mb-0">Preview Pencairan</h5>

                <button type="button" class="btn btn-primary" id="btnSimpanPencairan">
                    <i class="bi bi-save me-1"></i> Simpan Pencairan
                </button>
            </div>

            <div class="alert alert-info py-2">
                Data yang tidak ditemukan di database akan dilewati saat disimpan.
            </div>

            <div class="table-responsive">
                <table class="table table-bordered align-middle">
                    <thead class="table-light text-center">
                        <tr>
                            <th>No.</th>
                            <th>No. Pesanan</th>
                            <th>Pencairan</th>
                            <th>Catatan</th>
                            <th>Status DB</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody id="previewTbody"></tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            function rupiahToNumber(value) {
                return String(value || '').replace(/[^0-9\-]/g, '');
            }

            function renderPreview(data) {
                let tbody = $('#previewTbody');
                tbody.empty();

                if (!data || data.length === 0) {
                    tbody.append(`
                          <tr>
                            <td colspan="6" class="text-center text-muted">
                              Tidak ada data.
                            </td>
                          </tr>
                        `);
                    return;
                }

                data.forEach(function(item, index) {
                    let badge = item.ada_di_database ?
                        `<span class="badge bg-success">Ada</span>` :
                        `<span class="badge bg-danger">Tidak ditemukan</span>`;

                    let row = `
                          <tr>
                            <td class="text-center">${index + 1}</td>

                            <td>
                              <input type="text"
                                class="form-control form-control-sm no-pesanan"
                                value="${item.no_pesanan || ''}"
                                readonly>
                            </td>

                            <td>
                              <input type="number"
                                class="form-control form-control-sm pencairan text-end"
                                value="${item.pencairan || 0}">
                            </td>

                            <td>
                              <input type="text"
                                class="form-control form-control-sm notes"
                                value="${item.notes || ''}">
                            </td>

                            <td class="text-center">
                              ${item.status_db || '-'}
                            </td>
                            
                            <td class="d-none tanggal">  
                              ${item.tanggal || null}
                            </td>

                            <td class="text-center">
                              ${badge}
                            </td>
                          </tr>
                        `;

                    tbody.append(row);
                });

                $('#previewArea').show();
            }

            $('#formImportPencairan').on('submit', function(e) {
                e.preventDefault();

                const form = $(this);
                const btn = $('#btnPreview');
                const oldHtml = btn.html();

                function resetButton() {
                    btn.prop('disabled', false).html(oldHtml);
                }

                if (!$('#marketplace').val()) {
                    Swal.fire('Peringatan', 'Pilih marketplace dulu.', 'warning');
                    return;
                }

                if (!$('#filePencairan')[0].files.length) {
                    Swal.fire('Peringatan', 'Pilih file dulu.', 'warning');
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
                    success: function(response) {
                        if (
                            response.status === 'success' &&
                            Array.isArray(response.data) &&
                            response.data.length === 0
                        ) {
                            resetButton();
                            Swal.fire({
                                icon: 'success',
                                title: 'Import Selesai',
                                text: response.message ||
                                    'Seluruh pesanan berstatus KIRIM sudah berhasil diproses pencairannya.',
                                confirmButtonText: 'OK'
                            });

                            return;
                        }

                        if (response.status === 'success') {
                            btn.html(
                                '<span class="spinner-border spinner-border-sm me-2"></span>Memuat Preview...'
                            );

                            $.ajax({
                                url: "{{ route('kirim.getPreviewPencairan') }}",
                                method: 'GET',
                                success: function(previewResponse) {
                                    if (previewResponse.status === 'success') {
                                        renderPreview(previewResponse.data || []);
                                    } else {
                                        Swal.fire(
                                            'Gagal',
                                            'Gagal mengambil data preview.',
                                            'error'
                                        );
                                    }
                                },
                                error: function(xhr) {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Gagal',
                                        text: xhr.responseJSON?.message ||
                                            'Terjadi kesalahan saat mengambil preview.'
                                    });
                                },
                                complete: function() {
                                    resetButton();
                                }
                            });
                        } else {
                            resetButton();
                            Swal.fire(
                                'Gagal',
                                response.message || 'Gagal memproses file.',
                                'error'
                            );
                        }
                    },

                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal',
                            text: xhr.responseJSON?.message ||
                                'Gagal upload file.'
                        });
                    },
                }).fail(function() {
                    resetButton();
                });
            });

            $('#btnSimpanPencairan').on('click', function() {
                let data = [];

                $('#previewTbody tr').each(function() {
                    let row = $(this);

                    let noPesanan = row.find('.no-pesanan').val();
                    let pencairan = row.find('.pencairan').val();
                    let notes = row.find('.notes').val();
                    let tanggal = row.find('.tanggal').text().trim();

                    if (!noPesanan) {
                        return;
                    }

                    data.push({
                        no_pesanan: noPesanan,
                        pencairan: pencairan || 0,
                        notes: notes || '',
                        tanggal: tanggal || null
                    });
                });

                if (data.length === 0) {
                    alert('Tidak ada data untuk disimpan.');
                    return;
                }

                const btn = $(this);
                const oldHtml = btn.html();

                btn.prop('disabled', true)
                    .html(
                        '<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan...');

                $.ajax({
                    url: "{{ route('kirim.simpanPencairan') }}",
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': "{{ csrf_token() }}"
                    },
                    data: JSON.stringify({
                        data: data
                    }),
                    contentType: 'application/json; charset=utf-8',
                    processData: false,
                    success: function(res) {
                        if (res.status === 'success') {
                            alert(
                                'Import pencairan berhasil. Diperbarui: ' +
                                res.updated +
                                ', dilewati: ' +
                                res.skipped
                            );

                            window.location.href = "{{ route('pesanan.kirim') }}";
                        } else {
                            alert('Gagal menyimpan data.');
                        }
                    },
                    error: function(xhr) {
                        let msg = 'Terjadi kesalahan saat menyimpan.';

                        if (xhr.responseJSON?.message) {
                            msg = xhr.responseJSON.message;
                        }

                        alert(msg);
                    },
                    complete: function() {
                        btn.prop('disabled', false).html(oldHtml);
                    }
                });
            });
        });
    </script>
@endpush
