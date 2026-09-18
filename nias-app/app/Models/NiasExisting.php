<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class NiasExisting extends Model
{
    // Tabel NIAS yang berisi data atlet existing (bukan tabel pendaftaran)
    protected $table      = 'NIAS';
    protected $primaryKey = 'ID';

    // Tabel ini read-only dari aplikasi, tidak perlu timestamps
    public $timestamps = false;

    protected static function booted(): void
    {
        static::addGlobalScope('activeClub', function (Builder $builder) {
            $builder->whereNotIn(
                $builder->getModel()->getTable() . '.NAMACLUB',
                DB::table('InactiveClub')->select('NAMACLUB')
            );
        });
    }

    /**
     * Scope: hanya atlet yang BELUM berhenti.
     *
     * Kolom `berhenti` berasal dari DBNIAS.mdb: 0 = aktif, selain 0 = berhenti.
     * NULL / kosong diperlakukan sebagai aktif (sync_nias_mdb.py juga menulis
     * '0' untuk nilai kosong, jadi kolom ini tidak pernah NULL).
     *
     * Dipakai bersama oleh halaman /nias/existing dan export-nya supaya
     * aturan keduanya tidak pernah berbeda.
     */
    public function scopeAktif(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereNull('berhenti')
                ->orWhere('berhenti', '')
                ->orWhere('berhenti', '0');
        });
    }

    protected $casts = [
        'NIK' => 'encrypted',
    ];

    protected $fillable = [
        'NAMACLUB', 'NAMA', 'GENDER', 'TPTLAHIR', 'TGLLAHIR', 'NONIAS', 'EXPIRED',
    ];
}
