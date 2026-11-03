<?php

namespace Tests\Feature;

use App\Models\Person;
use App\Models\PersonField;
use App\Models\PersonFieldValue;
use App\Models\EmailLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PersonFieldsTest extends TestCase
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
        $f = $this->postJson('/api/person-fields', ['label' => 'Company name', 'type' => 'text'], $h)->assertCreated();
        $f->assertJsonPath('key', 'person_company_name')->assertJsonPath('values_count', 0); // not company_name: that is the built-in tag for YOUR company
        // a second field with the same label gets its own key
        $this->postJson('/api/person-fields', ['label' => 'Company name', 'type' => 'text'], $h)->assertCreated()->assertJsonPath('key', 'person_company_name_2');

        $this->postJson('/api/person-fields', ['label' => 'Plan', 'type' => 'dropdown'], $h)->assertStatus(422);                    // choices needed
        $this->postJson('/api/person-fields', ['label' => 'Plan', 'type' => 'dropdown', 'options' => [' ', '']], $h)->assertStatus(422);
        $this->postJson('/api/person-fields', ['label' => 'X', 'type' => 'nonsense'], $h)->assertStatus(422);
        $plan = $this->postJson('/api/person-fields', ['label' => 'Plan', 'type' => 'dropdown', 'options' => ['Basic', 'Pro', ' Pro ']], $h)->assertCreated();
        $plan->assertJsonPath('options', ['Basic', 'Pro']);

        // label + choices can change; the key (merge tag) and type are fixed so existing answers keep their meaning
        $this->putJson('/api/person-fields/'.$plan->json('id'), ['label' => 'Subscription plan', 'options' => ['Basic', 'Pro', 'Team'], 'key' => 'hacked', 'type' => 'text'], $h)
            ->assertOk()->assertJsonPath('label', 'Subscription plan')->assertJsonPath('key', 'plan')->assertJsonPath('type', 'dropdown')->assertJsonPath('options', ['Basic', 'Pro', 'Team']);

        $this->getJson('/api/person-fields', $h)->assertJsonCount(3);
    }

    public function test_people_carry_and_validate_custom_values_and_use_them_as_merge_tags(): void
    {
        $h = $this->owner();
        $this->postJson('/api/person-fields', ['label' => 'Company name', 'type' => 'text'], $h);
        $this->postJson('/api/person-fields', ['label' => 'Plan', 'type' => 'dropdown', 'options' => ['Basic', 'Pro']], $h);
        $this->postJson('/api/person-fields', ['label' => 'Staff', 'type' => 'number'], $h);

        $c = $this->postJson('/api/people', ['email' => 'ann@x.com', 'first_name' => 'Ann', 'custom' => ['person_company_name' => 'Acme', 'plan' => 'Pro']], $h)->assertCreated();
        $c->assertJsonPath('custom.person_company_name', 'Acme')->assertJsonPath('custom.plan', 'Pro');
        $id = $c->json('id');

        $this->postJson('/api/people', ['email' => 'bad@x.com', 'custom' => ['nope' => 'x']], $h)->assertStatus(422);               // unknown field
        $this->postJson('/api/people', ['email' => 'bad@x.com', 'custom' => ['plan' => 'Enterprise']], $h)->assertStatus(422);       // not an option
        $this->postJson('/api/people', ['email' => 'bad@x.com', 'custom' => ['staff' => 'many']], $h)->assertStatus(422);            // not a number
        $this->assertSame(1, Person::withoutGlobalScopes()->count());

        $this->getJson('/api/people', $h)->assertJsonPath('data.0.custom.person_company_name', 'Acme');

        // editing: change one, clear one, and an update without "custom" leaves them alone
        $this->putJson("/api/people/$id", ['email' => 'ann@x.com', 'custom' => ['person_company_name' => 'Globex', 'plan' => '']], $h)
            ->assertOk()->assertJsonPath('custom.person_company_name', 'Globex')->assertJsonMissingPath('custom.plan');
        $this->putJson("/api/people/$id", ['email' => 'ann@x.com', 'first_name' => 'Anna'], $h)->assertOk()->assertJsonPath('custom.person_company_name', 'Globex');

        // they work as merge tags in a real send
        $camp = $this->postJson('/api/campaigns', ['name' => 'C', 'type' => 'email', 'subject' => 'Hi {{first_name}} at {{person_company_name}}', 'body' => '<p>{{person_company_name}}</p> from {{company_name}}'], $h)->json('id');
        $this->postJson("/api/campaigns/$camp/send", [], $h)->assertOk();
        $log = EmailLog::withoutGlobalScopes()->where('recipient', 'ann@x.com')->firstOrFail();
        $this->assertSame('Hi Anna at Globex', $log->subject);
        $this->assertStringContainsString('<p>Globex</p> from Company A', $log->body); // person's employer vs. your own company

        // deleting a field hides it and its answers (nothing is erased) and only those; restoring brings them back
        $fieldId = PersonField::withoutGlobalScopes()->where('key', 'person_company_name')->value('id');
        $this->assertSame(1, PersonFieldValue::where('person_field_id', $fieldId)->count());
        $this->deleteJson("/api/person-fields/$fieldId", [], $h)->assertNoContent();
        $this->assertSame([], PersonField::all()->pluck('key')->intersect(['person_company_name'])->values()->all());
        $this->assertArrayNotHasKey('person_company_name', Person::withoutGlobalScopes()->first()->customMap());
        $this->assertSame(1, Person::withoutGlobalScopes()->count());
        $this->assertNotNull(PersonField::withoutGlobalScopes()->onlyTrashed()->find($fieldId));
        PersonField::withoutGlobalScopes()->onlyTrashed()->find($fieldId)->restore();
        $this->assertSame('Globex', Person::withoutGlobalScopes()->first()->customMap()['person_company_name']);
    }

    public function test_custom_fields_are_private_to_each_company(): void
    {
        $a = $this->owner('a@x.com', 'A');
        $id = $this->postJson('/api/person-fields', ['label' => 'Secret', 'type' => 'text'], $a)->json('id');
        $this->postJson('/api/people', ['email' => 'ann@x.com', 'custom' => ['secret' => 'value']], $a)->assertCreated();

        $b = $this->owner('b@y.com', 'B');
        $this->getJson('/api/person-fields', $b)->assertJsonCount(0);
        $this->putJson("/api/person-fields/$id", ['label' => 'x'], $b)->assertNotFound();
        $this->deleteJson("/api/person-fields/$id", [], $b)->assertNotFound();
        $this->postJson('/api/people', ['email' => 'bob@y.com', 'custom' => ['secret' => 'x']], $b)->assertStatus(422); // B has no such field
        // B can define a field with the same key independently
        $this->postJson('/api/person-fields', ['label' => 'Secret', 'type' => 'text'], $b)->assertCreated()->assertJsonPath('key', 'secret');
    }

    public function test_form_questions_become_person_fields_only_when_marked_and_only_when_saving_people(): void
    {
        $h = $this->owner();
        $a = $this->postJson('/api/forms', ['name' => 'Newsletter', 'type' => 'newsletter', 'fields' => [
            $this->f('first_name', 'First name'),
            $this->f('email', 'Email', ['required' => true]),
            $this->f('text', 'Company name'),                                 // custom question: saved on the person by default
            $this->f('textarea', 'Message'),                                  // long text: kept in the inbox by default
            $this->f('dropdown', 'Interest', ['options' => ['News', 'Tips'], 'to_person' => false]),   // explicitly inbox-only
        ]], $h)->assertCreated();

        $byLabel = collect($a->json('fields'))->keyBy('label');
        $this->assertTrue($byLabel['Company name']['to_person']);
        $this->assertFalse($byLabel['Message']['to_person']);
        $this->assertTrue($byLabel['Email']['to_person']);                   // standard fields always fill the person's own columns
        $this->assertSame(['person_company_name'], PersonField::pluck('key')->all()); // only the marked question was defined as a custom field

        $this->postJson('/api/public/forms/'.$a->json('slug'), ['email' => 'lee@x.com', 'first_name' => 'Lee', 'person_company_name' => 'Acme', 'message' => 'Call me', 'interest' => 'Tips'])->assertOk();
        $person = Person::withoutGlobalScopes()->where('email', 'lee@x.com')->firstOrFail();
        $this->assertSame('Lee', $person->first_name);                       // built-in column
        $this->assertEquals(['person_company_name' => 'Acme'], $person->customMap()); // managed custom field; message + interest are NOT on the person
        $inbox = $this->getJson('/api/forms/'.$a->json('id').'/submissions', $h)->json('data.0.data');
        $this->assertEquals(['First name' => 'Lee', 'Email' => 'lee@x.com', 'Company name' => 'Acme', 'Message' => 'Call me', 'Interest' => 'Tips'], collect($inbox)->pluck('value', 'label')->all());

        // a second form asking the same thing shares the field instead of creating a duplicate
        $this->postJson('/api/forms', ['name' => 'Other', 'fields' => [$this->f('email', 'Email'), $this->f('text', 'Company name')]], $h)->assertCreated();
        $this->assertSame(1, PersonField::count());

        // same name but a different kind of answer: it gets its own field, the old one is untouched
        $c = $this->postJson('/api/forms', ['name' => 'Clash', 'fields' => [$this->f('email', 'Email'), $this->f('dropdown', 'Company name', ['options' => ['A', 'B']])]], $h)->assertCreated();
        $this->assertSame('person_company_name_2', collect($c->json('fields'))->firstWhere('type', 'dropdown')['key']);
        $this->assertSame('text', PersonField::where('key', 'person_company_name')->value('type'));

        // two forms offering different choices for the same dropdown: every choice stays valid on the person field
        $this->postJson('/api/forms', ['name' => 'P1', 'fields' => [$this->f('email', 'Email'), $this->f('dropdown', 'Plan', ['options' => ['A', 'B']])]], $h)->assertCreated();
        $this->postJson('/api/forms', ['name' => 'P2', 'fields' => [$this->f('email', 'Email'), $this->f('dropdown', 'Plan', ['options' => ['B', 'C']])]], $h)->assertCreated();
        $this->assertSame(['A', 'B', 'C'], PersonField::where('key', 'plan')->first()->options);

        // a repeat submission never overwrites what we already know about the person
        $this->postJson('/api/public/forms/'.$a->json('slug'), ['email' => 'lee@x.com', 'person_company_name' => 'HACKED'])->assertOk();
        $this->assertSame('Acme', $person->fresh()->customMap()['person_company_name']);
    }

    public function test_forms_that_do_not_save_people_keep_everything_in_the_inbox(): void
    {
        $h = $this->owner();
        $form = $this->postJson('/api/forms', ['name' => 'Contact us', 'type' => 'contact', 'save_person' => false, 'notify_emails' => ['me@biz.com'],
            'autoreply' => true, 'autoreply_subject' => 'Thanks {{first_name}}', 'autoreply_body' => 'We got it.', 'fields' => [
                $this->f('first_name', 'First name'),
                $this->f('email', 'Email', ['required' => true]),
                $this->f('text', 'Company name', ['to_person' => true]),     // flagged, but this form does not save people
                $this->f('textarea', 'Message', ['required' => true]),
            ]], $h)->assertCreated();

        $this->assertSame(0, PersonField::count());                          // no custom field is defined for a form that never touches people

        $this->postJson('/api/public/forms/'.$form->json('slug'), ['first_name' => 'Pat', 'email' => 'pat@x.com', 'person_company_name' => 'Acme', 'message' => 'Hello'])->assertOk();
        $this->assertSame(0, Person::withoutGlobalScopes()->count());        // nobody added to people
        $this->assertSame(0, PersonFieldValue::count());
        $inbox = $this->getJson('/api/forms/'.$form->json('id').'/submissions', $h)->assertJsonPath('total', 1)->assertJsonPath('data.0.person', null);
        $this->assertSame('Acme', collect($inbox->json('data.0.data'))->firstWhere('label', 'Company name')['value']); // still all in the inbox

        $mails = collect(array_map(fn ($m) => $m->getOriginalMessage(), app('mailer')->getSymfonyTransport()->messages()->all()));
        $this->assertTrue($mails->contains(fn ($m) => $m->getTo()[0]->getAddress() === 'me@biz.com'));          // notification
        $this->assertTrue($mails->contains(fn ($m) => $m->getTo()[0]->getAddress() === 'pat@x.com' && $m->getSubject() === 'Thanks Pat')); // auto-reply with no person record
    }

    public function test_api_custom_fields_are_stored_in_managed_fields(): void
    {
        $h = $this->owner();
        $key = $this->postJson('/api/api-keys', ['name' => 'Zap'], $h)->assertCreated()->json('key');

        $this->app['auth']->forgetGuards();
        $r = $this->postJson('/api/v1/people', ['email' => 'api@x.com', 'custom_fields' => ['Company' => 'Acme', 'Plan tier' => 'Gold']], ['Authorization' => 'Bearer '.$key])->assertCreated();
        $r->assertJsonPath('custom.company', 'Acme')->assertJsonPath('custom.plan_tier', 'Gold');

        $this->assertEquals(['Company', 'Plan tier'], PersonField::withoutGlobalScopes()->orderBy('id')->pluck('label')->all());
        $this->assertEquals(['company' => 'Acme', 'plan_tier' => 'Gold'], Person::withoutGlobalScopes()->first()->customMap());
    }
    public function test_a_form_can_use_an_existing_custom_field_without_duplicating_it(): void
    {
        $h = $this->owner();
        $this->postJson('/api/person-fields', ['label' => 'Plan', 'type' => 'dropdown', 'options' => ['Free', 'Pro']], $h)->assertCreated();
        $form = $this->postJson('/api/forms', ['name' => 'F', 'fields' => [
            $this->f('email', 'Email', ['required' => true]),
            $this->f('dropdown', 'Which plan?', ['key' => 'plan', 'options' => ['Free', 'Pro'], 'to_person' => true]),
        ]], $h)->assertCreated();

        $this->assertSame(['plan'], PersonField::withoutGlobalScopes()->pluck('key')->all());
        $this->assertSame('plan', collect($form->json('fields'))->firstWhere('type', 'dropdown')['key']);

        $this->postJson('/api/public/forms/'.$form->json('slug'), ['email' => 'p@x.com', 'plan' => 'Pro'])->assertOk();
        $this->assertSame('Pro', Person::withoutGlobalScopes()->where('email', 'p@x.com')->firstOrFail()->customMap()['plan']);
    }
}
