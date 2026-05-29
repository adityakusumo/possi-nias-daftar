<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MstGaya extends Model
{
    protected $table = 'MstGaya';
    protected $primaryKey = 'IDGaya';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'NoUrut',
        'Gaya',
        'Keterangan',
    ];
}
