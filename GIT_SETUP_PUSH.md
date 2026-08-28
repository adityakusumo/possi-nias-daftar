# Setup Git Push dari VPS (SSH Deploy Key) — Panduan untuk AI Agent

> Dibuat: 2026-08-28 · VPS: web-possi-jatim · Repo: `github.com/adityakusumo/possi-nias-daftar`

---

## 1. Ringkasan

VPS ini bisa **pull dan push** ke GitHub menggunakan **SSH deploy key** (tanpa password/PAT tersimpan di file). Deploy key terikat ke satu repo dan bisa diatur read-only / read-write.

---

## 2. Cara Menambahkan Deploy Key (langkah manual — sekali saja)

### a. Generate key di VPS (sudah dilakukan)

```bash
su -s /bin/bash itpossijatim -c 'ssh-keygen -t ed25519 -C "possijatim-vps-deploy" -f /home/itpossijatim/.ssh/id_ed25519_github -N "" -q'
```

### b. Lihat public key

```bash
cat /home/itpossijatim/.ssh/id_ed25519_github.pub
```

### c. Tambahkan ke GitHub (dilakukan user di browser)

1. Buka repo → **Settings** → **Deploy keys** → **Add deploy key**
   URL: `https://github.com/adityakusumo/possi-nias-daftar/settings/keys`
2. Isi:
   - **Title**: `possi-vps`
   - **Key**: paste isi file `.pub` dari langkah b
3. ⚠️ **Centang "Allow write access"** — WAJIB agar bisa push (tanpa ini hanya bisa pull)
4. Klik **Add key**

---

## 3. Konfigurasi SSH di VPS (sudah dilakukan)

File `/home/itpossijatim/.ssh/config`:

```
Host github.com
    HostName github.com
    User git
    IdentityFile ~/.ssh/id_ed25519_github
    IdentitiesOnly yes
```

**Uji koneksi:**

```bash
su -s /bin/bash itpossijatim -c 'ssh -T git@github.com'
# Output sukses: "Hi adityakusumo/possi-nias-daftar! You've successfully authenticated..."
```

Origin repo `~/Git` sudah memakai SSH:

```bash
cd /home/itpossijatim/Git/possi-nias-daftar
git remote -v
# origin  git@github.com:adityakusumo/possi-nias-daftar.git (fetch/push)
```

---

## 4. Cara AI Agent Melakukan Push

**Workflow standar (dari VPS):**

```bash
# 1) Edit kode di ~/Git (source of truth di VPS)
cd /home/itpossijatim/Git/possi-nias-daftar

# 2) Commit
git add <file-berubah>
git -c user.name="itpossijatim" -c user.email="it.possijatim@gmail.com" commit -m "Pesan commit"

# 3) Push
git push origin main

# 4) Deploy ke /var/www (opsional, jika perubahan kode produksi)
bash /home/itpossijatim/possi-nias-daftar/.codewhale/deploy_new_workflow.sh
```

**Aturan untuk agent:**
- ✅ Boleh commit + push langsung untuk perubahan fitur/perbaikan yang sudah diminta user
- ⚠️ **SEBELUM force-push / rewrite history**: WAJIB minta izin user dulu
- 🚫 Jangan pernah commit file rahasia: `.env`, `*.sql` berisi PII (sudah ada di `.gitignore`)
- 📣 Selalu laporkan hash commit + hasil push ke user

---

## 5. Catatan Penting (sejarah)

- **2026-08-28**: History repo di-rewrite dengan `git filter-repo` untuk menghapus `temp.txt` dan semua `*.sql` dari **seluruh commit** (alasan: PII/rahasia pernah ter-expose). Semua commit hash berubah. Clone di **laptop** harus disinkronkan ulang:

  ```bash
  cd <folder-repo-di-laptop>
  git fetch origin
  git reset --hard origin/main
  ```

- `NIAS.sql` disimpan di disk `~/Git/nias-app/database_backup/NIAS.sql` **tanpa di-track git** (karena `*.sql` di-gitignore) — file ini dipakai workflow **"update nias table"** (target 3344 baris). Jangan dihapus.

---

## 6. Troubleshooting

| Gejala | Penyebab & Solusi |
|---|---|
| `Permission denied (publickey)` saat `ssh -T git@github.com` | Key tidak terdaftar / salah paste di GitHub → cek Settings → Deploy keys; pastikan `~/.ssh/config` benar; perms file key harus `600` |
| Autentikasi sukses tapi push ditolak | Deploy key tanpa centang **"Allow write access"** → edit key di GitHub, centang write access |
| `dubious ownership in repository` | Tambah: `git config --global --add safe.directory <path-repo>` |
| Setelah rewrite history, laptop "not found / diverged" | Jalankan `git fetch origin && git reset --hard origin/main` di laptop |

---

*Panduan ini disimpan di `/home/itpossijatim/GIT_SETUP_PUSH.md` sebagai referensi permanen.*
