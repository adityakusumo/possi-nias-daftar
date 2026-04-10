<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mirrors the NIAS table structure inside DBNIAS.mdb
     */
    public function up(): void
    {
        Schema::create('NIAS', function (Blueprint $table) {
            $table->bigIncrements('ID');

            // Identity
            $table->string('NONIAS', 20)->nullable()->unique()->comment('Nomor Induk Anggota Selam');
            $table->string('NAMA', 100)->comment('Nama lengkap');
            $table->string('GENDER', 1)->default('L')->comment('L=Laki-laki P=Perempuan');
            $table->date('TGLLAHIR')->comment('Tanggal lahir');
            $table->string('TEMPATLAHIR', 100)->nullable()->comment('Tempat lahir');
            $table->string('NIK', 20)->nullable()->comment('Nomor Induk Kependudukan');
            $table->string('EMAIL', 100)->nullable();

            // Club
            $table->string('NAMACLUB', 100)->comment('Nama klub/perguruan');
            $table->string('KDCLUB', 5)->nullable()->comment('Kode klub');
            $table->string('KDJENIS', 1)->nullable()->comment('0=Kota 1=Kab');
            $table->string('JENIS', 10)->nullable()->comment('KOTA or KAB');
            $table->string('KDKOTA', 10)->nullable()->comment('Kode kota/kab klub');
            $table->string('NAMAKOTA', 100)->nullable()->comment('Nama kota/kab klub');

            // Domisili
            $table->string('KDJENISDOM', 1)->nullable()->comment('0=Kota 1=Kab');
            $table->string('JENISDOM', 10)->nullable()->comment('KOTA or KAB');
            $table->string('KDPROPDOM', 5)->nullable()->default('05');
            $table->string('NAMAPROPDOM', 50)->nullable()->default('JAWA TIMUR');
            $table->string('KDKOTADOM', 10)->nullable()->comment('Kode kota/kab domisili');
            $table->string('NAMAKOTADOM', 100)->nullable()->comment('Nama kota/kab domisili');

            // Status & Dates
            $table->tinyInteger('STATUS')->default(1)->comment('1=Aktif 0=Non-aktif');
            $table->date('TGLDAFTAR')->comment('Tanggal pendaftaran');
            $table->date('EXPIRED')->comment('Auto = TGLDAFTAR + 2 tahun');
            $table->string('LASTMUTASI', 10)->nullable()->comment('YYYYMM terakhir update');
            $table->string('MUTASI', 5)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('NIAS');
    }
};
