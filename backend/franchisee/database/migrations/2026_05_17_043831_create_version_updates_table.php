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
        Schema::create('version_updates', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->string('version_number'); // e.g., #2.1.1008
            $table->string('month'); // e.g., October
            $table->integer('year'); // e.g., 2021
            $table->json('changes'); // Array of change items
            $table->date('release_date')->nullable();
            $table->boolean('is_published')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['year', 'month']);
            $table->index('release_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('version_updates');
    }
};
