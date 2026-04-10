<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('NIAS', function (Blueprint $table) {
            // Tambah kolom user_id setelah ID
            $table->unsignedBigInteger('user_id')->nullable()->after('ID')
                  ->comment('FK ke users.id — pelatih yang mendaftarkan');
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('NIAS', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};
