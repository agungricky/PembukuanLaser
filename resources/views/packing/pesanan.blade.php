@extends('layouts.app')

@section('content')

<div class="container-fluid py-3">

    {{-- HEADER --}}
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">

        <div class="d-flex align-items-center gap-3">
            <div class="rounded-4 bg-primary bg-opacity-10 d-flex align-items-center justify-content-center"
                 style="width:72px;height:72px;">
                <i class="bi bi-qr-code-scan fs-1 text-primary"></i>
            </div>
            <div>
                <h3 class="fw-bold mb-1">
                    Packing Scanner
                </h3>
                <div class="text-muted">
                    Scan No. Resi / No. Pesanan untuk mengubah status menjadi
                    <span class="fw-semibold text-success">Kirim</span>
                </div>
            </div>
        </div>

        <div class="text-end">
            <div class="small text-muted">
                Operator
            </div>
            <div class="fw-semibold">
                {{ auth()->user()->name }}
            </div>
        </div>
    </div>

    <div class="row g-4">
        {{-- AREA SCANNER --}}
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-5 text-center">
                    <div class="mb-4">
                        <div class="scan-circle mb-3">
                            <i class="bi bi-qr-code-scan fs-1"></i>
                        </div>

                        <h3 class="fw-bold mb-2">
                            Scan Resi
                        </h3>

                        <div class="text-muted">
                            Fokus otomatis • Enter = Scan • ESC = Reset
                        </div>
                    </div>

                    <div class="input-group input-group-lg mx-auto"
                         style="max-width:650px;">

                        <input
                            type="text"
                            id="scanInput"
                            class="form-control text-center fw-bold fs-2"
                            placeholder="Scan Barcode..."
                            autocomplete="off"
                            autofocus
                            spellcheck="false"
                            style="
                                font-family:Consolas,monospace;
                                letter-spacing:3px;
                                height:78px;
                            ">
                    </div>

                    <div class="mt-4">
                        <div class="alert alert-light border mb-0">
                            <i class="bi bi-info-circle me-1"></i>
                            Pastikan barcode jelas dan scanner berada pada fokus input.
                        </div>
                    </div>
                </div>
            </div>
        </div>
                {{-- STATUS --}}
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body">
                    <div class="text-center mb-4">
                        <h5 class="fw-bold mb-1">
                            Status Terakhir
                        </h5>

                        <small class="text-muted">
                            Informasi hasil scan terakhir
                        </small>
                    </div>

                    <div id="scanStatus"
                        class="border rounded-4 p-4 mb-4 bg-light">
                        <div class="text-center">
                            <i class="bi bi-clock-history display-4 text-secondary"></i>
                            <h5 class="mt-3 mb-1">
                                Siap Scan
                            </h5>
                            <div class="text-muted">
                                Silakan scan No. Resi atau No. Pesanan
                            </div>
                        </div>
                    </div>



                    {{-- STATISTIK --}}
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="card border-0 bg-primary bg-opacity-10">
                                <div class="card-body text-center py-3">
                                    <div
                                        class="fw-bold text-primary"
                                        style="font-size:32px;"
                                        id="statHariIni">
                                        {{ $hariIni }}

                                    </div>
                                    <small class="text-muted">
                                        Scan Hari Ini
                                    </small>
                                </div>
                            </div>
                        </div>

                        <div class="col-6">

                            <div class="card border-0 bg-success bg-opacity-10">

                                <div class="card-body text-center py-3">

                                    <div
                                        class="fw-bold text-success"
                                        style="font-size:32px;"
                                        id="statBelumDikirim">

                                        {{ $jumlahPesanan }}

                                    </div>

                                    <small class="text-muted">

                                        Belum Dikirim

                                    </small>

                                </div>

                            </div>

                        </div>

                    </div>



                    <hr class="my-4">



                    <div class="row g-3">

                        <div class="col-6">

                            <div class="card border-warning">

                                <div class="card-body py-3 text-center">

                                    <div
                                        class="fw-bold text-warning fs-3"
                                        id="statSpx">

                                        {{ $kurirHariIni->spx ?? 0 }}

                                    </div>

                                    <small>

                                        SPX

                                    </small>

                                </div>

                            </div>

                        </div>

                        <div class="col-6">

                            <div class="card border-dark">

                                <div class="card-body py-3 text-center">

                                    <div
                                        class="fw-bold text-dark fs-3"
                                        id="statJnt">

                                        {{ $kurirHariIni->jnt ?? 0 }}

                                    </div>

                                    <small>

                                        J&T

                                    </small>

                                </div>

                            </div>

                        </div>

                        <div class="col-6">

                            <div class="card border-danger">

                                <div class="card-body py-3 text-center">

                                    <div
                                        class="fw-bold text-danger fs-3"
                                        id="statAnteraja">

                                        {{ $kurirHariIni->anteraja ?? 0 }}

                                    </div>

                                    <small>

                                        Anteraja

                                    </small>

                                </div>

                            </div>

                        </div>

                        <div class="col-6">

                            <div class="card border-primary">

                                <div class="card-body py-3 text-center">

                                    <div
                                        class="fw-bold text-primary fs-3"
                                        id="statJne">

                                        {{ $kurirHariIni->jne ?? 0 }}

                                    </div>

                                    <small>

                                        JNE

                                    </small>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </div>

        </div>

    </div>

