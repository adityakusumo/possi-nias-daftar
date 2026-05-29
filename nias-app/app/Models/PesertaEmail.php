<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PesertaEmail extends Model
{
    protected $table = 'PesertaEmail';
    protected $primaryKey = 'IDPESERTAEMAIL';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'KDEVENT',
        'NAMAEVENT',
        'TGLMULAIEVENT',
        'TGLAKHIREVENT',
        'LOKASI',
        'ASAL',
        'NAMACLUB',
        'JENISDOM',
        'NAMAKOTADOM',
        'NAMAPROPDOM',
        'NAMANEGDOM',
        'GENDER',
        'KU',
        'NAMAATLET',
        'NONIAS',
        'TPTLAHIR',
        'TGLLAHIR',
        'NOMOR',
        'SP',
        'GAYA',
        'MM',
        'MMdes',
        'SS',
        'SSdes',
        'HS',
        'DAFTAR',
        'CETAKPIAGAMPESERTA',
        'IDATLET',
    ];
}
