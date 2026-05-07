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
        Schema::table('forum_comments', function (Blueprint $table) {
            $table->integer('likes_count')->default(0)->after('content');
            $table->unsignedBigInteger('parent_id')->nullable()->after('thread_id');

            $table->foreign('parent_id')->references('id')->on('forum_comments')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('forum_comments', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn(['likes_count', 'parent_id']);
        });
    }
};
