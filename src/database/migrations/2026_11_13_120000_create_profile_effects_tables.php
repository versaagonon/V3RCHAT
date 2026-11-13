<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create profile_effects table.
        if (! Schema::hasTable('profile_effects')) {
            Schema::create('profile_effects', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('pair1_file');
                $table->string('pair2_file');
                $table->unsignedInteger('pair1_duration_ms')->default(5000);
                $table->unsignedInteger('pair2_duration_ms')->default(5000);
                $table->boolean('is_system')->default(true);
                $table->timestamps();
            });
        }

        // 2. Create user_profile_effects pivot (gift-only, mirrors user_nameplates).
        if (! Schema::hasTable('user_profile_effects')) {
            Schema::create('user_profile_effects', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->index();
                $table->unsignedBigInteger('profile_effect_id')->index();
                $table->unsignedBigInteger('gifted_by_id')->nullable()->index();
                $table->timestamps();

                $table->unique(['user_id', 'profile_effect_id']);
            });
        }

        // 3. Add active_profile_effect_id to ch_user_settings.
        if (Schema::hasTable('ch_user_settings') && ! Schema::hasColumn('ch_user_settings', 'active_profile_effect_id')) {
            Schema::table('ch_user_settings', function (Blueprint $table) {
                $column = $table->unsignedBigInteger('active_profile_effect_id')->nullable();

                if (Schema::hasColumn('ch_user_settings', 'active_nameplate_id')) {
                    $column->after('active_nameplate_id');
                }
            });
        }

        // 4. Seed the six bundled 450x880 APNG pair effects.
        // Durations are the exact sum of every fcTL delay in each APNG file and are
        // used by the client to know when pair-1 (entrance) has finished playing.
        $initialEffects = [
            ['name' => 'Dark Rose', 'slug' => 'darkrose', 'pair1_file' => 'darkrose-pair-1.png', 'pair2_file' => 'darkrose-pair-2.png', 'pair1_duration_ms' => 3000, 'pair2_duration_ms' => 3000],
            ['name' => 'Falling Stars', 'slug' => 'fallingstars', 'pair1_file' => 'fallingstars-pair-1.png', 'pair2_file' => 'fallingstars-pair-2.png', 'pair1_duration_ms' => 4920, 'pair2_duration_ms' => 4920],
            ['name' => 'Let\'s Play', 'slug' => 'letsplay', 'pair1_file' => 'letsplay-pair-1.png', 'pair2_file' => 'letsplay-pair-2.png', 'pair1_duration_ms' => 5000, 'pair2_duration_ms' => 5000],
            ['name' => 'Midnight Owl', 'slug' => 'midnightownl', 'pair1_file' => 'midnightownl-pair-1.png', 'pair2_file' => 'midnightownl-pair-2.png', 'pair1_duration_ms' => 3000, 'pair2_duration_ms' => 3000],
            ['name' => 'Skull Ghost', 'slug' => 'skullghost', 'pair1_file' => 'skullghost-pair-1.png', 'pair2_file' => 'skullghost-pair-2.png', 'pair1_duration_ms' => 5000, 'pair2_duration_ms' => 5000],
            ['name' => 'Venom', 'slug' => 'venom', 'pair1_file' => 'venom-pair-1.png', 'pair2_file' => 'venom-pair-2.png', 'pair1_duration_ms' => 3500, 'pair2_duration_ms' => 4875],
        ];

        foreach ($initialEffects as $effect) {
            DB::table('profile_effects')->updateOrInsert(
                ['slug' => $effect['slug']],
                [
                    'name' => $effect['name'],
                    'pair1_file' => $effect['pair1_file'],
                    'pair2_file' => $effect['pair2_file'],
                    'pair1_duration_ms' => $effect['pair1_duration_ms'],
                    'pair2_duration_ms' => $effect['pair2_duration_ms'],
                    'is_system' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        // 5. Copy the bundled source assets into the public vendor folder when they
        //    are present in this repository layout (frontend/src/assets is a sibling
        //    of the Laravel app). Safe no-op on installs that ship assets already.
        $sourceDir = base_path('../frontend/src/assets/images/profileeffect');
        $targetDir = public_path('vendor/chatify/profileeffects');

        if (is_dir($sourceDir)) {
            if (! is_dir($targetDir)) {
                @mkdir($targetDir, 0755, true);
            }

            foreach ($initialEffects as $effect) {
                foreach ([$effect['pair1_file'], $effect['pair2_file']] as $fileName) {
                    if (is_file($sourceDir.'/'.$fileName) && ! is_file($targetDir.'/'.$fileName)) {
                        @copy($sourceDir.'/'.$fileName, $targetDir.'/'.$fileName);
                    }
                }
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ch_user_settings') && Schema::hasColumn('ch_user_settings', 'active_profile_effect_id')) {
            Schema::table('ch_user_settings', function (Blueprint $table) {
                $table->dropColumn('active_profile_effect_id');
            });
        }

        Schema::dropIfExists('user_profile_effects');
        Schema::dropIfExists('profile_effects');
    }
};
