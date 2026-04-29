<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MstKota extends Model
{
    // Nama tabel sesuai di database
    protected $table = 'MSTKOTA';

    // Primary key (sesuai deskripsi tabel Anda)
    protected $primaryKey = 'ID';

    // Karena tabel ini kemungkinan besar data statis (master), matikan timestamps
    public $timestamps = false;

    // Mass assignment protection
    protected $guarded = [];
}
