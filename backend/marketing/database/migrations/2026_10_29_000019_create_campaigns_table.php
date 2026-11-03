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
            $table->foreignId('contact_list_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('segment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('tag_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->enum('type', ['email', 'sms']);
            $table->string('subject')->nullable();
            $table->longText('body');
            $table->longText('design')->nullable();
            $table->unsignedInteger('audience_total')->nullable(); // how many contacts the audience had when it was sent (null until sent)
            $table->unsignedInteger('audience_skipped')->default(0); // contacts left out at send time (unsubscribed / no address): counted, not logged
            $table->json('attachments')->nullable(); // email only: files sent with every message [{token, name, size}]
            $table->enum('status', ['draft', 'scheduled', 'sending', 'sent', 'failed', 'cancelled'])->default('draft');
            $table->dateTime('scheduled_at')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->dateTime('deleted_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
