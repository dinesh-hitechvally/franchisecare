<?php

namespace Tests\Feature;

use App\Jobs\SendMessageJob;
use App\Models\Campaign;
use App\Models\Contact;
use App\Models\ContactList;
use App\Models\EmailLog;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SoftDeleteTest extends TestCase
{
    use RefreshDatabase;

    protected function owner(): array
    {
        $this->app['auth']->forgetGuards();
        $token = $this->postJson('/api/register', [
            'company_name' => 'Acme', 'name' => 'Owner', 'email' => 'o@x.com', 'password' => 'password123', 'password_confirmation' => 'password123',
        ])->assertOk()->json('token');

        return ['Authorization' => 'Bearer '.$token];
    }

    public function test_deleting_keeps_the_row_but_hides_it(): void
    {
        $h = $this->owner();
        $id = $this->postJson('/api/contacts', ['email' => 'a@x.com'], $h)->assertCreated()->json('id');
        $this->deleteJson("/api/contacts/$id", [], $h)->assertNoContent();

        $this->assertSame(0, Contact::withoutGlobalScope('company')->count());
        $this->assertNotNull(Contact::withoutGlobalScope('company')->withTrashed()->find($id)->deleted_at);
        $this->assertSame(0, $this->getJson("/api/contacts", $h)->assertOk()->json("total"));
    }

    public function test_a_deleted_email_name_or_field_can_be_used_again(): void
    {
        $h = $this->owner();
        $id = $this->postJson('/api/contacts', ['email' => 'a@x.com'], $h)->json('id');
        $this->deleteJson("/api/contacts/$id", [], $h)->assertNoContent();
        $this->postJson('/api/contacts', ['email' => 'a@x.com'], $h)->assertCreated();
        $this->postJson('/api/contacts', ['email' => 'a@x.com'], $h)->assertStatus(422); // still unique among live contacts

        $tag = $this->postJson('/api/tags', ['name' => 'VIP'], $h)->json('id');
        $this->deleteJson("/api/tags/$tag", [], $h)->assertNoContent();
        $this->postJson('/api/tags', ['name' => 'VIP'], $h)->assertCreated();
        $this->postJson('/api/tags', ['name' => 'VIP'], $h)->assertStatus(422);

        $field = $this->postJson('/api/contact-fields', ['label' => 'Plan', 'type' => 'text'], $h)->json('id');
        $this->deleteJson("/api/contact-fields/$field", [], $h)->assertNoContent();
        $this->assertSame('plan', $this->postJson('/api/contact-fields', ['label' => 'Plan', 'type' => 'text'], $h)->assertCreated()->json('key'));
    }

    public function test_list_members_table_has_a_clear_name_and_trashed_contacts_are_not_counted(): void
    {
        $h = $this->owner();
        $this->assertTrue(Schema::hasTable('contact_list_members'));
        $this->assertFalse(Schema::hasTable('contact_contact_list'));

        $list = $this->postJson('/api/lists', ['name' => 'News'], $h)->json('id');
        $a = $this->postJson('/api/contacts', ['email' => 'a@x.com', 'list_ids' => [$list]], $h)->json('id');
        $this->postJson('/api/contacts', ['email' => 'b@x.com', 'list_ids' => [$list]], $h)->assertCreated();
        $this->assertSame(2, ContactList::withCount('contacts')->withoutGlobalScope('company')->find($list)->contacts_count);

        $this->deleteJson("/api/contacts/$a", [], $h)->assertNoContent();
        $this->assertSame(1, ContactList::withCount('contacts')->withoutGlobalScope('company')->find($list)->contacts_count);
    }

    public function test_a_queued_message_is_not_sent_when_its_campaign_was_deleted(): void
    {
        $h = $this->owner();
        $cid = $this->postJson('/api/contacts', ['email' => 'a@x.com'], $h)->json('id');
        $camp = Campaign::withoutGlobalScopes()->create(['company_id' => Contact::withoutGlobalScopes()->find($cid)->company_id, 'name' => 'C', 'type' => 'email', 'subject' => 'S', 'body' => 'B']);
        $log = EmailLog::withoutGlobalScopes()->create(['company_id' => $camp->company_id, 'campaign_id' => $camp->id, 'contact_id' => $cid, 'recipient' => 'a@x.com', 'status' => 'queued', 'track_token' => str_repeat('t', 40)]);

        $this->deleteJson("/api/campaigns/{$camp->id}", [], $h)->assertNoContent();
        (new SendMessageJob($log->id))->handle(app(\App\Services\MessageRenderer::class), app(\App\Services\Sms\SmsManager::class));

        $log->refresh();
        $this->assertSame('skipped', $log->status);
        $this->assertStringContainsString('deleted', $log->error);
    }
}
