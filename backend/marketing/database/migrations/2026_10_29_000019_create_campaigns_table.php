<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('template_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('people_list_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('segment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('tag_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->enum('type', ['EMAIL', 'SMS', 'PUSH', 'WHATSAPP', 'MESSENGER', 'X']); // email | sms | push | whatsapp | messenger | x
            $table->string('subject')->nullable();
            $table->longText('body');
            $table->longText('design')->nullable();
            $table->unsignedInteger('audience_total')->nullable(); // how many people the audience had when it was sent (null until sent)
            $table->unsignedInteger('audience_skipped')->default(0); // people left out at send time (unsubscribed / no address): counted, not logged
            $table->json('attachments')->nullable(); // email only: files sent with every message [{token, name, size}]
            $table->enum('status', ['DRAFT', 'SCHEDULED', 'SENDING', 'SENT', 'FAILED', 'CANCELLED'])->default('DRAFT');
            $table->dateTime('scheduled_at')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->dateTime('deleted_at')->nullable();
            $table->string('push_url', 1000)->nullable();                // push: where a tap on the notification goes
            $table->string('push_image', 1000)->nullable();              // push: optional picture
            $table->boolean('push_all_devices')->default(false);        // push: every registered device, linked to a person or not, instead of an audience of people
            $table->enum('push_platform', ['WEB', 'ANDROID', 'IOS'])->nullable();            // push to all devices: web | android | ios (null = every kind)
            $table->unsignedBigInteger('social_account_id')->nullable(); // whatsapp / messenger / x: the connected account (integrations.id) it is sent through
            $table->string('social_template', 120)->nullable();          // whatsapp: the approved template that carries the text
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
