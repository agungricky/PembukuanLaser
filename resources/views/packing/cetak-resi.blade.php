@extends('layouts.app')

@section('content')
<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex align-items-center gap-3">
            <div class="rounded-4 bg-success bg-opacity-10 d-flex align-items-center justify-content-center"
                style="width:64px;height:64px;">
                <i class="bi bi-printer fs-2 text-success"></i>
            </div>
            <div>
                <h4 class="fw-bold mb-1">Cari & Cetak Resi</h4>
                <div class="text-muted small">
                    Cari request customer, pastikan semua barang tersedia, lalu cetak resi.
                </div>
            </div>
        </div>

        <div class="text-end">
            <div class="small text-muted">Operator</div>
            <div class="fw-semibold">{{ auth()->user()->name }}</div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4">
            <label class="form-label fw-semibold">Cari Request Customer</label>

            <div class="input-group input-group-lg">
                <span class="input-group-text bg-white">
                    <i class="bi bi-search"></i>
                </span>

                <input type="text"
                    id="requestSearch"
                    class="form-control fw-semibold"
                    placeholder="Plat / Nama / Tanggal..."
                    autocomplete="off"
                    autofocus>

                <button type="button"
                    class="btn btn-primary px-4"
                    id="btnCariRequest">
                    <i class="bi bi-search me-1"></i>
                    Cari
                </button>
            </div>

            <div class="text-muted small mt-2">
                Spasi dan tanda baca boleh diabaikan. Bisa mencari plat, nama, atau tanggal.
            </div>
        </div>
    </div>

    <div id="requestResult" class="mt-4" style="display:none;">
        <div class="card border-0 shadow-sm rounded-4 mb-3">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center gap-3 mb-3">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-card-checklist text-primary fs-5"></i>
                        <div class="fw-bold fs-5">Request Customer</div>
                    </div>

                    <div id="requestStatus"></div>
                </div>

                <div id="detailRequest"></div>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4 mb-3">
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <i class="bi bi-box-seam text-primary fs-5"></i>
                    <div class="fw-bold fs-5">Produk Pesanan</div>
                </div>

                <div id="produkPesanan"></div>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4 mb-3">
            <div class="card-body p-4">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <i class="bi bi-receipt text-primary fs-5"></i>
                    <div class="fw-bold fs-5">Informasi Pesanan</div>
                </div>

                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="text-muted small">Marketplace</div>
                        <div id="detailMarketplace">-</div>
                    </div>

                    <div class="col-md-4">
                        <div class="text-muted small">No. Resi</div>
                        <div class="fw-bold fs-5" id="detailNoResi">-</div>
                    </div>

                    <div class="col-md-4">
                        <div class="text-muted small">Nama Pembeli</div>
                        <div class="fw-bold fs-5" id="detailPembeli">-</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        <div class="text-muted small">PDF Resi</div>
                        <div id="detailPdf" class="mt-1">-</div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="button"
                            class="btn btn-outline-secondary px-4"
                            id="btnSkipRequest">
                            <i class="bi bi-arrow-counterclockwise me-1"></i>
                            Skip
                        </button>

                        <form action="{{ route('packing.cetakResi') }}"
                            method="POST"
                            target="_blank"
                            id="formCetakResi">
                            @csrf

                            <input type="hidden"
                                name="no_pesanan"
                                id="printNoPesanan">

                            <input type="hidden"
                                name="allow_reprint"
                                id="allowReprint"
                                value="0">

                            <button type="button"
                                class="btn btn-success px-4"
                                id="btnCetakResi"
                                disabled>
                                <i class="bi bi-printer me-1"></i>
                                Cetak Resi
                            </button>
                        </form>
                    </div>
                </div>

                <div id="printHint"
                    class="text-muted small mt-3"
                    style="display:none;">
                    <i class="bi bi-info-circle me-1"></i>
                    Pastikan semua barang / plat sudah tersedia dan dicentang sebelum mencetak resi.
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade"
    id="modalPilihPesanan"
    tabindex="-1"
    aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 rounded-4">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title fw-bold mb-1">Pilih Pesanan</h5>
                    <div class="text-muted small">
                        Ditemukan beberapa pesanan dengan request yang cocok.
                    </div>
                </div>

                <button type="button"
                    class="btn-close"
                    data-bs-dismiss="modal">
                </button>
            </div>

            <div class="modal-body">
                <div id="candidateList"></div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .request-list-item {
        border: 1px solid #dee2e6;
        border-radius: 14px;
        padding: 18px;
        background: #fff;
        transition: .15s;
    }

    .request-list-item + .request-list-item {
        margin-top: 12px;
    }

    .request-list-item.checked {
        border-color: #75b798;
        background: #f1f9f5;
    }

    .request-number {
        width: 38px;
        height: 38px;
        min-width: 38px;
        border-radius: 50%;
        background: #e7f1ff;
        color: #0d6efd;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        font-weight: 800;
    }

    .request-list-item.checked .request-number {
        background: #d1e7dd;
        color: #198754;
    }

    .request-main-value {
        font-size: 21px;
        line-height: 1.4;
        font-weight: 800;
        color: #212529;
        word-break: break-word;
    }

    .request-single .request-main-value {
        font-size: 23px;
    }

    .request-qty {
        font-size: 14px;
        font-weight: 700;
        padding: 7px 11px;
    }

    .request-check {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 10px 12px;
        transition: .15s;
    }

    .request-check.checked {
        background: #d1e7dd;
        color: #0f5132;
    }

    .request-check .form-check-input {
        width: 19px;
        height: 19px;
        margin-top: 1px;
    }

    .request-check .form-check-label {
        cursor: pointer;
        margin-left: 5px;
    }

    .produk-item {
        padding: 14px 0;
    }

    .produk-item:first-child {
        padding-top: 0;
    }

    .produk-item:last-child {
        padding-bottom: 0;
    }

    .produk-item + .produk-item {
        border-top: 1px solid #e9ecef;
    }

    .produk-sku {
        font-family: Consolas, monospace;
        font-size: 13px;
    }

    .candidate-item {
        transition: .15s;
    }

    .candidate-item:hover {
        border-color: #0d6efd !important;
        background: #f8fbff;
    }

    .candidate-request {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 10px 12px;
    }

    .candidate-request + .candidate-request {
        margin-top: 7px;
    }

    .candidate-product {
        padding: 3px 0;
    }

    @media (max-width: 767.98px) {
        .request-list-item {
            padding: 14px;
        }

        .request-main-value,
        .request-single .request-main-value {
            font-size: 18px;
        }

        .request-number {
            width: 34px;
            height: 34px;
            min-width: 34px;
            font-size: 14px;
        }
    }
