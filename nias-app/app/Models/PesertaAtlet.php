<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PesertaAtlet extends Model
{
    protected $table = 'Atlet';
    protected $primaryKey = 'IDATLET';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'NAMAATLET',
        'ASAL',
        'NAMACLUB',
        'JENISDOM',
        'NAMAKOTADOM',
        'NAMAPROPDOM',
        'GENDER',
        'KU',
        'SP',
        'NONIAS',
        'TGLLAHIR',
        'created_by',
        'updated_by',
        'EXPIRED',
    ];

    public function peserta()
    {
        return $this->belongsTo(MstPeserta::class, 'ASAL', 'ASAL');
    }
}
