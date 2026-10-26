<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Jadwal Piket LCK</title>
<style>
:root{color-scheme:light;--ink:#17233b;--muted:#66748c;--bg:#f5f7fb;--line:#e6eaf1;--accent:#2563eb}
*{box-sizing:border-box}body{margin:0;background:var(--bg);font-family:Inter,system-ui,-apple-system,"Segoe UI",sans-serif;color:var(--ink)}main{max-width:760px;margin:48px auto;padding:0 18px}header{text-align:center;margin-bottom:25px}h1{font-size:clamp(25px,5vw,34px);margin:0 0 9px;letter-spacing:-.7px}.subtitle{color:var(--muted);font-size:15px;margin:0}.week{font-size:14px;color:#355ca9;margin-top:9px;font-weight:650}.schedule{display:grid;gap:12px}.item{display:grid;grid-template-columns:175px 1fr;align-items:center;gap:20px;padding:19px 22px;background:#fff;border:1px solid var(--line);border-radius:15px;box-shadow:0 3px 15px #15254b07}.item.today{border-color:#86acef;box-shadow:0 0 0 2px #2563eb16}.day{font-weight:750;font-size:17px}.date{margin-top:5px;color:var(--muted);font-size:13px}.names{font-size:17px;font-weight:650;line-height:1.5}.tag{display:inline-block;margin-top:7px;background:#eaf1ff;color:#1d4ed8;font-size:11px;font-weight:700;padding:4px 8px;border-radius:100px}footer{text-align:center;margin-top:21px;color:var(--muted);font-size:12px}@media(max-width:520px){main{margin:26px auto}.item{grid-template-columns:1fr;gap:9px;padding:16px 18px}.day{font-size:16px}.names{font-size:16px}}@media print{body{background:white}main{max-width:100%;margin:0}.item{break-inside:avoid;box-shadow:none}.week,.subtitle{color:#333}}
</style>
</head>
<body>
<main>
<header>
<h1>Jadwal Piket LCK</h1>
<p class="subtitle" id="rentang">Senin – Sabtu</p>
<div class="week" id="minggu"></div>
</header>
<section class="schedule" id="jadwal" aria-label="Jadwal piket minggu ini"></section>
<footer>Jadwal berganti otomatis setiap Senin • Waktu Indonesia Barat</footer>
</main>
<script>
'use strict';
// Kelompok tetap sesuai kesepakatan.
const kelompok = [
  ['MAYA','ELSA'],
  ['RARA','IZA'],
  ['DIVA','EVA'],
  ['IMA','FAVIAN','AZIS'],
  ['ANDIKA','YAZID'],
  ['KAUNANG','ASHAR','RICKY']
];
const namaHari = ['Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
// Senin, 26 Oktober 2026 adalah awal minggu pertama.
const awalSiklusUTC = Date.UTC(2026,9,26);
const MS_HARI = 86400000;
const fmt = new Intl.DateTimeFormat('id-ID',{day:'numeric',month:'long',year:'numeric',timeZone:'UTC'});
function tanggalWIB(){
  const p = new Intl.DateTimeFormat('en-CA',{timeZone:'Asia/Jakarta',year:'numeric',month:'2-digit',day:'2-digit'}).formatToParts(new Date());
  const v = Object.fromEntries(p.map(x=>[x.type,x.value]));
  return Date.UTC(Number(v.year),Number(v.month)-1,Number(v.day));
}
function tampilkan(){
  const kini = tanggalWIB();
  const hari = new Date(kini).getUTCDay(); // Minggu=0, Senin=1
  const mundur = (hari+6)%7;
  const senin = kini - mundur*MS_HARI;
  const nomorMinggu = Math.floor((senin-awalSiklusUTC)/(7*MS_HARI));
  const siklus = ((nomorMinggu%6)+6)%6;
  const sabtu = senin+5*MS_HARI;
  document.getElementById('rentang').textContent = fmt.format(new Date(senin))+' – '+fmt.format(new Date(sabtu));
  document.getElementById('minggu').textContent = 'Minggu '+(siklus+1)+' dari 6';
  const target=document.getElementById('jadwal');
  target.replaceChildren();
  for(let i=0;i<6;i++){
    const t=senin+i*MS_HARI;
    const kartu=document.createElement('article');
    kartu.className='item'+(t===kini?' today':'');
    const kiri=document.createElement('div');
    const label=document.createElement('div'); label.className='day';label.textContent=namaHari[i];
    const tanggal=document.createElement('div');tanggal.className='date';tanggal.textContent=fmt.format(new Date(t));
    kiri.append(label,tanggal);
    if(t===kini){const tag=document.createElement('span');tag.className='tag';tag.textContent='HARI INI';kiri.append(tag)}
    const nama=document.createElement('div');nama.className='names';nama.textContent=kelompok[(i+siklus)%6].join(' · ');
    kartu.append(kiri,nama);target.append(kartu);
  }
}
tampilkan();
// Perbarui selama tab terbuka, termasuk saat tanggal/WIB berganti.
setInterval(tampilkan,60000);
document.addEventListener('visibilitychange',()=>{if(!document.hidden)tampilkan()});
</script>
</body>
</html>
