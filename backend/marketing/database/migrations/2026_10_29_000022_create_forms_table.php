<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type', 20)->default('newsletter'); // newsletter | contact | other — sets the defaults for what happens on submit
            $table->string('slug', 40)->unique();
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->text('fields');                       // JSON: [{id, key, type, label, placeholder, help, required, options}]
            $table->string('button_text')->default('Subscribe');
            $table->string('success_message')->default('Thanks for subscribing!');
            $table->string('existing_message', 500)->nullable(); // optional: shown instead of the thank-you when the person is already a person
            $table->text('style')->nullable();            // JSON: colours, font, corner radius, width
            $table->string('redirect_url', 500)->nullable(); // optional page to send people to after they submit
            $table->boolean('save_person')->default(true);  // create/update a person from the submission
            $table->boolean('subscribe')->default(true);     // new people count as subscribed to marketing (off for e.g. a contact form)
            $table->text('notify_emails')->nullable();       // JSON list: addresses that get an email for every submission
            $table->boolean('autoreply')->default(false);    // send the person a confirmation email
            $table->string('autoreply_subject')->nullable();
            $table->text('autoreply_body')->nullable();
            $table->json('autoreply_attachments')->nullable(); // files sent with the auto-reply: [{token, name, size}]
            $table->text('list_ids')->nullable();         // JSON
            $table->text('tag_ids')->nullable();          // JSON
            $table->boolean('active')->default(true);
            $table->unsignedInteger('views')->default(0);
            $table->unsignedInteger('submissions')->default(0);
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->dateTime('deleted_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forms');
    }
};
