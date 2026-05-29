<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MstEvent extends Model
{
    protected $table = 'MstEvent';
    protected $primaryKey = 'IDEVENT';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'KDEVENT',
        'NAMAEVENT',
        'TGLMULAIEVENT',
        'TGLAKHIREVENT',
        'LOKASI',
    ];
}
