<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** One contact's answer for one custom field. */
    public function up(): void
    {
        Schema::create('contact_field_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_field_id')->constrained()->cascadeOnDelete();
            $table->text('value');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['contact_id', 'contact_field_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_field_values');
    }
};
