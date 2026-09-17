<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="description" content="Emblem sound custom akrilik dengan UV DTF timbul. Bisa pakai logo sendiri, edit gratis, kuat menempel, tahan panas dan hujan." />
  <title>Emblem Sound Custom</title>
  <style>
    :root{
      --bg:#07090d;
      --panel:#10141b;
      --panel-2:#151b24;
      --text:#f7f8fa;
      --muted:#a9b1bd;
      --line:rgba(255,255,255,.09);
      --green:#25d366;
      --green-dark:#159447;
      --accent:#7cff9d;
      --max:1160px;
      --radius:26px;
    }
    *{box-sizing:border-box;margin:0;padding:0}
    html{scroll-behavior:smooth}
    body{
      font-family:Inter,ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;
      background:linear-gradient(180deg,#07090d 0%,#0b0f15 55%,#080a0e 100%);
      color:var(--text);
      line-height:1.55;
      padding-bottom:88px;
    }
    img{display:block;width:100%;height:100%;object-fit:cover}
    a{text-decoration:none;color:inherit}
    .container{width:min(var(--max),calc(100% - 36px));margin:auto}
    .section{padding:88px 0}
    .eyebrow{font-size:12px;letter-spacing:.18em;text-transform:uppercase;font-weight:900;color:var(--accent);margin-bottom:12px}
    .section-title{font-size:clamp(34px,5vw,58px);line-height:1.05;letter-spacing:-.04em;max-width:780px}
    .section-desc{color:var(--muted);font-size:17px;max-width:700px;margin-top:16px}
    .center{text-align:center;margin-inline:auto}

    .btn{
      display:inline-flex;align-items:center;justify-content:center;gap:10px;
      min-height:58px;padding:0 25px;border-radius:15px;font-weight:900;
      transition:.2s ease;border:1px solid transparent;
    }
    .btn:hover{transform:translateY(-2px)}
    .btn-wa{background:var(--green);color:#041a0b;box-shadow:0 16px 38px rgba(37,211,102,.25)}
    .btn-wa:hover{background:#34e978}
    .btn-ghost{border-color:var(--line);background:rgba(255,255,255,.04);color:#fff}
    .btn-lg{min-height:66px;padding:0 30px;font-size:18px}

    /* HERO */
    .hero{position:relative;overflow:hidden;padding:74px 0 54px}
    .hero:before{content:"";position:absolute;inset:-20% auto auto 50%;width:620px;height:620px;background:radial-gradient(circle,rgba(37,211,102,.2),transparent 62%);filter:blur(20px);pointer-events:none}
    .hero-grid{display:grid;grid-template-columns:1.02fr .98fr;gap:54px;align-items:center}
    .hero-badge{display:inline-flex;padding:8px 13px;border:1px solid var(--line);border-radius:999px;background:rgba(255,255,255,.04);font-weight:800;font-size:12px;margin-bottom:22px}
    .hero h1{font-size:clamp(46px,7vw,82px);line-height:.97;letter-spacing:-.055em;margin-bottom:20px}
    .hero h1 span{color:var(--accent)}
    .hero p{font-size:18px;color:#c7ced8;max-width:650px;margin-bottom:24px}
    .chips{display:flex;flex-wrap:wrap;gap:10px;margin-bottom:28px}
    .chip{padding:9px 12px;border-radius:11px;border:1px solid var(--line);background:rgba(255,255,255,.04);font-size:13px;font-weight:800}
    .hero-actions{display:flex;gap:12px;flex-wrap:wrap}
    .micro{font-size:12px;color:#8e98a6;margin-top:12px}

    .hero-card{position:relative;height:570px;border-radius:34px;overflow:hidden;background:#111;border:1px solid var(--line);box-shadow:0 40px 90px rgba(0,0,0,.42)}
    .hero-card img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover;object-position:center}
    .hero-card:after{content:"";position:absolute;inset:0;background:linear-gradient(180deg,transparent 50%,rgba(0,0,0,.78) 100%);pointer-events:none}
    .hero-overlay{position:absolute;z-index:2;left:24px;right:24px;bottom:22px}
    .hero-overlay strong{display:block;font-size:20px;margin-bottom:3px}
    .hero-overlay span{color:#d8dde5;font-size:13px}

    /* TRUST */
    .trust{border-top:1px solid var(--line);border-bottom:1px solid var(--line);background:rgba(255,255,255,.025)}
    .trust-grid{display:grid;grid-template-columns:repeat(4,1fr)}
    .trust-item{padding:24px 18px;text-align:center;border-right:1px solid var(--line)}
    .trust-item:last-child{border-right:none}
    .trust-item strong{display:block;font-size:16px}
    .trust-item span{font-size:12px;color:var(--muted)}

    /* PROOF GRID */
    .proof-grid{display:grid;grid-template-columns:1.25fr .75fr .75fr;grid-template-rows:260px 260px;gap:16px;margin-top:38px}
    .proof-card{position:relative;overflow:hidden;border-radius:22px;border:1px solid var(--line);background:var(--panel)}
    .proof-card.big{grid-row:1/3}
    .proof-card .label{position:absolute;left:14px;bottom:14px;z-index:2;background:rgba(0,0,0,.68);backdrop-filter:blur(8px);border:1px solid rgba(255,255,255,.12);padding:8px 10px;border-radius:10px;font-size:12px;font-weight:900}

    /* FEATURES */
    .feature-wrap{background:linear-gradient(180deg,rgba(255,255,255,.025),rgba(255,255,255,.012));border-top:1px solid var(--line);border-bottom:1px solid var(--line)}
    .features{display:grid;grid-template-columns:repeat(3,1fr);gap:18px;margin-top:40px}
    .feature{padding:26px;border-radius:20px;background:var(--panel);border:1px solid var(--line)}
    .feature-icon{width:48px;height:48px;border-radius:14px;display:grid;place-items:center;background:rgba(37,211,102,.11);margin-bottom:18px;font-size:22px}
    .feature h3{font-size:18px;margin-bottom:7px}
    .feature p{color:var(--muted);font-size:14px}

    /* SHOWCASE */
    .showcase{display:grid;grid-template-columns:1fr 1fr;gap:22px;margin-top:40px}
    .showcase-card{border:1px solid var(--line);background:var(--panel);border-radius:24px;overflow:hidden}
    .showcase-card .image{height:360px}
    .showcase-card .copy{padding:22px}
    .showcase-card h3{font-size:23px;margin-bottom:8px}
    .showcase-card p{font-size:14px;color:var(--muted)}

    /* CTA STRIP */
    .cta-strip{margin-top:34px;padding:26px;border-radius:22px;background:linear-gradient(135deg,rgba(37,211,102,.17),rgba(37,211,102,.04));border:1px solid rgba(37,211,102,.28);display:flex;align-items:center;justify-content:space-between;gap:20px}
    .cta-strip strong{font-size:22px;display:block}
    .cta-strip span{font-size:14px;color:#c7cfda}

    /* FINAL */
    .final-box{position:relative;overflow:hidden;border-radius:32px;padding:60px 48px;background:linear-gradient(135deg,#0f1712,#0a0d0b);border:1px solid rgba(37,211,102,.25);text-align:center}
    .final-box:before{content:"";position:absolute;inset:auto -10% -50% auto;width:500px;height:500px;background:radial-gradient(circle,rgba(37,211,102,.28),transparent 60%)}
    .final-box h2{font-size:clamp(38px,6vw,68px);line-height:1;letter-spacing:-.045em;max-width:800px;margin:0 auto 18px}
    .final-box p{color:#c7ced8;max-width:720px;margin:0 auto 28px}

    /* FLOATING CTA */
    .floating{position:fixed;left:50%;bottom:14px;transform:translateX(-50%);z-index:999;width:min(700px,calc(100% - 24px));display:flex;gap:12px;align-items:center;padding:10px 10px 10px 18px;border-radius:18px;background:rgba(8,11,15,.92);backdrop-filter:blur(14px);border:1px solid rgba(255,255,255,.12);box-shadow:0 20px 60px rgba(0,0,0,.45)}
    .floating-copy{flex:1}.floating-copy strong{display:block;font-size:13px}.floating-copy span{display:block;color:#95a0ad;font-size:10px}
    .floating .btn{min-height:50px;padding:0 18px;border-radius:12px;font-size:13px}

    footer{padding:18px 0 40px;text-align:center;color:#7e8996;font-size:12px}

    @media(max-width:950px){
      .hero-grid,.showcase{grid-template-columns:1fr}
      .hero-card{height:500px}
      .features{grid-template-columns:repeat(2,1fr)}
      .steps{grid-template-columns:repeat(2,1fr)}
      .gallery{grid-template-columns:repeat(2,1fr)}
      .proof-grid{grid-template-columns:1fr 1fr;grid-template-rows:340px 230px 230px}
      .proof-card.big{grid-column:1/3;grid-row:auto}
      .trust-grid{grid-template-columns:repeat(2,1fr)}
      .trust-item:nth-child(2){border-right:none}
      .trust-item:nth-child(-n+2){border-bottom:1px solid var(--line)}
    }
    @media(max-width:620px){
      body{padding-bottom:82px}
      .section{padding:66px 0}
      .hero{padding:44px 0 38px}
      .hero h1{font-size:46px}
      .hero p{font-size:16px}
      .hero-actions{display:grid;grid-template-columns:1fr}
      .hero-actions .btn{width:100%}
      .hero-card{height:430px}
      .features,.steps,.gallery{grid-template-columns:1fr}
      .proof-grid{grid-template-columns:1fr;grid-template-rows:none}
      .proof-card,.proof-card.big{grid-column:auto;grid-row:auto;height:300px}
      .cta-strip{flex-direction:column;align-items:flex-start}
      .cta-strip .btn{width:100%}
      .final-box{padding:44px 22px}
      .floating-copy{display:none}
      .floating .btn{width:100%;font-size:15px;min-height:54px}
    }
  </style>
</head>
<body>

<header class="hero">
  <div class="container hero-grid">
    <div>
      <div class="hero-badge">EMBLEM SOUND CUSTOM • UV DTF TIMBUL</div>
      <h1>Bikin Sound Kamu <span>Makin Beridentitas.</span></h1>
      <p>Emblem akrilik custom dengan cetakan UV DTF timbul. Bisa pakai logo sendiri, edit desain GRATIS, kuat menempel, tahan panas dan hujan.</p>
      <div class="chips">
        <div class="chip">✓ Bisa Custom Logo</div>
        <div class="chip">✓ Edit Gratis</div>
        <div class="chip">✓ Produksi ±1 Hari</div>
        <div class="chip">✓ Tanpa Minimum Order</div>
      </div>
      <div class="hero-actions">
        <a class="btn btn-wa btn-lg" href="https://wa.me/6285216458653?text=Halo,%20saya%20mau%20lihat%20katalog%20dan%20harga%20Emblem%20Sound">💬 LIHAT KATALOG & HARGA</a>
        <a class="btn btn-ghost btn-lg" href="#hasil">Lihat Hasil Produk</a>
      </div>
      <div class="micro">Konsultasi via WhatsApp • Tidak wajib langsung order</div>
    </div>

    <div class="hero-card">
      <img src="{{ asset('iklanpage/naya-hologram.png') }}" alt="Emblem sound custom hologram" />
      <div class="hero-overlay">
        <strong>Custom logo + efek hologram</strong>
        <span>Visual mencolok, potong mengikuti bentuk emblem.</span>
      </div>
    </div>
  </div>
</header>

<div class="trust">
  <div class="container trust-grid">
    <div class="trust-item"><strong>Custom Bebas</strong><span>Pakai logo sendiri</span></div>
    <div class="trust-item"><strong>Edit GRATIS</strong><span>Dibantu tim editor</span></div>
    <div class="trust-item"><strong>±1 Hari</strong><span>Proses produksi</span></div>
    <div class="trust-item"><strong>Lem Kuat</strong><span>Tinggal kupas & tempel</span></div>
  </div>
</div>

<section class="section" id="hasil">
  <div class="container">
    <div class="eyebrow">BUKTI PRODUK</div>
    <h2 class="section-title">Bukan Sekadar Stiker Biasa.</h2>
    <p class="section-desc">Base menggunakan akrilik sekitar 2–2,5 mm. Di atasnya dicetak UV DTF timbul, lalu bagian belakang diberi double tape kuat untuk pemasangan.</p>

    <div class="proof-grid">
      <div class="proof-card big">
        <img src="{{ asset('iklanpage/embel-audio-neon.png') }}" alt="Emblem sound timbul close-up" />
        <div class="label">UV DTF timbul • tekstur terasa saat diraba</div>
      </div>
      <div class="proof-card">
        <img src="{{ asset('iklanpage/thickness.jpg') }}" alt="Ketebalan emblem sound akrilik" />
        <div class="label">Akrilik ±2–2,5 mm</div>
      </div>
      <div class="proof-card">
        <img src="{{ asset('iklanpage/backing-tape.png') }}" alt="Double tape belakang emblem" />
        <div class="label">Double tape kuat</div>
      </div>
      <div class="proof-card">
        <img src="{{ asset('iklanpage/water-test.png') }}" alt="Uji air emblem sound" />
        <div class="label">Tahan panas & hujan</div>
      </div>
      <div class="proof-card">
        <img src="{{ asset('iklanpage/hologram-pair.png') }}" alt="Emblem sound efek hologram" />
        <div class="label">Varian efek hologram</div>
      </div>
    </div>

    <div class="cta-strip">
      <div>
        <strong>Mau lihat model lain & harganya?</strong>
        <span>CS akan kirim katalog dan bantu pilih ukuran/desain yang sesuai.</span>
      </div>
      <a class="btn btn-wa" href="https://wa.me/6285216458653?text=Halo,%20boleh%20kirim%20katalog%20Emblem%20Sound?">💬 MINTA KATALOG</a>
    </div>
  </div>
</section>

<section class="section feature-wrap">
  <div class="container">
    <div class="eyebrow center">KENAPA PILIH EMBLEM INI?</div>
    <h2 class="section-title center">Dibuat untuk branding sekaligus bikin sound lebih keren.</h2>
    <div class="features">
      <div class="feature"><div class="feature-icon">🎨</div><h3>Bisa Custom Logo</h3><p>Pakai nama atau logo sound kamu sendiri. Ukuran juga bisa menyesuaikan kebutuhan.</p></div>
      <div class="feature"><div class="feature-icon">✏️</div><h3>Edit Desain Gratis</h3><p>File belum rapi? Tim editor kami bantu rapikan sebelum masuk produksi.</p></div>
      <div class="feature"><div class="feature-icon">✨</div><h3>UV DTF Timbul</h3><p>Cetakan punya tekstur dan lapisan timbul yang terasa ketika disentuh.</p></div>
      <div class="feature"><div class="feature-icon">☀️</div><h3>Tahan Panas & Hujan</h3><p>Cocok untuk pemakaian indoor maupun outdoor.</p></div>
      <div class="feature"><div class="feature-icon">💪</div><h3>Daya Rekat Kuat</h3><p>Bagian belakang memakai double tape kuat. Cukup kupas lalu tempel.</p></div>
      <div class="feature"><div class="feature-icon">⚡</div><h3>Produksi Cepat</h3><p>Proses produksi biasanya sekitar satu hari.</p></div>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="eyebrow">PILIH GAYA KAMU</div>
    <h2 class="section-title">Pilih katalog atau kirim logo sendiri.</h2>
    <p class="section-desc">CS akan menawarkan katalog terlebih dahulu. Kalau ingin identitas sendiri, kamu bisa langsung kirim logo atau desain custom.</p>

    <div class="showcase">
      <div class="showcase-card">
        <div class="image"><img src="{{ asset('iklanpage/katalog.jpg') }}" alt="Contoh katalog emblem sound" /></div>
        <div class="copy"><h3>Pilih dari Katalog</h3><p>Pilih model yang sudah tersedia dan konsultasikan pilihan ukuran dengan CS.</p></div>
      </div>
      <div class="showcase-card">
        <div class="image"><img src="{{ asset('iklanpage/rzl-audio.jpg') }}" alt="Contoh emblem custom logo sendiri" /></div>
        <div class="copy"><h3>Pakai Logo Sendiri</h3><p>Kirim desainmu. Kalau belum siap cetak, tim editor kami bantu rapikan gratis.</p></div>
      </div>
    </div>

    <div class="cta-strip">
      <div>
        <strong>Punya logo sound sendiri?</strong>
        <span>Kirim sekarang. Kami bantu cek dan rapikan desainnya GRATIS.</span>
      </div>
      <a class="btn btn-wa" href="https://wa.me/6285216458653?text=Halo,%20saya%20punya%20logo%20sendiri%20dan%20ingin%20dibuatkan%20Emblem%20Sound">📤 KIRIM LOGO SEKARANG</a>
    </div>
  </div>
</section>


<section class="section">
  <div class="container">
    <div class="final-box">
      <div class="eyebrow center">CTA UTAMA</div>
      <h2>Punya logo sound? Bikin jadi emblem sekarang.</h2>
      <p>Kirim logomu ke WhatsApp. Tim kami bantu cek dan rapikan GRATIS. Bisa custom ukuran, tanpa minimum order, dan proses produksi sekitar satu hari.</p>
      <a class="btn btn-wa btn-lg" href="https://wa.me/6285216458653?text=Halo,%20saya%20mau%20buat%20Emblem%20Sound%20Custom.%20Saya%20punya%20logo%20sendiri.">💬 KIRIM LOGO & KONSULTASI GRATIS</a>
    </div>
  </div>
</section>

<footer><div class="container">© 2026 Emblem Sound Custom</div></footer>

<div class="floating">
  <div class="floating-copy"><strong>Mau lihat katalog & harga?</strong><span>Chat CS sekarang</span></div>
  <a class="btn btn-wa" href="https://wa.me/6285216458653?text=Halo,%20saya%20tertarik%20dengan%20Emblem%20Sound">💬 CHAT WHATSAPP</a>
</div>

</body>
</html>