# Setup CodeWhale (DeepSeek Coding Agent) di Ubuntu 24.04 VPS

Cara install CodeWhale — terminal coding agent untuk DeepSeek (dahulu bernama
`deepseek-tui`) — di VPS Ubuntu 24.04, cukup lewat terminal, tanpa GUI.

---

## Apa itu CodeWhale

CodeWhale adalah agent coding open-source (Rust, MIT, repo: `Hmbown/CodeWhale`)
yang bekerja langsung dari terminal:

- Terhubung ke API DeepSeek (bisa juga provider lain: Claude, GPT, Ollama, dst.)
- Membaca kode di project, mengedit file, menjalankan perintah, lalu mengecek
  hasilnya sendiri
- Mode interaktif (TUI), mode headless (`codewhale exec`), dan mode web lokal
  (`codewhale web`)
- Menggunakan **API key DeepSeek Anda sendiri** — setiap pemakaian model
  ditagih ke akun DeepSeek Anda

## Prasyarat

- VPS Ubuntu 24.04 fresh, akses SSH, koneksi internet
- **DeepSeek API key** — buat di https://platform.deepseek.com → API Keys
  (format: `sk-...`)

---

## Langkah 1 — Update sistem

```bash
sudo apt update && sudo apt upgrade -y
```

## Langkah 2 — Install CodeWhale (pilih salah satu jalur)

### Jalur A (paling cepat): installer resmi dari codewhale.net

```bash
curl -fsSL https://codewhale.net/install.sh | sh
```

Mengunduh binary resmi, verifikasi checksum, dan install ke `~/.local/bin`.
Tambahkan ke PATH:

```bash
echo 'export PATH="$HOME/.local/bin:$PATH"' >> ~/.bashrc
source ~/.bashrc
```

### Jalur B: npm (Node 18+)

```bash
sudo apt install -y nodejs npm
sudo npm install -g codewhale
```

### Jalur C: unduh binary langsung dari GitHub Releases

Cek arsitektur VPS dulu: `uname -m` (x86_64 atau aarch64).

```bash
mkdir -p ~/.local/bin
# x64:
curl -L -o ~/.local/bin/codewhale https://github.com/Hmbown/CodeWhale/releases/latest/download/codewhale-linux-x64
curl -L -o ~/.local/bin/codew     https://github.com/Hmbown/CodeWhale/releases/latest/download/codew-linux-x64
# ARM64: ganti codewhale-linux-x64 → codewhale-linux-arm64 (dan codew-...)

chmod +x ~/.local/bin/codewhale ~/.local/bin/codew
```

Verifikasi checksum (opsional tapi disarankan):

```bash
curl -L -o /tmp/cw.sha256 https://github.com/Hmbown/CodeWhale/releases/latest/download/codewhale-artifacts-sha256.txt
(cd ~/.local/bin && sha256sum -c /tmp/cw.sha256 --ignore-missing)
```

> Binary release sudah static (musl), jadi tidak butuh dependensi tambahan
> seperti libdbus-1 untuk runtime. `codew` adalah alias pendek dari binary
> yang sama.

## Langkah 3 — Verifikasi instalasi

```bash
codewhale --version
codewhale doctor        # cek API key, provider, runtime, dan PATH
```

`doctor` menampilkan pesan perbaikan jika ada yang kurang.

## Langkah 4 — Pasang API key DeepSeek

```bash
codewhale auth set --provider deepseek
```

Tempel API key saat diminta. Key disimpan di `~/.codewhale/` (permission 0600).
Cek status:

```bash
codewhale auth status
```

## Langkah 5 — Pakai

VPS tidak punya layar permanen, jadi jalankan TUI di dalam **tmux** supaya
session tetap hidup walau SSH putus:

```bash
sudo apt install -y tmux
tmux new -s codewhale
codewhale
```

- Detach: `Ctrl+b` lalu `d` — kembali dengan `tmux attach -t codewhale`
- Model default DeepSeek bisa diganti di dalam TUI dengan perintah `/model`
  (misal `deepseek-chat` atau `deepseek-reasoner`)
- Mode Plan (read-only) / Act / Full Access: `Tab` untuk ganti saat kolom
  perintah kosong; `Shift+Tab` ganti level approval
- Jalankan perintah shell lewat jalur approval: `!perintah`

Mode headless (untuk script / CI):

```bash
codewhale exec "perbaiki test yang gagal"
```

Mode web (hanya di 127.0.0.1, untuk akses dari browser lokal via SSH tunnel):

```bash
codewhale web
```

## Troubleshooting khusus Ubuntu 24.04

| Masalah | Solusi |
| --- | --- |
| `feature edition2024 is required` saat `cargo install` | Cargo bawaan apt di Ubuntu 24.04 (1.75) terlalu lama. Pakai jalur A/B/C (binary siap pakai), atau install Rust via rustup dulu: `curl --proto '=https' --tlsv1.2 -sSf https://sh.rustup.rs | sh` lalu `source "$HOME/.cargo/env"` |
| `linker 'cc' not found` saat build dari source | `sudo apt install -y build-essential pkg-config libdbus-1-dev` |
| `codewhale: command not found` setelah install npm | Direktori bin npm belum di PATH. Cek `npm prefix -g`, tambahkan `.../bin` ke PATH, atau pakai Jalur A yang ke `~/.local/bin` |
| TUI tampak rusak/karakter aneh | Pastikan `TERM=xterm-256color` (export di `.bashrc`), jalankan dalam tmux |
| Ingin update versi | `codewhale update`, atau ulangi `sudo npm install -g codewhale@latest` |

## Catatan penting

- API key DeepSeek adalah biaya Anda — agent bisa menjalankan perintah dan
  mengedit file. Mulai dengan mode **Plan** dan perhatikan prompt approval.
- Konfigurasi & session tersimpan di `~/.codewhale/`. Jika sebelumnya memakai
  `deepseek-tui`, config lama tetap terbaca (proyek ini adalah kelanjutannya).
- Untuk kerja di project: `cd /path/project && codewhale`.
