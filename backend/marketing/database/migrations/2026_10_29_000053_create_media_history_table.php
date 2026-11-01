<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Every change to a row of `media` leaves a full copy of the row here (see App\Services\History).
        // Columns first mirror the source table (all nullable, no constraints), then the change details.
        Schema::create('media_history', function (Blueprint $table) {
            $table->id('history_id');
            $table->unsignedBigInteger('id')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string('name', 255)->nullable();
            $table->string('file_name', 255)->nullable();
            $table->string('path', 500)->nullable();
            $table->string('token', 40)->nullable();
            $table->string('mime', 100)->nullable();
            $table->string('kind', 12)->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->string('alt', 255)->nullable();
            $table->string('source', 20)->nullable();
            $table->string('credit', 255)->nullable();
            $table->string('credit_url', 500)->nullable();
            $table->string('license', 100)->nullable();
            $table->string('source_url', 500)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->dateTime('deleted_at')->nullable();

            $table->unsignedBigInteger('history_company_id')->nullable();
            $table->string('change_action', 20);            // created | updated | deleted | restored | purged
            $table->json('changed_fields')->nullable();     // which columns differ from the previous copy
            $table->string('record_label')->nullable();     // readable name of the row at that moment
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->string('changed_by_name')->nullable();
            $table->string('change_source', 20);            // dashboard | api | public | system
            $table->string('change_ip', 45)->nullable();
            $table->dateTime('changed_at')->useCurrent();

            $table->index('id');
            $table->index(['history_company_id', 'changed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_history');
    }
};
