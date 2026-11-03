<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('people', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('source', 20)->default('manual'); // manual | import | form | api
            $table->boolean('is_subscribed')->default(true); // subscribed to the newsletter (email and SMS together)
            $table->dateTime('subscribed_at')->nullable();   // when they subscribed; empty while unsubscribed
            $table->string('unsubscribe_token', 40)->unique();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->dateTime('deleted_at')->nullable();

            $table->string('email_active')->nullable()->virtualAs('CASE WHEN deleted_at IS NULL THEN `email` END'); // unique only among rows that are not deleted
            $table->unique(['company_id', 'email_active']);
            $table->index(['company_id', 'phone']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('people');
    }
};
