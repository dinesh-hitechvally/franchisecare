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
        Schema::create('forum_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['topic', 'state', 'custom'])->default('custom');
            $table->string('icon')->nullable();
            $table->string('color')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->boolean('is_public')->default(true);
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });

        // Group members pivot table
        Schema::create('forum_group_members', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('group_id');
            $table->unsignedBigInteger('user_id');
            $table->enum('role', ['member', 'moderator', 'admin'])->default('member');
            $table->timestamps();

            $table->foreign('group_id')->references('id')->on('forum_groups')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['group_id', 'user_id']);
        });

        // Add group_id to forum_threads
        Schema::table('forum_threads', function (Blueprint $table) {
            $table->unsignedBigInteger('group_id')->nullable()->after('topic');
            $table->foreign('group_id')->references('id')->on('forum_groups')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('forum_threads', function (Blueprint $table) {
            $table->dropForeign(['group_id']);
            $table->dropColumn('group_id');
        });

        Schema::dropIfExists('forum_group_members');
        Schema::dropIfExists('forum_groups');
    }
};
