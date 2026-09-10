<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('InactiveClub')) {
            Schema::create('InactiveClub', function (Blueprint $table) {
                $table->string('KDPROP', 2)->nullable();
                $table->string('NAMAPROP', 50)->nullable();
                $table->string('KDJENIS', 1)->nullable();
                $table->string('JENIS', 4)->nullable();
                $table->string('KDKOTA', 5)->nullable();
                $table->string('NAMAKOTA', 50)->nullable();
                $table->string('KDCLUB', 2)->nullable();
                $table->string('NAMACLUB', 50)->nullable();
                $table->string('MSTNIAS', 10)->nullable();
                $table->string('CPERSON', 30)->nullable();
                $table->string('KETERANGAN', 50)->nullable();
                $table->increments('ID');
            });
        }

        if (Schema::hasTable('MSTCLUB')) {
            DB::statement(
                "INSERT INTO InactiveClub (KDPROP, NAMAPROP, KDJENIS, JENIS, KDKOTA, NAMAKOTA, KDCLUB, NAMACLUB, MSTNIAS, CPERSON, KETERANGAN)
                 SELECT m.KDPROP, m.NAMAPROP, m.KDJENIS, m.JENIS, m.KDKOTA, m.NAMAKOTA, m.KDCLUB, m.NAMACLUB, m.MSTNIAS, m.CPERSON, m.KETERANGAN
                 FROM MSTCLUB m
                 WHERE UPPER(TRIM(m.NAMACLUB)) = 'EAGLE DC'
                   AND NOT EXISTS (
                       SELECT 1 FROM InactiveClub i
                       WHERE UPPER(TRIM(i.NAMACLUB)) = 'EAGLE DC'
                   )"
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('InactiveClub');
    }
};
