<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MstDeposit extends Model
{
    protected $table = 'MstDeposit';
    protected $primaryKey = 'IDMstDeposit';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'JMLATLETMULAI',
        'JMLATLETSAMPAI',
        'RPDEPOSIT',
    ];
}
