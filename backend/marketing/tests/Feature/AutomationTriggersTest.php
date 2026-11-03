<?php

namespace Tests\Feature;

use App\Models\Person;
use App\Models\EmailLog;
use App\Models\WorkflowRun;
use App\Services\MessageRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class AutomationTriggersTest extends TestCase
{
    use RefreshDatabase;

    protected array $h;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app['auth']->forgetGuards();
        $token = $this->postJson('/api/register', [
            'company_name' => 'Acme', 'name' => 'Owner', 'email' => 'o@x.com', 'password' => 'password123', 'password_confirmation' => 'password123',
        ])->assertOk()->json('token');
        $this->h = ['Authorization' => 'Bearer '.$token, 'Accept' => 'application/json'];
    }

    protected function tag(string $name): int
    {
        return $this->postJson('/api/tags', ['name' => $name], $this->h)->assertCreated()->json('id');
    }

    protected function person(array $extra = []): Person
    {
        $id = $this->postJson('/api/people', $extra + ['email' => 'c'.uniqid().'@x.com'], $this->h)->assertCreated()->json('id');

        return Person::withoutGlobalScopes()->findOrFail($id);
    }

    /** An active workflow that starts on $trigger and tags the person with $tagId when it runs. */
    protected function workflow(string $trigger, array $config, int $tagId, array $steps = null): int
    {
        $id = $this->postJson('/api/workflows', [
            'name' => 'W '.$trigger, 'trigger_type' => $trigger, 'trigger_config' => $config, 'steps' => $steps ?? [['type' => 'add_tag', 'tag_id' => $tagId]],
        ], $this->h)->assertCreated()->json('id');
        $this->postJson("/api/workflows/$id/toggle", [], $this->h)->assertOk();

        return $id;
    }

    protected function tagged(Person $c, int $tagId): bool
    {
        return $c->tags()->where('tags.id', $tagId)->exists();
    }

    protected function sentEmail(Person $c, string $token, array $over = []): EmailLog
    {
        return EmailLog::withoutGlobalScopes()->create($over + [
            'company_id' => $c->company_id, 'person_id' => $c->id, 'recipient' => $c->email, 'body' => 'b', 'status' => 'sent', 'source' => 'campaign',
            'service' => 'default', 'sent_at' => now(), 'track_token' => $token,
        ]);
    }

    public function test_the_trigger_list_has_every_trigger_with_its_group(): void
    {
        $list = collect($this->getJson('/api/automation/triggers', $this->h)->assertOk()->json());
        $this->assertSame(['person_created', 'list_joined', 'list_left', 'tag_added', 'tag_removed', 'form_submitted', 'unsubscribed', 'link_clicked', 'email_opened', 'date_reached', 'inactive'], $list->pluck('type')->all());
        $this->assertSame(['People', 'Email activity', 'Time'], $list->pluck('group')->unique()->values()->all());
        $this->assertSame('url', $list->firstWhere('type', 'link_clicked')['config']);
    }

    public function test_clicking_a_link_starts_the_workflow_only_for_matching_links(): void
    {
        $clicked = $this->tag('Clicked');
        $c = $this->person();
        $this->sentEmail($c, 'tok-click');
        $this->workflow('link_clicked', ['url' => 'Pricing'], $clicked);

        $click = fn (string $u) => $this->get('/api/t/c/tok-click?'.http_build_query(['u' => $u, 's' => MessageRenderer::sign($u)]))->assertRedirect($u);
        $click('https://x.com/blog');
        $this->assertFalse($this->tagged($c, $clicked)); // not a link it listens to

        $click('https://x.com/pricing?plan=pro'); // matches, whatever the letter case
        $this->assertTrue($this->tagged($c, $clicked));
    }

    public function test_opening_an_email_starts_the_workflow_once_per_message(): void
    {
        $opened = $this->tag('Opened');
        $c = $this->person();
        $this->sentEmail($c, 'tok-open');
        $this->workflow('email_opened', [], $opened);

        $this->get('/api/t/o/tok-open.gif')->assertOk();
        $this->get('/api/t/o/tok-open.gif')->assertOk(); // the same message opened again
        $this->assertTrue($this->tagged($c, $opened));
        $this->assertSame(1, WorkflowRun::withoutGlobalScopes()->count());
    }

    public function test_a_first_open_through_a_click_counts_as_an_open_too(): void
    {
        $opened = $this->tag('Opened');
        $c = $this->person();
        $this->sentEmail($c, 'tok-c2');
        $this->workflow('email_opened', [], $opened);

        $u = 'https://x.com/a';
        $this->get('/api/t/c/tok-c2?'.http_build_query(['u' => $u, 's' => MessageRenderer::sign($u)]))->assertRedirect($u); // images were blocked, but they clicked
        $this->assertTrue($this->tagged($c, $opened));
    }

    public function test_unsubscribing_starts_the_workflow(): void
    {
        $left = $this->tag('Left');
        $c = $this->person();
        $this->workflow('unsubscribed', [], $left);

        $this->get('/api/unsubscribe/'.$c->unsubscribe_token)->assertOk();
        $this->assertTrue($this->tagged($c->fresh(), $left));
        $this->assertFalse((bool) $c->fresh()->is_subscribed);
    }

    public function test_removing_a_tag_or_leaving_a_list_starts_their_workflows(): void
    {
        $vip = $this->tag('VIP');
        $mark = $this->tag('WasVIP');
        $listId = $this->postJson('/api/lists', ['name' => 'Club'], $this->h)->assertCreated()->json('id');
        $c = $this->person(['tag_ids' => [$vip], 'list_ids' => [$listId]]);
        $this->workflow('tag_removed', ['tag_id' => $vip], $mark);
        $gone = $this->tag('LeftClub');
        $this->workflow('list_left', ['list_id' => $listId], $gone);

        $this->putJson('/api/people/'.$c->id, ['email' => $c->email, 'list_ids' => []], $this->h)->assertOk(); // taken off the list in the edit form
        $this->assertTrue($this->tagged($c, $gone));
        $this->assertFalse($this->tagged($c, $mark)); // the tag is still on them

        $this->postJson('/api/people/bulk', ['ids' => [$c->id], 'action' => 'remove_tag', 'tag_id' => $vip], $this->h)->assertOk(); // the bulk action
        $this->assertTrue($this->tagged($c, $mark));
    }

    public function test_a_workflow_that_removes_the_tag_it_listens_for_does_not_loop(): void
    {
        $vip = $this->tag('VIP');
        $c = $this->person(['tag_ids' => [$vip]]);
        $this->workflow('tag_removed', ['tag_id' => $vip], $vip, [['type' => 'remove_tag', 'tag_id' => $vip]]);

        $this->postJson('/api/people/bulk', ['ids' => [$c->id], 'action' => 'remove_tag', 'tag_id' => $vip], $this->h)->assertOk();
        $this->assertSame(1, WorkflowRun::withoutGlobalScopes()->count()); // its own remove_tag step found nothing to remove, so it did not fire again
    }

    public function test_a_date_trigger_starts_on_the_day_or_some_days_before_and_never_twice_a_day(): void
    {
        $field = $this->postJson('/api/person-fields', ['label' => 'Birthday', 'type' => 'date'], $this->h)->assertCreated()->json();
        $today = now()->startOfDay();
        $birthday = $this->person(['custom' => [$field['key'] => '1990-'.$today->format('m-d')]]);       // their birthday is today
        $soon = $this->person(['custom' => [$field['key'] => '1985-'.$today->copy()->addDays(3)->format('m-d')]]); // in three days
        $other = $this->person(['custom' => [$field['key'] => '1990-'.$today->copy()->addDays(40)->format('m-d')]]);

        $onTheDay = $this->tag('Happy birthday');
        $this->workflow('date_reached', ['field_id' => $field['id'], 'when' => 'on', 'days' => 0, 'yearly' => true], $onTheDay);
        $reminder = $this->tag('Birthday soon');
        $this->workflow('date_reached', ['field_id' => $field['id'], 'when' => 'before', 'days' => 3, 'yearly' => true], $reminder);

        Artisan::call('automation:time-triggers');
        $this->assertTrue($this->tagged($birthday, $onTheDay));
        $this->assertFalse($this->tagged($soon, $onTheDay));
        $this->assertTrue($this->tagged($soon, $reminder));
        $this->assertFalse($this->tagged($other, $onTheDay) || $this->tagged($other, $reminder));

        $runs = WorkflowRun::withoutGlobalScopes()->count();
        Artisan::call('automation:time-triggers'); // run again the same day
        $this->assertSame($runs, WorkflowRun::withoutGlobalScopes()->count());
    }

    public function test_a_date_that_is_not_repeated_yearly_only_matches_its_exact_day(): void
    {
        $field = $this->postJson('/api/person-fields', ['label' => 'Renewal', 'type' => 'date'], $this->h)->assertCreated()->json();
        $exact = $this->person(['custom' => [$field['key'] => now()->toDateString()]]);
        $lastYear = $this->person(['custom' => [$field['key'] => now()->subYear()->toDateString()]]);
        $tag = $this->tag('Renew now');
        $this->workflow('date_reached', ['field_id' => $field['id'], 'when' => 'on', 'days' => 0, 'yearly' => false], $tag);

        Artisan::call('automation:time-triggers');
        $this->assertTrue($this->tagged($exact, $tag));
        $this->assertFalse($this->tagged($lastYear, $tag));
    }

    public function test_the_not_opened_trigger_finds_the_right_people_and_waits_before_repeating(): void
    {
        $tag = $this->tag('Dormant');
        $silent = $this->person();                       // got an email 40 days ago, never opened anything
        $this->sentEmail($silent, 't1', ['sent_at' => now()->subDays(40)]);
        $engaged = $this->person();                      // got one 40 days ago but opened a recent one
        $this->sentEmail($engaged, 't2', ['sent_at' => now()->subDays(40)]);
        $this->sentEmail($engaged, 't3', ['sent_at' => now()->subDays(6), 'opened_at' => now()->subDays(5)]);
        $new = $this->person();                          // only got an email 5 days ago: has had no time to open
        $this->sentEmail($new, 't4', ['sent_at' => now()->subDays(5)]);
        $unsub = $this->person(['is_subscribed' => false]); // would qualify, but cannot be emailed any more
        $this->sentEmail($unsub, 't5', ['sent_at' => now()->subDays(40)]);
        $this->workflow('inactive', ['days' => 30], $tag);

        Artisan::call('automation:time-triggers');
        $this->assertTrue($this->tagged($silent, $tag));
        $this->assertFalse($this->tagged($engaged, $tag) || $this->tagged($new, $tag) || $this->tagged($unsub, $tag));

        $runs = WorkflowRun::withoutGlobalScopes()->count();
        Artisan::call('automation:time-triggers'); // the next day's run: not started again within the same 30 days
        $this->assertSame($runs, WorkflowRun::withoutGlobalScopes()->count());
    }

    public function test_the_new_triggers_ask_for_the_settings_they_need(): void
    {
        $post = fn (array $w) => $this->postJson('/api/workflows', $w + ['name' => 'W', 'steps' => []], $this->h);

        $post(['trigger_type' => 'date_reached', 'trigger_config' => []])->assertStatus(422)->assertJsonFragment(['message' => 'Choose the date field this workflow follows (a custom field of the type Date).']);
        $text = $this->postJson('/api/person-fields', ['label' => 'Nickname', 'type' => 'text'], $this->h)->json('id');
        $post(['trigger_type' => 'date_reached', 'trigger_config' => ['field_id' => $text]])->assertStatus(422); // not a date field
        $date = $this->postJson('/api/person-fields', ['label' => 'Joined', 'type' => 'date'], $this->h)->json('id');
        $post(['trigger_type' => 'date_reached', 'trigger_config' => ['field_id' => $date, 'when' => 'before', 'days' => 0]])->assertStatus(422); // "before" needs a number of days
        $post(['trigger_type' => 'date_reached', 'trigger_config' => ['field_id' => $date, 'when' => 'before', 'days' => 2, 'yearly' => true]])->assertCreated();

        $post(['trigger_type' => 'inactive', 'trigger_config' => []])->assertStatus(422);
        $post(['trigger_type' => 'inactive', 'trigger_config' => ['days' => 30]])->assertCreated();
    }
}
