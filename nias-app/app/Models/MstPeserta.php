<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MstPeserta extends Model
{
    protected $table = 'MstPeserta';
    protected $primaryKey = 'IDPESERTA';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'ASAL',
        'NAMACLUB',
        'JENISDOM',
        'NAMAKOTADOM',
        'NAMAPROPDOM',
        'NAMANEGDOM',
        'CONTACTPERSON',
        'TELPON',
        'OFFICIAL',
        'KETERANGAN',
        'email',
        'user_id',
        'lomba_user_id',
    ];
}
