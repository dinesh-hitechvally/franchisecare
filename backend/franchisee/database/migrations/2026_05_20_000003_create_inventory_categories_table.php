<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_categories', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('name');
            $table->string('slug')->nullable();
            $table->string('color')->nullable()->comment('Tailwind color classes for UI');
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('company_id');
            $table->index(['company_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_categories');
    }
};
