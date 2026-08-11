<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        // Seed nilai default
        DB::table('app_settings')->insert([
            // Jadwal pendaftaran NIAS — null = tertutup
            ['key' => 'nias_open_date',              'value' => null, 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'nias_close_date',             'value' => null, 'created_at' => now(), 'updated_at' => now()],
            // Batas akun per club — JSON: {"NAMA_CLUB": angka}
            // Club yang tidak ada di JSON → pakai default 2
            ['key' => 'nias_max_accounts_per_club',  'value' => '{}', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('app_settings');
    }
};
