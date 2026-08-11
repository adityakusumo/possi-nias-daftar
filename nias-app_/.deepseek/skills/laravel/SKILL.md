---
name: possi-nias-laravel
description: Panduan pengembangan project Laravel POSSI Jawa Timur — sistem pendaftaran NIAS (Nomor Induk Anggota Selam) dan daftar lomba finswimming. Gunakan skill ini setiap kali bekerja pada project ini: menambah fitur, memperbaiki bug, membuat migration, patch controller/blade, atau menambah route. Skill ini berisi konteks penuh stack, struktur database, konvensi kode, dan pola patch yang digunakan di project ini.
---

# POSSI NIAS Laravel — Skill Pengembangan

Project Laravel untuk POSSI (Persatuan Olahraga Selam Seluruh Indonesia) Jawa Timur. Mengelola pendaftaran NIAS atlet finswimming dan pendaftaran lomba.

---

## Stack & Environment

| Komponen | Detail |
|---|---|
| Framework | Laravel 11+ (Laravel 13.x aktif) |
| PHP | 8.4.x |
| Database | MariaDB, nama database `dbnias` |
| Web Server | Nginx + PHP-FPM (Ubuntu 24.04 VPS) |
| Domain | possijatim.my.id |
| Frontend | Bootstrap 5.3, Bootstrap Icons, Select2, SweetAlert2 |
| Storage | `Storage::disk('local')` → `storage/app/private/` |
| Auth | Custom (kolom `role` di tabel `users`), bukan Spatie |

---

## Struktur Database — Tabel Utama

### `users`
Akun pelatih/admin. Kolom penting:
- `nama`, `email`, `password`, `role` (`admin` / `regular`)
- `namaclub`, `gender`
- `bukti_transfer_path` — path file bukti transfer di storage

### `NIAS_STRUCT`
Data pendaftaran baru/update sebelum di-ACC admin. Ini adalah tabel kerja utama.
- `ID` — primary key (integer autoincrement)
- `STATUS` — `0`=expired/ditolak, `1`=ACC/disetujui, `2`=pending (baru disimpan), `3`=sudah dikirim email
- `is_sent` — boolean, true jika sudah dikirim ke POSSI
- `is_update` — boolean, membedakan pendaftaran baru vs update/perpanjang
- `user_id` — FK ke `users`
- `tipe_update` — `perpanjangan`, `update_club`, `update_domisili`, `update_all`
- `file_kk`, `file_foto`, `file_akte`, `file_ijazah`, `file_sk_mutasi` — path file di storage

### `NIAS`
Data atlet yang sudah di-ACC (read-only dari aplikasi). Dibaca via model `NiasExisting`.

### `app_settings`
Key-value store untuk konfigurasi:
- `nias_open_date`, `nias_close_date` — jadwal buka/tutup pendaftaran
- `nias_max_accounts_per_club` — JSON `{"NamaClub": maxAkun}`

### `MstTarifNias`
Tarif pendaftaran: `tipe` (`baru`/`update`), `biaya` (integer rupiah).

### `kontingens`
Data kontingen lomba per user.

### `MSTKOTA`
Master kota/kab Jawa Timur. Read-only, no timestamps.

### Tabel Lain (project lomba)
`A3`, `Atlet`, `Kompetisi`, `MstKU`, `MstEvent`, `MstGaya`, `MstTarif`, dll.

---

## Models

| Model | Tabel | Keterangan |
|---|---|---|
| `Nias` | `NIAS_STRUCT` | Data pendaftaran aktif |
| `NiasExisting` | `NIAS` | Data sudah ACC, read-only |
| `AppSetting` | `app_settings` | Helper: `get()`, `set()`, `isNiasOpen()`, `getMaxAccountsForClub()` |
| `MstTarifNias` | `MstTarifNias` | Helper: `getBiaya()`, `getAllTarif()` |
| `MstKota` | `MSTKOTA` | `$primaryKey = 'ID'`, no timestamps |
| `Kontingen` | `kontingens` | Data kontingen lomba |
| `User` | `users` | Auth custom, kolom `role` |

---

## Role & Auth

- **Admin**: akses semua data semua club, bisa ACC/reject, tidak diblok jadwal
- **Regular**: hanya data club sendiri, diblok saat pendaftaran tutup

