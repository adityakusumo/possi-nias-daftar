#!/usr/bin/env python3
"""
sync_nias_mdb.py — Sync the MariaDB `NIAS` table (Laravel app `dbnias`)
from an MS Access 2003 .mdb file (DBNIAS).

Strategy
--------
The MariaDB `NIAS` table is a read-only mirror of the Access `NIAS` table
(no FK references, the app only reads it via the `NiasExisting` model).
Rows are keyed by the natural key `NONIAS` (unique, never empty in the
source data).

For every row in the .mdb:
  * NONIAS not present in MariaDB  -> INSERT (new ID, auto-increment)
  * NONIAS present with any column different -> UPDATE
  * NONIAS present and identical   -> skipped

Rows that exist in MariaDB but are missing from the .mdb are left
untouched by default; use --deactivate-missing to mark them STATUS='0'.

Safety
------
1. A full MariaDB dump of the whole `dbnias` database is ALWAYS created
   in <app>/database_backup/ BEFORE anything is written.
2. The script aborts if the backup fails.
3. All writes run inside a single transaction; on error it rolls back.
4. `--dry-run` performs the backup + analysis but writes nothing.

Requirements
------------
* mdbtools   (provides `mdb-export`)  -> apt install mdbtools
* mariadb client (provides `mariadb` / `mariadb-dump`; mysql/mysqldump
  also work as fallback)
* Python 3 (stdlib only)

Usage
-----
  python3 scripts/sync_nias_mdb.py \
      --mdb "/home/adit/Downloads/DBNIAS(1).mdb"

  # preview only (still performs the backup)
  python3 scripts/sync_nias_mdb.py --dry-run

  # also deactivate rows that vanished from the .mdb
  python3 scripts/sync_nias_mdb.py --deactivate-missing
"""

import argparse
import datetime as dt
import os
import shutil
import subprocess
import sys
import tempfile

# ─────────────────────────── configuration ───────────────────────────

SCRIPT_DIR   = os.path.dirname(os.path.abspath(__file__))
APP_DIR      = os.path.abspath(os.path.join(SCRIPT_DIR, '..'))
ENV_PATH     = os.path.join(APP_DIR, '.env')
BACKUP_DIR   = os.path.join(APP_DIR, 'database_backup')

# Columns of the NIAS table (MariaDB legacy schema == Access schema),
# excluding the auto-increment primary key `ID`.
NIAS_COLUMNS = [
    'KDPROP', 'NAMAPROP', 'KDJENIS', 'JENIS', 'KDKOTA', 'NAMAKOTA',
    'KDCLUB', 'NAMACLUB', 'GENDER', 'NAMA', 'TPTLAHIR', 'TGLLAHIR',
    'STATUS', 'NONIAS', 'LASTMUTASI', 'MUTASI', 'EXPIRED',
    'KDJENISDOM', 'JENISDOM', 'KDKOTADOM', 'NAMAKOTADOM',
    'KDPROPDOM', 'NAMAPROPDOM', 'NIK', 'EMAIL', 'NOKARTUNAS',
]

# Columns compared for change detection (all except the key).
COMPARE_COLUMNS = [c for c in NIAS_COLUMNS if c != 'NONIAS']


# ─────────────────────────────── helpers ─────────────────────────────

def die(msg: str, code: int = 1) -> None:
    print(f"[ERROR] {msg}", file=sys.stderr)
    sys.exit(code)


def find_binary(names):
    for n in names:
        p = shutil.which(n)
        if p:
            return p
    return None


def parse_env(path: str) -> dict:
    """Parse a Laravel .env file (KEY=VALUE, optional quotes, # comments)."""
    env = {}
    if not os.path.isfile(path):
        die(f".env not found at {path}")
    with open(path, 'r', encoding='utf-8') as f:
        for raw in f:
            line = raw.strip()
            if not line or line.startswith('#') or '=' not in line:
                continue
            key, _, val = line.partition('=')
            key = key.strip()
            val = val.strip()
            if val.startswith('"') and val.endswith('"'):
                val = val[1:-1]
            elif val.startswith("'") and val.endswith("'"):
                val = val[1:-1]
            env[key] = val
    return env


def mysql_literal(v):
    """Escape a value as a MariaDB string literal (NULL for empty/None)."""
    if v is None:
        return 'NULL'
    s = str(v)
    if s == '':
        return 'NULL'
    # backslash is the escape char in default MariaDB sql_mode
    s = s.replace('\\', '\\\\')
    s = s.replace("'", "\\'")
    s = s.replace('\x00', '\\0')
    s = s.replace('\n', '\\n')
    s = s.replace('\r', '\\r')
    return "'" + s + "'"


def unescape_batch(v):
    """Undo the escapes the mariadb client applies in --batch output:
    tab -> \\t, newline -> \\n, CR -> \\r, backslash -> \\\\, NUL -> \\0.
    A single left-to-right pass keeps the mapping unambiguous."""
    if v is None:
        return None
    out = []
    i = 0
    while i < len(v):
        if v[i] == '\\' and i + 1 < len(v):
            nxt = v[i + 1]
            if nxt == 't':
                out.append('\t'); i += 2; continue
            if nxt == 'n':
                out.append('\n'); i += 2; continue
            if nxt == 'r':
                out.append('\r'); i += 2; continue
            if nxt == '0':
                out.append('\x00'); i += 2; continue
            if nxt == '\\':
                out.append('\\'); i += 2; continue
        out.append(v[i])
        i += 1
    return ''.join(out)