</div>

@endsection
@push('styles')

<style>

body{
    background:#f4f6fb;
}

.card{
    border:none;
    border-radius:18px;
    transition:.25s;
}

.card:hover{
    transform:translateY(-2px);
}

.scan-circle{

    width:110px;
    height:110px;

    margin:auto;

    border-radius:50%;

    display:flex;
    align-items:center;
    justify-content:center;

    background:#fff;
    border:4px solid #0d6efd22;

    color:#0d6efd;

    transition:.3s;

}

.scan-circle.active{

    background:#0d6efd;
    color:#fff;

    transform:scale(1.05);

}

#scanInput{

    height:78px;

    font-size:32px;
    font-weight:700;

    font-family:Consolas,monospace;

    letter-spacing:3px;

    border:2px solid #dee2e6;

    transition:.2s;

}

#scanInput:focus{

    border-color:#0d6efd;

    box-shadow:0 0 0 .25rem rgba(13,110,253,.15);

}

#manualScanBtn{

    width:90px;

    font-size:28px;

}

#scanStatus{

    min-height:210px;

    display:flex;

    align-items:center;
    justify-content:center;

    transition:.25s;

}

.status-success{

    background:#d1e7dd !important;
    border:1px solid #badbcc;

}

.status-danger{

    background:#f8d7da !important;
    border:1px solid #f5c2c7;

}

.status-warning{

    background:#fff3cd !important;
    border:1px solid #ffecb5;

}

.status-info{

    background:#cff4fc !important;
    border:1px solid #b6effb;

}

.stat-card{

    border-radius:16px;

    padding:16px;

    text-align:center;

    transition:.25s;

}

.stat-card:hover{

    transform:translateY(-3px);

}

.stat-number{

    font-size:34px;

    font-weight:700;

    line-height:1;

}

.stat-label{

    margin-top:8px;

    color:#6c757d;

    font-size:14px;

}

@media (max-width:991px){

    #scanInput{

        font-size:24px;

        letter-spacing:2px;

    }

}

@media (max-width:576px){

    #scanInput{

        height:64px;

        font-size:20px;

    }

    #manualScanBtn{

        width:70px;

    }

    .scan-circle{

        width:85px;
        height:85px;

    }

}

</style>

@endpush
@push('scripts')

<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>

<script>

