<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run()
    {
        User::create([
            'nama' => 'Admin_it',
            'gender' => 'L',
            'namaclub' => '-',
            'role' => 'admin',
            'email' => 'it.possijatim@gmail.com',
            'password' => Hash::make('Infoglobal@2019'),
        ]);
    }
}
