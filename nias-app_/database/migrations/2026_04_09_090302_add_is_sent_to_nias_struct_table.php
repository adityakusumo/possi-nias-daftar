<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('NIAS_STRUCT', function (Blueprint $table) {
            $table->boolean('is_sent')->default(false)->after('is_update');
            $table->timestamp('sent_at')->nullable()->after('is_sent');
        });
    }

    public function down(): void
    {
        Schema::table('NIAS_STRUCT', function (Blueprint $table) {
            $table->dropColumn(['is_sent', 'sent_at']);
        });
    }
};
