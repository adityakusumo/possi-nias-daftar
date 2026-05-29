<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kontingens', function (Blueprint $table) {
            // Make existing user_id nullable so lomba users can use lomba_user_id instead
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->unsignedBigInteger('lomba_user_id')->nullable()->after('user_id');
            $table->foreign('lomba_user_id')->references('id')->on('lomba_users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('kontingens', function (Blueprint $table) {
            $table->dropForeign(['lomba_user_id']);
            $table->dropColumn('lomba_user_id');
        });
    }
};
