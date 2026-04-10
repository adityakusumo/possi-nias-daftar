# ⚠️ PENTING: PENGINGAT BACKUP DATA ⚠️
> **INGAT:** Seluruh data di server VPS (termasuk Database MariaDB dan file di folder `storage`) bersifat sewa. Jika langganan VPS berakhir atau tidak diperpanjang, data akan **DIHAPUS PERMANEN** oleh provider.
> 
> **Tindakan Rutin:**
> 1. Selalu lakukan `git push` setelah ada perubahan kode.
> 2. Lakukan Export Database (`mysqldump`) secara berkala dan simpan file `.sql` di laptop Arch lokal.
> 3. Backup folder `storage/app/public` secara manual karena file ini tidak masuk ke Git.

---

## 🚀 Laravel Project: [Nama Project Kamu]

Project ini adalah aplikasi berbasis framework Laravel yang dideploy menggunakan stack **LEMP** (Linux, Nginx, MariaDB, PHP).

### 📂 Isi Repositori (Git)
Repositori ini berisi kode sumber aplikasi Laravel, yang meliputi:
- **App Logic:** Controller, Models, dan Service di folder `app/`.
- **Resources:** View (Blade templates), CSS, dan JavaScript mentah.
- **Routes:** Konfigurasi URL aplikasi.
- **Database Migrations:** Struktur tabel database (bukan datanya).
- **Konfigurasi:** File konfigurasi aplikasi (kecuali `.env`).

*Catatan: File sensitif seperti `.env`, folder `vendor/`, dan `node_modules/` sengaja diabaikan melalui `.gitignore` demi keamanan dan efisiensi.*

### 🛠️ Spesifikasi Lingkungan (Environment)
Aplikasi ini dikembangkan dan diuji pada lingkungan berikut:
- **OS Lokal:** Arch Linux
- **OS Server:** Ubuntu 24.04 LTS (Rekomendasi)
- **PHP:** 8.3.x
- **Database:** MariaDB 10.11.x
- **Web Server:** Nginx

### 🔧 Cara Instalasi di Server Baru
Jika kamu pindah ke VPS baru, ikuti langkah ini:

1. **Clone Project:**

   ```bash
   git clone [https://github.com/adityakusumo/possi-nias-daftar.git](https://github.com/adityakusumo/possi-nias-daftar.git)

   cd possi-nias-daftar
   ```

2. **Instalasi Dependencies:**
    ```bash
    composer install --no-dev --optimize-autoloader
    npm install && npm run build
    ```

3. **Konfigurasi Environment:**

    Salin .env.example menjadi .env.

    Sesuaikan konfigurasi database dan APP_URL.

    Jalankan 
    ```bash
    php artisan key:generate.
    ```

4. **Persiapan Database:**

    Buat database baru di MariaDB.

    Import dump file .sql (jika ada data lama):

    ```bash

    mariadb -u user -p nama_db < backup_terakhir.sql

    ```

    Jalankan migrasi: 
    
    ```bash
    php artisan migrate.
    ```

5. **Izin Folder (Permissions):**
    
    ```bash

    sudo chown -R www-data:www-data storage bootstrap/cache

📈 Rencana Pengembangan

    [ ] Implementasi SSL dengan Certbot (Let's Encrypt).

    [ ] Setup Task Scheduling (Cron Jobs) untuk Laravel.

    [ ] Otomatisasi backup database ke cloud eksternal.

Dibuat untuk dokumentasi belajar hosting mandiri.


### Penjelasan Tambahan:

1.  **Struktur Folder:** README ini membantu kamu (atau orang lain yang membaca kode kamu) untuk tahu cara melakukan *deployment* ulang dengan cepat.
2.  **Keamanan:** Bagian "Isi Repositori" menegaskan bahwa data rahasia tidak ada di Git, sehingga kamu diingatkan untuk selalu menyiapkan file `.env` secara manual di setiap server baru.
3.  **Konsistensi Versi:** Saya mencantumkan versi PHP 8.3 dan MariaDB 10.11 sesuai rencana *downgrade* yang kita bahas agar tetap sinkron dengan VPS Ubuntu.