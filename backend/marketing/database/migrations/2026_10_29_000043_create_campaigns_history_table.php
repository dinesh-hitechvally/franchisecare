<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Every change to a row of `campaigns` leaves a full copy of the row here (see App\Services\History).
        // Columns first mirror the source table (all nullable, no constraints), then the change details.
        Schema::create('campaigns_history', function (Blueprint $table) {
            $table->id('history_id');
            $table->unsignedBigInteger('id')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('template_id')->nullable();
            $table->unsignedBigInteger('people_list_id')->nullable();
            $table->unsignedBigInteger('segment_id')->nullable();
            $table->unsignedBigInteger('tag_id')->nullable();
            $table->string('name', 255)->nullable();
            $table->string('type', 30)->nullable();
            $table->string('subject', 255)->nullable();
            $table->longText('body')->nullable();
            $table->longText('design')->nullable();
            $table->unsignedInteger('audience_total')->nullable();
            $table->unsignedInteger('audience_skipped')->nullable();
            $table->json('attachments')->nullable();
            $table->string('status', 30)->nullable();
            $table->dateTime('scheduled_at')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->dateTime('deleted_at')->nullable();

            $table->unsignedBigInteger('history_company_id')->nullable();
            $table->enum('change_action', ['CREATED', 'UPDATED', 'DELETED', 'RESTORED', 'PURGED', 'BASELINE']);
            $table->json('changed_fields')->nullable();     // which columns differ from the previous copy
            $table->string('record_label')->nullable();     // readable name of the row at that moment
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->string('changed_by_name')->nullable();
            $table->enum('change_source', ['DASHBOARD', 'API', 'PUBLIC', 'SYSTEM']);
            $table->string('change_ip', 45)->nullable();
            $table->dateTime('changed_at')->useCurrent();

            $table->index('id');
            $table->index(['history_company_id', 'changed_at']);
            $table->string('push_url', 1000)->nullable();
            $table->string('push_image', 1000)->nullable();
            $table->boolean('push_all_devices')->nullable();
            $table->string('push_platform', 10)->nullable();
            $table->unsignedBigInteger('social_account_id')->nullable();
            $table->string('social_template', 120)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaigns_history');
    }
};
