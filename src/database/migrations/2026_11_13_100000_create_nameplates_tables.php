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
        // 1. Create nameplates table
        if (! Schema::hasTable('nameplates')) {
            Schema::create('nameplates', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('file_name');
                $table->boolean('is_system')->default(true);
                $table->timestamps();
            });
        }

        // 2. Create user_nameplates pivot table (gift-only: nodes never own by default)
        if (! Schema::hasTable('user_nameplates')) {
            Schema::create('user_nameplates', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->index();
                $table->unsignedBigInteger('nameplate_id')->index();
                $table->unsignedBigInteger('gifted_by_id')->nullable()->index();
                $table->timestamps();

                $table->unique(['user_id', 'nameplate_id']);
            });
        }

        // 3. Add active_nameplate_id to ch_user_settings
        if (Schema::hasTable('ch_user_settings') && ! Schema::hasColumn('ch_user_settings', 'active_nameplate_id')) {
            Schema::table('ch_user_settings', function (Blueprint $table) {
                $table->unsignedBigInteger('active_nameplate_id')->nullable()->after('active_border_id');
            });
        }

        // 4. Seed the bundled nameplates (448x84 / 672x126, 16:3 aspect)
        $initialNameplates = [
            ['name' => 'Crow', 'slug' => 'crow', 'file_name' => 'crow.png'],
            ['name' => 'Hand With Skull', 'slug' => 'handwithskull', 'file_name' => 'handwithskull.png'],
            ['name' => 'Heaven Leaf', 'slug' => 'heavenleaf', 'file_name' => 'heavenleaf.png'],
            ['name' => 'Many Eyes', 'slug' => 'manyeyes', 'file_name' => 'manyeyes.png'],
            ['name' => 'White Rose', 'slug' => 'whiterose', 'file_name' => 'whiterose.png'],
            ['name' => 'White Wolf', 'slug' => 'whitewolf', 'file_name' => 'whitewolf.png'],
        ];

        foreach ($initialNameplates as $nameplate) {
            DB::table('nameplates')->updateOrInsert(
                ['slug' => $nameplate['slug']],
                [
                    'name' => $nameplate['name'],
                    'file_name' => $nameplate['file_name'],
                    'is_system' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ch_user_settings') && Schema::hasColumn('ch_user_settings', 'active_nameplate_id')) {
            Schema::table('ch_user_settings', function (Blueprint $table) {
                $table->dropColumn('active_nameplate_id');
            });
        }

        Schema::dropIfExists('user_nameplates');
        Schema::dropIfExists('nameplates');
    }
};
