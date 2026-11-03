<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\FormSubmission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormExistingContactTest extends TestCase
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

    protected function form(array $over = []): string
    {
        $slug = $this->postJson('/api/forms', $over + ['name' => 'News', 'type' => 'newsletter', 'subscribe' => true, 'save_contact' => true, 'success_message' => 'Thanks for joining!'], $this->h)->assertCreated()->json('slug');
        $this->app['auth']->forgetGuards();

        return $slug;
    }

    protected function submit(string $slug, string $email, array $extra = [])
    {
        return $this->postJson("/api/public/forms/$slug", $extra + ['email' => $email, 'first_name' => 'Ann'])->assertOk()->json();
    }

    public function test_without_a_message_everybody_gets_the_same_thank_you_so_nothing_is_revealed(): void
    {
        $slug = $this->form();

        $first = $this->submit($slug, 'ann@x.com');
        $again = $this->submit($slug, 'ann@x.com'); // already a contact now
        $this->assertSame('Thanks for joining!', $first['message']);
        $this->assertSame('Thanks for joining!', $again['message']);
        $this->assertFalse($first['existing']);
        $this->assertFalse($again['existing']); // the visitor is never told

        $this->assertSame(1, Contact::withoutGlobalScopes()->count()); // and no duplicate
        // the owner can always see it: first submission created the contact, the second found it
        $this->assertSame([false, true], FormSubmission::withoutGlobalScopes()->orderBy('id')->pluck('contact_existed')->map(fn ($v) => (bool) $v)->all());
    }

    public function test_with_a_message_a_visitor_who_is_already_a_contact_sees_it_instead(): void
    {
        $slug = $this->form(['existing_message' => 'You are already on our list. We updated your details.']);

        $first = $this->submit($slug, 'ann@x.com');
        $this->assertSame(['Thanks for joining!', false], [$first['message'], $first['existing']]);

        $again = $this->submit($slug, 'ANN@x.com'); // the same address in another letter case
        $this->assertSame(['You are already on our list. We updated your details.', true], [$again['message'], $again['existing']]);

        $other = $this->submit($slug, 'bob@x.com'); // a new person still gets the normal thank-you
        $this->assertSame(['Thanks for joining!', false], [$other['message'], $other['existing']]);
        $this->assertSame(2, Contact::withoutGlobalScopes()->count());
    }

    public function test_a_form_that_does_not_save_contacts_never_says_they_existed(): void
    {
        $slug = $this->form(['save_contact' => false, 'existing_message' => 'Welcome back.']);
        Contact::withoutGlobalScopes()->forceCreate(['company_id' => (int) \App\Models\Company::value('id'), 'email' => 'ann@x.com', 'unsubscribe_token' => str_repeat('a', 40), 'is_subscribed' => true]);

        $answer = $this->submit($slug, 'ann@x.com');
        $this->assertSame(['Thanks for joining!', false], [$answer['message'], $answer['existing']]);
        $this->assertNull(FormSubmission::withoutGlobalScopes()->value('contact_existed')); // no contact was linked, so nothing to say
    }

    public function test_the_submissions_list_says_whether_each_person_was_new_or_existing(): void
    {
        $slug = $this->form();
        $this->submit($slug, 'ann@x.com');
        $this->submit($slug, 'ann@x.com');
        $id = $this->postJson('/api/forms', ['name' => 'Inbox only', 'type' => 'other', 'save_contact' => false], $this->h)->assertCreated()->json('id');

        $rows = collect($this->getJson('/api/submissions', $this->h)->assertOk()->json('data'));
        $this->assertSame([true, false], $rows->pluck('contact_existed')->all()); // newest first: existing, then new
        $this->assertSame('Ann', $rows->first()['data'][0]['value']);
        $this->assertSame(0, collect($this->getJson("/api/forms/$id/submissions", $this->h)->json('data'))->count());
    }

    public function test_the_message_is_optional_short_and_an_empty_one_is_stored_as_none(): void
    {
        $this->postJson('/api/forms', ['name' => 'A', 'existing_message' => str_repeat('x', 501)], $this->h)->assertStatus(422);

        $id = $this->postJson('/api/forms', ['name' => 'A', 'type' => 'newsletter', 'existing_message' => '   '], $this->h)->assertCreated()->assertJsonPath('existing_message', null)->json('id');
        $this->putJson("/api/forms/$id", ['name' => 'A', 'type' => 'newsletter', 'existing_message' => 'Welcome back!'], $this->h)->assertOk()->assertJsonPath('existing_message', 'Welcome back!');

        // the saved copy in history knows it too
        $history = collect($this->getJson("/api/history/form/$id", $this->h)->json());
        $this->getJson('/api/history/form/entry/'.$history->first()['history_id'], $this->h)->assertJsonPath('data.existing_message', 'Welcome back!');
    }

    public function test_the_hosted_page_words_the_panel_for_someone_already_known(): void
    {
        $slug = $this->form();
        $this->get("/f/$slug")->assertOk()->assertSee('We already have you in our records.', false);
    }
}
