<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('NIAS', function (Blueprint $table) {
            // Flag apakah record ini adalah UPDATE (perpanjangan), bukan daftar baru
            $table->boolean('is_update')->default(false)->after('STATUS');

            // Tanggal update/perpanjangan (berbeda dengan TGLDAFTAR awal)
            $table->date('TGLDAFTAR_UPDATE')->nullable()->after('is_update');
        });
    }

    public function down(): void
    {
        Schema::table('NIAS', function (Blueprint $table) {
            $table->dropColumn(['is_update', 'TGLDAFTAR_UPDATE']);
        });
    }
};
