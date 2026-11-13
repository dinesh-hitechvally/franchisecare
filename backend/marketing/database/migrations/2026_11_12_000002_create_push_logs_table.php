<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Every push notification sent or attempted, one row per device. Same shape as sms_logs / email_logs so reports can read it alike.
        Schema::create('push_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('campaign_id')->nullable()->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('workflow_id')->nullable()->index();
            $table->foreignId('person_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('push_device_id')->nullable()->index();
            $table->string('token', 40)->nullable()->unique();   // opaque id in the click link
            $table->string('recipient');                          // "Chrome on Windows" / "Android"
            $table->string('subject')->nullable();                // the notification title (named like email so one list can show both)
            $table->text('body')->nullable();
            $table->string('url', 1000)->nullable();              // where a tap goes
            $table->enum('status', ['QUEUED', 'SENT', 'FAILED', 'SKIPPED', 'LOGGED'])->default('QUEUED');      // QUEUED | SENT | FAILED | SKIPPED | LOGGED
            $table->enum('source', ['CAMPAIGN', 'AUTOMATION', 'FORM', 'TEST', 'NOTIFICATION'])->default('CAMPAIGN');    // campaign | automation | test
            $table->string('service', 30)->nullable();            // webpush | fcm | log
            $table->string('provider_id')->nullable();
            $table->text('error')->nullable();
            $table->unsignedInteger('clicks')->default(0);
            $table->dateTime('clicked_at')->nullable();
            $table->dateTime('sent_at')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->dateTime('deleted_at')->nullable();

            $table->index(['company_id', 'status']);
            $table->index(['campaign_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_logs');
    }
};
