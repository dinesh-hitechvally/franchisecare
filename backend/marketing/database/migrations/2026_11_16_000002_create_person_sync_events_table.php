<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The sync history of a person: when they arrived from somewhere, and when they were sent to a platform (or refused).
        Schema::create('person_sync_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('person_id')->constrained()->cascadeOnDelete();
            $table->string('platform', 30);
            $table->unsignedBigInteger('integration_id')->nullable();
            $table->unsignedBigInteger('sync_id')->nullable();                         // the integration_syncs run it belongs to
            $table->enum('direction', ['IN', 'OUT'])->default('IN');                   // in = came from the platform, out = pushed to it
            $table->enum('action', ['CREATED', 'LINKED', 'UPDATED', 'SENT', 'FAILED', 'SKIPPED']); // created: first appeared here | linked: already a person, now also on this platform
            $table->string('external_id', 191)->nullable();
            $table->string('label')->nullable();
            $table->text('message')->nullable();
            $table->dateTime('created_at')->nullable();

            $table->index(['person_id', 'id']);
            $table->index(['company_id', 'platform']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('person_sync_events');
    }
};
