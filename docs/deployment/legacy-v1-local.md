# Menjalankan aplikasi legacy versi 1 di lokal

## Snapshot yang dipakai

Branch `legacy-v1` dibuat dari commit `e60864d6f358a2b37083c2c223124b03a59e54f5`, parent langsung dari commit `0fad394efc0f71b25e4f6adab518cd0561d070e0` yang menambahkan tagihan, pembayaran cicilan, dan laporan pemasukan. Branch `main` tetap menjadi versi terbaru.

- Versi 1: [branch legacy-v1](https://github.com/mtsh388/webapp-ujian/tree/legacy-v1)
- Versi 2: branch `main`, URL aplikasi `/v2`

## Siapkan source versi 1 di Windows

Jalankan PowerShell:

```powershell
cd C:\Herd
git clone --branch legacy-v1 https://github.com/mtsh388/webapp-ujian.git webapp-ujian-v1
cd .\webapp-ujian-v1
composer install
Copy-Item .env.example .env
```

Isi file `.env` versi 1 dengan konfigurasi lokal yang sesuai. Untuk pengujian aman, gunakan database lokal salinan/terpisah terlebih dahulu. Jangan jalankan `php artisan migrate:fresh`, `migrate:refresh`, atau rollback terhadap database yang digunakan versi 2. Jika memakai database yang sama, pastikan migration versi 2 tidak mengubah atau menghapus struktur yang diperlukan versi 1.

Buat application key hanya bila `.env` versi 1 belum memiliki `APP_KEY`:

```powershell
php artisan key:generate
php artisan optimize:clear
php artisan route:list
```

## Tentang URL /v1

Membuat branch legacy tidak otomatis memasang source tersebut ke `http://webapp-ujian.test/v1`. Herd harus dikonfigurasi agar permintaan `/v1/*` diteruskan ke aplikasi legacy, sementara `/v2/*` tetap dilayani source utama. Kedua aplikasi Laravel perlu menangani prefix dan asset URL dengan benar; konfigurasi rewrite generik dapat merusak login, form POST/CSRF, asset, dan URL hasil redirect.

Karena konfigurasi site Herd lokal tidak dapat diubah melalui commit GitHub, setelah clone branch `legacy-v1` lakukan konfigurasi routing web server lokal untuk subpath `/v1`, lalu uji setidaknya login/logout, asset CSS/JS, halaman ujian, pengerjaan ujian, dan hasil ujian. Sampai pemetaan tersebut dibuat, `/v1` di site versi 2 dapat tetap menampilkan 404.