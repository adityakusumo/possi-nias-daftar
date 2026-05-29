<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Kompetisi extends Model
{
    protected $table = 'Kompetisi';

    protected $fillable = [
        'JNSKOMPETISI',
        'KETKOMPETISI',
        'WAJIBNIAS',
    ];

    public static function getJenis()
    {
        return DB::table('Kompetisi')->value('JNSKOMPETISI') ?? 'K';
    }

    public static function isWajibNias()
    {
        return DB::table('Kompetisi')->value('WAJIBNIAS') === '1';
    }
}
