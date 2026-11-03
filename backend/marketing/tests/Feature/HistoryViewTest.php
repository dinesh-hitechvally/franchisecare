<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HistoryViewTest extends TestCase
{
    use RefreshDatabase;

    protected function owner(string $email = 'o@x.com', string $co = 'Acme'): array
    {
        $this->app['auth']->forgetGuards();
        $token = $this->postJson('/api/register', [
            'company_name' => $co, 'name' => 'Owner', 'email' => $email, 'password' => 'password123', 'password_confirmation' => 'password123',
        ])->assertOk()->json('token');

        return ['Authorization' => 'Bearer '.$token, 'Accept' => 'application/json'];
    }

    public function test_an_old_saved_copy_can_be_viewed_with_its_content_but_not_changed(): void
    {
        $h = $this->owner();
        $t = $this->postJson('/api/templates', ['name' => 'Promo', 'type' => 'email', 'subject' => 'First', 'body' => '<h1>Version one</h1>'], $h)->assertCreated()->json('id');
        $this->putJson("/api/templates/$t", ['name' => 'Promo', 'type' => 'email', 'subject' => 'Second', 'body' => '<h1>Version two</h1>'], $h)->assertOk();

        $history = collect($this->getJson("/api/history/template/$t", $h)->assertOk()->json());
        $this->assertCount(2, $history);
        $oldest = $history->last();

        $entry = $this->getJson("/api/history/template/entry/{$oldest['history_id']}", $h)->assertOk();
        $entry->assertJsonPath('exists', true)->assertJsonPath('row_id', $t)->assertJsonPath('data.body', '<h1>Version one</h1>')->assertJsonPath('data.subject', 'First')->assertJsonPath('change_action', 'created');
        $newest = $this->getJson('/api/history/template/entry/'.$history->first()['history_id'], $h)->assertJsonPath('data.body', '<h1>Version two</h1>');

        // there is no way to change a copy
        $this->putJson("/api/history/template/entry/{$oldest['history_id']}", ['data' => ['body' => 'x']], $h)->assertStatus(405);
        $this->deleteJson("/api/history/template/entry/{$oldest['history_id']}", [], $h)->assertStatus(405);
    }

    public function test_pages_and_campaigns_have_content_but_other_records_do_not_and_other_companies_see_nothing(): void
    {
        $h = $this->owner();
        $page = $this->postJson('/api/landing-pages', ['name' => 'Spring', 'html' => '<p>hello page</p>', 'design' => ['kind' => 'page', 'blocks' => [['id' => 'a', 'type' => 'hero']], 'settings' => ['title' => 'Hi']]], $h)->assertCreated()->json('id');
        $copy = collect($this->getJson("/api/history/landing_page/$page", $h)->json())->first();
        $this->getJson("/api/history/landing_page/entry/{$copy['history_id']}", $h)->assertOk()->assertJsonPath('data.html', '<p>hello page</p>')->assertJsonPath('data.name', 'Spring')
            ->assertJsonPath('data.design.kind', 'page')->assertJsonPath('data.design.blocks.0.type', 'hero')->assertJsonPath('data.design.settings.title', 'Hi'); // the design comes back as data, so the editor can open the copy

        $tag = $this->postJson('/api/tags', ['name' => 'VIP'], $h)->assertCreated()->json('id');
        $tagCopy = collect($this->getJson("/api/history/tag/$tag", $h)->json())->first();
        $this->getJson("/api/history/tag/entry/{$tagCopy['history_id']}", $h)->assertNotFound(); // nothing to preview

        $other = $this->owner('b@x.com', 'Other');
        $this->getJson("/api/history/landing_page/entry/{$copy['history_id']}", $other)->assertNotFound();
    }

    public function test_the_timeline_of_all_pages_includes_every_teammate_but_my_activity_stays_personal(): void
    {
        $h = $this->owner();
        $this->postJson('/api/landing-pages', ['name' => 'Spring', 'html' => '<p>a</p>'], $h)->assertCreated();
        $mate = $this->postJson('/api/users', ['name' => 'Mate', 'email' => 'mate@x.com', 'password' => 'password123'], $h)->assertCreated();
        $this->app['auth']->forgetGuards();
        $token = $this->postJson('/api/login', ['email' => 'mate@x.com', 'password' => 'password123'])->assertOk()->json('token');
        $m = ['Authorization' => 'Bearer '.$token, 'Accept' => 'application/json'];
        $this->postJson('/api/landing-pages', ['name' => 'Summer', 'html' => '<p>b</p>'], $m)->assertCreated();

        $names = function (array $headers, string $q) {
            $this->app['auth']->forgetGuards(); // the second login above would otherwise stay the signed-in person
            return collect($this->getJson('/api/history?'.$q, $headers)->assertOk()->json('data'))->pluck('record_label')->sort()->values()->all();
        };
        $this->assertSame(['Spring', 'Summer'], $names($h, 'type=landing_page&all=1')); // the whole team's pages
        $this->assertSame(['Spring'], $names($h, 'type=landing_page'));                  // My activity: only what I did
        $this->assertNotContains('Summer', $names($h, 'all=1'));                         // no kind named: stays personal (the teammate's page is not in it)
        // people are never listed for everyone: the owner created Mate, but Mate does not see that, only his own changes
        $this->assertSame([], $names($m, 'type=user&all=1'));
        $this->assertContains('Mate', $names($h, 'type=user'));                          // the owner's own change is in the owner's personal feed
    }

    public function test_an_old_saved_copy_of_a_form_opens_as_the_builder_expects_it(): void
    {
        $h = $this->owner();
        $form = $this->postJson('/api/forms', ['name' => 'Newsletter', 'type' => 'newsletter', 'subscribe' => true, 'save_contact' => true, 'button_text' => 'Join', 'notify_emails' => ['a@x.com']], $h)->assertCreated()->json();
        $this->putJson('/api/forms/'.$form['id'], ['name' => 'Newsletter v2', 'type' => 'newsletter', 'subscribe' => true, 'save_contact' => true, 'button_text' => 'Subscribe now', 'notify_emails' => []], $h)->assertOk();

        $versions = collect($this->getJson('/api/history/form/'.$form['id'], $h)->assertOk()->json());
        $this->assertCount(2, $versions);
        $old = $this->getJson('/api/history/form/entry/'.$versions->last()['history_id'], $h)->assertOk();
        $old->assertJsonPath('data.name', 'Newsletter')->assertJsonPath('data.button_text', 'Join')->assertJsonPath('data.notify_emails', ['a@x.com']);
        $this->assertSame($form['fields'], $old->json('data.fields')); // fields in the builder's shape
        $this->assertArrayHasKey('accent', $old->json('data.style')); // style resolved, with defaults
        $this->assertSame(true, $old->json('data.subscribe'));         // booleans stay booleans
        $this->getJson('/api/history/form/entry/'.$versions->first()['history_id'], $h)->assertJsonPath('data.button_text', 'Subscribe now')->assertJsonPath('data.notify_emails', []);
    }

    public function test_a_saved_copy_says_whether_the_record_still_exists_so_its_editor_can_open(): void
    {
        $h = $this->owner();
        $page = $this->postJson('/api/landing-pages', ['name' => 'Spring', 'html' => '<p>a</p>'], $h)->assertCreated()->json('id');
        $copy = collect($this->getJson("/api/history/landing_page/$page", $h)->json())->first();
        $this->getJson("/api/history/landing_page/entry/{$copy['history_id']}", $h)->assertJsonPath('exists', true)->assertJsonPath('row_id', $page);

        $this->deleteJson("/api/landing-pages/$page", [], $h)->assertNoContent();
        $this->getJson("/api/history/landing_page/entry/{$copy['history_id']}", $h)->assertOk()->assertJsonPath('exists', false)->assertJsonPath('data.html', '<p>a</p>'); // still viewable, but there is no editor to open
    }

    public function test_a_campaign_copy_says_whether_the_campaign_can_still_be_edited(): void
    {
        $h = $this->owner();
        $id = $this->postJson('/api/campaigns', ['name' => 'Spring', 'type' => 'email', 'subject' => 'Hi', 'body' => '<p>x</p>'], $h)->assertCreated()->json('id');
        $copy = collect($this->getJson("/api/history/campaign/$id", $h)->json())->first();

        $this->getJson("/api/history/campaign/entry/{$copy['history_id']}", $h)->assertOk()->assertJsonPath('exists', true)->assertJsonPath('current_status', 'draft');

        \App\Models\Campaign::withoutGlobalScopes()->whereKey($id)->update(['status' => 'sent']);
        $this->getJson("/api/history/campaign/entry/{$copy['history_id']}", $h)->assertJsonPath('current_status', 'sent'); // gone out: its editor stays closed

        // only a campaign has a status to report
        $t = $this->postJson('/api/templates', ['name' => 'T', 'type' => 'email', 'subject' => 's', 'body' => '<p>x</p>'], $h)->assertCreated()->json('id');
        $tc = collect($this->getJson("/api/history/template/$t", $h)->json())->first();
        $this->getJson("/api/history/template/entry/{$tc['history_id']}", $h)->assertJsonPath('current_status', null);
    }

    public function test_a_change_to_the_encrypted_company_settings_is_listed_but_its_value_is_never_shown(): void
    {
        $h = $this->owner();
        $this->putJson('/api/settings', ['name' => 'Acme', 'profile' => ['phone' => '123']], $h)->assertOk();
        $this->putJson('/api/settings', ['name' => 'Acme', 'profile' => ['phone' => '456']], $h)->assertOk();

        $rows = collect($this->getJson('/api/history?type=company', $h)->assertOk()->json('data'));
        $change = $rows->pluck('changes')->first(fn ($c) => isset($c['settings']));
        $this->assertNotNull($change, 'the settings change is still in the list');
        $this->assertSame('••••••', $change['settings']['new']); // not the encrypted text, and not the phone number
        $this->assertStringNotContainsString('eyJpdiI6', json_encode($rows->all()));
    }
}
