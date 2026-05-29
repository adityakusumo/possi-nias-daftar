<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MstDenda extends Model
{
    protected $table = 'MstDenda';
    protected $primaryKey = 'IDDenda';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'RPDENDAOL',
        'RPDENDADQ',
        'RPDENDANOSWIM',
    ];
}
