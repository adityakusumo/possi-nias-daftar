<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MstTarif extends Model
{
    protected $table = 'MstTarif';
    protected $primaryKey = 'IDTARIF';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'NOURUT',
        'ASALPESERTA',
        'NAMAPROPINSI',
        'NAMANEGARA',
        'NOMOR',
        'KDTARIF',
        'KETERANGAN',
        'RPTARIF',
    ];
}
