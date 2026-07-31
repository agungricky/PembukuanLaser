<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>King Plat</title>
  <link rel="stylesheet" href="{{ asset('css/landing.css') }}">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.3/font/bootstrap-icons.css" rel="stylesheet"
    crossorigin="anonymous">
  <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet"
    crossorigin="anonymous">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
    integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
</head>
<body style="background: #EDF1F4;">
<div class="container">
    <div class="row">
        <div class="col-12 col-md-8 mt-3 mx-auto">
            <div class="card shadow rounded">
                <div class="card-body rounded">
                    <div class="col-12 col-md-10 mx-auto">
                        <div class="product-selection mt-3 mb-2">
                            <h2 class="font-weight-bold">Pilihan Jenis Kendaraan:</h2>

                                <div class="color-options">
                                    <table class="table table-bordered">
                                        <tbody>
                                            <tr>
                                                <td class="align-items-center">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" name="kendaraan" id="warna1" value="motor" checked>
                                                        <label class="form-check-label fw-bold text-secondary fs-5 text-wrap w-75" for="warna1">
                                                            Motor
                                                        </label>
                                                    </div>
                                                </td>
                                                <td class="align-items-center">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" name="kendaraan" id="warna2" value="mobil">
                                                        <label class="form-check-label fw-bold text-secondary fs-5 text-wrap w-75" for="warna2">
                                                            Mobil
                                                        </label>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="plate-number">
                                    <h5 class="m-0 mt-3 mb-1">Nomor Plat:</h5>
                                    <p class="text-secondary m-0">Contoh:</p>
                                    <p class="text-secondary m-0">AG 6459 EAD</p>
                                    <p class="text-secondary m-0">12 . 29</p>
                                    <table class="mt-2 mb-1">
                                        <tr>
                                            <td style="width: 25%;"><input type="text" id="inputPlatKode" class="form-control" name="plat_kode" placeholder="AG" maxlength="2" required></td>
                                            <td style="width: 45%;"><input type="text" id="inputPlatNomor" class="form-control" name="plat_nomor" placeholder="6459" maxlength="4" required></td>
                                            <td style="width: 30%;"><input type="text" id="inputPlatHuruf" class="form-control" name="plat_huruf" placeholder="EAD" maxlength="3" required></td>
                                        </tr>
                                    </table>
                                    <table>
                                        <tr>
                                            <td style="width: 70%;"><input type="text" id="inputPlatNama" class="form-control" name="plat_nama" placeholder="Request Nama" maxlength="13"></td>
                                            <td style="width: 30%;"><input type="text" id="inputPlatTahun" class="form-control" name="plat_tahun" placeholder="12 . 29" maxlength="7"></td>
                                        </tr>
                                    </table>
                                    <div class="mt-1">
                                        <p class="text-danger">*Isian dan format sesuai contoh.</p>
                                    </div>
                                </div>

                                <div class="form-group mt-3">
                                    <label for="warna"><b>Pilih Warna</b></label>
                                    <select class="form-control" id="warna" name="warna">
                                        <option value="led-putih-putih">Dasar Putih Angka Hitam</option>
                                        <option value="led-putih-hitam">Dasar hitam Angka Putih</option>
                                        <option value="putih-hitam">Dasar hitam Angka Gold</option>
                                        <option value="putih-putih">Dasar putih Angka Gold</option>
                                        <option value="putih-silver">Dasar putih Angka Silver</option>
                                        <option value="hitam-silver">Dasar hitam Angka Silver</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="font"><b>Pilih Font</b></label>
                                    <select class="form-control" id="font" name="font">
                                        <option value="font-a">BernardMTCondensed</option>
                                        <option value="font-b">FFGoodPro</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="lineType"><b>Pilih Font Jenis Garis</b></label>
                                    <select class="form-control" id="lineType" name="lineType">
                                        <option value="no-line">Tanpa Garis</option>
                                        <option value="straight-line">Garis Lurus</option>
                                        <option value="heartbeat-line">Garis Detak Jantung</option>
                                    </select>
                                </div>         

                                <div class="form-group" id="logoTypeContainer">
                                    <label for="logoType"><b>Pilih Logo</b></label>
                                    <select class="form-control" id="logoType" name="logoType">
                                        <option value="no-logo">Tanpa Logo</option>
                                        <option value="ig">Instagram</option>
                                        <option value="yt">YouTube</option>
                                        <option value="fb">Facebook</option>
                                        <option value="tt">TikTok</option>
                                        <option value="wa">WhatsApp</option>
                                    </select>
                                </div>    
                                <button type="button" id="applyChangesButton" class="btn btn-success btn-lg mt-1">
                                    <i class="bi bi-eye"></i> Lihat Plat Anda
                                </button>

                                <div class="d-flex justify-content-center align-items-center mt-5">
                                    <div class="display-tengah" id="display-plat">
                                        <div class="display-section text-center mt-4 mb-4 position-relative">
                                            
                                            <img id="produkGambar" src="{{ asset('images/dasarputih.png') }}" class="img-fluid">
                                            
                                            <div id="platNomorTampil" class="position-absolute top-0 start-50 translate-middle-x text-dark fw-bold d-flex" style="font-size: 3rem;">
                                                <table class="plat-nomor-table">
                                                    <tr>
                                                        <td class="plat-kode">
                                                            <span id="platKodeTampil"></span>
                                                        </td>
                                                        <td class="plat-nomor-tengah">
                                                            <span id="platNomorTengah"></span>
                                                        </td>
                                                        <td class="plat-huruf">
                                                            <span id="platHurufTampil"></span>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </div>

                                            <div class="position-absolute top-0 start-50 translate-middle-x d-flex">
                                                <table class="detak-table">
                                                    <tr>
                                                        <td class="detak-td">
                                                            <img id="detakGambar" src="" class="detak-img">
                                                        </td>
                                                    </tr>
                                                </table>
                                            </div>

                                            <div class="position-absolute top-0 start-50 translate-middle-x d-flex">
                                                <table class="plat-table">
                                                    <tr>
                                                        <td id="trplatTahunTampil" class="plat-td">
                                                            <div id="platTahunTampil" class="plat-tahun text-dark fw-bold"></div>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </div>

                                            <div class="position-absolute top-0 start-50 translate-middle-x d-flex">
                                                <table class="plat-nama-table">
                                                    <tr>
                                                        <td id="trplatNamaTampil" class="plat-nama-td">
                                                            <div id="platNamaTampil" class="plat-nama text-dark fw-bold"></div>
                                                        </td>
                                                    </tr>
                                                </table>
                                            </div>

                                            <div class="position-absolute top-0 start-50 translate-middle-x d-flex">
                                            <table id="trlogoGambar" class="logo-table">
                                                <tr>
                                                    <td class="logo-td">
                                                        <img id="logoGambar" src="" class="img-fluid logo-img" crossOrigin="anonymous">
                                                    </td>
                                                </tr>
                                            </table>
                                        </div>
                                           
                                        </div>
                                    </div>
                                </div>

                                <div class="text-center mt-2">
                                    <button id="downloadScreenshotButton" onclick="downloadScreenshot()" class="btn btn-primary">Download Gambar</button>
                                </div>

                                <div class="mt-5 mb-5">
                                    <a href="https://wa.me/6282139671019" id="btnKirim" class="btn btn-success btn-lg btn-block btn-custom" target="_blank">
                                        Beli Sekarang
                                        <i class="bi bi-arrow-right-circle"></i>
                                    </a>
                                </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="footer text-center py-4 mt-5">
    <span class="fw-bold footer-text">Powered by Ka-Page</span>
    <a href="mailto:kaunangbisnis1@gmail.com" class="d-block text-secondary mt-2 footer-email">kaunangbisnis1@gmail.com</a>
    <div class="mt-2 footer-year">&copy; 2024</div>
</div>
</body>
<script src="{{ asset('js/app.js') }}"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/dom-to-image/2.6.0/dom-to-image.min.js"></script>
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

</html>