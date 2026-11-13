<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A connection from a company to an online platform (Shopify, WooCommerce, WordPress, Magento) whose customers become People.
        Schema::create('integrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('provider', 30);                  // shopify | woocommerce | wordpress | magento
            $table->string('name');                          // the store or site name, as the platform reports it
            $table->string('store_url', 500);                // the address of the store / site (not a secret)
            $table->text('secrets');                         // encrypted JSON: tokens, keys, passwords
            $table->text('settings')->nullable();            // JSON: list_id, tags, subscribe_mode, sync_every, run_automations, ...
            $table->enum('status', ['CONNECTED', 'ERROR'])->default('CONNECTED'); // connected | error
            $table->text('last_error')->nullable();
            $table->dateTime('last_synced_at')->nullable();  // when the last successful sync STARTED (the next one only asks for changes since)
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();
            $table->dateTime('deleted_at')->nullable();

            $table->index(['company_id', 'provider']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integrations');
    }
};
