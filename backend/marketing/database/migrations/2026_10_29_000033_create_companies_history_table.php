<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Every change to a row of `companies` leaves a full copy of the row here (see App\Services\History).
        // Columns first mirror the source table (all nullable, no constraints), then the change details.
        Schema::create('companies_history', function (Blueprint $table) {
            $table->id('history_id');
            $table->unsignedBigInteger('id')->nullable();
            $table->string('name', 255)->nullable();
            $table->string('from_name', 255)->nullable();
            $table->string('from_email', 255)->nullable();
            $table->string('reply_to', 255)->nullable();
            $table->string('address', 255)->nullable();
            $table->text('settings')->nullable();
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
            $table->string('push_key', 40)->nullable();
            $table->string('public_key', 40)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies_history');
    }
};
