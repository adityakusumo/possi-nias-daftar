# 📦 Panduan Deploy Laravel + MariaDB di VPS Ubuntu 24.04

## Informasi Konfigurasi

| Parameter | Value |
|---|---|
| OS VPS | Ubuntu 24.04 LTS |
| PHP Version | 8.4.x |
| Database | MariaDB |
| User Ubuntu | `itpossijatim` |
| User MariaDB | `itpossi` |
| Nama Database | `dbnias` |
| Web Server | Nginx + PHP-FPM 8.4 |

## Prasyarat

Pastikan hal-hal berikut sudah terpenuhi:

- Akses SSH ke VPS Ubuntu 24.04 sebagai user `itpossijatim`
- PHP 8.4 sudah terinstall (`php8.4`, `php8.4-fpm`, dll)
- Composer sudah terinstall
- Git sudah terinstall
- Nginx sudah terinstall
- MariaDB sudah terinstall

---

## Langkah 1: Setup MariaDB

### 1.1 Login ke MariaDB sebagai root

```bash
sudo mariadb -u root
```

### 1.2 Buat user dan database

Jalankan perintah berikut di dalam prompt MariaDB:

```sql
CREATE DATABASE dbnias CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'itpossi'@'localhost' IDENTIFIED BY 'PASSWORD_KAMU';
GRANT ALL PRIVILEGES ON dbnias.* TO 'itpossi'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

> **📌 Catatan:** Ganti `PASSWORD_KAMU` dengan password yang kuat. Simpan password ini karena akan dipakai di file `.env` Laravel.

### 1.3 Verifikasi user dan database

```sql
sudo mariadb -u root
SHOW DATABASES;
SELECT User, Host FROM mysql.user;
EXIT;
```

---

## Langkah 2: Clone Project dari GitHub

### 2.1 Masuk ke direktori web server

```bash
cd /var/www
```

### 2.2 Clone repository

```bash
sudo git clone https://github.com/USERNAME/NAMA_REPO.git
```

> **📌 Catatan:** Ganti `USERNAME` dan `NAMA_REPO` sesuai repository GitHub kamu.

### 2.3 Set kepemilikan folder

```bash
sudo chown -R itpossijatim:www-data /var/www/NAMA_REPO
sudo chmod -R 755 /var/www/NAMA_REPO
```

### 2.4 Masuk ke folder project

```bash
cd /var/www/NAMA_REPO
```

---

## Langkah 3: Install Dependencies Composer

```bash
composer install --no-dev --optimize-autoloader
```

> **✅ Tip:** Jika muncul error permission, jalankan: `sudo chown -R itpossijatim:www-data /var/www/NAMA_REPO`

---

## Langkah 4: Konfigurasi File .env

### 4.1 Salin file .env dari contoh

```bash
cp .env.example .env
```

### 4.2 Edit file .env

```bash
nano .env
```

Sesuaikan konfigurasi berikut:

```env
APP_NAME=NamaAplikasimu
APP_ENV=production
APP_KEY=
APP_DEBUG=false

