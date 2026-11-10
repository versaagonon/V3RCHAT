<?php

declare(strict_types=1);

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
        // 1. Add role column to users table
        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('role', 20)->default('node')->after('email');
            });

            // Set user 1 or versaa to admin role
            DB::table('users')->where('id', 1)->update(['role' => 'admin']);
            DB::table('users')->where('username', 'versaa_prime')->update(['role' => 'admin']);
        }

        // 2. Create avatar_borders table
        if (!Schema::hasTable('avatar_borders')) {
            Schema::create('avatar_borders', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('file_name');
                $table->boolean('is_system')->default(true);
                $table->timestamps();
            });
        }

        // 3. Create user_avatar_borders pivot table (for gifted / unlocked borders)
        if (!Schema::hasTable('user_avatar_borders')) {
            Schema::create('user_avatar_borders', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->index();
                $table->unsignedBigInteger('border_id')->index();
                $table->unsignedBigInteger('gifted_by_id')->nullable()->index();
                $table->timestamps();

                $table->unique(['user_id', 'border_id']);
            });
        }

        // 4. Add active_border_id to ch_user_settings
        if (Schema::hasTable('ch_user_settings') && !Schema::hasColumn('ch_user_settings', 'active_border_id')) {
            Schema::table('ch_user_settings', function (Blueprint $table) {
                $table->unsignedBigInteger('active_border_id')->nullable()->after('avatar');
            });
        }

        // 5. Seed initial 12 animated borders
        $initialBorders = [
            ['name' => 'Angel Flower', 'slug' => 'angleflower', 'file_name' => 'angleflower.png'],
            ['name' => 'Golden Energy', 'slug' => 'animated', 'file_name' => 'animated.png'],
            ['name' => 'Dark Rose', 'slug' => 'darkrose', 'file_name' => 'darkrose.png'],
            ['name' => 'Dark Rose White', 'slug' => 'darkrosewhite', 'file_name' => 'darkrosewhite.png'],
            ['name' => 'Devil Horns', 'slug' => 'devil', 'file_name' => 'devil.png'],
            ['name' => 'Fallen Angel Black', 'slug' => 'fallenangleblack', 'file_name' => 'fallenangleblack.png'],
            ['name' => 'Fallen Angel Shade', 'slug' => 'fallenangleshade', 'file_name' => 'fallenangleshade.png'],
            ['name' => 'Fallen Angel White', 'slug' => 'fallenanglewhite', 'file_name' => 'fallenanglewhite.png'],
            ['name' => 'Green Mana', 'slug' => 'greenmana', 'file_name' => 'greenmana.png'],
            ['name' => 'In Love', 'slug' => 'inlove', 'file_name' => 'inlove.png'],
            ['name' => 'Pink Love', 'slug' => 'pinklove', 'file_name' => 'pinklove.png'],
            ['name' => 'Spider In Love', 'slug' => 'spiderfallinlove', 'file_name' => 'spiderfallinlove.png'],
        ];

        foreach ($initialBorders as $border) {
            DB::table('avatar_borders')->updateOrInsert(
                ['slug' => $border['slug']],
                [
                    'name' => $border['name'],
                    'file_name' => $border['file_name'],
                    'is_system' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('ch_user_settings') && Schema::hasColumn('ch_user_settings', 'active_border_id')) {
            Schema::table('ch_user_settings', function (Blueprint $table) {
                $table->dropColumn('active_border_id');
            });
        }

        Schema::dropIfExists('user_avatar_borders');
        Schema::dropIfExists('avatar_borders');

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('role');
            });
        }
    }
};
