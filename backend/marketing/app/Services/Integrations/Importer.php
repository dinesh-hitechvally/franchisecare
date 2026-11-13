<?php

namespace App\Services\Integrations;

use App\Models\Integration;
use App\Models\PeopleList;
use App\Models\Person;
use App\Models\Tag;
use App\Services\Automation;
use App\Services\Subscriptions;

/**
 * Turns the customers a platform returns into People, following the connection's settings.
 *
 *  - A person is matched by email (or by phone when the customer has no email). Nobody is duplicated.
 *  - A new person gets the connection's list and tags and is subscribed according to subscribe_mode:
 *      consent  only customers the platform says agreed to marketing (the safe default)
 *      all      everyone, except customers who opted out on the platform
 *  - An existing person is only completed: blank details are filled, nothing is overwritten, and a subscription is never
 *    switched on. The one change to a subscription is a customer who unsubscribed on the platform: they are unsubscribed here too.
 */
class Importer
{
    protected ?int $listId;

    protected array $tagIds = [];

    public function __construct(protected Integration $integration)
    {
        $companyId = $integration->company_id;

        $list = $integration->setting('list_id');
        $this->listId = $list && PeopleList::withoutGlobalScopes()->where('company_id', $companyId)->whereKey($list)->exists() ? (int) $list : null;

        // every imported person carries a tag naming where they came from, plus any the person chose
        $names = array_merge([Providers::find($integration->provider)?->label() ?? $integration->provider], (array) $integration->setting('tags', []));
        foreach (array_unique(array_filter(array_map(fn ($n) => mb_substr(trim((string) $n), 0, 60), $names))) as $name) {
            // company_id is not mass-assignable, and a queue worker has no signed-in user to stamp it: set it explicitly
            $tag = Tag::withoutGlobalScopes()->where('company_id', $companyId)->where('name', $name)->first()
                ?? (new Tag(['name' => $name, 'color' => '#3f51b5']))->forceFill(['company_id' => $companyId]);
            $tag->exists || $tag->save();
            $this->tagIds[] = $tag->id;
        }
    }

    /** Whether a NEW person from this customer starts out subscribed. */
    protected function subscribes(?string $consent): bool
    {
        return match ($this->integration->setting('subscribe_mode', 'consent')) {
            'all' => $consent !== 'unsubscribed',
            default => $consent === 'subscribed',
        };
    }

    /** @param array $stats {fetched, created, updated, skipped}: counted on as customers are handled */
    public function import(array $customers, array &$stats): void
    {
        foreach ($customers as $customer) {
            $stats['fetched']++;
            try {
                $this->one($customer, $stats);
            } catch (\Throwable $e) {
                report($e);
                $stats['skipped']++; // one bad customer must not stop the other thousands
            }
        }
    }

    protected function one(array $c, array &$stats): void
    {
        $email = ! empty($c['email']) && filter_var($c['email'], FILTER_VALIDATE_EMAIL) ? mb_strtolower($c['email']) : null;
        $phone = ! empty($c['phone']) ? mb_substr($c['phone'], 0, 30) : null;
        if (! $email && ! $phone) {
            $stats['skipped']++; // nobody to send to

            return;
        }

        $companyId = $this->integration->company_id;
        $person = Person::withoutGlobalScope('company')->where('company_id', $companyId)
            ->where(fn ($q) => $email ? $q->where('email', $email) : $q->where('phone', $phone))->first();
        $created = ! $person;
        $changed = false; // an existing person: did anything about them change?

        if ($created) {
            $person = (new Person)->forceFill([
                'company_id' => $companyId, 'email' => $email, 'phone' => $phone, 'first_name' => $c['first_name'] ?? null, 'last_name' => $c['last_name'] ?? null,
                'source' => $this->integration->provider, 'is_subscribed' => $this->subscribes($c['consent'] ?? null),
            ]);
            $person->save();
            $stats['created']++;
        } else {
            $fill = array_filter([
                'first_name' => $person->first_name ?: ($c['first_name'] ?? null),
                'last_name' => $person->last_name ?: ($c['last_name'] ?? null),
                'phone' => $person->phone ?: $phone,
            ]);
            $person->fill($fill);
            $changed = $person->isDirty();
            $person->save();
            if (($c['consent'] ?? null) === 'unsubscribed' && Subscriptions::set($person, false)) {
                $changed = true;
            }
        }

        $newLists = $this->listId && ! $person->lists()->where('people_lists.id', $this->listId)->exists() ? [$this->listId] : [];
        $newTags = array_values(array_diff($this->tagIds, $person->tags()->pluck('tags.id')->all()));
        if ($this->listId) {
            $person->lists()->syncWithoutDetaching([$this->listId]);
        }
        $person->tags()->syncWithoutDetaching($this->tagIds);

        if (! $created) {
            ($changed || $newLists || $newTags) ? $stats['updated']++ : $stats['skipped']++;
        }

        // joining a list or getting a tag can start workflows: only when the connection says so, so importing a whole store does not email everyone by surprise
        if ($this->integration->setting('run_automations', false)) {
            Automation::personChanged($person, $created, $newLists, $newTags);
        }
    }
}
