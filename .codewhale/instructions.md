# NIAS POSSI Jatim — Persistent Session Memory (this machine)

> Written 2026-08-21. This file is loaded automatically in future sessions on this VPS.
> Purpose: recognize the user's shorthand commands and remember how this system is built.

---

## ⚡ USER COMMAND → ACTION

| User says | What to do |
|---|---|
| **"deploy"** (or "git pull") | Run: `bash /home/itpossijatim/Git/possi-nias-daftar/.codewhale/deploy_new_workflow.sh` |
| **"update nias table"** | Run: `bash /home/itpossijatim/Git/possi-nias-daftar/.codewhale/update_nias_table.sh` (reads ~/Git dump, target 3344 rows, safety dump first) |
| **"git pull the /var/www"** | That workflow is OBSOLETE — /var/www is git-free. Interpret as "deploy". |

## 🔄 WHAT "DEPLOY" DOES (deploy_new_workflow.sh)

1. `git pull` in `/home/itpossijatim/Git/possi-nias-daftar` (the ONLY git checkout; run as user `itpossijatim`)
2. `rsync -a --delete` of `nias-app/` → `/var/www/possi-nias-daftar/nias-app/` with excludes: `.env`, `vendor/`, `storage/`, `database_backup/`, `.git/`
3. Remove `/var/www/.../nias-app/database_backup` (PII stays out of the web server)
4. Composer install only if composer.json/lock changed (vendor is otherwise preserved)
5. `chown -R itpossijatim:www-data` + `chmod -R 775 storage bootstrap/cache`
6. `php artisan storage:link` (rsync --delete removes the symlink!) + config/route/view/event caches
7. Restart php8.4-fpm + nginx; verify https://possijatim.my.id → HTTP 200

**Never overwrite**: `/var/www/possi-nias-daftar/nias-app/.env`, `vendor/`, `storage/` (contains live uploads in `storage/app/private/nias/{12,14}` — must survive every deploy).

## 🖥️ SYSTEM FACTS

- Site: **https://possijatim.my.id** · VPS 103.93.135.158 · Ubuntu 24.04 · Nginx 1.24 + PHP 8.4.24 + MariaDB 10.11 + Laravel 13.3
- App: `/var/www/possi-nias-daftar/nias-app` (owner `itpossijatim:www-data`); git-free by design (security)
- Git checkout: `/home/itpossijatim/Git/possi-nias-daftar` (origin: github.com/adityakusumo/possi-nias-daftar, public repo)
- **Git auth (sejak 2026-08-28)**: SSH deploy key `~/.ssh/id_ed25519_github` (itpossijatim) + `~/.ssh/config` untuk github.com (IdentityFile + IdentitiesOnly) — VPS BISA push/pull ke GitHub. Origin ~/Git sudah SSH: `git@github.com:adityakusumo/possi-nias-daftar.git`
- `*.sql` sudah dihapus dari repo (commit 575347a) + gitignore; `~/Git/nias-app/database_backup/NIAS.sql` tetap ada di disk (untuk workflow update nias table, tidak ter-track)
- DB: `dbnias` / user `itpossi`; password in `.env` (DB_PASSWORD) and `/root/.dbnias_pass` (root-only, 600); root DB access via `mariadb -u root` (unix socket)
- Admin web login: it.possijatim@gmail.com
- Mail: Gmail SMTP (smtp.gmail.com:587, it.possijatim@gmail.com, app password in .env → MAIL_PASSWORD)
- SSL: certbot auto-renew (possijatim.my.id + www)
- Firewall: ufw active, only 22/80/443

## 📁 REFERENCE FILES

- Handoff/progress: `/home/itpossijatim/Git/possi-nias-daftar/PROGRESS_20260811.md`
- Full setup & troubleshooting doc: `/home/itpossijatim/Git/possi-nias-daftar/VPS_SETUP_AND_TROUBLESHOOTING.md`
- Helper scripts: `/home/itpossijatim/Git/possi-nias-daftar/.codewhale/*.sh` (deploy_new_workflow.sh, update_nias_table.sh, fix_upload_413.sh, harden_server.sh, …)
- Safety dumps: `/root/dbnias_pre_20260811_restore_*.sql`, `/root/nias_pre_update_*.sql`

## 📌 OPEN ITEMS (user's discretion)

- Push the local cleanup commit + `git filter-repo` history purge (scrub temp.txt + *.sql from repo history) — user pushes from laptop; re-sync ~/Git afterward
- Set up git auth on VPS (deploy key or PAT) if repo goes private again
- Optional: fail2ban, login rate limiting, DB password rotation
- Optional cleanup: `/var/www/possi-nias-daftar/nias-app_/` leftover duplicate (database_backup already removed from it)
