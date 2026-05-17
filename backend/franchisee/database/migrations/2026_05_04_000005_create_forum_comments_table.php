<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forum_comments', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->unsignedBigInteger('thread_id');
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->unsignedBigInteger('author_id');
            $table->text('content');
            $table->integer('likes_count')->default(0);
            $table->timestamps();

            $table->index('thread_id');
            $table->index('parent_id');
            $table->index('author_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forum_comments');
    }
};