# Jika punya domain: APP_URL=https://domainmu.com
# Jika tidak punya domain: APP_URL=http://IP_PUBLIC_VPS
APP_URL=http://IP_PUBLIC_VPS_ATAU_DOMAIN

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=dbnias
DB_USERNAME=itpossi
DB_PASSWORD=PASSWORD_KAMU
```

> **📌 Catatan:** Simpan file dengan `CTRL+O` lalu Enter, kemudian keluar dengan `CTRL+X`.

### 4.3 Generate Application Key

```bash
php artisan key:generate
```

> **✅ Tip:** Perintah ini akan otomatis mengisi `APP_KEY` di file `.env` kamu.

---

## Langkah 5: Migrasi Database

### 5.1 Jalankan migrasi

```bash
php artisan migrate --force
```

### 5.2 (Opsional) Jalankan seeder

Jika project kamu memiliki data seeder:

```bash
php artisan db:seed --force
```

> **✅ Tip:** `--force` diperlukan agar artisan tidak menampilkan prompt konfirmasi di environment production.

---

## Langkah 6: Restore Database dari File .sql

Gunakan langkah ini jika kamu sudah punya file `.sql` existing (backup dari database lama) dan ingin me-restore-nya ke database `dbnias`, **sebagai pengganti** `php artisan migrate` di Langkah 5.

### 6.1 Upload file .sql ke VPS

Dari komputer lokal kamu, jalankan perintah berikut di terminal lokal (bukan di VPS):

```bash
scp /path/lokal/namafile.sql itpossijatim@IP_PUBLIC_VPS:/home/itpossijatim/
```

> **📌 Catatan:** Ganti `/path/lokal/namafile.sql` dengan lokasi file `.sql` di komputer kamu, dan `IP_PUBLIC_VPS` dengan IP VPS kamu.

### 6.2 Masuk ke VPS dan verifikasi file terupload

```bash
ssh itpossijatim@IP_PUBLIC_VPS
ls -lh ~/namafile.sql
```

### 6.3 Restore file .sql ke database dbnias

```bash
mariadb -u itpossi -p dbnias < ~/namafile.sql
```

Kamu akan diminta memasukkan password user `itpossi`. Tunggu hingga proses selesai (tidak ada output berarti sukses).

> **✅ Tip:** Jika file `.sql` berukuran besar, gunakan perintah berikut agar tidak timeout:
> ```bash
> nohup mariadb -u itpossi -p dbnias < ~/namafile.sql &
> ```

### 6.4 Verifikasi hasil restore

```bash
mariadb -u itpossi -p dbnias
```

Lalu di dalam prompt MariaDB:

```sql
SHOW TABLES;
SELECT COUNT(*) FROM nama_tabel_utama;
EXIT;
```

> **📌 Catatan:** Ganti `nama_tabel_utama` dengan salah satu nama tabel di database kamu untuk memastikan data sudah masuk.

### 6.5 Bersihkan file .sql setelah selesai (opsional tapi disarankan)

```bash
rm ~/namafile.sql
```

---

## Langkah 7: Set Permission Storage & Cache

```bash
sudo chown -R itpossijatim:www-data /var/www/possi-nias-daftar/nias-app/storage
sudo chown -R itpossijatim:www-data /var/www/possi-nias-daftar/nias-app/bootstrap/cache
sudo chmod -R 775 /var/www/possi-nias-daftar/nias-app/storage
sudo chmod -R 775 /var/www/possi-nias-daftar/nias-app/bootstrap/cache
```

> **📌 Catatan:** Permission yang benar pada folder `storage` dan `bootstrap/cache` sangat penting agar Laravel bisa menulis log, cache, dan session.

---

## Langkah 8: Konfigurasi Nginx

### 8.1 Buat file konfigurasi Nginx

```bash
sudo nano /etc/nginx/sites-available/NAMA_REPO
```

Isi dengan konfigurasi berikut:

```nginx
server {
    listen 80;
    server_name IP_PUBLIC_VPS_ATAU_DOMAIN;
    root /var/www/NAMA_REPO/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

> **📌 Catatan:** Ganti `IP_PUBLIC_VPS_ATAU_DOMAIN` dan `NAMA_REPO` sesuai milikmu.

### 8.2 Aktifkan konfigurasi Nginx

```bash
sudo ln -s /etc/nginx/sites-available/NAMA_REPO /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

> **✅ Tip:** Pastikan output `nginx -t` menunjukkan: `syntax is ok` dan `test is successful`.

### 8.3 Pastikan PHP-FPM 8.4 berjalan

```bash
sudo systemctl enable php8.4-fpm
sudo systemctl start php8.4-fpm
sudo systemctl status php8.4-fpm
```

---

## Langkah 9: Optimasi Laravel untuk Production

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

> **✅ Tip:** Jalankan perintah ini setiap kali ada perubahan konfigurasi atau kode baru.

---

## Langkah 10: Update Project (Workflow Git Pull)

Setiap kali ada perubahan kode dari GitHub, jalankan urutan perintah ini:

```bash
cd /var/www/NAMA_REPO
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
sudo systemctl restart php8.4-fpm
sudo systemctl restart nginx
```

> **📌 Catatan:** Ganti `main` dengan nama branch kamu jika berbeda (misalnya `master`).

---

## Langkah 11: Menghubungkan Domain ke VPS

### 11.1 Setting DNS Zone di Registrar (contoh: Hostinger)

Login ke panel registrar domain kamu, masuk ke menu **DNS Zone**, lalu pastikan record berikut sudah ada:

| Jenis | Nama | Konten | TTL |
|---|---|---|---|
| A | `@` | `103.127.99.32` | 3600 |
| CNAME | `www` | `possijatim.my.id` | 300 |

> **📌 Catatan:** Record `A` mengarahkan domain utama ke IP VPS. Record `CNAME` mengarahkan `www` ke domain utama. Kedua record ini wajib ada.

### 11.2 Cek propagasi DNS

Tunggu 5–30 menit setelah setting DNS, lalu cek apakah sudah propagasi:

```
https://dnschecker.org/#A/possijatim.my.id
```

Jika sudah muncul IP `103.127.99.32` di banyak lokasi (hijau semua), DNS sudah aktif.

### 11.3 Update server_name di Nginx

```bash
sudo nano /etc/nginx/sites-available/nias-app
```

Ubah bagian `server_name`:

```nginx
server_name possijatim.my.id www.possijatim.my.id;
```

Simpan, lalu test dan restart Nginx:

```bash
sudo nginx -t
sudo systemctl restart nginx
```

### 11.4 Update APP_URL di .env Laravel

```bash
nano /var/www/possi-nias-daftar/nias-app/.env
```

Ubah:

```env
APP_URL=http://possijatim.my.id
```

Lalu clear config cache:

```bash
cd /var/www/possi-nias-daftar/nias-app
php artisan config:cache
```

### 11.5 Test akses domain

Buka browser dan akses:

```
http://possijatim.my.id
http://www.possijatim.my.id
```

---

## Langkah 12: Pasang SSL Gratis (HTTPS) dengan Certbot

Agar website bisa diakses via `https://` dengan sertifikat SSL gratis dari Let's Encrypt:

### 12.1 Install Certbot

```bash
sudo apt install certbot python3-certbot-nginx -y
```

### 12.2 Request sertifikat SSL

```bash
sudo certbot --nginx -d possijatim.my.id -d www.possijatim.my.id
```

Ikuti instruksinya:
- Masukkan alamat email (untuk notifikasi renewal)
- Ketik `Y` untuk setuju terms of service
- Pilih apakah mau redirect HTTP ke HTTPS (pilih `2` agar otomatis redirect)

### 12.3 Update APP_URL di .env ke HTTPS

```bash
nano /var/www/possi-nias-daftar/nias-app/.env
```

Ubah:

```env
APP_URL=https://possijatim.my.id
```

Lalu:

```bash
cd /var/www/possi-nias-daftar/nias-app
php artisan config:cache
```

### 12.4 Test akses HTTPS

Buka browser:

```
https://possijatim.my.id
```

> **✅ Sertifikat SSL Let's Encrypt gratis dan auto-renew setiap 90 hari.** Untuk cek jadwal renewal: `sudo certbot renew --dry-run`

---

## Troubleshooting

### Error: 500 Internal Server Error

- Cek log Laravel: `tail -f /var/www/NAMA_REPO/storage/logs/laravel.log`
- Pastikan `APP_KEY` sudah di-generate: `php artisan key:generate`
- Pastikan permission storage sudah benar (lihat Langkah 6)

### Error: Database Connection Refused

- Pastikan MariaDB berjalan: `sudo systemctl status mariadb`
- Cek user dan password di `.env` sesuai dengan yang dibuat di Langkah 1
- Coba koneksi manual: `mariadb -u itpossi -p dbnias`

### Error: Nginx 502 Bad Gateway

- Pastikan PHP-FPM 8.4 berjalan: `sudo systemctl status php8.4-fpm`
- Cek socket path di konfigurasi Nginx: `/run/php/php8.4-fpm.sock`
- Restart: `sudo systemctl restart php8.4-fpm nginx`

### Error: Permission Denied pada Storage

```bash
sudo chown -R www-data:www-data /var/www/NAMA_REPO/storage
sudo chmod -R 775 /var/www/NAMA_REPO/storage
```

---

*Dokumen ini dibuat untuk setup Laravel di VPS Ubuntu 24.04 — itpossijatim*