def norm(v):
    """Normalize a raw value from either source; '' and 'NULL' -> None."""
    if v is None:
        return None
    s = v.strip()
    if s in ('', 'NULL', '\\N'):
        return None
    return s


def run(cmd, **kw):
    """Run a command, print it (password masked), return CompletedProcess."""
    shown = []
    for part in cmd:
        if part.startswith('-p') and len(part) > 2:
            shown.append('-p***')
        else:
            shown.append(part)
    print('$ ' + ' '.join(shown))
    return subprocess.run(cmd, text=True, capture_output=True, **kw)


# ──────────────────────────── main pipeline ──────────────────────────

def main():
    ap = argparse.ArgumentParser(
        description='Sync MariaDB NIAS table from a DBNIAS .mdb file.')
    ap.add_argument('--mdb', default='/home/adit/Downloads/DBNIAS(1).mdb',
                    help='Path to the Access 2003 .mdb database '
                         '(default: %(default)s)')
    ap.add_argument('--app-dir', default=APP_DIR,
                    help='Laravel app dir containing .env '
                         '(default: parent of this script)')
    ap.add_argument('--backup-dir', default=None,
                    help='Backup directory (default: <app>/database_backup)')
    ap.add_argument('--dry-run', action='store_true',
                    help='Backup + analyze only, write nothing to the DB')
    ap.add_argument('--deactivate-missing', action='store_true',
                    help='Also set STATUS=0 for NONIAS rows that exist in '
                         'MariaDB but are absent from the .mdb')
    ap.add_argument('--verbose', action='store_true',
                    help='Show every changed row')
    args = ap.parse_args()

    if not os.path.isfile(args.mdb):
        die(f"mdb file not found: {args.mdb}")

    env = parse_env(os.path.join(args.app_dir, '.env'))
    db_host = env.get('DB_HOST', '127.0.0.1')
    db_port = env.get('DB_PORT', '3306')
    db_name = env.get('DB_DATABASE', 'dbnias')
    db_user = env.get('DB_USERNAME', '')
    db_pass = env.get('DB_PASSWORD', '')

    mysql_bin  = find_binary(['mariadb', 'mysql'])
    dump_bin   = find_binary(['mariadb-dump', 'mysqldump'])
    mdb_export = find_binary(['mdb-export'])
    if not mdb_export:
        die("mdb-export not found — install it with:  sudo apt install mdbtools")
    if not mysql_bin or not dump_bin:
        die("MariaDB client tools not found (need mariadb/mysql + mariadb-dump/mysqldump)")

    base_args = ['-h', db_host, '-P', str(db_port), '-u', db_user]
    if db_pass:
        base_args += ['-p' + db_pass]
    charset = ['--default-character-set=utf8mb4']

    backup_dir = args.backup_dir or os.path.join(args.app_dir, 'database_backup')
    os.makedirs(backup_dir, exist_ok=True)
    stamp = dt.datetime.now().strftime('%Y%m%d_%H%M%S')
    backup_path = os.path.join(backup_dir, f'{stamp}_dbnias_backup.sql')

    # ── 1. Mandatory backup ─────────────────────────────────────────
    if args.dry_run:
        print(f"[DRY-RUN] would create backup: {backup_path}")
    else:
        print(f"[1/5] Backing up database '{db_name}' -> {backup_path}")
        dump_args = [dump_bin] + base_args + charset + [
            '--single-transaction', '--no-tablespaces', '--hex-blob', db_name,
        ]
        res = run(dump_args + ['--routines', '--triggers'])
        if res.returncode != 0:
            # e.g. stale mysql.proc (needs mariadb-upgrade) breaks
            # SHOW FUNCTION STATUS; retry without routines/triggers.
            print("      [WARN] full dump failed, retrying without "
                  "routines/triggers:\n" + res.stderr.strip())
            res = run(dump_args)
        if res.returncode != 0:
            die(f"backup FAILED ({res.returncode}):\n{res.stderr}\n"
                f"Aborting — no changes were made.")
        with open(backup_path, 'w', encoding='utf-8') as f:
            f.write(res.stdout)
        if not os.path.getsize(backup_path):
            die("backup file is empty — aborting before any write")
        print(f"      backup OK ({os.path.getsize(backup_path)} bytes)")

    # ── 2. Export the .mdb NIAS table ───────────────────────────────
    print("[2/5] Exporting NIAS from the .mdb file")
    res = run([mdb_export, '-T', '%Y-%m-%d', '-D', '%Y-%m-%d', args.mdb, 'NIAS'])
    if res.returncode != 0:
        die(f"mdb-export FAILED ({res.returncode}):\n{res.stderr}")
    csv_text = res.stdout

    import csv
    import io
    reader = csv.DictReader(io.StringIO(csv_text))
    if not reader.fieldnames or 'NONIAS' not in reader.fieldnames:
        die(f"unexpected mdb column layout: {reader.fieldnames}")
    mdb_rows = {}
    for r in reader:
        nonias = (r.get('NONIAS') or '').strip()
        if not nonias:
            print(f"[WARN] skipping mdb row without NONIAS: {r}")
            continue
        if nonias in mdb_rows:
            print(f"[WARN] duplicate NONIAS in mdb: {nonias} — keeping last")
        mdb_rows[nonias] = r

    # ── 3. Dump current MariaDB NIAS ────────────────────────────────
    print("[3/5] Reading current NIAS rows from MariaDB")
    col_list = ', '.join('`%s`' % c for c in NIAS_COLUMNS)
    res = run([mysql_bin] + base_args + charset + [db_name, '-N',
              '-e', f'SELECT {col_list} FROM NIAS'])
    if res.returncode != 0:
        die(f"SELECT from NIAS FAILED ({res.returncode}):\n{res.stderr}")

    db_rows = {}
    for line in res.stdout.splitlines():
        vals = line.split('\t')
        if len(vals) != len(NIAS_COLUMNS):
            print(f"[WARN] skipping malformed row ({len(vals)} fields): {line[:80]}")
            continue
        d = dict(zip(NIAS_COLUMNS, (unescape_batch(v) for v in vals)))
        if d.get('NONIAS'):
            db_rows[d['NONIAS']] = d

    # ── 4. Compute the diff ─────────────────────────────────────────
    print("[4/5] Computing differences")
    to_insert = []   # (nonias, mdb_row)
    to_update = []   # (nonias, changed_columns {col: new_value})
    identical = 0

    for nonias in sorted(mdb_rows):
        m = mdb_rows[nonias]
        if nonias not in db_rows:
            to_insert.append((nonias, m))
            continue
        changed = {}
        for c in COMPARE_COLUMNS:
            if norm(m.get(c)) != norm(db_rows[nonias].get(c)):
                changed[c] = norm(m.get(c))
        if changed:
            to_update.append((nonias, changed))
        else:
            identical += 1

    missing = sorted(set(db_rows) - set(mdb_rows))

    print(f"      mdb rows           : {len(mdb_rows)}")
    print(f"      MariaDB rows       : {len(db_rows)}")
    print(f"      to INSERT          : {len(to_insert)}")
    print(f"      to UPDATE          : {len(to_update)}")
    print(f"      identical (skip)   : {identical}")
    print(f"      missing from mdb   : {len(missing)}"
          + ("  (will be deactivated)" if args.deactivate_missing else
             "  (left untouched)"))

    if args.verbose:
        for nonias, m in to_insert:
            print(f"      + INSERT {nonias} {m.get('NAMA')}")
        for nonias, changed in to_update:
            print(f"      ~ UPDATE {nonias}: {changed}")

    if args.dry_run:
        print("[DRY-RUN] no database changes were made.")
        return

    # ── 5. Apply inside one transaction ─────────────────────────────
    print("[5/5] Applying changes inside a transaction")
    sql = ['SET NAMES utf8mb4;', 'START TRANSACTION;']
    for nonias, m in to_insert:
        cols = ', '.join('`%s`' % c for c in NIAS_COLUMNS)
        vals = ', '.join(mysql_literal(norm(m.get(c))) for c in NIAS_COLUMNS)
        sql.append(f'INSERT INTO NIAS ({cols}) VALUES ({vals});')
    for nonias, changed in to_update:
        sets = ', '.join('`%s` = %s' % (c, mysql_literal(v))
                         for c, v in changed.items())
        sql.append(f"UPDATE NIAS SET {sets} WHERE NONIAS = {mysql_literal(nonias)};")
    if args.deactivate_missing and missing:
        keys = ', '.join(mysql_literal(k) for k in missing)
        sql.append(f"UPDATE NIAS SET STATUS = '0' WHERE NONIAS IN ({keys});")
    sql.append('COMMIT;')

    # pipe the SQL script into the client on stdin (keeps credentials off argv)
    with tempfile.NamedTemporaryFile('w', suffix='.sql',
                                     delete=False, encoding='utf-8') as f:
        f.write('\n'.join(sql))
        sql_path = f.name
    with open(sql_path, encoding='utf-8') as f:
        res = subprocess.run(
            [mysql_bin] + base_args + charset + [db_name],
            stdin=f, text=True, capture_output=True)
    os.unlink(sql_path)

    if res.returncode != 0:
        print(f"[ERROR] transaction FAILED — rolled back:\n{res.stderr}",
              file=sys.stderr)
        print(f"        restore point: {backup_path}")
        sys.exit(1)

    print(f"      OK: {len(to_insert)} inserted, {len(to_update)} updated, "
          f"{identical} unchanged.")
    print(f"      Backup: {backup_path}")
    print(f"      Restore with:  mariadb -u{db_user} -p {db_name} < {backup_path}")


if __name__ == '__main__':
    main()
