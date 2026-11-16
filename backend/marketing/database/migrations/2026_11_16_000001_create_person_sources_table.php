<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Where a person comes from, and the id they have there. One row per place: a person can be on several platforms at once
        // (a Shopify customer who is also a Mailchimp subscriber and filled in a form has three rows).
        Schema::create('person_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('person_id')->constrained()->cascadeOnDelete();
            $table->string('platform', 30);                           // manual | import | form | api | shopify | mailchimp | messenger | x …
            $table->unsignedBigInteger('integration_id')->default(0); // the connection (0 = not through a connection)
            $table->string('external_id', 191)->default('');          // the person's id on that platform (a form's id for forms)
            $table->string('label')->nullable();                      // the connection's, form's or file's name
            $table->dateTime('first_seen_at')->nullable();
            $table->dateTime('last_synced_at')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            $table->unique(['person_id', 'platform', 'integration_id', 'external_id'], 'person_sources_unique');
            $table->index(['company_id', 'platform', 'external_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('person_sources');
    }
};
