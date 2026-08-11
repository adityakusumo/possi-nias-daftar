<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class LombaUser extends Model
{
    protected $table = 'lomba_users';

    protected $fillable = [
        'email',
        'nama',
        'no_wa',
        'password',
    ];

    protected $hidden = [
        'password',
    ];

    // ── Auto-hash password when set ──────────────────────────────
    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = $value ? Hash::make($value) : null;
    }

    // ── Check if user has already set a password ─────────────────
    public function isRegistered(): bool
    {
        return !is_null($this->password);
    }

    // ── Verify password ──────────────────────────────────────────
    public function verifyPassword(string $plain): bool
    {
        if (!$this->password) return false;
        return Hash::check($plain, $this->password);
    }

    public function kontingen()
    {
        return $this->hasOne(Kontingen::class, 'lomba_user_id');
    }
}
