<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $table = config('chatify.tables.notifications', 'ch_notifications');

        if (! Schema::hasTable($table)) {
            Schema::create($table, function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->unsignedBigInteger('user_id');
                $table->string('type', 32); // 'information' | 'request'
                $table->string('title');
                $table->text('body');
                $table->string('image_url')->nullable();
                $table->json('data')->nullable();
                $table->string('status', 32)->default('unread'); // 'unread' | 'read' | 'pending' | 'accepted' | 'declined'
                $table->timestamps();

                $table->index(['user_id', 'type', 'status']);
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        $table = config('chatify.tables.notifications', 'ch_notifications');
        Schema::dropIfExists($table);
    }
};
