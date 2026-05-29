<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MstBiayaExtra extends Model
{
    protected $table = 'MstBiayaExtra';
    protected $primaryKey = 'IDExtra';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'RPBIAYAEXTRA',
        'KETERANGAN',
    ];
}
