<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tambah kolom role ke tabel users
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 10)->default('regular')
                  ->after('namaclub')
                  ->comment('admin or regular');
        });

        // Tambah kolom file uploads ke tabel NIAS
        Schema::table('NIAS', function (Blueprint $table) {
            $table->string('file_kk', 255)->nullable()->after('MUTASI')
                  ->comment('Upload file Kartu Keluarga');
            $table->string('file_foto', 255)->nullable()->after('file_kk')
                  ->comment('Upload file Foto');
            $table->string('file_akte', 255)->nullable()->after('file_foto')
                  ->comment('Upload file Akte Lahir');
            $table->string('file_ijazah', 255)->nullable()->after('file_akte')
                  ->comment('Upload file Ijazah');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
        Schema::table('NIAS', function (Blueprint $table) {
            $table->dropColumn(['file_kk','file_foto','file_akte','file_ijazah']);
        });
    }
};
