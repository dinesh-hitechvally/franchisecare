<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Definitions of the extra data a company keeps on its people, beyond the built-in columns (name, email, phone). */
    public function up(): void
    {
        Schema::create('person_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('key', 40);          // merge-tag name, e.g. company_name
            $table->string('label');            // what people see, e.g. "Company name"
            $table->string('type', 20)->default('text'); // text | textarea | number | date | dropdown | radio | checkbox
            $table->text('options')->nullable();         // JSON list, for dropdown / radio
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->dateTime('deleted_at')->nullable();

            $table->string('key_active')->nullable()->virtualAs('CASE WHEN deleted_at IS NULL THEN `key` END'); // unique only among rows that are not deleted
            $table->unique(['company_id', 'key_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('person_fields');
    }
};
