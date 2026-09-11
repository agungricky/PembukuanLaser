document.addEventListener('DOMContentLoaded', function () {

    const inputNama =
        document.getElementById('inputNamaSound');

    const namaSound =
        document.getElementById('namaSoundTampil');

    const namaArea =
        document.getElementById('namaSoundArea');

    const captureSound =
        document.getElementById('captureSound');

    const btnDownload =
        document.getElementById('btnDownloadSound');


    /* ==========================================
       VALIDASI
    ========================================== */

    if (
        !inputNama ||
        !namaSound ||
        !namaArea ||
        !captureSound ||
        !btnDownload
    ) {

        console.error(
            'Element halaman Sound tidak lengkap.'
        );

        return;
    }


    /* ==========================================
       NORMALISASI NAMA
    ========================================== */

    function bersihkanNama(nilai) {

        return String(nilai || '')
            .toUpperCase()
            .replace(/\s+/g, ' ')
            .trimStart();
    }


    /* ==========================================
       RAPATKAN HORIZONTAL

       FONT-SIZE TIDAK PERNAH DIUBAH.
       TINGGI TULISAN TETAP.
    ========================================== */

    function sesuaikanLebarNama() {

    const scaleNormal = 0.86;

    /* reset hanya lebar, posisi tidak pernah disentuh */
    namaSound.style.setProperty(
        '--scale-x',
        '1'
    );

    const lebarArea =
        namaArea.clientWidth;

    const lebarNama =
        namaSound.offsetWidth;

    if (
        lebarArea <= 0 ||
        lebarNama <= 0
    ) {
        return;
    }

    const batasLebar =
        lebarArea * 0.98;

    let scaleX = scaleNormal;

    if (
        lebarNama * scaleNormal >
        batasLebar
    ) {
        scaleX =
            batasLebar / lebarNama;
    }

    namaSound.style.setProperty(
        '--scale-x',
        scaleX.toFixed(4)
    );
}

    /* ==========================================
       UPDATE NAMA
    ========================================== */

    function updateNama() {

        const nama =
            bersihkanNama(
                inputNama.value
            );


        namaSound.textContent =
            nama !== ''
                ? nama
                : 'NAMA';


        requestAnimationFrame(
            function () {

                sesuaikanLebarNama();

            }
        );
    }


    /* ==========================================
       INPUT REALTIME
    ========================================== */

    inputNama.addEventListener(
        'input',
        function () {

            /*
                Tidak ada pembatas jumlah huruf.
            */

            inputNama.value =
                inputNama.value
                    .toUpperCase()
                    .replace(/\s+/g, ' ');


            updateNama();
        }
    );


    /* ==========================================
       RESIZE WINDOW
    ========================================== */

    let resizeTimer;


    window.addEventListener(
        'resize',
        function () {

            clearTimeout(
                resizeTimer
            );


            resizeTimer =
                setTimeout(
                    function () {

                        sesuaikanLebarNama();

                    },
                    100
                );
        }
    );


    /* ==========================================
       NAMA FILE
    ========================================== */

    function buatNamaFile(nama) {

        let hasil =
            String(nama || 'NAMA')
                .toUpperCase()
                .trim()
                .replace(
                    /[^A-Z0-9]+/g,
                    '_'
                )
                .replace(
                    /^_+|_+$/g,
                    ''
                );


        if (hasil === '') {
            hasil = 'NAMA';
        }


        return hasil + '_SOUND.png';
    }


    /* ==========================================
       DOWNLOAD
    ========================================== */

    btnDownload.addEventListener(
        'click',
        function () {

            const nama =
                bersihkanNama(
                    inputNama.value
                ) || 'NAMA';


            const targetWidth =
                1600;


            const width =
                captureSound.offsetWidth;


            const height =
                captureSound.offsetHeight;


            if (
                width <= 0 ||
                height <= 0
            ) {

                alert(
                    'Preview belum siap.'
                );

                return;
            }


            const scale =
                targetWidth / width;


            btnDownload.disabled =
                true;


            btnDownload.textContent =
                'Memproses...';


            domtoimage
                .toPng(
                    captureSound,
                    {

                        width:
                            Math.round(
                                width * scale
                            ),

                        height:
                            Math.round(
                                height * scale
                            ),

                        style: {

                            transform:
                                `scale(${scale})`,

                            transformOrigin:
                                'top left',

                            width:
                                width + 'px',

                            height:
                                height + 'px'

                        }

                    }
                )

                .then(
                    function (dataUrl) {

                        const link =
                            document.createElement(
                                'a'
                            );


                        link.download =
                            buatNamaFile(
                                nama
                            );


                        link.href =
                            dataUrl;


                        document.body.appendChild(
                            link
                        );


                        link.click();


                        link.remove();

                    }
                )

                .catch(
                    function (error) {

                        console.error(
                            'Gagal membuat PNG:',
                            error
                        );


                        alert(
                            'Gagal membuat gambar.'
                        );

                    }
                )

                .finally(
                    function () {

                        btnDownload.disabled =
                            false;


                        btnDownload.textContent =
                            'Download PNG';

                    }
                );

        }
    );


    /* ==========================================
       INITIAL
    ========================================== */

    function mulai() {

        updateNama();

    }


    if (document.fonts) {

        document.fonts.ready.then(
            function () {

                mulai();

            }
        );

    } else {

        mulai();

    }

});
