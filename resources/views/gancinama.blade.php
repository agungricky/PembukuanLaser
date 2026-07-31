<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Desain Gantungan Nama</title>
</head>
<style>
  @font-face {
    font-family: "Darling Coffee";
    src: url("/ganci_nama/fonts/Darling Coffee.otf") format("opentype");
    font-display: swap;
  }

  @font-face {
    font-family: "Magic Sound";
    src: url("/ganci_nama/fonts/Magic Sound.ttf") format("truetype");
    font-display: swap;
  }

  :root {
    --panel: #1a1625;
    --text: #e8e6f0;
    --muted: #9b95b3;
    --accent: #00d4ff;
    --bg: #0f0d16;
    --border: #2d2540;
    --button: #6366f1;
    --button-hover: #4f46e5;
  }

  * {
    box-sizing: border-box;
  }

  body {
    font-family: system-ui, -apple-system, "Poppins", Arial, sans-serif;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    background: linear-gradient(135deg, #0f0d16 0%, #1a1625 100%);
    margin: 0;
    padding: 24px;
    color: var(--text);
  }

  .container {
    width: min(920px, 100%);
    text-align: center;
  }

  .controls {
    display: flex;
    gap: 10px;
    justify-content: center;
    flex-wrap: wrap;
    margin: 14px 0 18px;
  }

  input,
  select,
  button {
    padding: 10px 12px;
    font-size: 16px;
    border-radius: 10px;
    border: 1px solid var(--border);
    outline: none;
    transition: all 0.3s ease;
  }

  input,
  select {
    background: var(--panel);
    color: var(--text);
    border: 1px solid var(--border);
  }

  input:focus,
  select:focus {
    border-color: var(--accent);
    box-shadow: 0 0 0 3px rgba(0, 212, 255, 0.1);
  }

  button {
    cursor: pointer;
    background: var(--button);
    color: #fff;
    border: none;
    padding: 10px 16px;
    font-weight: 500;
  }

  button:hover {
    background: var(--button-hover);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
  }

  button:active {
    transform: translateY(0);
  }

  .preview {
    margin-top: 18px;
    background: var(--panel);
    border: 1px solid var(--border);
    border-radius: 14px;
    padding: 14px;
    display: grid;
    gap: 10px;
    justify-items: center;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4);
  }

  #canvasWrapper {
    position: relative;
    display: inline-block;
  }

  #previewCanvas {
    width: 100%;
    max-width: 850px;
    height: auto;
    background: transparent;
    display: block;
    border-radius: 8px;
  }

  .resize-handle {
    position: absolute;
    background: var(--accent);
    z-index: 10;
    transition: background 0.2s ease;
  }

  .resize-handle:hover {
    background: var(--button);
  }

  .resize-top,
  .resize-bottom {
    height: 6px;
    left: 0;
    right: 0;
    cursor: ns-resize;
  }

  .resize-left,
  .resize-right {
    width: 6px;
    top: 0;
    bottom: 0;
    cursor: ew-resize;
  }

  .resize-top {
    top: -3px;
  }

  .resize-bottom {
    bottom: -3px;
  }

  .resize-left {
    left: -3px;
  }

  .resize-right {
    right: -3px;
  }

  .hint {
    color: var(--muted);
    font-size: 14px;
  }

  #exportPngBtn {
    margin-top: 8px;
  }
</style>

<body>
  <div class="container">
    <h1>Desain Gantungan Nama</h1>

    <div class="controls">
      <input type="text" id="nameInput" placeholder="Masukkan namamu" autocomplete="off" />

      <select id="characterSelect">
        <option value="none">Tanpa karakter</option>
        <option value="Smart">Smart</option>
        <option value="Cute">Cute</option>
        <option value="Active">Active</option>
        <option value="Calm">Calm</option>
        <option value="Religi">Religi</option>
      </select>

      <select id="fontSelect">
        <option value="Darling Coffee">Darling Coffee</option>
        <option value="Magic Sound">Magic Sound</option>
      </select>

      <select id="schemeSelect">
        <option value="pelangi">Warna pelangi</option>
        <option value="hitam">Hitam</option>
        <option value="sage">Sage</option>
        <option value="mustard">Mustard</option>
        <option value="pink">Pink</option>
        <option value="blue">Blue</option>
        <option value="navy">Navy</option>
        <option value="white">White</option>
      </select>

      <button id="generateBtn" type="button">Generate</button>
    </div>

    <div id="preview" class="preview">
      <div id="canvasWrapper">
        <canvas id="previewCanvas" width="850" height="220"></canvas>
      </div>
      <div class="hint" id="hintText">
        Masukkan nama lalu klik Generate.
      </div>
    </div>

    <button id="exportPngBtn" type="button">Export PNG</button>
  </div>
