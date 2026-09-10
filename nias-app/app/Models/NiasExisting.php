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

    protected $casts = [
        'NIK' => 'encrypted',
    ];

    protected $fillable = [
        'NAMACLUB', 'NAMA', 'GENDER', 'TPTLAHIR', 'TGLLAHIR', 'NONIAS', 'EXPIRED',
    ];
}
