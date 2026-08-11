<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MstTarifNias extends Model
{
    protected $table    = 'MstTarifNias';
    protected $fillable = ['tipe', 'biaya'];

    // ── Helper: ambil biaya berdasarkan tipe ─────────────────────
    public static function getBiaya(string $tipe): int
    {
        $row = static::where('tipe', $tipe)->first();
        return $row ? (int) $row->biaya : 0;
    }

    // ── Helper: ambil semua tarif sebagai array ['baru'=>x, 'update'=>y]
    public static function getAllTarif(): array
    {
        return static::all()->pluck('biaya', 'tipe')->toArray();
    }
}
