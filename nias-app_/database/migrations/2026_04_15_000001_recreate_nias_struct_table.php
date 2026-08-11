<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop dan recreate NIAS_STRUCT dengan struktur lengkap
        Schema::dropIfExists('NIAS_STRUCT');

        Schema::create('NIAS_STRUCT', function (Blueprint $table) {
            // ── Primary key ───────────────────────────────────────
            $table->integer('ID')->autoIncrement()->primary();

            // ── Kolom original dari tabel NIAS ────────────────────
            $table->string('KDPROP',      2)->nullable();
            $table->string('NAMAPROP',    50)->nullable();
            $table->string('KDJENIS',     1)->nullable();
            $table->string('JENIS',       4)->nullable();
            $table->string('KDKOTA',      5)->nullable();
            $table->string('NAMAKOTA',    50)->nullable();
            $table->string('KDCLUB',      2)->nullable();
            $table->string('NAMACLUB',    30)->nullable();
            $table->string('GENDER',      2)->nullable();
            $table->string('NAMA',        50)->nullable();
            $table->string('TEMPATLAHIR', 100)->nullable(); // TPTLAHIR di NIAS lama, TEMPATLAHIR di app
            $table->date('TGLLAHIR')->nullable();
            $table->tinyInteger('STATUS')->default(2); // 0=expired, 1=acc, 2=pending
            $table->string('NONIAS',      14)->nullable();
            $table->string('LASTMUTASI',  6)->nullable();
            $table->string('MUTASI',      1)->nullable();
            $table->date('EXPIRED')->nullable();
            $table->string('KDJENISDOM',  1)->nullable();
            $table->string('JENISDOM',    4)->nullable();
            $table->string('KDKOTADOM',   5)->nullable();
            $table->string('NAMAKOTADOM', 50)->nullable();
            $table->string('KDPROPDOM',   2)->nullable();
            $table->string('NAMAPROPDOM', 50)->nullable();
            $table->string('NIK',         16)->nullable();
            $table->string('EMAIL',       50)->nullable();
            $table->string('NOKARTUNAS',  50)->nullable();

            // ── Kolom tambahan aplikasi ───────────────────────────
            $table->unsignedBigInteger('user_id')->nullable();
            $table->date('TGLDAFTAR')->nullable();
            $table->date('TGLDAFTAR_UPDATE')->nullable();
            $table->boolean('is_update')->default(false);
            $table->string('tipe_update',     50)->nullable();
            $table->string('mutasi_luar_jatim', 5)->nullable();
            $table->boolean('is_sent')->default(false);
            $table->timestamp('sent_at')->nullable();
            $table->string('file_kk',       255)->nullable();
            $table->string('file_foto',     255)->nullable();
            $table->string('file_akte',     255)->nullable();
            $table->string('file_ijazah',   255)->nullable();
            $table->string('file_sk_mutasi',255)->nullable();

            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('NIAS_STRUCT');
    }
};
