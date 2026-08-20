# Sync NIAS dari DBNIAS.mdb — Panduan Penggunaan

Script **`sync_nias_mdb.py`** memperbarui tabel `NIAS` di MariaDB (database `dbnias`
milik aplikasi Laravel POSSI Jatim) dari file database **MS Access 2003** (`DBNIAS.mdb`)
yang dikeluarkan oleh kantor POSSI.

> **Penting:** sebelum menulis apa pun ke database, script **selalu membuat backup**
> penuh `dbnias` terlebih dahulu. Jika backup gagal, proses dihentikan.

---

## 1. Ringkasan

- Tabel `NIAS` di MariaDB adalah **mirror read-only** dari tabel `NIAS` di Access.
  Aplikasi Laravel hanya membaca tabel ini (model `NiasExisting`), tidak pernah menulis.
- Kunci alami tiap baris adalah kolom **`NONIAS`** (Nomor Induk Anggota Selam), unik dan
  tidak pernah kosong di data sumber.
- Perilaku sinkronisasi per baris dari file `.mdb`:
  - `NONIAS` belum ada di MariaDB → **INSERT** (baris baru)
  - `NONIAS` ada dan ada kolom yang berbeda → **UPDATE**
  - `NONIAS` ada dan identik → dilewati (tidak menyentuh apa pun)
  - Baris yang ada di MariaDB tapi **tidak ada** di `.mdb` → **dibiarkan** secara default
    (gunakan `--deactivate-missing` jika ingin menandainya `STATUS='0'`)

Script **tidak pernah menghapus data**.

---

## 2. Kebutuhan (Requirements)

| Komponen | Keterangan | Cara cek / install |
|---|---|---|
| Python 3 (stdlib only) | Sudah ada di Ubuntu | `python3 --version` |
| `mdb-export` (mdbtools) | Membaca file `.mdb` | `sudo apt install mdbtools` |
| `mariadb` / `mariadb-dump` | Client database | `sudo apt install mariadb-client` |
| `.env` | Kredensial DB dibaca dari `<app>/.env` | sudah ada di `nias-app/.env` |

Semua nilai DB (`DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`)
diambil otomatis dari `.env` — tidak perlu diketik manual.

---

## 3. Lokasi File

```
nias-app/
├── scripts/
│   ├── sync_nias_mdb.py      ← script utama
│   └── README.md             ← panduan ini
└── database_backup/          ← backup otomatis tersimpan di sini
```

---

## 4. Cara Pakai

Jalankan dari dalam folder `nias-app`:

```bash
cd /home/adit/Documents/GitHub/possi-nias-daftar/nias-app

# 1) Sinkronisasi penuh (backup dulu, lalu update)
python3 scripts/sync_nias_mdb.py

# 2) Dry-run: backup + analisis saja, TIDAK menulis ke database
python3 scripts/sync_nias_mdb.py --dry-run

# 3) Sinkronisasi + nonaktifkan baris yang hilang dari .mdb
python3 scripts/sync_nias_mdb.py --deactivate-missing

# 4) Pakai file .mdb lain
python3 scripts/sync_nias_mdb.py --mdb "/home/adit/Downloads/DBNIAS(1).mdb"

# 5) Tampilkan detail tiap baris yang berubah
python3 scripts/sync_nias_mdb.py --verbose
```

### Opsi lengkap

| Opsi | Fungsi |
|---|---|
| `--mdb PATH` | Lokasi file `.mdb` (default: `/home/adit/Downloads/DBNIAS(1).mdb`) |
| `--app-dir DIR` | Folder Laravel yang berisi `.env` (default: induk dari folder `scripts/`) |
| `--backup-dir DIR` | Folder backup (default: `<app>/database_backup`) |
| `--dry-run` | Backup + analisis saja, tidak menulis apa pun |
| `--deactivate-missing` | Set `STATUS='0'` untuk `NONIAS` yang ada di MariaDB tapi hilang dari `.mdb` |
| `--verbose` | Cetak detail setiap baris yang di-INSERT / di-UPDATE |

---

## 5. Alur Proses (5 Langkah)

Setiap run mencetak langkah `[1/5]` sampai `[5/5]`:

1. **Backup wajib** — `mariadb-dump` penuh database `dbnias` → `database_backup/YYYYmmdd_HHMMSS_dbnias_backup.sql`. Berhenti jika gagal.
2. **Export `.mdb`** — `mdb-export` membaca tabel `NIAS` dari file Access; tanggal dinormalisasi ke `YYYY-MM-DD`.
3. **Baca kondisi MariaDB** — seluruh baris `NIAS` saat ini diambil untuk dibandingkan.
4. **Hitung perbedaan** — tentukan baris baru (INSERT), berubah (UPDATE), dan identik (skip).
5. **Terapkan dalam satu transaksi** — semua INSERT/UPDATE dijalankan dalam `START TRANSACTION ... COMMIT`; jika ada error, `ROLLBACK` otomatis.

Contoh keluaran run yang sukses:

```
[1/5] Backing up database 'dbnias' -> .../20260820_120504_dbnias_backup.sql
      backup OK (937334 bytes)
[2/5] Exporting NIAS from the .mdb file
[3/5] Reading current NIAS rows from MariaDB
[4/5] Computing differences
      mdb rows           : 3344
      MariaDB rows       : 3329
      to INSERT          : 15
      to UPDATE          : 44
      identical (skip)   : 3285
      missing from mdb   : 0  (left untouched)
[5/5] Applying changes inside a transaction
      OK: 15 inserted, 44 updated, 3285 unchanged.
      Backup: .../database_backup/20260820_120504_dbnias_backup.sql
```

