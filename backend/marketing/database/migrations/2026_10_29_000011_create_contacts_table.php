<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('source', 20)->default('manual'); // manual | import | form | api
            $table->boolean('email_subscribed')->default(true);
            $table->boolean('sms_subscribed')->default(true);
            $table->timestamp('email_unsubscribed_at')->nullable();
            $table->timestamp('sms_unsubscribed_at')->nullable();
            $table->string('unsubscribe_token', 40)->unique();
            $table->timestamps();
            $table->softDeletes();

            $table->string('email_active')->nullable()->virtualAs('CASE WHEN deleted_at IS NULL THEN `email` END'); // unique only among rows that are not deleted
            $table->unique(['company_id', 'email_active']);
            $table->index(['company_id', 'phone']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
