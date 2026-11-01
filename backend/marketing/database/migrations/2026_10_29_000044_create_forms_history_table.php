<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Every change to a row of `forms` leaves a full copy of the row here (see App\Services\History).
        // Columns first mirror the source table (all nullable, no constraints), then the change details.
        Schema::create('forms_history', function (Blueprint $table) {
            $table->id('history_id');
            $table->unsignedBigInteger('id')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('name', 255)->nullable();
            $table->string('type', 20)->nullable();
            $table->string('slug', 40)->nullable();
            $table->string('title', 255)->nullable();
            $table->text('description')->nullable();
            $table->text('fields')->nullable();
            $table->string('button_text', 255)->nullable();
            $table->string('success_message', 255)->nullable();
            $table->text('style')->nullable();
            $table->string('redirect_url', 500)->nullable();
            $table->boolean('save_contact')->nullable();
            $table->boolean('subscribe')->nullable();
            $table->text('notify_emails')->nullable();
            $table->boolean('autoreply')->nullable();
            $table->string('autoreply_subject', 255)->nullable();
            $table->text('autoreply_body')->nullable();
            $table->json('autoreply_attachments')->nullable();
            $table->text('list_ids')->nullable();
            $table->text('tag_ids')->nullable();
            $table->boolean('active')->nullable();
            $table->unsignedInteger('views')->nullable();
            $table->unsignedInteger('submissions')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->timestamp('deleted_at')->nullable();

            $table->unsignedBigInteger('history_company_id')->nullable();
            $table->string('change_action', 20);            // created | updated | deleted | restored | purged
            $table->json('changed_fields')->nullable();     // which columns differ from the previous copy
            $table->string('record_label')->nullable();     // readable name of the row at that moment
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->string('changed_by_name')->nullable();
            $table->string('change_source', 20);            // dashboard | api | public | system
            $table->string('change_ip', 45)->nullable();
            $table->timestamp('changed_at')->useCurrent();

            $table->index('id');
            $table->index(['history_company_id', 'changed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forms_history');
    }
};
