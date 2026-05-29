<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KwtDaftarDeposit extends Model
{
    protected $table = 'rKwtDaftarDeposit';
    protected $primaryKey = 'IDKWTTOT';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'NOURUT',
        'TGLLUNAS',
        'ASAL',
        'NAMACLUB',
        'JENISDOM',
        'NAMAKOTADOM',
        'NAMAPROPDOM',
        'NAMANEGDOM',
        'ALAMATCLUB',
        'NOKWT',
        'NOMOR',
        'KDTARIF',
        'RPTARIF',
        'JMLATLET',
        'JMLNOLOMBA',
        'RPTOTDAFTAR',
        'RPDEPOSIT',
        'RPTOTDAFTDEPO',
        'RPLAIN',
        'RPTOTAL',
        'TANDATANGAN',
        'email',
    ];
}
