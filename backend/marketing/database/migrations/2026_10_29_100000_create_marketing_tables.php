<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('businesses', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('from_name')->nullable();
            $t->string('from_email')->nullable();
            $t->string('reply_to')->nullable();
            $t->string('address')->nullable(); // shown in email footer (anti-spam compliance)
            $t->text('settings')->nullable();  // encrypted JSON: sms + image provider credentials
            $t->timestamps();
        });

        Schema::table('users', function (Blueprint $t) {
            $t->foreignId('business_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
        });

        Schema::create('contacts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('business_id')->constrained()->cascadeOnDelete();
            $t->string('first_name')->nullable();
            $t->string('last_name')->nullable();
            $t->string('email')->nullable();
            $t->string('phone')->nullable();
            $t->json('custom_fields')->nullable();
            $t->boolean('email_subscribed')->default(true);
            $t->boolean('sms_subscribed')->default(true);
            $t->string('unsubscribe_token', 40)->unique();
            $t->timestamps();
            $t->unique(['business_id', 'email']);
            $t->index(['business_id', 'phone']);
        });

        Schema::create('contact_lists', function (Blueprint $t) {
            $t->id();
            $t->foreignId('business_id')->constrained()->cascadeOnDelete();
            $t->string('name');
            $t->text('description')->nullable();
            $t->timestamps();
        });

        Schema::create('contact_contact_list', function (Blueprint $t) {
            $t->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $t->foreignId('contact_list_id')->constrained()->cascadeOnDelete();
            $t->primary(['contact_id', 'contact_list_id']);
        });

        Schema::create('templates', function (Blueprint $t) {
            $t->id();
            $t->foreignId('business_id')->constrained()->cascadeOnDelete();
            $t->string('name');
            $t->enum('type', ['email', 'sms']);
            $t->string('subject')->nullable();
            $t->longText('body');
            $t->timestamps();
        });

        Schema::create('campaigns', function (Blueprint $t) {
            $t->id();
            $t->foreignId('business_id')->constrained()->cascadeOnDelete();
            $t->foreignId('template_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('contact_list_id')->nullable()->constrained()->nullOnDelete();
            $t->string('name');
            $t->enum('type', ['email', 'sms']);
            $t->string('subject')->nullable();
            $t->longText('body');
            $t->enum('status', ['draft', 'scheduled', 'sending', 'sent', 'failed'])->default('draft');
            $t->timestamp('scheduled_at')->nullable();
            $t->timestamp('started_at')->nullable();
            $t->timestamp('completed_at')->nullable();
            $t->timestamps();
        });

        Schema::create('message_logs', function (Blueprint $t) {
            $t->id();
            $t->foreignId('business_id')->constrained()->cascadeOnDelete();
            $t->foreignId('campaign_id')->nullable()->constrained()->cascadeOnDelete();
            $t->foreignId('contact_id')->nullable()->constrained()->nullOnDelete();
            $t->enum('channel', ['email', 'sms']);
            $t->string('recipient');
            $t->string('subject')->nullable();
            $t->longText('body')->nullable();
            $t->enum('status', ['queued', 'sent', 'failed', 'skipped'])->default('queued');
            $t->string('provider_id')->nullable();
            $t->text('error')->nullable();
            $t->string('track_token', 40)->unique();
            $t->timestamp('sent_at')->nullable();
            $t->timestamp('opened_at')->nullable();
            $t->unsignedInteger('clicks')->default(0);
            $t->timestamps();
            $t->index(['business_id', 'channel', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_logs');
        Schema::dropIfExists('campaigns');
        Schema::dropIfExists('templates');
        Schema::dropIfExists('contact_contact_list');
        Schema::dropIfExists('contact_lists');
        Schema::dropIfExists('contacts');
        Schema::table('users', fn (Blueprint $t) => $t->dropConstrainedForeignId('business_id'));
        Schema::dropIfExists('businesses');
    }
};
