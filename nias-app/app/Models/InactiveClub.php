<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InactiveClub extends Model
{
    protected $table = 'InactiveClub';
    protected $primaryKey = 'ID';
    public $timestamps = false;
    protected $guarded = [];
}
