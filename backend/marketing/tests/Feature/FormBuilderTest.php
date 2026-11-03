<?php

namespace Tests\Feature;

use App\Models\Person;
use App\Models\EmailLog;
use App\Services\FormFields;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormBuilderTest extends TestCase
{
    use RefreshDatabase;

    protected function owner(string $email = 'a@x.com', string $co = 'Company A'): array
    {
        $this->app['auth']->forgetGuards();
        $token = $this->postJson('/api/register', [
            'company_name' => $co, 'name' => 'Owner', 'email' => $email, 'password' => 'password123', 'password_confirmation' => 'password123',
        ])->assertOk()->json('token');

        return ['Authorization' => 'Bearer '.$token];
    }

    protected function f(string $type, string $label, array $extra = []): array
    {
        return ['id' => 'x'.substr(md5($label), 0, 5), 'type' => $type, 'label' => $label] + $extra;
    }

    public function test_builder_saves_every_field_type_and_serves_them(): void
    {
        $h = $this->owner();
        $res = $this->postJson('/api/forms', [
            'name' => 'Full', 'title' => 'Hello', 'redirect_url' => 'https://example.com/thanks',
            'style' => ['accent' => '#ff0000', 'radius' => 12, 'font' => 'serif', 'width' => 520],
            'fields' => [
                $this->f('heading', 'About you'),
                $this->f('first_name', 'First name'),
                $this->f('email', 'Email', ['required' => true]),
                $this->f('phone', 'Mobile'),
                $this->f('text', 'Company name', ['placeholder' => 'Acme']),
                $this->f('textarea', 'Anything else?'),
                $this->f('number', 'Staff'),
                $this->f('date', 'Start date'),
                $this->f('dropdown', 'Plan', ['options' => ['Basic', 'Pro', ' Pro ', '']]),
                $this->f('radio', 'Contact me by', ['options' => ['Email', 'SMS']]),
                $this->f('checkbox', 'I agree', ['required' => true]),
                $this->f('paragraph', 'We never share your details.'),
            ],
        ], $h)->assertCreated();

        $fields = collect($res->json('fields'));
        $this->assertSame('person_company_name', $fields->firstWhere('type', 'text')['key']); // key derived from the label, kept clear of the built-in {{company_name}}
        $this->assertSame('plan', $fields->firstWhere('type', 'dropdown')['key']);
        $this->assertSame(['Basic', 'Pro'], $fields->firstWhere('type', 'dropdown')['options']); // trimmed, de-duplicated, blanks dropped
        $this->assertNull($fields->firstWhere('type', 'heading')['key']);
        $res->assertJsonPath('style.accent', '#ff0000')->assertJsonPath('style.font', 'serif')->assertJsonPath('redirect_url', 'https://example.com/thanks');

        $html = $this->get('/f/'.$res->json('slug'))->assertOk()->getContent();
        foreach (['<h3 class="fc-h">About you', 'name="person_company_name"', '<textarea name="anything_else"', '<select name="plan"', 'type="radio" name="contact_me_by"', 'type="checkbox" name="i_agree"', 'We never share', 'type="date"', 'type="number"'] as $needle) {
            $this->assertStringContainsString($needle, $html, "missing: $needle");
        }
        $this->assertStringContainsString('#ff0000', $html); // accent colour reached the page
        $this->assertStringContainsString('Georgia', $html); // font choice too
    }

    public function test_submission_validates_by_type_and_stores_custom_answers(): void
    {
        $h = $this->owner();
        $slug = $this->postJson('/api/forms', ['name' => 'F', 'success_message' => 'Done!', 'redirect_url' => 'https://example.com/ty', 'fields' => [
            $this->f('email', 'Email', ['required' => true]),
            $this->f('text', 'Company', ['required' => true]),
            $this->f('number', 'Staff'),
            $this->f('date', 'Start'),
            $this->f('dropdown', 'Plan', ['options' => ['Basic', 'Pro'], 'required' => true]),
            $this->f('checkbox', 'Newsletter'),
            $this->f('checkbox', 'Terms', ['required' => true]),
        ]], $h)->assertCreated()->json('slug');

        $ok = ['email' => 'Lee@Acme.com', 'company' => 'Acme', 'staff' => '12', 'start' => '2026-12-01', 'plan' => 'Pro', 'newsletter' => true, 'terms' => true];

        // each rule bites
        $this->postJson("/api/public/forms/$slug", array_merge($ok, ['company' => '']))->assertStatus(422)->assertJsonValidationErrors('company');
        $this->postJson("/api/public/forms/$slug", array_merge($ok, ['staff' => 'many']))->assertStatus(422)->assertJsonValidationErrors('staff');
        $this->postJson("/api/public/forms/$slug", array_merge($ok, ['start' => 'soon']))->assertStatus(422)->assertJsonValidationErrors('start');
        $this->postJson("/api/public/forms/$slug", array_merge($ok, ['plan' => 'Enterprise']))->assertStatus(422)->assertJsonValidationErrors('plan'); // not one of the options
        $this->postJson("/api/public/forms/$slug", array_merge($ok, ['terms' => false]))->assertStatus(422)->assertJsonValidationErrors('terms'); // required consent
        $this->assertSame(0, Person::withoutGlobalScopes()->count());

        $this->postJson("/api/public/forms/$slug", $ok)->assertOk()->assertJsonPath('message', 'Done!')->assertJsonPath('redirect', 'https://example.com/ty');

        $c = Person::withoutGlobalScopes()->firstOrFail();
        $this->assertSame('lee@acme.com', $c->email);
        // custom answers now live in managed person fields (not a hidden blob on the person)
        $this->assertEquals(['company' => 'Acme', 'staff' => '12', 'start' => '2026-12-01', 'plan' => 'Pro', 'newsletter' => 'Yes', 'terms' => 'Yes'], $c->customMap());

        // answers are usable as merge tags
        $vars = (new \App\Services\MessageRenderer)->vars($c->company, $c, 'email');
        $this->assertSame('Acme', $vars['company']);
        $this->assertSame('Pro', $vars['plan']);

        // a second submission fills blanks only: it can't overwrite what is already known about that person
        $this->postJson("/api/public/forms/$slug", array_merge($ok, ['company' => 'HACKED', 'staff' => '99']))->assertOk();
        $this->assertSame('Acme', Person::withoutGlobalScopes()->first()->customMap()['company']);
        $this->assertSame(1, Person::withoutGlobalScopes()->count());
    }

    public function test_builder_rules_are_enforced(): void
    {
        $h = $this->owner();
        $base = ['name' => 'F'];
        $post = fn (array $fields, array $extra = []) => $this->postJson('/api/forms', $base + ['fields' => $fields] + $extra, $h);

        $post([$this->f('text', 'Just text')])->assertStatus(422); // needs email or phone
        $post([$this->f('email', 'Email'), $this->f('email', 'Email again')])->assertStatus(422); // standard field only once
        $post([$this->f('email', 'Email'), $this->f('dropdown', 'Plan', ['options' => []])])->assertStatus(422); // dropdown needs options
        $post([$this->f('email', 'Email'), $this->f('radio', 'Pick', ['options' => ['  ', '']])])->assertStatus(422);
        $post([$this->f('email', 'Email'), $this->f('bogus', 'x')])->assertStatus(422); // unknown type
        $post([$this->f('email', 'Email')], ['redirect_url' => 'javascript:alert(1)'])->assertStatus(422); // only http(s)
        $post([$this->f('email', 'Email')], ['style' => ['accent' => 'red;}</style><script>']])->assertStatus(422); // style can't carry CSS/HTML
        $post(array_fill(0, 31, $this->f('text', 'Q')))->assertStatus(422); // max 30 fields

        // custom keys can't shadow person columns or each other
        $res = $post([$this->f('email', 'Email'), $this->f('text', 'Email', ['key' => 'email']), $this->f('text', 'Same'), $this->f('text', 'Same')])->assertCreated();
        $keys = array_column($res->json('fields'), 'key');
        $this->assertSame(count($keys), count(array_unique($keys)));
        $this->assertContains('email', $keys);
        $this->assertNotSame('email', $res->json('fields.1.key'));
        $this->assertSame(['same', 'same_2'], array_slice($keys, 2));
    }

    public function test_labels_and_options_are_escaped_on_the_public_page(): void
    {
        $h = $this->owner();
        $res = $this->postJson('/api/forms', ['name' => 'XSS', 'title' => '<img src=x onerror=alert(1)>', 'fields' => [
            $this->f('email', '<script>alert(1)</script>'),
            $this->f('dropdown', 'Pick', ['options' => ['"><svg onload=alert(1)>']]),
        ]], $h)->assertCreated();

        $html = $this->get('/f/'.$res->json('slug'))->getContent();
        $this->assertStringNotContainsString('<script>alert(1)', $html);
        $this->assertStringNotContainsString('<img src=x', $html);
        $this->assertStringNotContainsString('<svg onload', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function test_forms_made_before_the_builder_still_work(): void
    {
        $h = $this->owner();
        $id = $this->postJson('/api/forms', ['name' => 'Old'], $h)->json('id');
        // simulate a legacy row: only {key,label,enabled,required}
        \App\Models\Form::withoutGlobalScopes()->whereKey($id)->update(['fields' => json_encode([
            ['key' => 'first_name', 'label' => 'First name', 'enabled' => true, 'required' => false],
            ['key' => 'last_name', 'label' => 'Last name', 'enabled' => false, 'required' => false],
            ['key' => 'email', 'label' => 'Email', 'enabled' => true, 'required' => true],
        ])]);

        $form = $this->getJson("/api/forms/$id", $h)->assertOk();
        $this->assertSame(['first_name', 'email'], array_column($form->json('fields'), 'type')); // disabled field dropped, shape upgraded
        $this->assertSame('first_name', $form->json('fields.0.key'));

        $slug = $form->json('slug');
        $this->get("/f/$slug")->assertOk()->assertSee('name="email"', false);
        $this->postJson("/api/public/forms/$slug", ['email' => 'old@x.com', 'first_name' => 'Old'])->assertOk();
        $this->assertSame('Old', Person::withoutGlobalScopes()->where('email', 'old@x.com')->value('first_name'));
        $this->assertCount(2, FormFields::normalize(null)); // empty/missing -> defaults
    }

    public function test_forms_are_private_to_their_company(): void
    {
        $a = $this->owner('a@x.com', 'A');
        $id = $this->postJson('/api/forms', ['name' => 'Mine'], $a)->json('id');
        $b = $this->owner('b@y.com', 'B');
        $this->getJson("/api/forms/$id", $b)->assertNotFound();
        $this->putJson("/api/forms/$id", ['name' => 'x'], $b)->assertNotFound();
        $this->getJson('/api/forms', $b)->assertJsonCount(0);
    }

    public function test_fields_can_share_a_row_using_column_widths(): void
    {
        $h = $this->owner();
        $res = $this->postJson('/api/forms', ['name' => 'Columns', 'fields' => [
            $this->f('heading', 'Your name', ['width' => 'half']),          // headings/paragraphs always take the whole row
            $this->f('first_name', 'First', ['width' => 'half']),
            $this->f('last_name', 'Last', ['width' => 'half']),
            $this->f('email', 'Email', ['width' => 'two_thirds']),
            $this->f('phone', 'Mobile', ['width' => 'third']),
            $this->f('text', 'Town'),                                      // no width given -> full
            $this->f('text', 'Suburb', ['width' => 'quarter']),
        ]], $h)->assertCreated();

        $this->assertSame(['full', 'half', 'half', 'two_thirds', 'third', 'full', 'quarter'], array_column($res->json('fields'), 'width'));
        $this->postJson('/api/forms', ['name' => 'Bad', 'fields' => [$this->f('email', 'Email', ['width' => 'huge'])]], $h)->assertStatus(422);

        $html = $this->get('/f/'.$res->json('slug'))->assertOk()->getContent();
        $this->assertStringContainsString('<div class="fc-row">', $html);
        foreach (['fc-w-half', 'fc-w-two_thirds', 'fc-w-third', 'fc-w-quarter', 'fc-w-full'] as $cls) {
            $this->assertStringContainsString('class="fc-f '.$cls.'"', $html, "missing cell $cls");
        }
        // first and last name are in consecutive half-width cells, so they sit on one row
        $this->assertMatchesRegularExpression('/fc-w-half">.*?name="first_name".*?fc-w-half">.*?name="last_name"/s', $html);
        $this->assertStringContainsString('.fc-w-half{width:calc(50% - 7px)}', $html);   // 50% minus half the 14px gap
        $this->assertStringContainsString('@media(max-width:480px)', $html);              // stacks on phones

        // forms saved before columns existed render exactly as before: one field per row
        $this->assertSame('full', FormFields::normalize([['key' => 'email', 'label' => 'Email', 'enabled' => true, 'required' => true]])[0]['width']);
        $this->assertSame('full', FormFields::field(['type' => 'text', 'label' => 'x'])['width']);
    }

    /** Every email the app tried to send in this test (the test mailer keeps them in memory). */
    protected function sentMails(): array
    {
        return array_map(fn ($m) => $m->getOriginalMessage(), app('mailer')->getSymfonyTransport()->messages()->all());
    }

    public function test_contact_form_is_an_inbox_with_notification_and_autoreply_and_no_marketing_consent(): void
    {
        $h = $this->owner();
        $form = $this->postJson('/api/forms', [
            'name' => 'Contact us', 'type' => 'contact', 'subscribe' => false, 'save_person' => true,
            'notify_emails' => ['Team@Biz.com', 'boss@biz.com', 'team@biz.com'],           // case-insensitive duplicates collapse
            'autoreply' => true, 'autoreply_subject' => 'Got it, {{first_name}}', 'autoreply_body' => "Hi {{first_name}},\n<script>x</script>\nWe'll reply soon.",
            'fields' => [
                $this->f('first_name', 'First name', ['required' => true]),
                $this->f('email', 'Email', ['required' => true]),
                $this->f('textarea', 'Message', ['required' => true]),
            ],
        ], $h)->assertCreated();
        $form->assertJsonPath('type', 'contact')->assertJsonPath('subscribe', false)->assertJsonPath('notify_emails', ['team@biz.com', 'boss@biz.com']);

        $this->postJson('/api/public/forms/'.$form->json('slug'), ['first_name' => 'Sam', 'email' => 'sam@x.com', 'message' => '<b>Hello</b> & welcome'])->assertOk();

        // 1. a person exists, but was NOT opted in to marketing (they only asked a question)
        $c = Person::withoutGlobalScopes()->where('email', 'sam@x.com')->firstOrFail();
        $this->assertFalse((bool) $c->is_subscribed);
        $this->assertNull($c->subscribed_at);
        $this->assertSame('form', $c->source);

        // 2. the submission is in the form's inbox
        $inbox = $this->getJson('/api/forms/'.$form->json('id').'/submissions', $h)->assertOk();
        $inbox->assertJsonPath('total', 1)->assertJsonPath('data.0.person.email', 'sam@x.com');
        $this->assertSame(['First name' => 'Sam', 'Email' => 'sam@x.com', 'Message' => '<b>Hello</b> & welcome'], collect($inbox->json('data.0.data'))->pluck('value', 'label')->all());

        // 3. each notify address got the answers, Reply-To is the visitor, and visitor text is escaped
        $mails = collect($this->sentMails());
        $notes = $mails->filter(fn ($m) => str_starts_with($m->getSubject(), 'New submission'));
        $this->assertSame(['boss@biz.com', 'team@biz.com'], $notes->map(fn ($m) => $m->getTo()[0]->getAddress())->sort()->values()->all());
        $note = $notes->first();
        $this->assertSame('sam@x.com', $note->getReplyTo()[0]->getAddress());
        $this->assertStringContainsString('&lt;b&gt;Hello&lt;/b&gt; &amp; welcome', $note->getHtmlBody());
        $this->assertStringNotContainsString('<b>Hello</b>', $note->getHtmlBody());

        // 4. the visitor got the auto-reply, merge tags filled in, template text escaped, and it is in Message Logs
        $reply = $mails->first(fn ($m) => $m->getTo()[0]->getAddress() === 'sam@x.com');
        $this->assertSame('Got it, Sam', $reply->getSubject());
        $this->assertStringContainsString('Hi Sam,', $reply->getHtmlBody());
        $this->assertStringNotContainsString('<script>x</script>', $reply->getHtmlBody());
        $log = EmailLog::withoutGlobalScopes()->where('recipient', 'sam@x.com')->firstOrFail();
        $this->assertSame('sent', $log->status);
        $this->assertNull($log->campaign_id);

        // a newsletter-type form opts people in
        $news = $this->postJson('/api/forms', ['name' => 'News', 'type' => 'newsletter'], $h)->assertCreated();
        $news->assertJsonPath('subscribe', true);
        $this->postJson('/api/public/forms/'.$news->json('slug'), ['email' => 'fan@x.com'])->assertOk();
        $this->assertTrue((bool) Person::withoutGlobalScopes()->where('email', 'fan@x.com')->value('is_subscribed'));
    }

    public function test_a_form_can_skip_saving_people_and_just_be_an_inbox(): void
    {
        $h = $this->owner();
        $form = $this->postJson('/api/forms', ['name' => 'Feedback', 'type' => 'other', 'save_person' => false, 'notify_emails' => ['me@biz.com'], 'fields' => [
            $this->f('text', 'Your name'),
            $this->f('textarea', 'Feedback', ['required' => true]),    // no email or phone field needed
        ]], $h)->assertCreated();

        $this->postJson('/api/public/forms/'.$form->json('slug'), ['your_name' => 'Anon', 'feedback' => 'Love it'])->assertOk();

        $this->assertSame(0, Person::withoutGlobalScopes()->count());                     // nobody was added to people
        $this->getJson('/api/forms/'.$form->json('id').'/submissions', $h)->assertJsonPath('total', 1)->assertJsonPath('data.0.person', null);
        $this->assertCount(1, $this->sentMails());                                          // the notification still goes out
        $this->getJson('/api/forms', $h)->assertJsonPath('0.submissions', 1);
    }

    public function test_form_action_settings_are_validated(): void
    {
        $h = $this->owner();
        $post = fn (array $extra, ?array $fields = null) => $this->postJson('/api/forms', ['name' => 'F', 'fields' => $fields ?? [$this->f('email', 'Email')]] + $extra, $h);

        $post(['type' => 'sales'])->assertStatus(422);                                                           // unknown type
        $post(['notify_emails' => ['not-an-email']])->assertStatus(422);
        $post(['notify_emails' => array_map(fn ($i) => "a$i@x.com", range(1, 6))])->assertStatus(422);          // max 5 addresses
        $post(['autoreply' => true])->assertStatus(422);                                                         // needs subject + message
        $post(['autoreply' => true, 'autoreply_subject' => 'Hi', 'autoreply_body' => 'Thanks'], [$this->f('phone', 'Mobile')])->assertStatus(422); // needs an email field
        $post(['save_person' => true], [$this->f('text', 'Just text')])->assertStatus(422);                     // saving people needs email/phone
        $post(['save_person' => false], [$this->f('text', 'Just text')])->assertCreated();                      // ...but a pure inbox does not
        $post(['autoreply' => true, 'autoreply_subject' => 'Hi', 'autoreply_body' => 'Thanks'])->assertCreated()->assertJsonPath('autoreply', true);
    }

    public function test_submissions_inbox_is_private_exportable_and_deletable(): void
    {
        $a = $this->owner('a@x.com', 'A');
        $form = $this->postJson('/api/forms', ['name' => 'Inbox', 'fields' => [$this->f('email', 'Email', ['required' => true]), $this->f('text', 'Note')]], $a)->assertCreated();
        $slug = $form->json('slug');
        $this->postJson("/api/public/forms/$slug", ['email' => 'one@x.com', 'note' => '=HYPERLINK("http://evil","x")'])->assertOk();   // spreadsheet formula
        $this->postJson("/api/public/forms/$slug", ['email' => 'two@x.com', 'note' => 'fine'])->assertOk();

        $csv = $this->get('/api/forms/'.$form->json('id').'/submissions/export', $a)->assertOk()->streamedContent();
        $this->assertStringContainsString('"Submitted at","Person email",Email,Note', $csv);
        $this->assertStringContainsString('one@x.com', $csv);
        $this->assertStringNotContainsString(',=HYPERLINK', $csv);          // formula is neutralised with a leading quote
        $this->assertStringContainsString("'=HYPERLINK", $csv);

        $first = $this->getJson('/api/forms/'.$form->json('id').'/submissions', $a)->json('data.0.id');

        // another company can neither read, export nor delete them
        $b = $this->owner('b@y.com', 'B');
        $this->getJson('/api/forms/'.$form->json('id').'/submissions', $b)->assertNotFound();
        $this->get('/api/forms/'.$form->json('id').'/submissions/export', $b)->assertNotFound();
        $this->deleteJson('/api/forms/'.$form->json('id')."/submissions/$first", [], $b)->assertNotFound();

        $this->app['auth']->forgetGuards();
        $this->deleteJson('/api/forms/'.$form->json('id')."/submissions/$first", [], $a)->assertNoContent();
        $this->getJson('/api/forms/'.$form->json('id').'/submissions', $a)->assertJsonPath('total', 1);
    }
}
