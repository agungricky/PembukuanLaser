let selectedFont = "";
let selectedColor = "";

document.getElementById("applyChangesButton")?.addEventListener("click", () => {
    document.getElementById("downloadScreenshotButton").style.display = "block";
});

document.getElementById("font")?.addEventListener("change", function () {
    selectedFont = this.value;

    const fontMap = {
        f1: "AdriaDeco",
        f2: "Aeroblade DEMO",
        f3: "airstrike",
        f4: "BernardMTStd-Condensed",
        f5: "Bismillah Script",
        f6: "COOPER BL",
        f7: "Crizen",
        f8: "Design System C W01 900R",
        f9: "Ductus W01 Bold",
        f10: "flamenco-d",
        f11: "gang of three",
        f12: "Helvetica Black Condensed",
        f13: "hemi head bd it",
        f14: "Henshin",
        f15: "Jacksilver",
        f16: "JAPANESE_2020",
        f17: "La Macchina",
        f18: "LTAtomatic",
        f19: "MACHINEN",
        f20: "Marmellata(Jam)_demo",
        f21: "No Seven Bold",
        f22: "Osaka San",
        f23: "Planet Kosmos",
        f24: "Rockabilly",
        f25: "Sketter DEMO",
        f26: "Slantblaze Pro",
        f27: "Transformers Movie",
        f28: "VANGO-Regular",
        f29: "Vermin Vibes",
        f30: "vespa font",
    };

    const fontFamily = fontMap[selectedFont] || "inherit";
    this.style.fontFamily = `'${fontFamily}', sans-serif`;
});

document.getElementById("warna")?.addEventListener("change", function () {
    selectedColor = this.value;
});

document.getElementById("applyChangesButton")?.addEventListener("click", () => {
    const tampilEmblem = document.getElementById("tampilEmblem");
    const kata = document.getElementById("inputEmblem").value;
    tampilEmblem.innerText = kata;

    const isMobile = window.innerWidth <= 768;

    const fontMap = {
        f1: { family: "AdriaDeco", size: isMobile ? "25px" : "45px" },
        f2: { family: "Aeroblade DEMO", size: isMobile ? "25px" : "45px" },
        f3: { family: "airstrike", size: isMobile ? "27px" : "45px" },
        f4: {
            family: "BernardMTStd-Condensed",
            size: isMobile ? "35px" : "50px",
        },
        f5: { family: "Bismillah Script", size: isMobile ? "30px" : "45px" },
        f6: { family: "COOPER BL", size: isMobile ? "23px" : "40px" },
        f7: { family: "Crizen", size: isMobile ? "28px" : "45px" },
        f8: {
            family: "Design System C W01 900R",
            size: isMobile ? "28px" : "45px",
        },
        f9: { family: "Ductus W01 Bold", size: isMobile ? "28px" : "45px" },
        f10: { family: "flamenco-d", size: isMobile ? "32px" : "50px" },
        f11: { family: "gang of three", size: isMobile ? "28px" : "45px" },
        f12: {
            family: "Helvetica Black Condensed",
            size: isMobile ? "28px" : "45px",
        },
        f13: { family: "hemi head bd it", size: isMobile ? "28px" : "45px" },
        f14: { family: "Henshin", size: isMobile ? "30px" : "45px" },
        f15: { family: "Jacksilver", size: isMobile ? "25px" : "45px" },
        f16: { family: "JAPANESE_2020", size: isMobile ? "25px" : "45px" },
        f17: { family: "La Macchina", size: isMobile ? "22px" : "40px" },
        f18: { family: "LTAtomatic", size: isMobile ? "28px" : "45px" },
        f19: { family: "MACHINEN", size: isMobile ? "40px" : "53px" },
        f20: {
            family: "Marmellata(Jam)_demo",
            size: isMobile ? "30px" : "45px",
        },
        f21: { family: "No Seven Bold", size: isMobile ? "28px" : "45px" },
        f22: { family: "Osaka San", size: isMobile ? "28px" : "45px" },
        f23: { family: "Planet Kosmos", size: isMobile ? "28px" : "45px" },
        f24: { family: "Rockabilly", size: isMobile ? "25px" : "45px" },
        f25: { family: "Sketter DEMO", size: isMobile ? "15px" : "25px" },
        f26: { family: "Slantblaze Pro", size: isMobile ? "20px" : "35px" },
        f27: { family: "Transformers Movie", size: isMobile ? "28px" : "45px" },
        f28: { family: "VANGO-Regular", size: isMobile ? "28px" : "45px" },
        f29: { family: "Vermin Vibes", size: isMobile ? "28px" : "45px" },
        f30: { family: "vespa font", size: isMobile ? "28px" : "45px" },
    };

    if (fontMap[selectedFont]) {
        tampilEmblem.style.fontFamily = `'${fontMap[selectedFont].family}', sans-serif`;
        tampilEmblem.style.fontSize = fontMap[selectedFont].size;
    }

    tampilEmblem.className = "emblem-base"; // Reset semua warna, simpan kelas dasar

    switch (selectedColor) {
        case "Merah":
            tampilEmblem.classList.add("merah");
            break;
        case "Biru":
            tampilEmblem.classList.add("biru");
            break;
        case "Silver":
            tampilEmblem.classList.add("abu");
            break;
        case "Gold":
            tampilEmblem.classList.add("text-warning");
            break;
        case "Hijau":
            tampilEmblem.classList.add("hijau");
            break;
        case "Hitam":
            tampilEmblem.classList.add("hitam");
            break;
        case "Rose Gold":
            tampilEmblem.classList.add("rose");
            break;
    }
});

document
    .getElementById("btnKirim")
    ?.addEventListener("click", function (event) {
        event.preventDefault();

        const waNumber = "6285216458520";
        const warna = document.getElementById("warna").value;
        const namaEmblem = document.getElementById("inputEmblem").value;
        let fontSelect = document.getElementById("font");
        let fontEmblem = fontSelect.options[fontSelect.selectedIndex].text;

        if (!namaEmblem) {
            alert("Silakan masukkan nama emblem terlebih dahulu!");
            return;
        }

        let pesan = `Halo, saya ingin pesan Emblem Akrilik warna ${warna} dengan nama '${namaEmblem}', menggunakan font '${fontEmblem}'`;
        const waUrl = `https://wa.me/${waNumber}?text=${encodeURIComponent(
            pesan
        )}`;
        window.open(waUrl, "_blank");
    });

function downloadScreenshot() {
    const content = document.getElementById("downloadEmblem");
    if (!content) {
        console.error('Elemen dengan ID "downloadEmblem" tidak ditemukan.');
        return;
    }

    const fileName = `${document.getElementById("inputEmblem").value}.png`;

    domtoimage
        .toBlob(content, { scale: 2 })
        .then((blob) => {
            const link = document.createElement("a");
            link.href = URL.createObjectURL(blob);
            link.download = fileName;
            link.click();
        })
        .catch((error) => {
            console.error("Kesalahan saat mengambil screenshot:", error);
            alert(
                "Terjadi kesalahan saat mengambil screenshot. Silakan coba lagi."
            );
        });
}
