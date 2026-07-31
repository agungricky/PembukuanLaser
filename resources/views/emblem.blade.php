<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>King Plat</title>

    <link rel="stylesheet" href="{{ asset('css/emblem.css') }}">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.3/font/bootstrap-icons.css"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
        integrity="sha512-..." crossorigin="anonymous" />

</head>
<body style="background: #EDF1F4;">
    <div class="container">
        <div class="row">
            <div class="col-12 col-md-8 mt-3 mx-auto">
                <div class="card shadow rounded">
                    <div class="card-body rounded">
                        <div class="col-12 col-md-10 mx-auto">
                            <div class="product-selection mt-3 mb-2">
                                <h2 class="text-center my-4 custom-title">
                                    Custom Emblem
                                </h2>
                                <div class="text-center mt-3">
                                    <img src="{{ asset('images/fontnew.jpg') }}" alt="Contoh Font"
                                        class="img-fluid rounded shadow">
                                </div>
                                <div class="form-group mt-3">
                                    <label class="mb-1"><b>Nama Emblem:</b></label>
                                    <input type="text" id="inputEmblem" class="form-control" name="plat_kode"
                                        placeholder="Masukan Nama Emblem" required>
                                </div>
                                <div class="form-group mt-2">
                                    <label for="font"><b>Pilih Font</b></label>
                                    <select id="font" name="font" class="form-control control-font">
                                        <option value="">Pilih Font</option>
                                        @foreach ($fonts as $font)
                                            <option value="{{ $font['value'] }}" style="font-family: {{ $font['css'] }};">
                                                {{ $font['label'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group mt-2">
                                    <label for="warna"><b>Warna Timbul</b></label>
                                    <select class="form-control" id="warna" name="warna">
                                        <option value="">Pilih Warna</option>
                                        <option value="Merah">Merah</option>
                                        <option value="Biru">Biru</option>
                                        <option value="Silver">Silver</option>
                                        <option value="Gold">Gold</option>
                                        <option value="Hijau">Hijau</option>
                                        <option value="Hitam">Hitam</option>
                                        <option value="Rose Gold">Rose Gold</option>
                                    </select>
                                </div>
                                <button type="button" id="applyChangesButton" class="btn btn-lg mt-2"
                                    style="background-color: #007bff; color: white; font-weight: bold; border: none;">
                                    <i class="bi bi-eye"></i> Lihat Emblem Anda
                                </button>
                                <div class="text-container mt-3" id="downloadEmblem">
                                    <div class="emblem-bottom text-warning outline-behind" id="tampilEmblem"></div>
                                </div>
                                <div class="d-flex justify-content-center mt-2 px-3">
                                    <button id="downloadScreenshotButton" onclick="downloadScreenshot()"
                                        class="btn btn-warning w-auto px-4" style="max-width: 280px; display: none;">
                                        <i class="fa fa-download me-2" aria-hidden="true"></i> Download Gambar
                                    </button>
                                </div>
                                <div class="mt-3 mb-5">
                                    <a href="https://wa.me/" id="btnKirim" class="btn btn-lg w-100"
                                        style="background-color: #007bff; color: white; font-weight: bold; border: none;"
                                        target="_blank">
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
        <a href="mailto:kaunangbisnis1@gmail.com"
            class="d-block text-secondary mt-2 footer-email">kaunangbisnis1@gmail.com</a>
        <div class="mt-2 footer-year">&copy; 2024</div>
    </div>
    <script src="{{ asset('js/emblem.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/dom-to-image/2.6.0/dom-to-image.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/0.4.1/html2canvas.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>