<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class A3Entry extends Model
{
    protected $table = 'A3';
    protected $primaryKey = 'IDA3P';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'GENDER',
        'KU',
        'NAMAATLET',
        'ASAL',
        'NAMACLUB',
        'JENISDOM',
        'NAMAKOTADOM',
        'NAMAPROPDOM',
        'MON50MM',
        'MON50SS',
        'MON50HS',
        'MON100MM',
        'MON100SS',
        'MON100HS',
        'MON200MM',
        'MON200SS',
        'MON200HS',
        'MON400MM',
        'MON400SS',
        'MON400HS',
        'MON800MM',
        'MON800SS',
        'MON800HS',
        'MON1500MM',
        'MON1500SS',
        'MON1500HS',
        'SUB50MM',
        'SUB50SS',
        'SUB50HS',
        'SUB100MM',
        'SUB100SS',
        'SUB100HS',
        'SUB200MM',
        'SUB200SS',
        'SUB200HS',
        'SUB400MM',
        'SUB400SS',
        'SUB400HS',
        'APN50MM',
        'APN50SS',
        'APN50HS',
        'IMM100MM',
        'IMM100SS',
        'IMM100HS',
        'IMM400MM',
        'IMM400SS',
        'IMM400HS',
        'IMM800MM',
        'IMM800SS',
        'IMM800HS',
        'ESTMON200MM',
        'ESTMON200SS',
        'ESTMON200HS',
        'ESTMON400MM',
        'ESTMON400SS',
        'ESTMON400HS',
        'ESTMON800MM',
        'ESTMON800SS',
        'ESTMON800HS',
        'ESTSUB200MM',
        'ESTSUB200SS',
        'ESTSUB200HS',
        'ESTSUB400MM',
        'ESTSUB400SS',
        'ESTSUB400HS',
        'ESTMONM200MM',
        'ESTMONM200SS',
        'ESTMONM200HS',
        'ESTMONM400MM',
        'ESTMONM400SS',
        'ESTMONM400HS',
        'ESTSUBM200MM',
        'ESTSUBM200SS',
        'ESTSUBM200HS',
        'ESTSUBM400MM',
        'ESTSUBM400SS',
        'ESTSUBM400HS',
        'SP',
        'TGLLAHIR',
        'NOMOR',
        'GENDERMIX',
        'email',
    ];

    public function atlet(): BelongsTo
    {
        return $this->belongsTo(PesertaAtlet::class, 'NAMAATLET', 'NAMAATLET')
            ->whereColumn('A3.ASAL', 'Atlet.ASAL');
    }
}