**Cek role di controller:**
```php
$isAdmin = Auth::user()->role === 'admin';
```

**Cek jadwal di web.php (guard route):**
```php
if (auth()->user()->role !== 'admin' && !\App\Models\AppSetting::isNiasOpen()) {
    return redirect()->route('nias.index')->with('nias_closed', true);
}
```

**authorizeNias() — admin bypass:**
```php
private function authorizeNias(Nias $nias): void
{
    if (Auth::user()->role === 'admin') return;
    if ((int) $nias->user_id !== (int) Auth::id()) abort(403);
}
```

---

## Routing Conventions

- Routes di `routes/web.php`, semua dalam `Route::middleware('auth')`
- Admin-only routes menggunakan `Route::middleware(['auth', 'admin'])` (alias di `bootstrap/app.php`)
- Route dengan guard jadwal menggunakan closure function di `web.php`
- Route tidak pakai `Route::resource()` — semua explicit untuk menghindari konflik

**Nama route penting:**
```
welcome, welcome.saveChoice, welcome.reset
nias.index, nias.create, nias.store, nias.show, nias.edit, nias.update, nias.destroy
nias.existing, nias.update-data, nias.export, nias.send-email
nias.file, nias.bukti-transfer, nias.serve-bukti
nias.acc, nias.reject
nias.destroy-selected, nias.destroy-all
nias.destroy-sent-selected, nias.destroy-sent-all
settings, settings.nias.save, settings.nias.reset, settings.tarif.save
settings.resetPassword, settings.deleteUser
settings.akun.show, settings.akun.destroySelected, settings.akun.destroyAll
user.setting, user.setting.password
lomba.index, lomba.form_a1, lomba.form_a1_namaatlet, form_a1.saveKontingen
password.request, password.send, password.reset, password.update
auth.login.show, auth.login, auth.register.show, auth.register, auth.logout
```

---

## Views Structure

```
resources/views/
├── auth/
│   ├── login.blade.php
│   ├── register.blade.php
│   ├── forgot_password.blade.php
│   └── reset_password.blade.php
├── nias/
│   ├── index.blade.php       — list pendaftaran (belum & sudah dikirim)
│   ├── create.blade.php      — form daftar baru
│   ├── edit.blade.php        — form edit
│   ├── show.blade.php        — detail + preview dokumen + acc/reject (admin)
│   ├── update_nias.blade.php — form update/perpanjang
│   ├── existing.blade.php    — data NIAS existing (tabel NIAS)
│   └── _form.blade.php       — form partial (dipakai create & edit)
├── lomba/
│   ├── index.blade.php
│   ├── form_a1_kontingen.blade.php
│   └── form_a1_namaatlet.blade.php
├── layouts/
│   └── app.blade.php         — navbar Bootstrap, @yield('content'), @stack('scripts')
├── welcome.blade.php         — portal pilihan app (standalone, navbar sendiri)
├── settings.blade.php        — 3 tab: Setting NIAS, Setting Lomba, Manajemen Akun
├── setting_user.blade.php    — info akun + ganti password (user regular)
└── setting_akun_show.blade.php — detail akun user (admin)
```

---

## File Storage

```
storage/app/private/
├── nias/{user_id}/           — dokumen atlet (KK, foto, akte, ijazah, SK mutasi)
└── bukti_transfer/           — bukti transfer pembayaran
```

**Serve file:**
```php
return response()->file(Storage::disk('local')->path($path));
```

**Upload file atlet:**
```php
$path = $request->file('file_kk')->store("nias/{$userId}", 'local');
```

**Naming bukti transfer:** `{NAMAUSER}_{YYYYMMDD_HHmm}.{ext}`

---

## CSV Export Format

- Delimiter: `;` (semicolon)
- Encoding: UTF-8 BOM
- NIK dan NONIAS diberi prefix `'` (apostrof) agar Excel tidak salah baca
- Format tanggal: `m/d/Y` (mm/dd/yyyy)
- NAMAKOTADOM di-strip prefix kota/kab via `stripWilayahPrefix()`
- Urutan kolom penting: `..., NIK, STATUS NIAS, NONIAS, Daftar NIAS, Jenis Daftar, Keterangan`
- Hanya data dengan `is_sent = false` yang masuk CSV/ZIP

