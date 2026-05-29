<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NolombaAktif extends Model
{
    protected $table = 'NOLOMBAAKTIF';

    protected $fillable = [
        'GAYA',
        'AKTIF',
        'KATEGORI',
    ];
}
