<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forum_comment_likes', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->unsignedBigInteger('forum_comment_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();

            $table->index('forum_comment_id');
            $table->index('user_id');
            $table->unique(['forum_comment_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forum_comment_likes');
    }
};
