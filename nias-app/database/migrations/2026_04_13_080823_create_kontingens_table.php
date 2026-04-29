<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint; // Pastikan baris ini ada
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Ganti 'Bundle' menjadi 'Blueprint' di bawah ini:
        Schema::create('kontingens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('jns_kompetisi', 1); // K atau P
            $table->string('nama_kontingen');
            $table->string('jenis_wilayah')->nullable(); // KOTA/KABUPATEN
            $table->string('nama_wilayah')->nullable();
            $table->string('provinsi')->default('JAWA TIMUR');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kontingens');
    }
};
