<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Every email the platform sent or tried to send. SMS has its own table (sms_logs).
        Schema::create('email_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('campaign_id')->nullable()->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('workflow_id')->nullable()->index(); // set for emails sent by an automation workflow
            $table->foreignId('person_id')->nullable()->constrained()->nullOnDelete();
            $table->string('recipient');
            $table->string('subject')->nullable();
            $table->longText('body')->nullable();
            $table->enum('status', ['queued', 'sent', 'failed', 'skipped', 'logged'])->default('queued'); // logged = only written to the log (test mode / log mailer): nothing was delivered
            $table->string('source', 20)->default('campaign'); // campaign | automation | form | test | notification
            $table->string('service', 30)->nullable();          // which connected service carried it (gmail, sendgrid…; "default" = platform mail)
            $table->string('provider_id')->nullable();
            $table->text('error')->nullable();
            $table->string('track_token', 40)->unique();
            $table->dateTime('sent_at')->nullable();
            $table->dateTime('opened_at')->nullable();
            $table->unsignedInteger('clicks')->default(0);
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->dateTime('deleted_at')->nullable();

            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_logs');
    }
};