</style>
@endpush

@push('scripts')
<script>
$(function () {
    const input = $('#requestSearch');
    const result = $('#requestResult');
    const btnCari = $('#btnCariRequest');
    const btnCetak = $('#btnCetakResi');

    let currentPesanan = null;
    let currentRequests = [];

    btnCari.on('click', function () {
        cariRequest();
    });

    input.on('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            cariRequest();
        }
    });

    $('#btnSkipRequest').on('click', function () {
        resetRequest();
    });

    $(document).on('click', '.btnPilihPesanan', function () {
        const noPesanan = String($(this).data('no-pesanan'));
        const modalElement = document.getElementById('modalPilihPesanan');
        const modal = bootstrap.Modal.getInstance(modalElement);

        if (modal) {
            modal.hide();
        }

        cariRequest(noPesanan);
    });

    $(document).on('change', '.checkRequestBarang', function () {
        const checked = $(this).is(':checked');

        $(this)
            .closest('.request-check')
            .toggleClass('checked', checked);

        $(this)
            .closest('.request-list-item')
            .toggleClass('checked', checked);

        updateRequestStatus();
    });

    btnCetak.on('click', function () {
        if (!currentPesanan) {
            return;
        }

        if (!semuaRequestSiap()) {
            Swal.fire({
                icon: 'warning',
                title: 'Barang Belum Lengkap',
                text: 'Pastikan semua barang / plat request sudah tersedia dan dicentang.'
            });
            return;
        }

        if (!currentPesanan.pdf_tersedia) {
            Swal.fire({
                icon: 'error',
                title: 'PDF Tidak Tersedia',
                text: 'PDF resi untuk pesanan ini belum tersedia.'
            });
            return;
        }

        if (currentPesanan.sudah_print) {
            const tanggal = currentPesanan.first_printed_at || '-';
            const operator = currentPesanan.first_printed_by || '-';
            const jumlah = parseInt(currentPesanan.print_count || 1);

            Swal.fire({
                icon: 'warning',
                title: 'Resi Sudah Pernah Dicetak',
                html: `
                    <div class="text-start mt-3">
                        <div class="mb-3">
                            Resi ini sudah pernah dicetak sebelumnya.
                        </div>

                        <div class="border rounded-3 p-3 bg-light">
                            <div class="mb-3">
                                <div class="text-muted small">Cetak Pertama</div>
                                <div class="fw-bold">${escapeHtml(tanggal)}</div>
                            </div>

                            <div class="mb-3">
                                <div class="text-muted small">Dicetak Oleh</div>
                                <div class="fw-bold">${escapeHtml(operator)}</div>
                            </div>

                            <div>
                                <div class="text-muted small">Total Cetak</div>
                                <div class="fw-bold">${jumlah}x</div>
                            </div>
                        </div>

                        <div class="mt-3">
                            Apakah ingin mencetak ulang resi ini?
                        </div>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: '<i class="bi bi-printer me-1"></i> Ya, Cetak Ulang',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#198754',
                reverseButtons: true
            }).then(function (swalResult) {
                if (!swalResult.isConfirmed) {
                    return;
                }

                $('#allowReprint').val('1');
                submitCetak();
            });

            return;
        }

        $('#allowReprint').val('0');
        submitCetak();
    });

    function submitCetak() {
        if (!currentPesanan) {
            return;
        }

        document.getElementById('formCetakResi').submit();

        setTimeout(function () {
            if (currentPesanan && currentPesanan.no_pesanan) {
                cariRequest(currentPesanan.no_pesanan);
            }
        }, 1500);
    }

    function cariRequest(noPesanan = null) {
        const keyword = $.trim(input.val());

        if (!keyword) {
            Swal.fire({
                icon: 'warning',
                title: 'Request Kosong',
                text: 'Masukkan request customer terlebih dahulu.'
            });

            input.focus();
            return;
        }

        const originalHtml = btnCari.html();

        btnCari
            .prop('disabled', true)
            .html(`
                <span class="spinner-border spinner-border-sm me-2"></span>
                Mencari...
            `);

        result.hide();
        currentPesanan = null;
        currentRequests = [];
        btnCetak.prop('disabled', true);

        const data = {
            _token: "{{ csrf_token() }}",
            request_search: keyword
        };

        if (noPesanan) {
            data.no_pesanan = noPesanan;
        }

        $.ajax({
            url: "{{ route('packing.cariRequest') }}",
            type: 'POST',
            dataType: 'json',
            data: data,

            success: function (response) {
                if (!response.success) {
                    showError(response.message || 'Data tidak ditemukan.');
                    return;
                }

                if (
                    response.multiple &&
                    Array.isArray(response.candidates)
                ) {
                    tampilkanPilihanPesanan(response.candidates);
                    return;
                }

                tampilkanPesanan(response);
            },

            error: function (xhr) {
                showError(
                    xhr.responseJSON?.message ||
                    'Request customer tidak ditemukan.'
                );
            },

            complete: function () {
                btnCari
                    .prop('disabled', false)
                    .html(originalHtml);
            }
        });
    }

    function tampilkanPilihanPesanan(candidates) {
        let html = '';

        candidates.forEach(function (item, index) {
            const requests = Array.isArray(item.requests)
                ? item.requests
                : [];

            let requestHtml = '';

            if (requests.length) {
                requests.forEach(function (request, requestIndex) {
                    const parts = getRequestParts(request);

                    requestHtml += `
                        <div class="candidate-request">
                            <div class="d-flex justify-content-between align-items-center gap-3">
                                <div>
                                    ${requests.length > 1 ? `
                                        <span class="badge bg-primary me-2">
                                            ${requestIndex + 1}
                                        </span>
                                    ` : ''}

                                    <span class="fw-bold">
                                        ${parts.join(' | ') || '-'}
                                    </span>
                                </div>

                                <span class="badge bg-dark text-nowrap">
                                    Qty ${parseInt(request.jumlah || 1)}
                                </span>
                            </div>
                        </div>
                    `;
                });
            } else {
                requestHtml = `
                    <div class="text-muted small">
                        Request tidak tersedia.
                    </div>
                `;
            }

            let produkHtml = '';

            (item.produk || []).forEach(function (produk) {
                produkHtml += `
                    <div class="candidate-product small">
                        <span class="fw-semibold">
                            ${escapeHtml(produk.nama_produk || '-')}
                        </span>

                        <span class="text-muted">
                            • ${escapeHtml(produk.variasi || '-')}
                        </span>

                        <span class="text-muted">
                            • ${escapeHtml(produk.sku || '-')}
                        </span>

                        <span class="fw-semibold">
                            • x${parseInt(produk.jumlah || 0)}
                        </span>
                    </div>
                `;
            });

            if (!produkHtml) {
                produkHtml = `
                    <div class="text-muted small">
                        Tidak ada produk.
                    </div>
                `;
            }

            html += `
                <div class="candidate-item border rounded-4 p-3 mb-3">
                    <div class="d-flex justify-content-between align-items-start gap-3">
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center gap-2 mb-3">
                                <div class="fw-bold fs-5">
                                    Pilihan ${index + 1}
                                </div>

                                ${marketplaceBadge(item.marketplace)}

                                <span class="badge bg-primary">
                                    ${requests.length} Request
                                </span>
                            </div>

                            <div class="mb-3">
                                <div class="text-muted small fw-semibold mb-2">
                                    Request Customer
                                </div>
                                ${requestHtml}
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <div class="text-muted small">Nama Pembeli</div>
                                    <div class="fw-semibold">
                                        ${escapeHtml(item.nama_pembeli || '-')}
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="text-muted small">No. Resi</div>
                                    <div class="fw-semibold">
                                        ${escapeHtml(item.no_resi || '-')}
                                    </div>
                                </div>
                            </div>

                            <div>
                                <div class="text-muted small mb-1">
                                    Produk Pesanan
                                </div>
                                ${produkHtml}
                            </div>
                        </div>

                        <div>
                            <button type="button"
                                class="btn btn-primary btnPilihPesanan"
                                data-no-pesanan="${escapeHtml(item.no_pesanan)}">
                                <i class="bi bi-check2 me-1"></i>
                                Pilih
                            </button>
                        </div>
                    </div>
                </div>
            `;
        });

        $('#candidateList').html(html);

        const modal = bootstrap.Modal.getOrCreateInstance(
            document.getElementById('modalPilihPesanan')
        );

        modal.show();
    }

    function tampilkanPesanan(response) {
        const pesanan = response.pesanan;
        const requests = Array.isArray(response.requests)
            ? response.requests
            : [];

        currentPesanan = pesanan;
        currentRequests = requests;

        tampilkanRequest(requests);
        tampilkanProduk(pesanan.produk || []);

        $('#detailMarketplace').html(
            marketplaceBadge(pesanan.marketplace)
        );

        $('#detailNoResi').text(
            pesanan.no_resi || '-'
        );

        $('#detailPembeli').text(
            pesanan.nama_pembeli || '-'
        );

        $('#printNoPesanan').val(
            pesanan.no_pesanan
        );

        $('#allowReprint').val('0');

        tampilkanPdf(pesanan);
        updateRequestStatus();
        result.slideDown();
    }

    function tampilkanRequest(requests) {
        if (!Array.isArray(requests) || !requests.length) {
            $('#detailRequest').html(`
                <div class="text-muted text-center py-4">
                    Request customer tidak tersedia.
                </div>
            `);
            return;
        }

        const perluChecklist = requests.length > 1;
        let html = '';

        requests.forEach(function (request, index) {
            const jumlah = parseInt(request.jumlah || 1);
            const parts = getRequestParts(request);
            const requestText = parts.length
                ? parts.join(' | ')
                : '-';

            html += `
                <div class="request-list-item ${perluChecklist ? '' : 'request-single'}">
                    <div class="d-flex align-items-start gap-3">
                        ${perluChecklist ? `
                            <div class="request-number">
                                ${index + 1}
                            </div>
                        ` : ''}

                        <div class="flex-grow-1">
                            <div class="d-flex justify-content-between align-items-start gap-3">
                                <div class="request-main-value">
                                    ${requestText}
                                </div>

                                <span class="badge bg-dark request-qty text-nowrap">
                                    Qty ${jumlah}
                                </span>
                            </div>

                            ${perluChecklist ? `
                                <div class="request-check mt-3">
                                    <div class="form-check mb-0">
                                        <input
                                            class="form-check-input checkRequestBarang"
                                            type="checkbox"
                                            id="requestCheck${index}">

                                        <label
                                            class="form-check-label fw-semibold"
                                            for="requestCheck${index}">
                                            Barang / plat ini sudah ada
                                        </label>
                                    </div>
                                </div>
                            ` : ''}
                        </div>
                    </div>
                </div>
            `;
        });

        $('#detailRequest').html(html);
    }

    function tampilkanProduk(produk) {
        if (!produk.length) {
            $('#produkPesanan').html(`
                <div class="text-muted text-center py-4">
                    Tidak ada produk.
                </div>
            `);
            return;
        }

        let html = '';

        produk.forEach(function (item, index) {
            html += `
                <div class="produk-item">
                    <div class="d-flex justify-content-between align-items-start gap-3">
                        <div class="flex-grow-1">
                            <div class="fw-bold fs-5">
                                ${index + 1}.
                                ${escapeHtml(item.nama_produk || '-')}
                            </div>

                            <div class="row g-2 mt-2">
                                <div class="col-md-6">
                                    <div class="text-muted small">Variasi</div>
                                    <div class="fw-semibold">
                                        ${escapeHtml(item.variasi || '-')}
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="text-muted small">SKU</div>
                                    <div class="fw-semibold produk-sku">
                                        ${escapeHtml(item.sku || '-')}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="text-center flex-shrink-0">
                            <div class="text-muted small">Jumlah</div>
                            <div class="fw-bold fs-3">
                                x${parseInt(item.jumlah || 0)}
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });

        $('#produkPesanan').html(html);
    }

    function updateRequestStatus() {
        const totalRequest = currentRequests.length;

        if (totalRequest === 0) {
            $('#requestStatus').empty();
            $('#printHint').hide();
            btnCetak.prop('disabled', true);
            return;
        }

        if (totalRequest === 1) {
            $('#requestStatus').html(`
                <span class="badge bg-primary fs-6">
                    1 REQUEST
                </span>
            `);

            $('#printHint').hide();
            updatePrintButton();
            return;
        }

        const checks = $('.checkRequestBarang');
        const total = checks.length;
        const siap = checks.filter(':checked').length;

        if (siap === total && total === totalRequest) {
            $('#requestStatus').html(`
                <span class="badge bg-success fs-6">
                    <i class="bi bi-check-circle-fill me-1"></i>
                    ${siap}/${totalRequest} SIAP
                </span>
            `);
            $('#printHint').hide();
        } else {
            $('#requestStatus').html(`
                <span class="badge bg-warning text-dark fs-6">
                    ${siap}/${totalRequest} SIAP
                </span>
            `);
            $('#printHint').show();
        }

        updatePrintButton();
    }

    function semuaRequestSiap() {
        if (currentRequests.length === 0) {
            return false;
        }

        if (currentRequests.length === 1) {
            return true;
        }

        const checks = $('.checkRequestBarang');

        return (
            checks.length === currentRequests.length &&
            checks.filter(':checked').length === checks.length
        );
    }

    function updatePrintButton() {
        const bolehCetak =
            currentPesanan &&
            currentPesanan.pdf_tersedia &&
            semuaRequestSiap();

        btnCetak.prop(
            'disabled',
            !bolehCetak
        );
    }

    function tampilkanPdf(pesanan) {
        if (!pesanan.pdf_tersedia) {
            $('#detailPdf').html(`
                <span class="badge bg-danger">
                    <i class="bi bi-x-circle me-1"></i>
                    PDF Tidak Tersedia
                </span>
            `);

            updatePrintButton();
            return;
        }

        let html = `
            <span class="badge bg-success">
                <i class="bi bi-check-circle me-1"></i>
                PDF Tersedia
            </span>
        `;

        if (pesanan.sudah_print) {
            html += `
                <div class="text-warning small mt-2">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                    Pernah dicetak
                    ${parseInt(pesanan.print_count || 1)}x
                </div>
            `;
        }

        $('#detailPdf').html(html);
        updatePrintButton();
    }

    function getRequestParts(request) {
        const parts = [];

        if (request.plat_lengkap) {
            parts.push(
                escapeHtml(request.plat_lengkap)
            );
        }

        if (request.nama) {
            parts.push(
                escapeHtml(request.nama)
            );
        }

        if (request.tanggal_bulan_tahun) {
            parts.push(
                escapeHtml(request.tanggal_bulan_tahun)
            );
        }

        return parts;
    }

    function marketplaceBadge(marketplace) {
        const value = String(marketplace || '');

        if (value.toLowerCase() === 'shopee') {
            return `
                <span class="badge bg-warning text-dark">
                    Shopee
                </span>
            `;
        }

        if (value.toLowerCase() === 'tiktok') {
            return `
                <span class="badge bg-dark">
                    TikTok
                </span>
            `;
        }

        return `
            <span class="badge bg-secondary">
                ${escapeHtml(value || '-')}
            </span>
        `;
    }

    function showError(message) {
        resetRequest(false);

        Swal.fire({
            icon: 'error',
            title: 'Tidak Ditemukan',
            text: message
        });

        input.focus();
        input.select();
    }

    function resetRequest(clearInput = true) {
        currentPesanan = null;
        currentRequests = [];

        result.hide();
        $('#detailRequest').empty();
        $('#requestStatus').empty();
        $('#produkPesanan').empty();
        $('#detailMarketplace').text('-');
        $('#detailNoResi').text('-');
        $('#detailPembeli').text('-');
        $('#detailPdf').text('-');
        $('#printNoPesanan').val('');
        $('#allowReprint').val('0');
        $('#printHint').hide();

        btnCetak.prop('disabled', true);

        if (clearInput) {
            input.val('');
        }

        input.focus();
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
});
</script>
@endpush
