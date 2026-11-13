<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Every change to a row of `push_devices` leaves a full copy of the row here (see App\Services\History): a browser or app registering,
        // being linked to a person, turning notifications off or on again, being removed. The token and keys are masked.
        Schema::create('push_devices_history', function (Blueprint $table) {
            $table->id('history_id');
            $table->unsignedBigInteger('id')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('person_id')->nullable();
            $table->string('platform', 10)->nullable();
            $table->string('token_hash', 40)->nullable();
            $table->text('token')->nullable();
            $table->text('keys')->nullable();
            $table->string('label', 120)->nullable();
            $table->string('status', 10)->nullable();
            $table->dateTime('last_seen_at')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

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
            $table->string('page_url', 1000)->nullable();
            $table->string('device_uid', 64)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_devices_history');
    }
};
