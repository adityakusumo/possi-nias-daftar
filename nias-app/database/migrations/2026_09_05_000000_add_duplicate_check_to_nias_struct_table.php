<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fitur duplicate check (duplicate-check.txt):
 * 1. Flag has_possible_duplicate pada registrasi NIAS baru (is_update=false)
 *    yang cocok persis (LOWER nama + gender + tgl lahir) dengan atlet di
 *    tabel master NIAS.
 *
 * Status DIBATALKAN tidak memerlukan kolom baru — memakai STATUS=4
 * (0=expired/ditolak, 1=disetujui, 2=pending, 3=terkirim, 4=dibatalkan/duplikat).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('NIAS_STRUCT', function (Blueprint $table) {
            if (!Schema::hasColumn('NIAS_STRUCT', 'has_possible_duplicate')) {
                $table->boolean('has_possible_duplicate')->default(false)->after('is_sent');
            }
        });
    }

    public function down(): void
    {
        Schema::table('NIAS_STRUCT', function (Blueprint $table) {
            if (Schema::hasColumn('NIAS_STRUCT', 'has_possible_duplicate')) {
                $table->dropColumn('has_possible_duplicate');
            }
        });
    }
};