</body>
<script>
  const nameInput = document.getElementById("nameInput");
  const characterSelect = document.getElementById("characterSelect");
  const fontSelect = document.getElementById("fontSelect");
  const schemeSelect = document.getElementById("schemeSelect");
  const generateBtn = document.getElementById("generateBtn");
  const exportPngBtn = document.getElementById("exportPngBtn");
  const canvas = document.getElementById("previewCanvas");
  const ctx = canvas.getContext("2d");
  const hintText = document.getElementById("hintText");
  const wrapper = document.getElementById("canvasWrapper");

  const colors = [
    "#1E88E5",
    "#43A047",
    "#E040FB",
    "#FB8C00",
    "#00897B",
  ];

  const colorSchemes = {
    hitam: { outline: "#2b2a29", fill: "#727271" },
    sage: { outline: "#4a9e2f", fill: "#9ec869" },
    mustard: { outline: "#ef7f1a", fill: "#ffed00" },
    pink: { outline: "#f00a90", fill: "#f69ac9" },
    blue: { outline: "#2b85c4", fill: "#09cde7" },
    navy: { outline: "#6520ae", fill: "#ba9ae5" },
    white: { outline: "#9d9e9e", fill: "#fefefe" },
  };

  const characters = {
      Smart: @json(asset('ganci_nama/foto/2.svg')),
      Cute: @json(asset('ganci_nama/foto/4.svg')),
      Active: @json(asset('ganci_nama/foto/5.svg')),
      Calm: @json(asset('ganci_nama/foto/3.svg')),
      Religi: @json(asset('ganci_nama/foto/1.svg')),
  };

  let objects = [];
  let activeObject = null;
  let selectedObject = null;
  let dragOffset = { x: 0, y: 0 };

  // kontrol rapat huruf + padding export
  const LETTER_GAP = -2;  // bikin huruf lebih dempet
  const PAD = 20;         // ruang aman kiri/kanan saat export [web:83]

  schemeSelect.addEventListener("change", () => {
    const isPelangi = schemeSelect.value === "pelangi";

    if (isPelangi) {
      characterSelect.disabled = false;
    } else {
      characterSelect.value = "none";
      characterSelect.disabled = true;
      if (objects.length) {
        objects = objects.filter((o) => o.type !== "character");
      }
    }
  });

  document.fonts.ready.then(() => {
    hintText.textContent = "Font siap. Masukkan nama.";
  });

  generateBtn.addEventListener("click", generateKeychain);
  exportPngBtn.addEventListener("click", exportPNG);

  function getMousePos(evt) {
    const rect = canvas.getBoundingClientRect();
    const scaleX = canvas.width / rect.width;
    const scaleY = canvas.height / rect.height;
    return {
      x: (evt.clientX - rect.left) * scaleX,
      y: (evt.clientY - rect.top) * scaleY,
    };
  }

  function generateKeychain() {
    const name = nameInput.value.trim();
    if (!name) {
      hintText.textContent = "Masukkan nama dulu!";
      return;
    }

    const selected = characterSelect.value;

    // TANPA KARAKTER
    if (selected === "none") {
      objects = [
        {
          type: "text",
          text: name,
          x: 40,
          y: 140,
          w: 0,
          h: 100,
        },
      ];

      redrawCanvas();
      hintText.textContent = "Geser teks lalu export PNG";
      return;
    }

    // DENGAN KARAKTER
    const img = new Image();
    img.src = characters[selected];

    objects = [
      {
        type: "character",
        img,
        x: 20,
        y: 50,
        w: 120,
        h: 120,
      },
      {
        type: "text",
        text: name,
        x: 170,
        y: 140,
        w: 0,
        h: 100,
      },
    ];

    img.onload = redrawCanvas;
    hintText.textContent = "Geser objek lalu export PNG";
  }

  function redrawCanvas() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    for (const obj of objects) {
      if (obj.type === "character") {
        ctx.drawImage(obj.img, obj.x, obj.y, obj.w, obj.h);
      }
      if (obj.type === "text") {
        drawText(obj);
      }
    }
  }

  function getSelectedFont() {
    return fontSelect?.value || "Darling Coffee";
  }

  function drawText(obj) {
    const fontName = getSelectedFont();
    const isMagic = fontName === "Magic Sound";

    const fontSize = isMagic ? 65 : 100;
    const lineWidth = isMagic ? 13 : 15;

    const schemeKey = schemeSelect.value;
    const usePelangi = schemeKey === "pelangi";
    const scheme = colorSchemes[schemeKey] || colorSchemes.hitam;

    ctx.font = "bold " + fontSize + "px '" + fontName + "'";
    ctx.lineJoin = "round";
    ctx.lineWidth = lineWidth;
    ctx.textBaseline = "alphabetic";

    let x = obj.x;

    for (let i = 0; i < obj.text.length; i++) {
      const ch = obj.text[i];

      // non-pelangi: masih pakai outline
      if (!usePelangi) {
        ctx.strokeStyle = scheme.outline;
        ctx.strokeText(ch, x, obj.y);
      }

      ctx.fillStyle = usePelangi ? colors[i % colors.length] : scheme.fill;
      ctx.fillText(ch, x, obj.y);

      x += ctx.measureText(ch).width + LETTER_GAP; // huruf lebih dempet [web:65]
    }

    const m = ctx.measureText(obj.text);
    const left = m.actualBoundingBoxLeft ?? 0;
    const right = m.actualBoundingBoxRight ?? m.width;

    obj.w = left + right + (obj.text.length - 1) * LETTER_GAP; // lebar real [web:83]
  }

  canvas.addEventListener("mousedown", (e) => {
    const { x, y } = getMousePos(e);
    activeObject = null;

    for (let i = objects.length - 1; i >= 0; i--) {
      const o = objects[i];

      if (o.type === "character") {
        if (x >= o.x && x <= o.x + o.w && y >= o.y && y <= o.y + o.h) {
          activeObject = o;
          selectedObject = o;
          dragOffset.x = x - o.x;
          dragOffset.y = y - o.y;
          break;
        }
      }

      if (o.type === "text") {
        if (x >= o.x && x <= o.x + o.w && y >= o.y - o.h && y <= o.y) {
          activeObject = o;
          selectedObject = o;
          dragOffset.x = x - o.x;
          dragOffset.y = y - o.y;
          break;
        }
      }
    }
  });

  canvas.addEventListener("mousemove", (e) => {
    if (!activeObject) return;

    const { x, y } = getMousePos(e);

    let nx = x - dragOffset.x;
    let ny = y - dragOffset.y;

    if (activeObject.type === "character") {
      nx = Math.max(0, Math.min(nx, canvas.width - activeObject.w));
      ny = Math.max(0, Math.min(ny, canvas.height - activeObject.h));
    }

    if (activeObject.type === "text") {
      nx = Math.max(0, Math.min(nx, canvas.width - activeObject.w));
      ny = Math.max(activeObject.h, Math.min(ny, canvas.height));
    }

    activeObject.x = nx;
    activeObject.y = ny;
    redrawCanvas();
  });

  canvas.addEventListener("mouseup", () => (activeObject = null));
  canvas.addEventListener("mouseleave", () => (activeObject = null));

  ["top", "right", "bottom", "left"].forEach((side) => {
    const h = document.createElement("div");
    h.className = `resize-handle resize-${side}`;
    h.dataset.side = side;
    wrapper.appendChild(h);
  });

  let resizing = null;
  let startMouse = { x: 0, y: 0 };
  let startSize = { w: 0, h: 0 };

  wrapper.addEventListener("mousedown", (e) => {
    if (!e.target.classList.contains("resize-handle")) return;
    resizing = e.target.dataset.side;
    startMouse.x = e.clientX;
    startMouse.y = e.clientY;
    startSize.w = canvas.width;
    startSize.h = canvas.height;
    e.preventDefault();
  });

  window.addEventListener("mousemove", (e) => {
    if (!resizing) return;

    const dx = e.clientX - startMouse.x;
    const dy = e.clientY - startMouse.y;

    if (resizing === "right") {
      canvas.width = Math.max(200, startSize.w + dx);
    }

    if (resizing === "bottom") {
      canvas.height = Math.max(100, startSize.h + dy);
    }

    if (resizing === "left") {
      const newW = Math.max(200, startSize.w - dx);
      const diff = newW - canvas.width;
      canvas.width = newW;
      for (const o of objects) o.x += diff;
    }

    if (resizing === "top") {
      const newH = Math.max(100, startSize.h - dy);
      const diff = newH - canvas.height;
      canvas.height = newH;
      for (const o of objects) o.y += diff;
    }

    redrawCanvas();
  });

  window.addEventListener("mouseup", () => (resizing = null));

  function exportPNG() {
    if (!objects.length) return;

    const scale = 10;

    const fontName = getSelectedFont();
    const isMagic = fontName === "Magic Sound";
    const fontSize = isMagic ? 65 : 100;
    const lineWidth = isMagic ? 13 : 15;

    // default: pakai ukuran canvas sekarang
    let outW = canvas.width;
    let outH = canvas.height;

    const textObj = objects.find(o => o.type === "text");

    // kalau ada teks, sesuaikan lebar output dengan teks + padding [web:83]
    if (textObj) {
      ctx.font = "bold " + fontSize + "px '" + fontName + "'";
      const m = ctx.measureText(textObj.text);

      const left = m.actualBoundingBoxLeft ?? 0;
      const right = m.actualBoundingBoxRight ?? m.width;

      const textW = left + right + (textObj.text.length - 1) * LETTER_GAP;
      outW = Math.ceil(textW + PAD * 2);
    }

    const outCanvas = document.createElement("canvas");
    outCanvas.width = outW * scale;
    outCanvas.height = outH * scale;

    const outCtx = outCanvas.getContext("2d");
    outCtx.scale(scale, scale);
    outCtx.clearRect(0, 0, outW, outH); // jadi transparan [web:36]

    const schemeKey = schemeSelect.value;
    const usePelangi = schemeKey === "pelangi";
    const scheme = colorSchemes[schemeKey] || colorSchemes.hitam;

    for (const obj of objects) {
      if (obj.type === "character") {
        outCtx.drawImage(obj.img, obj.x, obj.y, obj.w, obj.h);
      }

      if (obj.type === "text") {
        outCtx.font = "bold " + fontSize + "px '" + fontName + "'";
        outCtx.lineJoin = "round";
        outCtx.lineWidth = lineWidth;
        outCtx.textBaseline = "alphabetic";

        let x = PAD;      // mulai dari padding kiri supaya aman
        const y = obj.y;

        for (let i = 0; i < obj.text.length; i++) {
          const ch = obj.text[i];

          if (!usePelangi) {
            outCtx.strokeStyle = scheme.outline;
            outCtx.strokeText(ch, x, y); // outline non-pelangi [web:30]
          }

          outCtx.fillStyle = usePelangi ? colors[i % colors.length] : scheme.fill;
          outCtx.fillText(ch, x, y);

          x += outCtx.measureText(ch).width + LETTER_GAP;
        }
      }
    }

    outCanvas.toBlob((blob) => {
      const url = URL.createObjectURL(blob);
      const a = document.createElement("a");
      a.href = url;
      a.download = "gantungan-nama.png";
      document.body.appendChild(a);
      a.click();
      a.remove();
      URL.revokeObjectURL(url);
    }, "image/png");
  }

  window.addEventListener("keydown", (e) => {
    if (!selectedObject) return;

    let step = e.shiftKey ? 10 : 1;

    let nx = selectedObject.x;
    let ny = selectedObject.y;

    if (e.key === "ArrowLeft") nx -= step;
    if (e.key === "ArrowRight") nx += step;
    if (e.key === "ArrowUp") ny -= step;
    if (e.key === "ArrowDown") ny += step;

    if (selectedObject.type === "character") {
      nx = Math.max(0, Math.min(nx, canvas.width - selectedObject.w));
      ny = Math.max(0, Math.min(ny, canvas.height - selectedObject.h));
    }

    if (selectedObject.type === "text") {
      nx = Math.max(0, Math.min(nx, canvas.width - selectedObject.w));
      ny = Math.max(selectedObject.h, Math.min(ny, canvas.height));
    }

    selectedObject.x = nx;
    selectedObject.y = ny;

    redrawCanvas();
    e.preventDefault();
  });
</script>
</html>