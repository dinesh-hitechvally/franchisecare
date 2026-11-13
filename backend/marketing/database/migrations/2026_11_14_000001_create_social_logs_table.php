<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Every WhatsApp / Messenger / X message a campaign sent (or tried to). Same shape as sms_logs; `platform` says which one.
        Schema::create('social_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('campaign_id')->nullable()->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('workflow_id')->nullable()->index();
            $table->foreignId('person_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('integration_id')->nullable()->index(); // the connected page / number it went through
            $table->enum('platform', ['WHATSAPP', 'MESSENGER', 'X']);                    // whatsapp | messenger | x
            $table->string('recipient');                       // phone number or account id
            $table->text('body')->nullable();
            $table->enum('status', ['QUEUED', 'SENT', 'FAILED', 'SKIPPED', 'LOGGED'])->default('QUEUED');   // QUEUED | SENT | FAILED | SKIPPED
            $table->enum('source', ['CAMPAIGN', 'AUTOMATION', 'FORM', 'TEST', 'NOTIFICATION'])->default('CAMPAIGN'); // campaign | test
            $table->string('service', 30)->nullable();
            $table->string('provider_id')->nullable();
            $table->text('error')->nullable();
            $table->dateTime('sent_at')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->dateTime('deleted_at')->nullable();

            $table->index(['company_id', 'platform', 'status']);
            $table->index(['campaign_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_logs');
    }
};
