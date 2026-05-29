<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LombaUser extends Model
{
    protected $table = 'lomba_users';

    protected $fillable = [
        'email',
        'nama',
        'no_wa',
    ];

    public function kontingen()
    {
        return $this->hasOne(Kontingen::class, 'lomba_user_id');
    }
}
