<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pakai nama tabel huruf besar sesuai model (protected $table = 'NIAS')
        Schema::table('NIAS', function (Blueprint $table) {
            // Kolom is_update & TGLDAFTAR_UPDATE (dari migration sebelumnya)
            // Tambahkan hanya jika belum ada, agar aman dijalankan ulang
            if (!Schema::hasColumn('NIAS', 'is_update')) {
                $table->boolean('is_update')->default(false)->after('STATUS');
            }
            if (!Schema::hasColumn('NIAS', 'TGLDAFTAR_UPDATE')) {
                $table->date('TGLDAFTAR_UPDATE')->nullable()->after('is_update');
            }
            if (!Schema::hasColumn('NIAS', 'tipe_update')) {
                $table->string('tipe_update', 50)->nullable()->after('TGLDAFTAR_UPDATE');
            }
            if (!Schema::hasColumn('NIAS', 'file_sk_mutasi')) {
                $table->string('file_sk_mutasi', 255)->nullable()->after('file_ijazah');
            }
        });
    }

    public function down(): void
    {
        Schema::table('NIAS', function (Blueprint $table) {
            $table->dropColumn(['is_update', 'TGLDAFTAR_UPDATE', 'tipe_update', 'file_sk_mutasi']);
        });
    }
};
