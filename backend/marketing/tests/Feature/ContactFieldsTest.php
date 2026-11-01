<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\ContactField;
use App\Models\ContactFieldValue;
use App\Models\EmailLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactFieldsTest extends TestCase
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
        return ['id' => 'x'.substr(md5($label.$type), 0, 5), 'type' => $type, 'label' => $label] + $extra;
    }

    public function test_custom_fields_can_be_managed(): void
    {
        $h = $this->owner();
        $f = $this->postJson('/api/contact-fields', ['label' => 'Company name', 'type' => 'text'], $h)->assertCreated();
        $f->assertJsonPath('key', 'contact_company_name')->assertJsonPath('values_count', 0); // not company_name: that is the built-in tag for YOUR company
        // a second field with the same label gets its own key
        $this->postJson('/api/contact-fields', ['label' => 'Company name', 'type' => 'text'], $h)->assertCreated()->assertJsonPath('key', 'contact_company_name_2');

        $this->postJson('/api/contact-fields', ['label' => 'Plan', 'type' => 'dropdown'], $h)->assertStatus(422);                    // choices needed
        $this->postJson('/api/contact-fields', ['label' => 'Plan', 'type' => 'dropdown', 'options' => [' ', '']], $h)->assertStatus(422);
        $this->postJson('/api/contact-fields', ['label' => 'X', 'type' => 'nonsense'], $h)->assertStatus(422);
        $plan = $this->postJson('/api/contact-fields', ['label' => 'Plan', 'type' => 'dropdown', 'options' => ['Basic', 'Pro', ' Pro ']], $h)->assertCreated();
        $plan->assertJsonPath('options', ['Basic', 'Pro']);

        // label + choices can change; the key (merge tag) and type are fixed so existing answers keep their meaning
        $this->putJson('/api/contact-fields/'.$plan->json('id'), ['label' => 'Subscription plan', 'options' => ['Basic', 'Pro', 'Team'], 'key' => 'hacked', 'type' => 'text'], $h)
            ->assertOk()->assertJsonPath('label', 'Subscription plan')->assertJsonPath('key', 'plan')->assertJsonPath('type', 'dropdown')->assertJsonPath('options', ['Basic', 'Pro', 'Team']);

        $this->getJson('/api/contact-fields', $h)->assertJsonCount(3);
    }

    public function test_contacts_carry_and_validate_custom_values_and_use_them_as_merge_tags(): void
    {
        $h = $this->owner();
        $this->postJson('/api/contact-fields', ['label' => 'Company name', 'type' => 'text'], $h);
        $this->postJson('/api/contact-fields', ['label' => 'Plan', 'type' => 'dropdown', 'options' => ['Basic', 'Pro']], $h);
        $this->postJson('/api/contact-fields', ['label' => 'Staff', 'type' => 'number'], $h);

        $c = $this->postJson('/api/contacts', ['email' => 'ann@x.com', 'first_name' => 'Ann', 'custom' => ['contact_company_name' => 'Acme', 'plan' => 'Pro']], $h)->assertCreated();
        $c->assertJsonPath('custom.contact_company_name', 'Acme')->assertJsonPath('custom.plan', 'Pro');
        $id = $c->json('id');

        $this->postJson('/api/contacts', ['email' => 'bad@x.com', 'custom' => ['nope' => 'x']], $h)->assertStatus(422);               // unknown field
        $this->postJson('/api/contacts', ['email' => 'bad@x.com', 'custom' => ['plan' => 'Enterprise']], $h)->assertStatus(422);       // not an option
        $this->postJson('/api/contacts', ['email' => 'bad@x.com', 'custom' => ['staff' => 'many']], $h)->assertStatus(422);            // not a number
        $this->assertSame(1, Contact::withoutGlobalScopes()->count());

        $this->getJson('/api/contacts', $h)->assertJsonPath('data.0.custom.contact_company_name', 'Acme');

        // editing: change one, clear one, and an update without "custom" leaves them alone
        $this->putJson("/api/contacts/$id", ['email' => 'ann@x.com', 'custom' => ['contact_company_name' => 'Globex', 'plan' => '']], $h)
            ->assertOk()->assertJsonPath('custom.contact_company_name', 'Globex')->assertJsonMissingPath('custom.plan');
        $this->putJson("/api/contacts/$id", ['email' => 'ann@x.com', 'first_name' => 'Anna'], $h)->assertOk()->assertJsonPath('custom.contact_company_name', 'Globex');

        // they work as merge tags in a real send
        $camp = $this->postJson('/api/campaigns', ['name' => 'C', 'type' => 'email', 'subject' => 'Hi {{first_name}} at {{contact_company_name}}', 'body' => '<p>{{contact_company_name}}</p> from {{company_name}}'], $h)->json('id');
        $this->postJson("/api/campaigns/$camp/send", [], $h)->assertOk();
        $log = EmailLog::withoutGlobalScopes()->where('recipient', 'ann@x.com')->firstOrFail();
        $this->assertSame('Hi Anna at Globex', $log->subject);
        $this->assertStringContainsString('<p>Globex</p> from Company A', $log->body); // contact's employer vs. your own company

        // deleting a field hides it and its answers (nothing is erased) and only those; restoring brings them back
        $fieldId = ContactField::withoutGlobalScopes()->where('key', 'contact_company_name')->value('id');
        $this->assertSame(1, ContactFieldValue::where('contact_field_id', $fieldId)->count());
        $this->deleteJson("/api/contact-fields/$fieldId", [], $h)->assertNoContent();
        $this->assertSame([], ContactField::all()->pluck('key')->intersect(['contact_company_name'])->values()->all());
        $this->assertArrayNotHasKey('contact_company_name', Contact::withoutGlobalScopes()->first()->customMap());
        $this->assertSame(1, Contact::withoutGlobalScopes()->count());
        $this->assertNotNull(ContactField::withoutGlobalScopes()->onlyTrashed()->find($fieldId));
        ContactField::withoutGlobalScopes()->onlyTrashed()->find($fieldId)->restore();
        $this->assertSame('Globex', Contact::withoutGlobalScopes()->first()->customMap()['contact_company_name']);
    }

    public function test_custom_fields_are_private_to_each_company(): void
    {
        $a = $this->owner('a@x.com', 'A');
        $id = $this->postJson('/api/contact-fields', ['label' => 'Secret', 'type' => 'text'], $a)->json('id');
        $this->postJson('/api/contacts', ['email' => 'ann@x.com', 'custom' => ['secret' => 'value']], $a)->assertCreated();

        $b = $this->owner('b@y.com', 'B');
        $this->getJson('/api/contact-fields', $b)->assertJsonCount(0);
        $this->putJson("/api/contact-fields/$id", ['label' => 'x'], $b)->assertNotFound();
        $this->deleteJson("/api/contact-fields/$id", [], $b)->assertNotFound();
        $this->postJson('/api/contacts', ['email' => 'bob@y.com', 'custom' => ['secret' => 'x']], $b)->assertStatus(422); // B has no such field
        // B can define a field with the same key independently
        $this->postJson('/api/contact-fields', ['label' => 'Secret', 'type' => 'text'], $b)->assertCreated()->assertJsonPath('key', 'secret');
    }

    public function test_form_questions_become_contact_fields_only_when_marked_and_only_when_saving_contacts(): void
    {
        $h = $this->owner();
        $a = $this->postJson('/api/forms', ['name' => 'Newsletter', 'type' => 'newsletter', 'fields' => [
            $this->f('first_name', 'First name'),
            $this->f('email', 'Email', ['required' => true]),
            $this->f('text', 'Company name'),                                 // custom question: saved on the contact by default
            $this->f('textarea', 'Message'),                                  // long text: kept in the inbox by default
            $this->f('dropdown', 'Interest', ['options' => ['News', 'Tips'], 'to_contact' => false]),   // explicitly inbox-only
        ]], $h)->assertCreated();

        $byLabel = collect($a->json('fields'))->keyBy('label');
        $this->assertTrue($byLabel['Company name']['to_contact']);
        $this->assertFalse($byLabel['Message']['to_contact']);
        $this->assertTrue($byLabel['Email']['to_contact']);                   // standard fields always fill the contact's own columns
        $this->assertSame(['contact_company_name'], ContactField::pluck('key')->all()); // only the marked question was defined as a custom field

        $this->postJson('/api/public/forms/'.$a->json('slug'), ['email' => 'lee@x.com', 'first_name' => 'Lee', 'contact_company_name' => 'Acme', 'message' => 'Call me', 'interest' => 'Tips'])->assertOk();
        $contact = Contact::withoutGlobalScopes()->where('email', 'lee@x.com')->firstOrFail();
        $this->assertSame('Lee', $contact->first_name);                       // built-in column
        $this->assertEquals(['contact_company_name' => 'Acme'], $contact->customMap()); // managed custom field; message + interest are NOT on the contact
        $inbox = $this->getJson('/api/forms/'.$a->json('id').'/submissions', $h)->json('data.0.data');
        $this->assertEquals(['First name' => 'Lee', 'Email' => 'lee@x.com', 'Company name' => 'Acme', 'Message' => 'Call me', 'Interest' => 'Tips'], collect($inbox)->pluck('value', 'label')->all());

        // a second form asking the same thing shares the field instead of creating a duplicate
        $this->postJson('/api/forms', ['name' => 'Other', 'fields' => [$this->f('email', 'Email'), $this->f('text', 'Company name')]], $h)->assertCreated();
        $this->assertSame(1, ContactField::count());

        // same name but a different kind of answer: it gets its own field, the old one is untouched
        $c = $this->postJson('/api/forms', ['name' => 'Clash', 'fields' => [$this->f('email', 'Email'), $this->f('dropdown', 'Company name', ['options' => ['A', 'B']])]], $h)->assertCreated();
        $this->assertSame('contact_company_name_2', collect($c->json('fields'))->firstWhere('type', 'dropdown')['key']);
        $this->assertSame('text', ContactField::where('key', 'contact_company_name')->value('type'));

        // two forms offering different choices for the same dropdown: every choice stays valid on the contact field
        $this->postJson('/api/forms', ['name' => 'P1', 'fields' => [$this->f('email', 'Email'), $this->f('dropdown', 'Plan', ['options' => ['A', 'B']])]], $h)->assertCreated();
        $this->postJson('/api/forms', ['name' => 'P2', 'fields' => [$this->f('email', 'Email'), $this->f('dropdown', 'Plan', ['options' => ['B', 'C']])]], $h)->assertCreated();
        $this->assertSame(['A', 'B', 'C'], ContactField::where('key', 'plan')->first()->options);

        // a repeat submission never overwrites what we already know about the person
        $this->postJson('/api/public/forms/'.$a->json('slug'), ['email' => 'lee@x.com', 'contact_company_name' => 'HACKED'])->assertOk();
        $this->assertSame('Acme', $contact->fresh()->customMap()['contact_company_name']);
    }

    public function test_forms_that_do_not_save_contacts_keep_everything_in_the_inbox(): void
    {
        $h = $this->owner();
        $form = $this->postJson('/api/forms', ['name' => 'Contact us', 'type' => 'contact', 'save_contact' => false, 'notify_emails' => ['me@biz.com'],
            'autoreply' => true, 'autoreply_subject' => 'Thanks {{first_name}}', 'autoreply_body' => 'We got it.', 'fields' => [
                $this->f('first_name', 'First name'),
                $this->f('email', 'Email', ['required' => true]),
                $this->f('text', 'Company name', ['to_contact' => true]),     // flagged, but this form does not save contacts
                $this->f('textarea', 'Message', ['required' => true]),
            ]], $h)->assertCreated();

        $this->assertSame(0, ContactField::count());                          // no custom field is defined for a form that never touches contacts

        $this->postJson('/api/public/forms/'.$form->json('slug'), ['first_name' => 'Pat', 'email' => 'pat@x.com', 'contact_company_name' => 'Acme', 'message' => 'Hello'])->assertOk();
        $this->assertSame(0, Contact::withoutGlobalScopes()->count());        // nobody added to contacts
        $this->assertSame(0, ContactFieldValue::count());
        $inbox = $this->getJson('/api/forms/'.$form->json('id').'/submissions', $h)->assertJsonPath('total', 1)->assertJsonPath('data.0.contact', null);
        $this->assertSame('Acme', collect($inbox->json('data.0.data'))->firstWhere('label', 'Company name')['value']); // still all in the inbox

        $mails = collect(array_map(fn ($m) => $m->getOriginalMessage(), app('mailer')->getSymfonyTransport()->messages()->all()));
        $this->assertTrue($mails->contains(fn ($m) => $m->getTo()[0]->getAddress() === 'me@biz.com'));          // notification
        $this->assertTrue($mails->contains(fn ($m) => $m->getTo()[0]->getAddress() === 'pat@x.com' && $m->getSubject() === 'Thanks Pat')); // auto-reply with no contact record
    }

    public function test_api_custom_fields_are_stored_in_managed_fields(): void
    {
        $h = $this->owner();
        $key = $this->postJson('/api/api-keys', ['name' => 'Zap'], $h)->assertCreated()->json('key');

        $this->app['auth']->forgetGuards();
        $r = $this->postJson('/api/v1/contacts', ['email' => 'api@x.com', 'custom_fields' => ['Company' => 'Acme', 'Plan tier' => 'Gold']], ['Authorization' => 'Bearer '.$key])->assertCreated();
        $r->assertJsonPath('custom.company', 'Acme')->assertJsonPath('custom.plan_tier', 'Gold');

        $this->assertEquals(['Company', 'Plan tier'], ContactField::withoutGlobalScopes()->orderBy('id')->pluck('label')->all());
        $this->assertEquals(['company' => 'Acme', 'plan_tier' => 'Gold'], Contact::withoutGlobalScopes()->first()->customMap());
    }
    public function test_a_form_can_use_an_existing_custom_field_without_duplicating_it(): void
    {
        $h = $this->owner();
        $this->postJson('/api/contact-fields', ['label' => 'Plan', 'type' => 'dropdown', 'options' => ['Free', 'Pro']], $h)->assertCreated();
        $form = $this->postJson('/api/forms', ['name' => 'F', 'fields' => [
            $this->f('email', 'Email', ['required' => true]),
            $this->f('dropdown', 'Which plan?', ['key' => 'plan', 'options' => ['Free', 'Pro'], 'to_contact' => true]),
        ]], $h)->assertCreated();

        $this->assertSame(['plan'], ContactField::withoutGlobalScopes()->pluck('key')->all());
        $this->assertSame('plan', collect($form->json('fields'))->firstWhere('type', 'dropdown')['key']);

        $this->postJson('/api/public/forms/'.$form->json('slug'), ['email' => 'p@x.com', 'plan' => 'Pro'])->assertOk();
        $this->assertSame('Pro', Contact::withoutGlobalScopes()->where('email', 'p@x.com')->firstOrFail()->customMap()['plan']);
    }
}
