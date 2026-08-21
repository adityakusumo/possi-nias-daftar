# 🔐 Panduan Setup SSH (.pem) + CodeWhale di VPS Ubuntu (dari Laptop Arch Linux)

Panduan ini untuk kondisi **sudah punya file kunci `.pem`** (private key) dan
tinggal mengaktifkan akses SSH dari laptop **Arch Linux** ke VPS **Ubuntu 24.04**,
lalu dilanjutkan dengan **install & setup CodeWhale** di VPS.

## Informasi Konfigurasi

| Parameter | Nilai (contoh nyata) |
|---|---|
| Host (Server) | VPS Ubuntu 24.04 LTS |
| IP Server | `103.93.135.158` |
| Domain | `possijatim.my.id` |
| User SSH | `itpossijatim` |
| Remote PC (Client) | Arch Linux |
| File kunci (.pem) | `~/.ssh/possi_key` |
| Alias SSH | `possi-vps` |

## Prasyarat

- File `.pem` (private key) sudah ada — bisa format OpenSSH
  (`-----BEGIN OPENSSH PRIVATE KEY-----`) maupun PEM lama
- Akses awal ke VPS (password dari panel provider, atau kunci lama yang masih terdaftar)
- Laptop Arch Linux dengan `openssh` terinstall (sudah default)
- **DeepSeek API key** untuk CodeWhale (buat di https://platform.deepseek.com → API Keys)

> **📌 Catatan keamanan:** Private key (`.pem`) cukup disimpan di **client (laptop)**.
> Yang didaftarkan di server hanya **public key**. Jangan pernah menaruh private key
> di server.

---

# BAGIAN A — SETUP SSH DENGAN FILE .pem

## Langkah 1: Siapkan kunci di sisi Client (Arch Linux)

### 1.1 Letakkan file .pem di `~/.ssh` dengan permission benar

```bash
mkdir -p ~/.ssh && chmod 700 ~/.ssh
install -m600 /path/asal/possi_key ~/.ssh/possi_key
```

> **📌 Catatan:** `install -m600` = menyalin sekaligus set permission `600`.
> Folder `.ssh` harus `700` dan file kunci harus `600`. Jika permission terlalu
> terbuka, OpenSSH menolak dengan `Permissions 0644 for 'possi_key' are too open`.

### 1.2 Generate public key (kalau belum punya .pub)

```bash
ssh-keygen -y -f ~/.ssh/possi_key > ~/.ssh/possi_key.pub
cat ~/.ssh/possi_key.pub
```

> **✅ Tip:** `ssh-keygen -y` menurunkan public key dari private key tanpa
> mengubah file aslinya. Outputnya (satu baris `ssh-ed25519 AAAA... user@arch`)
> yang nanti ditempel di server.

## Langkah 2: Daftarkan public key di sisi Host (Ubuntu VPS)

### 2.1 Login awal ke server

Dari laptop:

```bash
ssh itpossijatim@103.93.135.158
```

(pakai password dari panel provider, atau kunci lama yang masih terdaftar)

### 2.2 Siapkan `authorized_keys` dan tempel public key

Jalankan **di dalam server**:

```bash
mkdir -p ~/.ssh && chmod 700 ~/.ssh
touch ~/.ssh/authorized_keys && chmod 600 ~/.ssh/authorized_keys
nano ~/.ssh/authorized_keys
```

Tempel output `cat ~/.ssh/possi_key.pub` tadi (satu baris), simpan `CTRL+O` lalu `CTRL+X`.

> **📌 Catatan:** `authorized_keys` boleh berisi banyak baris — setiap baris =
> satu kunci yang diizinkan (misal kunci RSA lama + kunci ed25519 baru
> sekaligus, supaya semua perangkat tetap bisa masuk).

### 2.3 Alternatif sekali jalan dari client: ssh-copy-id

Kalau akses awal pakai password, bisa langsung dari laptop:

```bash
ssh-copy-id -i ~/.ssh/possi_key.pub itpossijatim@103.93.135.158
```

### 2.4 Verifikasi konfigurasi sshd (hanya kalau masih gagal)

```bash
sudo grep -E "^(PubkeyAuthentication|PasswordAuthentication|AuthorizedKeysFile)" /etc/ssh/sshd_config
sudo systemctl restart ssh
```

Pastikan `PubkeyAuthentication yes` (default di Ubuntu). Setelah key aktif,
sebaiknya matikan login password: `PasswordAuthentication no` di
`/etc/ssh/sshd_config`, lalu `sudo systemctl restart ssh`.

## Langkah 3: Buat alias SSH di `~/.ssh/config` (Arch Linux)

Buka/ buat file konfigurasi:

```bash
nano ~/.ssh/config
```

Tambahkan blok berikut (contoh alias `possi-vps`):

```text
Host possi-vps
    HostName possijatim.my.id
    User itpossijatim
    IdentityFile ~/.ssh/possi_key
    IdentitiesOnly yes
    ServerAliveInterval 60
    ServerAliveCountMax 3
```

| Opsi | Fungsi |
|---|---|
| `Host` | Nama alias yang dipakai saat `ssh possi-vps` |
| `HostName` | IP atau domain tujuan (boleh `103.93.135.158` langsung) |
| `User` | User SSH di server |
| `IdentityFile` | Path private key (.pem) |
| `IdentitiesOnly yes` | Hanya pakai kunci yang ditunjuk — penting kalau `~/.ssh` punya banyak kunci atau SSH agent aktif, supaya tidak mencoba kunci lain dulu |
| `ServerAliveInterval` | Kirim keepalive agar koneksi tidak putus saat idle |

> **📌 Catatan:** `IdentityFile` boleh pakai `~` (`~/.ssh/possi_key`), tidak wajib
> path absolut.

## Langkah 4: Tes koneksi

```bash
ssh possi-vps
```

Verifikasi alias yang terbaca benar:

```bash
ssh -G possi-vps | grep -Ei "^(hostname|user|identityfile)"
```

Output yang diharapkan:

```text
hostname possijatim.my.id
user itpossijatim
identityfile /home/adit/.ssh/possi_key
```

## Langkah 5: Perintah sehari-hari dengan alias

```bash
scp file_backup.sql possi-vps:~/
rsync -avz ./folder_data possi-vps:~/backup/
ssh possi-vps "mariadb -u itpossi -p dbnias < ~/file_backup.sql"
```

---

## Troubleshooting SSH

| Masalah | Solusi |
|---|---|
| `REMOTE HOST IDENTIFICATION HAS CHANGED!` | `ssh-keygen -R possijatim.my.id` lalu `ssh possi-vps` lagi (ketik `yes`). Terjadi saat domain/IP pindah ke server **baru** — `known_hosts` di laptop masih menyimpan host key server lama. Koneksi lewat IP langsung tetap sukses, tapi lewat hostname gagal. |
| Takut salah server (MITM)? | Cocokkan fingerprint host key server: di server jalankan `ssh-keygen -lf /etc/ssh/ssh_host_ed25519_key.pub`, bandingkan dengan yang ditampilkan client |
| `Permission denied (publickey)` | `ssh -v possi-vps` untuk lihat kunci mana yang ditawarkan. Cek public key sudah ada di `authorized_keys` server; cek permission (`~/.ssh` = 700, file = 600); pastikan `IdentitiesOnly yes` |
| `Permissions 0644 for 'possi_key' are too open` | `chmod 600 ~/.ssh/possi_key` |
| Koneksi sering putus saat idle | Tambahkan `ServerAliveInterval 60` dan `ServerAliveCountMax 3` di config |
| Gagal via hostname tapi sukses via IP | Hampir pasti `known_hosts` (lihat baris pertama) |
| Config sepertinya tidak terbaca | `ssh -G possi-vps` untuk lihat nilai yang benar-benar dipakai; pastikan tidak ada blok `Host *` lain yang menimpa di atasnya |
| Key lama tidak dipakai sama sekali | Pastikan `IdentityFile` menunjuk file yang benar dan `IdentitiesOnly yes` |

---

# BAGIAN B — INSTALL & SETUP CODEWHALE DI VPS

> **📌 Catatan:** Troubleshooting lengkap CodeWhale ada di file terpisah
> `Setup_CodeWhale_VPS.md` di repo ini. Bagian ini ringkasannya.

## Langkah 6: Install CodeWhale (pilih salah satu jalur)

### Jalur A (paling cepat) — installer resmi

```bash
curl -fsSL https://codewhale.net/install.sh | sh
echo 'export PATH="$HOME/.local/bin:$PATH"' >> ~/.bashrc
source ~/.bashrc
```

### Jalur B — npm (Node 18+)

```bash
sudo apt install -y nodejs npm
sudo npm install -g codewhale
```

### Jalur C — binary langsung dari GitHub Releases

```bash
uname -m        # cek arsitektur: x86_64 atau aarch64
mkdir -p ~/.local/bin
# x64:
curl -L -o ~/.local/bin/codewhale https://github.com/Hmbown/CodeWhale/releases/latest/download/codewhale-linux-x64
curl -L -o ~/.local/bin/codew     https://github.com/Hmbown/CodeWhale/releases/latest/download/codew-linux-x64
# ARM64: ganti codewhale-linux-x64 → codewhale-linux-arm64 (dan codew-...)
chmod +x ~/.local/bin/codewhale ~/.local/bin/codew
```

## Langkah 7: Verifikasi & pasang API key

```bash
codewhale --version
codewhale doctor          # cek API key, provider, runtime, PATH
codewhale auth set --provider deepseek   # tempel API key (format sk-...)
codewhale auth status
```

> **📌 Catatan:** API key tersimpan di `~/.codewhale/` (permission 0600).
> Pemakaian model ditagih ke akun DeepSeek Anda sendiri.

## Langkah 8: Pakai di dalam tmux (biar session tetap hidup)

```bash
sudo apt install -y tmux
tmux new -s codewhale
codewhale
```

| Aksi | Perintah |
|---|---|
| Detach (keluar tapi session jalan) | `Ctrl+b` lalu `d` |
| Kembali ke session | `tmux attach -t codewhale` |
| Ganti model | `/model` (misal `deepseek-chat`, `deepseek-reasoner`) |
| Ganti mode Plan / Act / Full Access | `Tab` saat kolom perintah kosong |
| Jalankan perintah shell lewat approval | `!perintah` |
| Mode headless (script / CI) | `codewhale exec "perintah"` |
| Mode web (lokal, akses via SSH tunnel) | `codewhale web` |

Update versi:

```bash
codewhale update
```

> **✅ Tip:** Mulai dengan mode **Plan** dan perhatikan prompt approval —
> CodeWhale bisa menjalankan perintah dan mengedit file di server.

---

*Dokumen ini dibuat untuk setup SSH (.pem) + CodeWhale di VPS Ubuntu 24.04 — itpossijatim*
