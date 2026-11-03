<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Every SMS the platform sent or tried to send ("log" service = test mode, nothing left the server). Email has its own table (email_logs).
        Schema::create('sms_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('campaign_id')->nullable()->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('workflow_id')->nullable()->index(); // set for texts sent by an automation workflow
            $table->foreignId('person_id')->nullable()->constrained()->nullOnDelete();
            $table->string('recipient');
            $table->text('body')->nullable();
            $table->enum('status', ['queued', 'sent', 'failed', 'skipped', 'logged'])->default('queued'); // logged = only written to the log (test mode / log mailer): nothing was delivered
            $table->string('source', 20)->default('campaign'); // campaign | automation | form | test
            $table->string('service', 30)->nullable();          // which connected service carried it (twilio, vonage…; "log" = test mode)
            $table->string('provider_id')->nullable();
            $table->text('error')->nullable();
            $table->dateTime('sent_at')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->dateTime('deleted_at')->nullable();

            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_logs');
    }
};
