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
        Schema::create('forum_thread_likes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('forum_thread_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();

            $table->unique(['forum_thread_id', 'user_id']);
            $table->foreign('forum_thread_id')->references('id')->on('forum_threads')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::create('forum_comment_likes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('forum_comment_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();

            $table->unique(['forum_comment_id', 'user_id']);
            $table->foreign('forum_comment_id')->references('id')->on('forum_comments')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('forum_comment_likes');
        Schema::dropIfExists('forum_thread_likes');
    }
};
