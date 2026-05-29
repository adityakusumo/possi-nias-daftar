<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MstKu extends Model
{
    protected $table = 'MstKU';
    protected $primaryKey = 'IDKU';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'KU',
        'TGLACUAN',
        'LAHIRMULAI',
        'LAHIRSAMPAI',
    ];
}
