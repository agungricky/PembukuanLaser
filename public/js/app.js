document.getElementById("applyChangesButton").onclick = function() {
    document.getElementById("downloadScreenshotButton").style.display = "block";
};
document.getElementById("inputPlatNomor").addEventListener("input", function (event) {
    this.value = this.value.replace(/[^0-9]/g, '').slice(0, 4);
});
document.getElementById("inputPlatKode").addEventListener("input", function (event) {
    this.value = this.value.replace(/[^A-Z]/g, '').slice(0, 2);
});
document.getElementById("inputPlatHuruf").addEventListener("input", function (event) {
    this.value = this.value.replace(/[^A-Z]/g, '').slice(0, 3);
});

function downloadScreenshot() {
    const content = document.getElementById('display-plat');
    if (!content) {
        console.error('Elemen dengan ID "display-plat" tidak ditemukan.');
        return;
    }
    
    const platKode = document.getElementById("inputPlatKode").value;
    const platNomor = document.getElementById("inputPlatNomor").value;
    const platHuruf = document.getElementById("inputPlatHuruf").value;
    const fileName = `${platKode}_${platNomor}_${platHuruf}.png`;

    const options = {
        scale: 2,
    };

    domtoimage.toBlob(content, options)
        .then(blob => {
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = fileName;
            link.click();
        })
        .catch(function (error) {
            console.error('Kesalahan saat menghasilkan screenshot:', error);
            alert('Terjadi kesalahan saat mengambil screenshot. Silakan coba lagi.');
        });
}




document.querySelectorAll('input[name="kendaraan"]').forEach((radio) => {
    radio.addEventListener('change', function() {
        const selectedValue = this.value;
        const logoTypeContainer = document.getElementById('logoTypeContainer');
        const logoGambar = document.getElementById('logoGambar');

        if (selectedValue === 'mobil') {
            logoTypeContainer.style.display = 'none';
            logoGambar.style.display = 'none';
        } else {
            logoTypeContainer.style.display = 'block';
            logoGambar.style.display = 'block';
        }
    });
});

document.querySelector('input[name="kendaraan"]:checked').dispatchEvent(new Event('change'));

document.getElementById('applyChangesButton').addEventListener('click', function() {
    const selectedValue = document.querySelector('input[name="kendaraan"]:checked').value;
    const platNamaTampil = document.getElementById('platNamaTampil');
    const logoGambar = document.getElementById('logoGambar');
    const trplatTahunTampil = document.getElementById('trplatTahunTampil');
    const trplatNamaTampil = document.getElementById('trplatNamaTampil');

    if (selectedValue === 'mobil') {
        trplatTahunTampil.style.textAlign = 'center';
        trplatNamaTampil.style.textAlign = 'right';
        platNamaTampil.style.right = '24%';
        platNamaTampil.style.left = 'auto';
        logoGambar.style.display = 'none'; 
        logoGambar.style.visibility = 'hidden';
    } else {
        trplatTahunTampil.style.textAlign = 'right';
        trplatNamaTampil.style.textAlign = 'left';
        logoGambar.style.display = 'block';
        logoGambar.style.visibility = 'visible';
    }
});

let selectedFont = '';
let selectedLineType = '';
let selectedLogo = '';
let selectedColor = '';

document.getElementById('font').addEventListener('change', function() {
    selectedFont = this.value;
});

document.getElementById('lineType').addEventListener('change', function() {
    selectedLineType = this.value;
});

document.getElementById('logoType').addEventListener('change', function() {
    selectedLogo = this.value;
});

document.getElementById('warna').addEventListener('change', function() {
    selectedColor = this.value;
});

