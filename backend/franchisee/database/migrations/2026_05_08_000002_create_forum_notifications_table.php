<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('forum_notifications', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->unsignedBigInteger('group_id')->nullable();
            $table->unsignedBigInteger('thread_id');
            $table->unsignedBigInteger('comment_id')->nullable();
            $table->string('type', 50);
            $table->string('message');
            $table->boolean('is_read')->default(false);
            $table->timestamps();

            $table->index('user_id');
            $table->index('actor_id');
            $table->index('group_id');
            $table->index('thread_id');
            $table->index('comment_id');
            $table->index(['user_id', 'is_read']);
            $table->index(['group_id', 'created_at']);
            $table->index(['thread_id', 'comment_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('forum_notifications');
    }
};
