<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kontingen extends Model
{
    protected $fillable = [
        'user_id',
        'lomba_user_id',
        'jns_kompetisi',
        'nama_kontingen',
        'jenis_wilayah',
        'nama_wilayah',
        'provinsi',
    ];
}
