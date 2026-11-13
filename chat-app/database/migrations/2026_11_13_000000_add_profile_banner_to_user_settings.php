<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ch_user_settings', function (Blueprint $table) {
            $table->string('profile_banner')->nullable()->after('chat_background');
        });
    }

    public function down(): void
    {
        Schema::table('ch_user_settings', function (Blueprint $table) {
            $table->dropColumn('profile_banner');
        });
    }
};

