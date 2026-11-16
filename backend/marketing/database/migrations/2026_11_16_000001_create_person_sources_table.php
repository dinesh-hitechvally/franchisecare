<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Where a person comes from, and the id they have there. One row per place: a person can be on several platforms at once
        // (a Shopify customer who is also a Mailchimp subscriber and filled in a form has three rows).
        Schema::create('person_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('person_id')->constrained()->cascadeOnDelete();
            $table->string('platform', 30);                          // manual | import | form | api | shopify | mailchimp | messenger | x …
            $table->unsignedBigInteger('integration_id')->default(0); // the connection (0 = not through a connection)
            $table->string('external_id', 191)->default('');         // the person's id on that platform (a form's id for forms)
            $table->string('label')->nullable();                     // the connection's, form's or file's name
            $table->dateTime('first_seen_at')->nullable();
            $table->dateTime('last_synced_at')->nullable();
            $table->dateTime('created_at')->nullable();
            $table->dateTime('updated_at')->nullable();

            $table->unique(['person_id', 'platform', 'integration_id', 'external_id'], 'person_sources_unique');
            $table->index(['company_id', 'platform', 'external_id']);
        });

        // people that already exist: record the source they were created with, and any Messenger / X ids typed on them
        $now = now()->format('Y-m-d H:i:s');
        DB::table('people')->orderBy('id')->chunkById(500, function ($rows) use ($now) {
            $insert = [];
            foreach ($rows as $p) {
                $at = $p->created_at ?: $now;
                $insert[] = ['company_id' => $p->company_id, 'person_id' => $p->id, 'platform' => $p->source ?: 'manual', 'integration_id' => 0, 'external_id' => '', 'label' => null, 'first_seen_at' => $at, 'last_synced_at' => $at, 'created_at' => $now, 'updated_at' => $now];
                foreach ((array) json_decode((string) $p->handles, true) as $platform => $id) {
                    if (in_array($platform, ['messenger', 'x'], true) && is_string($id) && $id !== '') {
                        $insert[] = ['company_id' => $p->company_id, 'person_id' => $p->id, 'platform' => $platform, 'integration_id' => 0, 'external_id' => mb_substr($id, 0, 191), 'label' => null, 'first_seen_at' => $at, 'last_synced_at' => $at, 'created_at' => $now, 'updated_at' => $now];
                    }
                }
            }
            DB::table('person_sources')->insertOrIgnore($insert);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('person_sources');
    }
};