$(function () {

    const scanInput = $('#scanInput');
    const scanStatus = $('#scanStatus');
    const btnScan = $('#manualScanBtn');

    let scanTimer = null;
    let scanning = false;

    /*
    |--------------------------------------------------------------------------
    | Fokus Scanner
    |--------------------------------------------------------------------------
    */

    function focusScanner() {

        setTimeout(function () {

            scanInput.trigger('focus');

        }, 100);

    }

    focusScanner();

    $(window).on('focus', function () {

        focusScanner();

    });

    $(document).on('click', function () {

        focusScanner();

    });

    scanInput.on('blur', function () {

        focusScanner();

    });

    /*
    |--------------------------------------------------------------------------
    | ESC Reset
    |--------------------------------------------------------------------------
    */

    $(document).on('keydown', function (e) {

        if (e.key === 'Escape') {

            scanInput.val('');

            resetStatus();

            focusScanner();

        }

    });

    /*
    |--------------------------------------------------------------------------
    | AUTO SCAN
    |--------------------------------------------------------------------------
    */

    scanInput.on('input', function () {

        clearTimeout(scanTimer);

        scanTimer = setTimeout(function () {

            const kode = scanInput.val().trim();

            if (kode.length < 5) {
                return;
            }

            if (scanning) {
                return;
            }

            scanning = true;

            scanResi(kode);

        }, 80);

    });

    /*
    |--------------------------------------------------------------------------
    | Tombol Manual
    |--------------------------------------------------------------------------
    */

    btnScan.on('click', function () {

        if (scanning) {
            return;
        }

        const kode = scanInput.val().trim();

        if (kode === '') {

            showMessage(

                '⚠ Silakan scan barcode terlebih dahulu.',

                'warning'

            );

            playBeep(350,0.25);

            focusScanner();

            return;

        }

        scanning = true;

        scanResi(kode);

    });

    /*
    |--------------------------------------------------------------------------
    | AJAX SCAN
    |--------------------------------------------------------------------------
    */

    function scanResi(kode) {

        scanInput.prop('disabled', true);

        btnScan.prop('disabled', true);

        showMessage(

            '🔄 Scanning...<br><strong>'+kode+'</strong>',

            'info'

        );

        $.ajax({

            url: "{{ route('packing.scan') }}",

            type: "POST",

            data: {

                _token: "{{ csrf_token() }}",

                no_pesanan: kode

            },

            success: function(response){

                scanning = false;

                scanInput.prop('disabled', false);

                btnScan.prop('disabled', false);

                if(!response.success){

                    playBeep(300,0.35);

                    playVoice('error');

                    showMessage(

                        response.message,

                        'danger'

                    );

                    scanInput.select();

                    focusScanner();

                    return;

                }

                playBeep(900,0.18);

                let voice = response.ekspedisi;

                if(!['spx','jnt','jne','anteraja'].includes(voice)){

                    voice='success';

                }

                playVoice(voice);

                showMessage(

                    response.message,

                    'success',

                    response

                );

                /*
                |--------------------------------------------------------------------------
                | Update Statistik
                |--------------------------------------------------------------------------
                */

                if(response.hari_ini!==undefined){

                    $('#statHariIni').text(response.hari_ini);

                }

                if(response.remaining_proses!==undefined){

                    $('#statBelumDikirim').text(response.remaining_proses);

                }

                if(response.kurir_hari_ini){

                    $('#statSpx').text(response.kurir_hari_ini.spx ?? 0);

                    $('#statJnt').text(response.kurir_hari_ini.jnt ?? 0);

                    $('#statAnteraja').text(response.kurir_hari_ini.anteraja ?? 0);

                    $('#statJne').text(response.kurir_hari_ini.jne ?? 0);

                }

                scanInput.val('');

                focusScanner();

            },

            error:function(xhr){

                scanning=false;

                scanInput.prop('disabled',false);

                btnScan.prop('disabled',false);

                let pesan='❌ Terjadi kesalahan.';

                if(xhr.responseJSON && xhr.responseJSON.message){

                    pesan=xhr.responseJSON.message;

                }

                playBeep(250,0.35);

                playVoice('error');

                showMessage(

                    pesan,

                    'danger'

                );

                scanInput.select();

                focusScanner();

            }

        });

    }
        /*
    |--------------------------------------------------------------------------
    | STATUS MESSAGE
    |--------------------------------------------------------------------------
    */

    function showMessage(message, type = 'info', data = null) {

        scanStatus
            .removeClass(
                'status-success status-danger status-warning status-info'
            )
            .addClass('status-' + type);

        let icon = 'bi-info-circle-fill';

        switch (type) {

            case 'success':
                icon = 'bi-check-circle-fill';
                break;

            case 'danger':
                icon = 'bi-x-circle-fill';
                break;

            case 'warning':
                icon = 'bi-exclamation-triangle-fill';
                break;

            case 'info':
                icon = 'bi-arrow-repeat';
                break;

        }

        let html = `
            <div class="w-100 text-center">

                <i class="bi ${icon}"
                    style="font-size:64px;"></i>

                <div class="mt-3 fw-bold fs-4">

                    ${message}

                </div>
        `;

        if (data && data.success) {

            html += `

                <hr class="my-3">

                <table class="table table-borderless table-sm mb-0 text-start">

                    <tr>

                        <td width="35%" class="text-muted">
                            No. Pesanan
                        </td>

                        <td class="fw-semibold">
                            ${data.no_pesanan ?? '-'}
                        </td>

                    </tr>

                    <tr>

                        <td class="text-muted">
                            No. Resi
                        </td>

                        <td class="fw-semibold">
                            ${data.no_resi ?? '-'}
                        </td>

                    </tr>

                    <tr>

                        <td class="text-muted">
                            Kurir
                        </td>

                        <td class="fw-semibold">
                            ${(data.ekspedisi ?? '-').toUpperCase()}
                        </td>

                    </tr>

                    <tr>

                        <td class="text-muted">
                            Jam Scan
                        </td>

                        <td class="fw-semibold">
                            ${data.scan_time ?? '-'}
                        </td>

                    </tr>

                </table>

            `;

        }

        html += `</div>`;

        scanStatus.html(html);

    }

    /*
    |--------------------------------------------------------------------------
    | RESET STATUS
    |--------------------------------------------------------------------------
    */

    function resetStatus() {

        scanStatus
            .removeClass(
                'status-success status-danger status-warning'
            )
            .addClass('status-info');

        scanStatus.html(`

            <div class="text-center">

                <i class="bi bi-qr-code-scan"
                   style="font-size:64px;"></i>

                <h4 class="mt-3 mb-2">

                    Siap Scan

                </h4>

                <div class="text-muted">

                    Scan No. Resi atau No. Pesanan

                </div>

            </div>

        `);

    }

    /*
    |--------------------------------------------------------------------------
    | AUDIO BEEP
    |--------------------------------------------------------------------------
    */

    const audioContext =
        new(window.AudioContext || window.webkitAudioContext)();

    function playBeep(freq = 800, duration = 0.15) {

        try {

            const oscillator =
                audioContext.createOscillator();

            const gain =
                audioContext.createGain();

            oscillator.connect(gain);

            gain.connect(audioContext.destination);

            oscillator.frequency.value = freq;

            oscillator.type = 'sine';

            gain.gain.value = 0.20;

            oscillator.start();

            oscillator.stop(audioContext.currentTime + duration);

        }

        catch(e){}

    }

    /*
    |--------------------------------------------------------------------------
    | VOICE
    |--------------------------------------------------------------------------
    */

    function playVoice(file){

        try{

            const allow = [
                'spx',
                'jnt',
                'jne',
                'anteraja',
                'success',
                'error'
            ];

            if(!allow.includes(file)){

                file='success';

            }

            const audio =
                new Audio('/sounds/'+file+'.mp3');

            audio.volume = 1;

            audio.play();

        }

        catch(e){}

    }
        /*
    |--------------------------------------------------------------------------
    | JAM DIGITAL (OPSIONAL)
    |--------------------------------------------------------------------------
    */

    function updateClock() {

        const now = new Date();

        const jam = String(now.getHours()).padStart(2, '0');
        const menit = String(now.getMinutes()).padStart(2, '0');
        const detik = String(now.getSeconds()).padStart(2, '0');

        $('#clock').text(`${jam}:${menit}:${detik} WIB`);

    }

    if ($('#clock').length) {

        updateClock();

        setInterval(updateClock, 1000);

    }

    /*
    |--------------------------------------------------------------------------
    | AUTO FOCUS SCANNER
    |--------------------------------------------------------------------------
    */

    $(window).on('focus', function () {

        if (!scanning) {

            focusScanner();

        }

    });

    scanInput.on('blur', function () {

        if (!scanning) {

            setTimeout(function () {

                focusScanner();

            }, 100);

        }

    });

    /*
    |--------------------------------------------------------------------------
    | AUTO SELECT INPUT
    |--------------------------------------------------------------------------
    */

    scanInput.on('focus', function () {

        $(this).select();

    });

    /*
    |--------------------------------------------------------------------------
    | INISIALISASI
    |--------------------------------------------------------------------------
    */

    resetStatus();

    focusScanner();

});

</script>

@endpush