<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NiasExisting extends Model
{
    // Tabel NIAS yang berisi data atlet existing (bukan tabel pendaftaran)
    protected $table      = 'NIAS';
    protected $primaryKey = 'ID';

    // Tabel ini read-only dari aplikasi, tidak perlu timestamps
    public $timestamps = false;

    protected $fillable = [
        'NAMACLUB', 'NAMA', 'GENDER', 'TPTLAHIR', 'TGLLAHIR', 'NONIAS', 'EXPIRED',
    ];
}
