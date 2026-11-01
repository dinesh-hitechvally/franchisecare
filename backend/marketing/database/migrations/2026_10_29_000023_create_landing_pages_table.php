<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('landing_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug', 40)->unique();
            $table->string('title')->nullable();
            $table->longText('design')->nullable();
            $table->longText('html');
            $table->foreignId('form_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('published')->default(false);
            $table->unsignedInteger('views')->default(0);
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->dateTime('deleted_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landing_pages');
    }
};
