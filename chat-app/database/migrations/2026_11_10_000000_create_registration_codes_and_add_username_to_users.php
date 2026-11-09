<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('registration_codes')) {
            Schema::create('registration_codes', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('max_uses')->nullable();
                $table->unsignedInteger('times_used')->default(0);
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();
            });

            // Seed the syndicate partner clearance key
            DB::table('registration_codes')->insert([
                'code' => 'V3ROURPARTNERSINCE2020',
                'description' => 'Syndicate Partner Clearance Key',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'username')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('username')->nullable()->unique()->after('name');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'username')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('username');
            });
        }

        Schema::dropIfExists('registration_codes');
    }
};

