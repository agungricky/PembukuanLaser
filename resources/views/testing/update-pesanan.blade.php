<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Uji Update Pesanan V11 | DDK</title>
    <style>
        * {
            box-sizing: border-box
        }

        body {
            margin: 0;
            background: #f3f5f8;
            color: #182438;
            font: 14px/1.5 system-ui, sans-serif
        }

        header {
            background: #14243c;
            color: white;
            padding: 18px 5%;
            display: flex;
            justify-content: space-between;
            align-items: center
        }

        header b {
            letter-spacing: 1px
        }

        main {
            max-width: 1150px;
            margin: 30px auto;
            padding: 0 18px 100px
        }

        h1 {
            font-size: 28px;
            margin: 0
        }

        h2 {
            font-size: 17px;
            margin: 0 0 18px
        }

        p {
            margin: 6px 0 16px;
            color: #637185
        }

        .card {
            background: white;
            border: 1px solid #dfe5ed;
            border-radius: 14px;
            padding: 22px;
            margin: 16px 0;
            box-shadow: 0 3px 12px #18243806
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px
        }

        .wide {
            grid-column: 1/-1
        }

        label {
            display: block;
            font-weight: 600;
            font-size: 13px
        }

        input,
        select,
        textarea {
            width: 100%;
            margin-top: 6px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 10px;
            background: white;
            color: inherit;
            font: inherit
        }

        input:focus,
        select:focus,
        textarea:focus {
            outline: 2px solid #8cb5ff;
            outline-offset: 1px
        }

        .missing-field {
            background: #fff7df;
            border-color: #d7a23c
        }

        input[readonly] {
            background: #eef2f6
        }

        textarea {
            min-height: 70px;
            resize: vertical
        }

        button {
            border: 0;
            border-radius: 8px;
            padding: 10px 16px;
            font: 600 14px system-ui;
            cursor: pointer;
            background: #e8eef6;
            color: #233b5e
        }

        button.primary {
            background: #225dd4;
            color: white
        }

        button.danger {
            background: #fff0f0;
            color: #b42318
        }

        button:disabled {
            opacity: .5;
            cursor: wait
        }

        .row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            flex-wrap: wrap
        }

        .product {
            border: 1px solid #dce4ed;
            border-radius: 12px;
            padding: 16px;
            margin-top: 14px
        }

        .photos {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin: 12px 0
        }

        .photo {
            width: 115px;
            border: 1px solid #dce4ed;
            border-radius: 8px;
            overflow: hidden;
            background: #f4f6f9
        }

        .photo img {
            width: 100%;
            height: 83px;
            object-fit: cover;
            display: block
        }

        .photo small {
            display: block;
            padding: 5px;
            overflow-wrap: anywhere
        }

        .photo button {
            width: 100%;
            padding: 6px;
            font-size: 12px
        }

        .notice {
            background: #fff7e6;
            border: 1px solid #f0d6a0;
            padding: 12px;
            border-radius: 8px;
            color: #795415;
            margin: 12px 0
        }

        .footer {
            position: sticky;
            bottom: 0;
            background: white;
            border-top: 1px solid #dce4ed;
            padding: 15px 5%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            z-index: 2
        }

        #status {
            white-space: pre-wrap;
            overflow-wrap: anywhere
        }

        details summary {
            cursor: pointer;
            font-weight: 600
        }

        small {
            color: #64748b
        }

        @media(max-width:700px) {
            .grid {
                grid-template-columns: 1fr 1fr
            }

            .card {
                padding: 15px
            }
        }

        @media(max-width:420px) {
            .grid {
                grid-template-columns: 1fr
            }

            .footer {
                flex-wrap: wrap
            }
        }
    </style>
</head>

