<?php

namespace App\Http\Controllers;

use App\Models\Person;
use App\Models\PersonField;
use App\Models\PeopleList;
use App\Services\FormFields;
use App\Models\Tag;
use App\Services\Automation;
use App\Services\Subscriptions;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/** Public integration API (/api/v1) — authenticated with an API key from Settings → API Integrations. */
class ApiV1Controller extends Controller
{
    /** Create or update a person by email (or phone). Lists/tags are matched by name and created when missing. */
    public function upsert(Request $request)
    {
        $data = $request->validate([
            'email' => 'nullable|email|max:255|required_without:phone',
            'phone' => 'nullable|string|max:30|required_without:email',
            'first_name' => 'nullable|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'custom_fields' => 'nullable|array|max:30',
            'custom_fields.*' => ['nullable', 'max:2000', fn ($attr, $v, $fail) => is_array($v) ? $fail('Custom field values must be text or numbers.') : null],
            'lists' => 'nullable|array|max:20',
            'lists.*' => 'string|max:255',
            'tags' => 'nullable|array|max:20',
            'tags.*' => 'string|max:60',
        ]);
        $email = isset($data['email']) ? Str::lower($data['email']) : null;

        $person = Person::where(fn ($q) => $email ? $q->where('email', $email) : $q->where('phone', $data['phone']))->first();
        $created = ! $person;
        $fields = array_filter(['first_name' => $data['first_name'] ?? null, 'last_name' => $data['last_name'] ?? null, 'phone' => $data['phone'] ?? null]);

        if ($created) {
            $person = Person::create($fields + ['email' => $email, 'source' => 'api']);
        } else {
            $person->update($fields);
        }
        \App\Services\PersonSources::seen($person, 'api', ['created' => $created, 'changed' => ! $created && $person->wasChanged()]);

        // custom fields: define any that are new (as plain text), then save the answers
        $custom = [];
        foreach ($data['custom_fields'] ?? [] as $label => $value) {
            $key = FormFields::keyFor((string) $label, []);
            // `key` is guarded against mass assignment, so a new field must be built with forceFill
            $field = PersonField::where('key', $key)->first()
                ?? tap((new PersonField)->forceFill(['key' => $key, 'label' => trim((string) $label), 'type' => 'text']))->save();
            $custom[$field->key] = (string) $value;
        }
        $person->setCustom($custom);

        $newLists = $newTags = [];
        foreach ($data['lists'] ?? [] as $name) {
            $list = PeopleList::firstOrCreate(['name' => $name]);
            if (! $person->lists()->where('people_lists.id', $list->id)->exists()) {
                $newLists[] = $list->id;
            }
            $person->lists()->syncWithoutDetaching([$list->id]);
        }
        foreach ($data['tags'] ?? [] as $name) {
            $tag = Tag::firstOrCreate(['name' => $name]);
            if (! $person->tags()->where('tags.id', $tag->id)->exists()) {
                $newTags[] = $tag->id;
            }
            $person->tags()->syncWithoutDetaching([$tag->id]);
        }
        Automation::personChanged($person, $created, $newLists, $newTags);

        $person->load('lists:id,name', 'tags:id,name')->setAttribute('custom', (object) $person->customMap());
        $person->unsetRelation('fieldValues');

        return response($person, $created ? 201 : 200);
    }

    public function show(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        return Person::with('lists:id,name', 'tags:id,name')->where('email', Str::lower($request->query('email')))->firstOrFail();
    }

    public function unsubscribe(Request $request)
    {
        $data = $request->validate(['email' => 'required|email', 'channel' => 'nullable|in:email,sms']);
        $person = Person::where('email', Str::lower($data['email']))->firstOrFail();
        Subscriptions::set($person, false); // 'channel' is still accepted so existing integrations keep working, but it unsubscribes the person from the newsletter as a whole

        return ['ok' => true];
    }

    /**
     * An Android / iOS app registers (or refreshes) its Firebase token, optionally linked to a person by email, phone or id.
     * With create_person=true and an email that is not known yet, the person is created.
     */
    public function pushDevice(Request $request)
    {
        $data = $request->validate([
            'token' => 'required|string|max:4096', 'platform' => 'required|in:android,ios',
            'email' => 'nullable|email|max:255', 'phone' => 'nullable|string|max:30', 'person_id' => 'nullable|integer',
            'label' => 'nullable|string|max:120', 'create_person' => 'nullable|boolean',
            'device_id' => 'nullable|string|max:64', // the app's own install id: a new Firebase token for the same installation keeps ONE device
        ]);
        $company = $request->user()->company;

        $who = array_filter(['email' => $data['email'] ?? null, 'phone' => $data['phone'] ?? null, 'person_id' => $data['person_id'] ?? null]);
        if ($request->boolean('create_person') && ! empty($data['email']) && ! \App\Services\Push\Devices::person($company, $who)) {
            Person::create(['email' => Str::lower($data['email']), 'source' => 'api']);
        }
        $device = \App\Services\Push\Devices::register($company, $data['platform'], $data['token'], null, $data['label'] ?? null, $who, null, $data['device_id'] ?? null);

        return response()->json(['id' => $device->id, 'linked' => (bool) $device->person_id], 201);
    }

    /** The app no longer wants notifications on this installation. */
    public function pushDeviceRemove(Request $request)
    {
        $data = $request->validate(['token' => 'required|string|max:4096']);
        \App\Models\PushDevice::where('token_hash', \App\Models\PushDevice::hashOf($data['token']))->get()->each(fn ($d) => $d->update(['status' => 'revoked'])); // one by one, so each is written to the device history

        return response()->noContent();
    }
}