document.getElementById('applyChangesButton').addEventListener('click', function() {
    const elements = [
        document.getElementById('platKodeTampil'),
        document.getElementById('platNomorTengah'),
        document.getElementById('platHurufTampil'),
        document.getElementById('platTahunTampil'),
        document.getElementById('platNamaTampil')
    ];

    elements.forEach(element => {
        switch (selectedFont) {
            case 'font-a':
                element.style.fontFamily = 'BernardMTCondensed, sans-serif';
                break;
            case 'font-b':
                element.style.fontFamily = 'FFGoodPro, sans-serif';
                break;
        }
    });

    const detakGambar = document.getElementById('detakGambar');
    switch (selectedLineType) {
        case 'no-line':
            detakGambar.style.display = 'none';
            break;
        case 'straight-line':
            detakGambar.style.display = 'block';
            detakGambar.src = "/images/lurus.png";
            break;
        case 'heartbeat-line':
            detakGambar.style.display = 'block';
            detakGambar.src = "/images/detak.png";
            break;
    }

    const trlogoGambar = document.getElementById('trlogoGambar');
    const logoGambar = document.getElementById('logoGambar');
    switch (selectedLogo) {
        case 'no-logo':
            logoGambar.style.display = 'none';
            break;
        case 'ig':
            logoGambar.style.display = 'block';
            logoGambar.src = "/images/ig.png";
            logoGambar.style.maxWidth = '20px';
            trlogoGambar.style.left = '-100px';
            break;
        case 'yt':
            logoGambar.style.display = 'block';
            logoGambar.src = "/images/yt.png";
            logoGambar.style.maxWidth = '22px';
            trlogoGambar.style.left = '-102px';
            break;
        case 'fb':
            logoGambar.style.display = 'block';
            logoGambar.src = "/images/fb.png";
            logoGambar.style.maxWidth = '21px';
            trlogoGambar.style.left = '-101px';
            break;
        case 'tt':
            logoGambar.style.display = 'block';
            logoGambar.src = "/images/tt.png";
            logoGambar.style.maxWidth = '23px';
            trlogoGambar.style.left = '-101px';
            break;
        case 'wa':
            logoGambar.style.display = 'block';
            logoGambar.src = "/images/wa.png";
            logoGambar.style.maxWidth = '22px';
            trlogoGambar.style.left = '-101px';
            break;
    }

    const img = document.getElementById('produkGambar');
    elements.forEach(element => {
        element.classList.remove('text-dark', 'text-warning', 'text-white', 'text-silver');
    });
    switch (selectedColor) {
        case 'putih-hitam':
            img.src = "/images/dasarhitam.png";
            elements.forEach(element => element.classList.add('text-warning'));
            break;
        case 'putih-putih':
            img.src = "/images/dasarputih.png";
            elements.forEach(element => element.classList.add('text-warning'));
            break;
        case 'led-putih-hitam':
            img.src = "/images/dasarhitam.png";
            elements.forEach(element => element.classList.add('text-white'));
            break;
        case 'led-putih-putih':
            img.src = "/images/dasarputih.png";
            elements.forEach(element => element.classList.add('text-dark'));
            break;
        case 'putih-silver':
            img.src = "/images/dasarputih.png";
            elements.forEach(element => element.classList.add('text-silver'));
            break;
        case 'hitam-silver':
            img.src = "/images/dasarhitam.png";
            elements.forEach(element => element.classList.add('text-silver'));
            break;
    }
});

document.getElementById('applyChangesButton').addEventListener('click', function() {
    const kode = document.getElementById('inputPlatKode').value;
    const nomor = document.getElementById('inputPlatNomor').value;
    const huruf = document.getElementById('inputPlatHuruf').value;
    const tahun = document.getElementById('inputPlatTahun').value;
    const nama = document.getElementById('inputPlatNama').value;

    const platKodeTampil = document.getElementById('platKodeTampil');
    platKodeTampil.innerText = kode;
    const platNomorTengah = document.getElementById('platNomorTengah');
    platNomorTengah.innerText = nomor;
    const platHurufTampil = document.getElementById('platHurufTampil');
    platHurufTampil.innerText = huruf;
    const platTahunTampil = document.getElementById('platTahunTampil');
    platTahunTampil.innerText = tahun;
    const platNamaTampil = document.getElementById('platNamaTampil');
    platNamaTampil.innerText = nama;
});

const btnKirim = document.getElementById('btnKirim');
const selectWarna = document.getElementById('warna');
const radios = document.querySelectorAll('input[name="kendaraan"]');

btnKirim.addEventListener('click', function(event) {
    event.preventDefault();
    const waNumber = '6285216458520';
    const warna = selectWarna.value;
    let kendaraan = '';

    for (let radio of radios) {
        if (radio.checked) {
            kendaraan = radio.getAttribute('value');
            break;
        }
    }

    let pesan;
    switch (warna) {
        case 'led-putih-putih':
            pesan = `Halo, saya ingin melakukan pemesanan Plat ${kendaraan} Akrilik Dasar Putih Angka Hitam`;
            break;
        case 'led-putih-hitam':
            pesan = `Halo, saya ingin melakukan pemesanan Plat ${kendaraan} Akrilik Dasar Hitam Angka Putih`;
            break;
        case 'putih-hitam':
            pesan = `Halo, saya ingin melakukan pemesanan Plat ${kendaraan} Akrilik Dasar Hitam Angka Gold`;
            break;
        case 'putih-putih':
            pesan = `Halo, saya ingin melakukan pemesanan Plat ${kendaraan} Akrilik Dasar Putih Angka Gold`;
            break;
        default:
            pesan = 'Silakan pilih warna terlebih dahulu!';
    }

    if (pesan) {
        btnKirim.href = `https://wa.me/${waNumber}?text=${encodeURIComponent(pesan)}`;
        window.open(btnKirim.href, '_blank');
    } else {
        alert('Silakan pilih kendaraan terlebih dahulu!');
    }
});
