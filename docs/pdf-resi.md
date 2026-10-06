# PDF resi terkompresi

Import membaca dan menyimpan PDF asli tanpa Ghostscript. Saat cetak, `CompatibleFpdi` mencoba FPDI biasa terlebih dahulu. Jika FPDI menolak compressed cross-reference, script `scripts/pdf-compatible.cjs` menulis salinan dengan object streams dinonaktifkan menggunakan pdf-lib. File asli tidak diubah.

Hasil disimpan di `storage/app/private/resi_pdf_cache`, berdasarkan SHA-256 isi file. Cetak berikutnya menggunakan salinan tersebut. Penguncian file mencegah dua request membuat cache yang sama bersamaan. PDF biasa tidak membutuhkan proses Node.js.

Server cetak membutuhkan Node.js dan dependency production:

```sh
npm ci --omit=dev --ignore-scripts
```

Jika Node.js tidak tersedia pada PATH proses PHP, tambahkan path executable ke `.env`, misalnya:

```dotenv
PDF_NODE_BINARY="C:/laragon/bin/nodejs/node-v18/node.exe"
```

Pada Linux, gunakan path executable Node.js yang terpasang di server. Jika konfigurasi Laravel di-cache, jalankan `php artisan config:cache` setelah mengubah pengaturan.

Cache dapat dihapus saat tidak ada proses cetak berjalan; cetak selanjutnya akan membuatnya kembali.

Verifikasi:

```sh
php vendor/bin/phpunit tests/Feature/CompatibleFpdiTest.php
```
