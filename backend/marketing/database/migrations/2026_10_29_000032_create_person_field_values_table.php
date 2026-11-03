<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** One person's answer for one custom field. */
    public function up(): void
    {
        Schema::create('person_field_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('person_id')->constrained()->cascadeOnDelete();
            $table->foreignId('person_field_id')->constrained()->cascadeOnDelete();
            $table->text('value');
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->dateTime('deleted_at')->nullable();

            $table->unique(['person_id', 'person_field_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('person_field_values');
    }
};
