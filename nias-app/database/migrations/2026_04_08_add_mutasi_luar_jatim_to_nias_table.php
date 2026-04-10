<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('NIAS', function (Blueprint $table) {
            if (!Schema::hasColumn('NIAS', 'mutasi_luar_jatim')) {
                // Nilai: 'ya' | 'tidak' | null (untuk daftar baru / tipe non-domisili)
                $table->string('mutasi_luar_jatim', 5)->nullable()->after('tipe_update');
            }
        });
    }

    public function down(): void
    {
        Schema::table('NIAS', function (Blueprint $table) {
            $table->dropColumn('mutasi_luar_jatim');
        });
    }
};