**stripWilayahPrefix helper:**
```php
private function stripWilayahPrefix(?string $nama): string
{
    if (!$nama) return '';
    return trim(preg_replace('/^(kota|kab\.?|kabupaten)\s+/i', '', $nama));
}
```

---

## Pola Patch File

Karena pengembangan dilakukan iteratif dengan banyak patch, gunakan pola ini:

**Python patch (untuk string multi-line):**
```python
# Selalu gunakan file script terpisah, bukan heredoc inline
# untuk menghindari escape karakter backslash
cat > /tmp/patch_nama.py << 'SCRIPTEOF'
with open('/mnt/user-data/outputs/NamaFile.php', 'r') as f:
    content = f.read()
content = content.replace(old_str, new_str)
with open('/mnt/user-data/outputs/NamaFile.php', 'w') as f:
    f.write(content)
SCRIPTEOF
python3 /tmp/patch_nama.py
```

**str_replace tool** — untuk patch kecil yang tidak mengandung backslash problematik

**Perhatian:**
- String PHP yang mengandung `\N`, `\U`, `\H` (namespace) akan menyebabkan `SyntaxError` di Python inline heredoc — gunakan file script
- Selalu print hasil patch untuk verifikasi: `print("OK" if "string_baru" in content else "FAIL")`
- Setelah patch controller, cek syntax: `php -l NamaFile.php`

---

## Konvensi Penting

### Controller
- `store()` — STATUS default `2` (pending)
- `sendEmail()` — update STATUS ke `3`, `is_sent = true`
- `acc()` — STATUS ke `1`
- `reject()` — STATUS ke `0`
- Admin selalu bypass `authorizeNias()`
- `index()` mengirim: `$records`, `$sentRecords`, `$totalSemua`, `$totalBaru`, `$totalUpdate`, `$isNiasOpen`, `$tarifNias`, `$hasBukti`

### Blade
- Semua halaman selain `welcome.blade.php` extends `layouts.app`
- `welcome.blade.php` standalone dengan navbar sendiri (fixed top)
- Flash messages via `session('success')` dan `session('error')`
- SweetAlert2 untuk konfirmasi delete/acc/reject
- Select2 untuk dropdown yang panjang
- `$isNiasOpen` — selalu kirim dari controller, jangan query di blade

### JENISDOM & NAMAKOTADOM (form update)
- Select dropdown hanya untuk NAMAKOTADOM (format: "KOTA MALANG", "KAB KEDIRI")
- JENISDOM diisi otomatis via JS dari prefix pilihan NAMAKOTADOM
- Saat save ke DB: strip prefix dari NAMAKOTADOM, JENISDOM = prefix yang di-parse

---

## Middleware

```php
// bootstrap/app.php
$middleware->alias([
    'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
]);
$middleware->redirectGuestsTo(fn() => route('auth.login.show'));
```

`EnsureUserIsAdmin` — redirect ke `welcome` dengan error jika bukan admin.

---

## Forgot Password

Menggunakan `Illuminate\Auth\Passwords\PasswordBroker`. Route wajib ada di `web.php`:
```php
Route::get('/forgot-password',        [ForgotPasswordController::class, 'showForm'])->name('password.request');
Route::post('/forgot-password',       [ForgotPasswordController::class, 'sendLink'])->name('password.send');
Route::get('/reset-password/{token}', [ResetPasswordController::class,  'showForm'])->name('password.reset');
Route::post('/reset-password',        [ResetPasswordController::class,  'reset'])->name('password.update');
```
Route ini sering hilang saat web.php di-overwrite — selalu cek keberadaannya.

---

## WelcomeController Logic

```php
public function show()
{
    if ($user->role === 'admin' && session()->has('last_app')) {
        // Redirect ke last_app (nias/lomba)
    }
    return view('welcome');
}
```

**Penting:** Tombol "Kembali ke Portal" untuk admin harus mengarah ke `route('welcome.reset')` bukan `route('welcome')`, agar session `last_app` dihapus dulu dan admin benar-benar kembali ke halaman pilihan.

---

## Deploy Commands

```bash
php artisan migrate
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear
```
