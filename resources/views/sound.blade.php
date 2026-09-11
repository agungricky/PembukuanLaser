<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>Custom Emblem Sound</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        rel="stylesheet"
        href="{{ asset('css/sound.css') }}"
    >
</head>

<body>

<div class="container sound-container">

    <div class="sound-card mx-auto">

        {{-- HEADER --}}
        <div class="sound-header">

            <h3 class="sound-title">
                Custom Emblem Sound
            </h3>

            <p class="sound-subtitle">
                Masukkan nama sound system Anda
            </p>

        </div>


        {{-- INPUT NAMA --}}
        <div class="mb-4">

            <label
                for="inputNamaSound"
                class="form-label fw-bold"
            >
                Nama Sound
            </label>

            <input
                type="text"
                id="inputNamaSound"
                class="form-control form-control-lg"
                placeholder="Contoh: KING"
                maxlength="24"
                autocomplete="off"
            >

            <div class="form-text">
                Ukuran nama akan otomatis disesuaikan
                jika terlalu panjang.
            </div>

        </div>


        {{-- PREVIEW --}}
        <div class="mb-3">

            <label class="form-label fw-bold">
                Preview
            </label>

            <div class="preview-wrapper">

                <div
                    id="captureSound"
                    class="sound-preview"
                >

                    {{-- TEMPLATE --}}
                    <img
                        id="templateSound"
                        class="sound-template"
                        src="{{ asset('images/emblem/template-01.svg') }}"
                        alt="Template Emblem Sound"
                        draggable="false"
                    >


                    {{-- AREA NAMA DINAMIS --}}
                    <div
                        id="namaSoundArea"
                        class="nama-sound-area"
                    >

                        <span
                            id="namaSoundTampil"
                            class="nama-sound"
                        >
                            NAMA
                        </span>

                    </div>

                </div>

            </div>

        </div>


        {{-- BUTTON --}}
        <div class="d-grid gap-2 mt-4">

            <button
                type="button"
                id="btnDownloadSound"
                class="btn btn-primary btn-lg"
            >
                Download PNG
            </button>

        </div>

    </div>

</div>


<script
    src="https://cdnjs.cloudflare.com/ajax/libs/dom-to-image/2.6.0/dom-to-image.min.js">
</script>

<script src="{{ asset('js/sound.js') }}"></script>

</body>
</html>