<body>
    <header><b>DDK / PESANAN</b><span>Update langsung</span></header>
    <main>
        <h1>Edit pesanan</h1>
        <p>Form pengujian V11 · GET otomatis mengisi form. Update menggunakan Bearer Token. Produk lama: jumlah dan
            catatan dapat diubah.</p>
        <section class="card">
            <div class="row"><label>Nomor pesanan<input id="lookup" placeholder="20261124001A"></label><button
                    type="button" id="load">Ambil data pesanan (GET)</button></div>
            <details>
                <summary>Konfigurasi API</summary>
                <div class="grid" style="margin-top:12px"><label class="wide">URL GET detail<input id="getUrl"
                            placeholder="/api/.../20261124001A"></label><label class="wide">URL update<input
                            id="postUrl" placeholder="/api/..."></label><label>Method backend<select id="method">
                            <option>PATCH</option>
                            <option selected>POST</option>
                            <option>PUT</option>
                        </select></label><label>Bearer token (wajib)<input id="token" type="password"
                            autocomplete="off"></label></div><small>Gunakan URL sebenarnya. Untuk PATCH/PUT, pengiriman
                    memakai POST dengan _method Laravel.</small>
            </details>
        </section>
        <div class="notice" id="missing">Masukkan nomor pesanan untuk mengambil data dari API.</div>
        <form id="form" novalidate hidden>
            <section class="card">
                <h2>01 · Data pesanan</h2>
                <div class="grid" id="order"></div>
            </section>
            <section class="card">
                <h2>02 · Alamat pengiriman</h2>
                <div class="grid" id="address"></div>
            </section>
            <section class="card">
                <h2>03 · Pembayaran</h2>
                <div class="grid" id="payment"></div>
                <div id="oldPayment"></div><label>Pilih file bukti pembayaran BARU dari komputer<input type="file"
                        id="buktiFile" name="bukti_bayar"></label><button type="button" id="clearPayment">Batalkan file
                    baru</button><small>Foto lama di atas hanya berasal dari server. File baru yang dipilih akan
                    ditampilkan terpisah di bawah.</small>
                <p id="fileInfo">Belum ada file baru dipilih — bukti lama tidak akan diubah.</p>
                <div id="newPaymentPreview"></div>
                <pre id="uploadDebug" style="white-space:pre-wrap;overflow-wrap:anywhere"></pre>
            </section>
            <section class="card">
                <div class="row">
                    <h2>04 · Produk pesanan</h2><button type="button" id="add">+ Tambah produk</button>
                </div>
                <div class="notice">Produk yang tidak disertakan akan dihapus beserta foto di FileGator. Foto produk
                    hanya ditampilkan sebagai referensi: service update saat ini tidak mendukung perubahan foto produk
                    satu per satu.</div>
                <div id="products"></div>
            </section>
            <div id="status" role="status"></div>
        </form>
        <section class="card">
            <h2>Response API / DD Laravel</h2>
            <div id="httpStatus">Belum ada request.</div>
            <pre id="rawResponse" style="white-space:pre-wrap;overflow-wrap:anywhere;max-height:420px;overflow:auto"></pre><iframe id="debugFrame" sandbox="" hidden
                style="width:100%;height:440px;border:1px solid #dce4ed"></iframe>
        </section>
    </main>
    <div class="footer">
        <div><small>Total harga pesanan</small><br><strong id="sum">Rp0</strong></div>
        <div class="row"><button type="button" id="preview">Lihat payload</button><button class="primary"
                type="submit" form="form" id="submit" disabled>Simpan perubahan</button></div>
    </div>
    <script>
        const $ = id => document.getElementById(id),
            form = $('form');
        let originalIds = [],
            busy = false;
        let products = [],
            oldPayment = null,
            paymentPreviewUrl = null,
            loadedOrder = null;
        const rupiah = n => new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            maximumFractionDigits: 0
        }).format(n);
        const el = (tag, txt) => {
            let e = document.createElement(tag);
            if (txt !== undefined) e.textContent = txt;
            return e
        };
        const specs = {
            order: [
                ['no_pesanan', 'Nomor pesanan', 'text'],
                ['tanggal_order', 'Tanggal order', 'datetime-local'],
                ['nama_pembeli', 'Nama pembeli', 'text'],
                ['total_hpp', 'Total HPP', 'number'],
                ['total_harga', 'Total harga', 'number'],
                ['total_admin', 'Total admin', 'number'],
                ['chat_masuk_at', 'Chat masuk', 'datetime-local'],
                ['notes', 'Catatan pesanan', 'textarea', true]
            ],
            address: [
                ['provinsi', 'Kode provinsi', 'text'],
                ['kabupaten_kota', 'Kode kabupaten/kota', 'text'],
                ['kecamatan', 'Kode kecamatan', 'text'],
                ['desa', 'Kode desa', 'text', true],
                ['alamat_lengkap', 'Alamat lengkap', 'textarea'],
                ['phone', 'Nomor WhatsApp', 'tel']
            ],
            payment: [
                ['jenis_transaksi', 'Jenis transaksi', 'select'],
                ['cod', 'COD', 'number', true],
                ['non_cod', 'Non-COD', 'number', true],
                ['dp', 'DP', 'number', true],
                ['por', 'POR (bayar setelah jadi)', 'number', true]
            ]
        };

        function field(name, title, type, optional = false, value = '') {
            let l = el('label', title),
                i = el(type === 'textarea' ? 'textarea' : type === 'select' ? 'select' : 'input');
            i.name = name;
            if (type === 'select') {
                for (const [v, t] of [
                        ['', 'Pilih'],
                        ['cod', 'COD'],
                        ['non-cod', 'Non-COD'],
                        ['dp', 'DP'],
                        ['por', 'POR']
                    ]) {
                    let o = el('option', t);
                    o.value = v;
                    i.append(o)
                }
            } else if (type !== 'textarea') i.type = type;
            i.value = value ?? '';
            if (type === 'datetime-local') i.step = 1;
            if (type === 'number') {
                i.min = 0;
                i.step = ['cod', 'non_cod', 'dp', 'por'].includes(name) ? '0.01' : '1'
            }
            if (name === 'user_id') i.min = 1;
            if (name === 'no_pesanan') i.readOnly = true;
            if (type === 'textarea') l.className = 'wide';
            l.append(i);
            return l
        }
        for (const [id, fields] of Object.entries(specs))
            for (const s of fields) $(id).append(field(...s));
        const get = n => form.elements.namedItem(n);
        const set = (n, v) => {
            get(n).value = v ?? ''
        };

        function localDate(v) {
            if (!v) return '';
            if (!/Z$|[+-]\d\d:\d\d$/.test(v)) return v.replace(' ', 'T').slice(0, 19);
            let d = new Date(v);
            if (isNaN(d)) return '';
            return new Intl.DateTimeFormat('sv-SE', {
                timeZone: 'Asia/Jakarta',
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hourCycle: 'h23'
            }).format(d).replace(' ', 'T')
        }

        function populate(response) {
            let d = response.data ?? response,
                b = d.informasiPembeli ?? {},
                a = b.address ?? {},
                o = d.pesanan ?? {},
                p = (Array.isArray(d.pembayaran) ? d.pembayaran[0] : d.pembayaran) ?? {};
            const incomingOrder = String(o.noPesanan ?? o.no_pesanan ?? '');
            if (!incomingOrder || incomingOrder !== $('lookup').value.trim()) throw Error(
                'Nomor pesanan response tidak cocok.');
            if (!Array.isArray(d.informasiProduk)) throw Error('Response tidak memuat array informasiProduk.');
            if (d.informasiProduk.some(x => !(x.id_per_produk ?? x.id))) throw Error(
                'ID produk lama tidak tersedia; update diblokir agar tidak membuat duplikat.');
            clearPaymentSelection();
            loadedOrder = incomingOrder;
            $('uploadDebug').textContent = '';
            let vals = {
                no_pesanan: o.noPesanan ?? o.no_pesanan,
                tanggal_order: localDate(o.tanggal_order ?? o.tanggalOrder ?? o.input_at ?? o.tanggalInput),
                nama_pembeli: b.namaPembeli,
                total_hpp: o.total_hpp ?? o.totalHpp,
                total_harga: o.totalHarga ?? o.total_harga,
                total_admin: o.total_admin ?? o.totalAdmin,
                chat_masuk_at: localDate(o.chat_masuk_at ?? o.chatMasukAt ?? o.chatMasuk),
                notes: o.catatanPesanan ?? o.notes,
                provinsi: a.provinsi,
                kabupaten_kota: a.kabupatenKota,
                kecamatan: a.kecamatan,
                desa: a.desa,
                alamat_lengkap: a.alamatLengkap,
                phone: b.phone,
                jenis_transaksi: p.jenis_transaksi,
                cod: p.cod,
                non_cod: p.non_cod,
                dp: p.dp,
                por: p.por
            };
            Object.entries(vals).forEach(([k, v]) => set(k, v));
            oldPayment = p.nama_file ? {
                path: p.nama_file,
                url: p.bukti_bayar
            } : null;
            $('oldPayment').replaceChildren();
            if (oldPayment) {
                let l = el('p', 'Bukti lama: ' + oldPayment.path + ' ');
                if (oldPayment.url) {
                    let a = el('a', 'Lihat');
                    a.href = oldPayment.url;
                    a.target = '_blank';
                    a.rel = 'noopener noreferrer';
                    l.append(a)
                }
                $('oldPayment').append(l);
                if (oldPayment.url && /\.(jpg|jpeg|png)$/i.test(oldPayment.path)) {
                    let im = el('img');
                    im.src = oldPayment.url;
                    im.alt = 'Bukti pembayaran lama';
                    im.style =
                        'max-width:180px;max-height:130px;object-fit:contain;border-radius:8px;display:block;margin-bottom:12px';
                    $('oldPayment').append(im)
                }
            }
            products = (d.informasiProduk ?? []).map(x => ({
                id: x.id_per_produk ?? x.id ?? null,
                nama_produk: x.produk?.nama_produk ?? x.nama_produk ?? '',
                variasi: x.produk?.variasi ?? x.variasi ?? '',
                jumlah: x.jumlah ?? 1,
                hpp: x.hpp ?? x.hpp_satuan ?? '',
                harga: x.harga ?? x.harga_satuan ?? '',
                sku: x.sku ?? '',
                custom: String((x.sku ?? '').endsWith('C') ? 1 : 0),
                catatan: x.catatan ?? '',
                old: (x.reqfoto ?? []).map(f => ({
                    path: f.nama_file,
                    url: f.foto
                })),
                files: []
            }));
            originalIds = products.map(p => String(p.id));
            render();
            $('missing').textContent =
                'GET berhasil. Periksa field sebelum menyimpan. Foto produk lama hanya untuk referensi. Total HPP, harga dan admin diisi dari GET dan dapat disesuaikan manual.';
            $('status').textContent = '';
            form.hidden = false;
            form.dataset.saved = '0';
            $('submit').disabled = false
        }

        function render() {
            let root = $('products');
            root.replaceChildren();
            products.forEach((p, i) => {
                let card = el('div');
                card.className = 'product';
                let row = el('div');
                row.className = 'row';
                row.append(el('strong', 'Produk ' + (i + 1) + (p.id ? ' · ID ' + p.id : ' · Baru')));
                let del = el('button', 'Hapus produk');
                del.type = 'button';
                del.className = 'danger';
                del.onclick = () => {
                    if (confirm('Hapus produk dari pesanan? Produk akan dihapus saat update.')) {
                        products.splice(i, 1);
                        render()
                    }
                };
                row.append(del);
                card.append(row);
                let grid = el('div');
                grid.className = 'grid';
                for (let [k, t, type] of [
                        ['nama_produk', 'Nama produk (informasi)', 'text'],
                        ['variasi', 'Variasi (informasi)', 'text'],
                        ['sku', 'SKU', 'text'],
                        ['jumlah', 'Jumlah', 'number'],
                        ['catatan', 'Catatan', 'textarea'], ...(p.id === null ? [
                            ['hpp', 'HPP produk baru', 'number'],
                            ['harga', 'Harga produk baru', 'number']
                        ] : [])
                    ]) {
                    let l;
                    if (type === 'custom') {
                        l = el('label', t);
                        let s = el('select');
                        for (let [v, t] of [
                                ['', 'Pilih'],
                                ['0', 'Tidak'],
                                ['1', 'Ya']
                            ]) {
                            let o = el('option', t);
                            o.value = v;
                            s.append(o)
                        }
                        s.required = true;
                        s.value = p[k];
                        l.append(s)
                    } else {
                        l = field('product_' + i + '_' + k, t, type, k === 'catatan', p[k]);
                        let f = l.lastChild;
                        if (['nama_produk', 'variasi'].includes(k)) {
                            f.readOnly = true;
                            f.required = false;
                            f.removeAttribute('name');
                        }
                        if (k === 'sku' && p.id !== null) f.readOnly = true;
                        if (k === 'jumlah') f.min = 1;
                        if (k === 'hpp' || k === 'harga') f.step = '0.01'
                    }
                    if (['hpp', 'harga', 'custom'].includes(k) && p[k] === '') {
                        l.lastChild.classList.add('missing-field');
                        l.append(el('small', 'Belum tersedia dari API'));
                    }
                    l.querySelector('input,select,textarea').oninput = e => {
                        if (k === 'sku' && p[k] !== e.target.value) {
                            p.hpp = '';
                            p.harga = '';
                        }
                        p[k] = e.target.value;
                        sum()
                    };
                    grid.append(l)
                }
                card.append(grid);
                card.append(el('p', 'Foto lama: ' + p.old.length + '. Hanya referensi.'));
                let photos = el('div');
                photos.className = 'photos';

                function thumb(url, name, remove) {
                    let b = el('div');
                    b.className = 'photo';
                    let img = el('img');
                    img.alt = 'Foto produk';
                    if (url) img.src = url;
                    b.append(img, el('small', name));
                    let bt = el('button', 'Hapus');
                    bt.type = 'button';
                    if (remove) {
                        bt.onclick = remove;
                        b.append(bt);
                    }
                    photos.append(b)
                }
                p.old.forEach(f => thumb(f.url, f.path ?? 'Foto lama', null));
                p.files.forEach((f, j) => {
                    let u = URL.createObjectURL(f);
                    thumb(u, f.name, () => {
                        p.files.splice(j, 1);
                        render()
                    });
                    photos.lastChild.firstChild.onload = () => URL.revokeObjectURL(u)
                });
                card.append(photos);
                let inp = el('input');
                inp.type = 'file';
                inp.multiple = true;
                inp.onchange = () => {
                    let fs = [...inp.files];
                    p.files.push(...fs);
                    render()
                };
                root.append(card)
            });
            sum()
        }

        function sum() {
            $('sum').textContent = rupiah(Number(get('total_harga').value) || 0)
        }
        get('total_harga').addEventListener('input', sum);
        $('add').onclick = () => {
            products.push({
                id: null,
                nama_produk: '',
                variasi: '',
                sku: '',
                jumlah: 1,
                hpp: '',
                harga: '',
                custom: '',
                catatan: '',
                old: [],
                files: []
            });
            render()
        };

        function payload() {
            let fd = new FormData();
            if ($('method').value !== 'POST') fd.append('_method', $('method').value);
            for (const fields of Object.values(specs))
                for (const [k, , type] of fields) {
                    let v = get(k).value;
                    if (type === 'datetime-local' && v) v = v.replace('T', ' ') + (v.length === 16 ? ':00' : '');
                    if (['total_hpp', 'total_harga', 'total_admin'].includes(k) && v !== '') v = String(Number(v));
                    fd.append(k, v)
                }
            for (const input of form.querySelectorAll('input[type="file"][name]')) {
                if (input.closest('.product')) continue;
                if (input.files.length) fd.append(input.name, input.files[0], input.files[0].name);
            }
            products.forEach((p, i) => {
                let prefix = `pesanan_perproduk[${i}]`;
                for (let k of ['id', 'jumlah', 'hpp', 'harga', 'sku', 'catatan'])
                    if (k !== 'id' || p.id !== null) fd.append(`${prefix}[${k}]`, p[k] ?? '');
            });
            return fd
        }

        function headers() {
            const token = $('token').value.trim().replace(/^Bearer\s+/i, '');
            if (!token) throw Error('Isi Bearer Token terlebih dahulu.');
            return {
                Accept: 'application/json',
                Authorization: 'Bearer ' + token
            };
        }
        async function request(url, opts) {
            const target = new URL(url, location.href);
            if (target.origin !== 'https://pembukuan.test') throw Error('Endpoint harus pada https://pembukuan.test');
            $('debugFrame').hidden = true;
            $('debugFrame').srcdoc = '';
            $('rawResponse').textContent = '';
            $('httpStatus').textContent = 'Mengirim request...';
            let r = await fetch(target, {
                credentials: 'omit',
                redirect: 'error',
                ...opts
            });
            let raw = await r.text();
            $('httpStatus').textContent = 'HTTP ' + r.status + ' ' + r.statusText;
            let d;
            try {
                d = JSON.parse(raw);
                $('rawResponse').textContent = JSON.stringify(d, null, 2)
            } catch {
                $('rawResponse').textContent = raw;
                if (raw.includes('sf-dump') || (r.headers.get('content-type') || '').includes('text/html')) {
                    $('debugFrame').srcdoc = raw;
                    $('debugFrame').hidden = false;
                }
                throw Error('Response bukan JSON. Lihat Response API / DD Laravel.');
            }
            if (!r.ok || d.success === false) throw Error(JSON.stringify(d.errors ?? d.message ?? d, null, 2));
            return d;
        }
        // Endpoint GET dan update pesanan.
        const API = {
            detail: 'https://pembukuan.test/api/pesanan/edit/{no_pesanan}',
            update: 'https://pembukuan.test/api/pesanan/update'
        };
        $('getUrl').value = API.detail;
        $('postUrl').value = API.update;
        async function loadPesanan() {
            const noPesanan = $('lookup').value.trim();
            if (busy) return;
            if (!noPesanan) {
                $('missing').textContent = 'Masukkan nomor pesanan.';
                return;
            }
            form.hidden = true;
            $('submit').disabled = true;
            $('load').disabled = true;
            $('missing').textContent = 'Mengambil data pesanan...';
            try {
                const url = $('getUrl').value.replace(/\{no_pesanan\}|%7Bno_pesanan%7D/gi, encodeURIComponent(
                    noPesanan));
                const response = await request(url, {
                    method: 'GET',
                    headers: headers()
                });
                if (!(response.data ?? response)?.pesanan) throw Error('Response API tidak memuat data pesanan.');
                populate(response);
            } catch (e) {
                $('missing').textContent = 'Gagal mengambil data: ' + e.message;
            } finally {
                $('load').disabled = false;
            }
        }

        function updatePaymentPreview() {
            const paymentFile = $('buktiFile').files[0];
            const root = $('newPaymentPreview');
            root.replaceChildren();
            if (paymentPreviewUrl) {
                URL.revokeObjectURL(paymentPreviewUrl);
                paymentPreviewUrl = null;
            }
            $('fileInfo').textContent = paymentFile ? 'FILE BARU SIAP DIKIRIM: ' + paymentFile.name + ' (' + paymentFile
                .size + ' byte)' : 'Belum ada file baru dipilih — bukti lama tidak akan diubah.';
            if (paymentFile && paymentFile.type.startsWith('image/')) {
                paymentPreviewUrl = URL.createObjectURL(paymentFile);
                const img = el('img');
                img.src = paymentPreviewUrl;
                img.alt = 'Preview lokal file baru (belum diupload)';
                img.style = 'max-width:220px;max-height:180px;object-fit:contain;display:block';
                root.append(img, el('small', 'Preview lokal — belum membuktikan upload berhasil.'));
            }
        }

        function clearPaymentSelection() {
            $('buktiFile').value = '';
            updatePaymentPreview();
        }

        function capturePayment() {
            updatePaymentPreview();
            showUploadDebug(payload());
        }
        $('buktiFile').addEventListener('change', capturePayment);
        $('buktiFile').addEventListener('input', capturePayment);

        $('clearPayment').onclick = () => {
            clearPaymentSelection();
            showUploadDebug(payload());
        };

        function showUploadDebug(fd) {
            const files = [...fd.entries()].filter(([k, v]) => v instanceof File).map(([k, v]) => ({
                field: k,
                nama: v.name,
                ukuran: v.size,
                tipe: v.type
            }));
            $('uploadDebug').textContent = JSON.stringify({
                method: 'POST',
                method_backend: $('method').value,
                jumlah_file: files.length,
                files
            }, null, 2);
        }
        $('load').onclick = loadPesanan;
        $('lookup').addEventListener('keydown', e => {
            if (e.key === 'Enter') loadPesanan();
        });
        $('preview').onclick = () => {
            if (form.hidden) return;
            try {
                const fd = payload();
                showUploadDebug(fd);
                $('status').textContent = JSON.stringify([...fd].map(([k, v]) => [k, v instanceof File ? {
                    nama: v.name,
                    ukuran: v.size,
                    tipe: v.type
                } : v]), null, 2)
            } catch (e) {
                $('status').textContent = e.message
            }
        };
        form.onsubmit = async e => {
            e.preventDefault();
            if (form.hidden || busy || form.dataset.saved === '1') return;
            try {
                if (!$('postUrl').value) throw Error('Isi URL submit pada konfigurasi API.');
                if (!products.length) throw Error(
                    'Minimal satu produk wajib disertakan untuk mencegah penghapusan seluruh produk.');
                const removed = originalIds.filter(id => !products.some(p => String(p.id) === id));
                if (!confirm('Simpan pesanan ' + loadedOrder + '? ' + (removed.length ?
                        'Produk yang akan dihapus permanen beserta foto: ' + removed.join(', ') :
                        'Tidak ada produk lama yang dihapus.'))) return;
                let fd = payload();
                showUploadDebug(fd);
                busy = true;
                $('load').disabled = true;
                $('submit').disabled = true;
                console.table([...fd.entries()].filter(([k, v]) => v instanceof File).map(([k, v]) => ({
                    field: k,
                    name: v.name,
                    size: v.size
                })));
                let d = await request($('postUrl').value.replace('{no_pesanan}', encodeURIComponent(get(
                    'no_pesanan').value)), {
                    method: 'POST',
                    headers: headers(),
                    body: fd
                });
                form.dataset.saved = '1';
                $('status').textContent = (d.message ?? 'Perubahan berhasil disimpan.') +
                    ' — GET ulang secara manual untuk memeriksa data. Preview lokal bukan bukti file tersimpan. Periksa file di server; ambil ulang data sebelum menyimpan perubahan berikutnya.'
            } catch (e) {
                $('status').textContent = e.message
            } finally {
                busy = false;
                $('load').disabled = false;
                $('submit').disabled = form.hidden || form.dataset.saved === '1';
                $('status').scrollIntoView({
                    behavior: 'smooth',
                    block: 'center'
                })
            }
        };
        // GET otomatis saat halaman dibuka dengan parameter nomor pesanan.
        const noPesanan = new URLSearchParams(window.location.search).get('no_pesanan');
        if (noPesanan) {
            $('lookup').value = noPesanan;
        }
        $('missing').textContent = 'Isi Bearer Token dan nomor pesanan, lalu klik Ambil data pesanan.';
        document.querySelector('details').open = true;
    </script>
</body>

</html>
