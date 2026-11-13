<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A device that can receive push: a browser that allowed notifications (web), or an Android / iOS app (a Firebase token).
        Schema::create('push_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('person_id')->nullable()->constrained()->nullOnDelete(); // who it belongs to; a device nobody claimed cannot be targeted by a campaign
            $table->enum('platform', ['WEB', 'ANDROID', 'IOS']);                // web | android | ios
            $table->string('token_hash', 40);              // sha1 of the token / endpoint: one row per device
            $table->string('device_uid', 64)->nullable();  // made by the browser (or the app install id): keeps ONE row across turning notifications off and on
            $table->text('token');                         // FCM registration token (mobile), or the browser's push endpoint (web)
            $table->text('keys')->nullable();              // web only: the browser's public key and auth secret (JSON)
            $table->string('label', 120)->nullable();      // "Chrome on Windows"
            $table->string('page_url', 1000)->nullable();  // the page where a browser turned notifications on
            $table->enum('status', ['ACTIVE', 'REVOKED'])->default('ACTIVE'); // active | revoked (unsubscribed, or the service said the device is gone)
            $table->dateTime('last_seen_at')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            $table->unique(['company_id', 'token_hash']);
            $table->unique(['company_id', 'device_uid']);
            $table->index(['company_id', 'person_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_devices');
    }
};