---

## 6. Backup & Restore

### Backup otomatis
- Lokasi: `nias-app/database_backup/`
- Format nama: `YYYYmmdd_HHMMSS_dbnias_backup.sql` (mengikuti pola backup lama di folder itu)
- Dibuat **sebelum** setiap update, jadi selalu ada titik pemulihan.

### Restore manual (jika perlu)
```bash
cd /home/adit/Documents/GitHub/possi-nias-daftar/nias-app
set -a && . ./.env && set +a
mariadb -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" \
  < database_backup/20260820_120504_dbnias_backup.sql
```

### Verifikasi hasil sinkronisasi
```bash
cd /home/adit/Documents/GitHub/possi-nias-daftar/nias-app
set -a && . ./.env && set +a
mariadb -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USERNAME" -p"$DB_PASSWORD" "$DB_DATABASE" \
  -e "SELECT COUNT(*) AS total FROM NIAS; \
      SELECT NONIAS, NAMA, STATUS, EXPIRED FROM NIAS WHERE STATUS='1' LIMIT 5;"
```

---

## 7. Catatan Data (Penting)

- **STATUS** — tersimpan sebagai string `'1'` (aktif) / `'0'` (non-aktif), sama seperti di `.mdb`.
- **GENDER** — `'Pa'` = laki-laki, `'Pi'` = perempuan (format asli Access, bukan `L`/`P`).
- **Tanggal** — `TGLLAHIR` dan `EXPIRED` ditulis sebagai `YYYY-MM-DD`.
- **Kolom kosong** — string kosong di `.mdb` disimpan sebagai `NULL` di MariaDB.
- **`ID`** — selalu dibiarkan auto-increment; `ID` dari Access tidak dipakai agar tidak
  bentrok, dan tidak ada tabel lain yang mereferensikan `NIAS.ID` (sudah dicek via FK).
- **Baris yang identik tidak ditulis ulang** — script membandingkan nilai semua kolom
  sebelum memutuskan UPDATE, jadi run berulang aman (idempotent).

### Quirk data sumber yang perlu diketahui
- Sebagian baris di `DBNIAS.mdb` memiliki string literal `None` di kolom `EMAIL`
  (artefak import Access, ±223 baris). Script menyalinnya apa adanya agar mirror tetap
  setia. Jika ingin dibersihkan jadi `NULL`, jalankan sekali:
  ```sql
  UPDATE NIAS SET EMAIL = NULL WHERE EMAIL = 'None';
  ```
- Beberapa nama atlet mengandung karakter newline (ditulis dua baris di Access).
  Script menyalinnya apa adanya; browser akan menampilkan sebagai spasi.

---

## 8. Troubleshooting

### `mdb-export not found`
```bash
sudo apt install mdbtools
```

### Backup gagal dengan pesan `Column count of mysql.proc is wrong ... mariadb-upgrade`
Server MariaDB perlu di-upgrade (stale `mysql.proc` setelah update versi). Script otomatis
mencoba backup ulang **tanpa** routines/triggers, jadi proses tetap jalan — tetapi
sebaiknya perbaiki server sekali:
```bash
sudo mariadb-upgrade
```
Perintah ini hanya perlu dijalankan sekali oleh admin server.

### Backup gagal / file backup kosong
Script berhenti **sebelum** menulis apa pun ke database. Periksa pesan error
`mariadb-dump` di output, perbaiki, lalu jalankan ulang.

### `unexpected mdb column layout`
File `.mdb` yang dipakai tidak memiliki struktur kolom `NIAS` yang dikenal (kolom
`NONIAS` tidak ditemukan). Pastikan file yang benar (tabel `NIAS` dari DBNIAS).

### Baris `malformed` saat membaca MariaDB
Sangat jarang; hanya muncul jika data mengandung karakter tab yang tidak ter-escape.
Baris itu dilewati dan dicatat dengan peringatan `[WARN]`.

---

## 9. FAQ

**Apakah script menghapus data?**
Tidak. Tidak ada perintah `DELETE` atau `TRUNCATE`. Baris yang tidak ada di `.mdb`
tetap utuh; opsional ditandai `STATUS='0'` dengan `--deactivate-missing`.

**Apakah script menyentuh tabel lain?**
Tidak. Hanya `INSERT`/`UPDATE` pada tabel `NIAS`. Backup mencakup seluruh database
`dbnias` untuk keamanan.

**Boleh dijalankan berkali-kali?**
Boleh. Aman dan idempotent — run kedua yang tidak ada perubahan akan melaporkan
`0 inserted, 0 updated`.

**Apakah perlu menghentikan aplikasi Laravel dulu?**
Tidak wajib. Tabel `NIAS` hanya dibaca aplikasi; update berjalan dalam satu transaksi
singkat. Jika ingin paling aman, jalankan di jam sepi.

**Di server VPS (produksi) caranya?**
- Install mdbtools: `sudo apt install mdbtools`
- Salin folder `scripts/` + file `.mdb` ke server
- Pastikan `.env` di `nias-app/` berisi kredensial DB produksi
- Jalankan `python3 scripts/sync_nias_mdb.py`

---

*Terakhir diperbarui: 2026-08-20 — sinkronisasi pertama berhasil: 15 INSERT, 44 UPDATE,
3285 unchanged (backup `20260820_120504_dbnias_backup.sql`).*
