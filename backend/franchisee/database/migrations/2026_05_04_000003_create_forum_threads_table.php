<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forum_threads', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('title')->nullable();
            $table->text('content');
            $table->unsignedBigInteger('author_id');
            $table->string('topic')->default('General');
            $table->unsignedBigInteger('group_id')->nullable();
            $table->boolean('is_pinned')->default(false);
            $table->integer('likes_count')->default(0);
            $table->timestamps();

            $table->index('author_id');
            $table->index('group_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forum_threads');
    }
};
