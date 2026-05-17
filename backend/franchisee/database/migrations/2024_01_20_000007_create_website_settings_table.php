<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('website_settings', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('site_title')->nullable();
            $table->string('tagline')->nullable();
            $table->string('contact_email')->nullable();
            $table->boolean('enable_online_booking')->default(true);
            $table->boolean('show_pricing')->default(true);
            $table->string('website_url')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_keywords', 500)->nullable();
            $table->text('meta_description')->nullable();
            $table->timestamps();

            $table->index('company_id');
            $table->unique('company_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('website_settings');
    }
};
