# Deployment multi-versi untuk demo-bimbel

## Tujuan

- `/` menampilkan halaman pemilihan versi.
- `/v1` tetap diarahkan ke aplikasi lama (tanpa fitur keuangan).
- `/v2` menjalankan kode terbaru dari branch `main`, termasuk fitur keuangan.
- Kedua versi boleh menggunakan database yang sama hanya setelah migration versi terbaru dipastikan kompatibel dengan versi lama.

## Perubahan aplikasi

Aplikasi versi terbaru menggunakan `APP_ROUTE_PREFIX=v2`. Semua route web dan autentikasi aplikasi terbaru berada di bawah prefix tersebut, sehingga URL seperti login menjadi `/v2/login` dan dashboard menjadi `/v2/dashboard`.

Variabel lingkungan:

```dotenv
APP_URL=https://demo-bimbel.dtgroup.co.id
APP_ROUTE_PREFIX=v2
LEGACY_APP_URL=/v1
SESSION_PATH=/v2
SESSION_COOKIE=webapp_ujian_v2_session
```

Jangan menambahkan `/v2` ke `APP_URL`; prefix route diatur melalui `APP_ROUTE_PREFIX`. Pertahankan asset URL ke root domain kecuali konfigurasi web server memang memakai strategi asset terpisah. Cookie sesi versi 2 dibatasi ke path `/v2` dan memakai nama tersendiri agar tidak bertabrakan dengan sesi aplikasi lama.

## Arsitektur server yang diperlukan

Repository ini hanya berisi kode versi terbaru. Kode versi lama tidak disalin atau ditimpa oleh perubahan ini. Agar `/v1` tetap hidup, konfigurasi web server harus memetakan `/v1` ke direktori publik aplikasi lama, dan memetakan permintaan aplikasi baru ke `public/index.php` versi terbaru. Karena Laravel biasanya dihidangkan dari document root `public`, implementasi pemetaan subpath harus disesuaikan dengan konfigurasi Nginx/PHP-FPM server yang sebenarnya; jangan menebak atau menyalin konfigurasi ke production tanpa menguji syntax dan routing.

Sebelum mengaktifkan konfigurasi server:
1. Backup source code lama dan database.
2. Pasang source terbaru di direktori terpisah.
3. Konfigurasikan document root/handler PHP untuk kedua aplikasi.
4. Audit semua migration terlebih dahulu.
5. Jalankan migration hanya setelah memastikan tabel/kolom lama tidak dihapus atau diubah secara tidak kompatibel.
6. Uji login, logout, asset, upload, ujian, hasil ujian, serta seluruh fitur keuangan di `/v2`; pastikan alur lama tetap berfungsi di `/v1`.
7. Siapkan cara rollback kode tanpa memulihkan snapshot database lama yang bisa menghapus transaksi baru.

## Pengembangan lokal

Setelah menarik perubahan terbaru dan membersihkan cache:

```powershell
git pull origin main
# Sesuaikan .env lokal dengan SESSION_PATH=/v2 dan SESSION_COOKIE=webapp_ujian_v2_session
php artisan optimize:clear
php artisan route:list --path=v2
```

Dengan konfigurasi default, uji halaman pemilihan pada `http://webapp-ujian.test/` dan login versi terbaru pada `http://webapp-ujian.test/v2/login`. Jalur `/v1` hanya akan berfungsi secara lokal jika aplikasi legacy juga dipetakan oleh web server lokal; repository ini tidak berisi source legacy tersebut.

## Catatan database bersama

Menggunakan satu database berarti migration dan operasi tulis versi terbaru dapat memengaruhi versi lama. Hindari migration destruktif, perubahan nama kolom tanpa kompatibilitas, atau perubahan arti status yang digunakan kode lama. Backup adalah langkah pemulihan, bukan pengganti pengujian kompatibilitas.