<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    protected $fillable = ['key', 'value'];

    // ── Helper: get value by key ──────────────────────────────────
    public static function get(string $key, mixed $default = null): mixed
    {
        $row = static::where('key', $key)->first();
        return $row ? $row->value : $default;
    }

    // ── Helper: set value by key ──────────────────────────────────
    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    // ── Cek apakah pendaftaran NIAS sedang dibuka ─────────────────
    // Return true  → pendaftaran terbuka
    // Return false → tertutup (belum diset, atau di luar range)
    public static function isNiasOpen(): bool
    {
        $open  = static::get('nias_open_date');
        $close = static::get('nias_close_date');

        if (!$open || !$close) {
            return false; // Belum diset → tertutup
        }

        $today = Carbon::today();
        return $today->between(Carbon::parse($open), Carbon::parse($close));
    }

    // ── Ambil batas maksimum akun per club ────────────────────────
    // Jika club tidak ada di JSON → kembalikan default 2
    public static function getMaxAccountsForClub(string $namaclub): int
    {
        $json = static::get('nias_max_accounts_per_club', '{}');
        $map  = json_decode($json, true) ?? [];
        return isset($map[$namaclub]) ? (int) $map[$namaclub] : 2;
    }

    // ── Simpan batas akun per club ────────────────────────────────
    public static function setMaxAccountsForClub(string $namaclub, int $max): void
    {
        $json = static::get('nias_max_accounts_per_club', '{}');
        $map  = json_decode($json, true) ?? [];
        $map[$namaclub] = $max;
        static::set('nias_max_accounts_per_club', json_encode($map));
    }

    // ── Penyesuaian manual jumlah NIAS (halaman Catatan Keuangan) ─
    // Format JSON: {"<user_id>": {"baru": n, "update": n}}
    // Dipakai bila baris NIAS_STRUCT sudah tidak ada lagi (mis. sudah
    // diproses/dihapus), supaya hitungan jumlah & nominal pada halaman
    // Catatan Keuangan dan Export ZIP tetap benar.
    public static function getFinanceAdjustment(int $userId): array
    {
        $json = static::get('nias_finance_adjustment', '{}');
        $map  = json_decode($json, true) ?: [];
        $row  = $map[(string) $userId] ?? [];

        return [
            'baru'   => (int) ($row['baru']   ?? 0),
            'update' => (int) ($row['update'] ?? 0),
        ];
    }

    // ── Simpan penyesuaian manual jumlah NIAS untuk satu user ─────
    public static function setFinanceAdjustment(int $userId, int $baru, int $update = 0): void
    {
        $json = static::get('nias_finance_adjustment', '{}');
        $map  = json_decode($json, true) ?: [];
        $map[(string) $userId] = ['baru' => $baru, 'update' => $update];
        static::set('nias_finance_adjustment', json_encode($map));
    }
}
