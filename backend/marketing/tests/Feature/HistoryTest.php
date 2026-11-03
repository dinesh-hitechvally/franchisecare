<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use App\Services\History;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class HistoryTest extends TestCase
{
    use RefreshDatabase;

    protected function owner(string $email = 'o@x.com', string $co = 'Acme'): array
    {
        $this->app['auth']->forgetGuards();
        $token = $this->postJson('/api/register', [
            'company_name' => $co, 'name' => 'Olivia Owner', 'email' => $email, 'password' => 'password123', 'password_confirmation' => 'password123',
        ])->assertOk()->json('token');

        return ['Authorization' => 'Bearer '.$token];
    }

    public function test_every_tracked_table_has_a_history_table_with_all_of_its_columns(): void
    {
        foreach (History::TYPES as $table) {
            $this->assertTrue(Schema::hasTable($table.'_history'), "$table has no history table");
            $source = array_filter(Schema::getColumns($table), fn ($c) => empty($c['generation']));
            $missing = array_diff(array_column($source, 'name'), Schema::getColumnListing($table.'_history'));
            $this->assertSame([], array_values($missing), "{$table}_history is missing columns of $table: add them to its history table");
        }
    }

    public function test_each_change_leaves_a_full_copy_of_the_row(): void
    {
        $h = $this->owner();
        $id = $this->postJson('/api/people', ['email' => 'a@x.com', 'first_name' => 'Ann', 'phone' => '+61400000000'], $h)->json('id');
        $this->putJson("/api/people/$id", ['email' => 'a@x.com', 'first_name' => 'Anna', 'phone' => '+61400000000'], $h)->assertOk();
        $this->deleteJson("/api/people/$id", [], $h)->assertNoContent();

        $copies = DB::table('people_history')->where('id', $id)->orderBy('history_id')->get();
        $this->assertSame(['created', 'updated', 'deleted'], $copies->pluck('change_action')->all());
        $this->assertSame(['Ann', 'Anna', 'Anna'], $copies->pluck('first_name')->all()); // the whole row is mirrored each time
        $this->assertSame('+61400000000', $copies[0]->phone);
        $this->assertNull($copies[1]->deleted_at);
        $this->assertNotNull($copies[2]->deleted_at);
        $this->assertSame(['first_name'], json_decode($copies[1]->changed_fields, true));
        $this->assertSame('Olivia Owner', $copies[1]->changed_by_name);
        $this->assertSame('dashboard', $copies[1]->change_source);
        $this->assertSame('a@x.com', $copies[0]->record_label);
    }

    public function test_a_records_history_endpoint_shows_old_and_new_values_and_its_list_and_tag_changes(): void
    {
        $h = $this->owner();
        $list = $this->postJson('/api/lists', ['name' => 'News'], $h)->json('id');
        $tag = $this->postJson('/api/tags', ['name' => 'VIP'], $h)->json('id');
        $id = $this->postJson('/api/people', ['email' => 'a@x.com', 'first_name' => 'Ann', 'list_ids' => [$list], 'tag_ids' => [$tag]], $h)->json('id');
        $this->putJson("/api/people/$id", ['email' => 'a@x.com', 'first_name' => 'Anna', 'list_ids' => [], 'tag_ids' => [$tag]], $h)->assertOk();

        $rows = collect($this->getJson("/api/history/person/$id", $h)->assertOk()->json());
        $update = $rows->first(fn ($r) => $r['type'] === 'person' && $r['change_action'] === 'updated');
        $this->assertSame(['old' => 'Ann', 'new' => 'Anna'], $update['changes']['first_name']);
        $this->assertArrayNotHasKey('last_name', $update['changes']);

        $members = $rows->where('type', 'list_membership')->pluck('change_action')->sort()->values()->all();
        $this->assertSame(['created', 'deleted'], $members);
        $this->assertSame('a@x.com → list News', $rows->firstWhere('type', 'list_membership')['record_label']);
        $this->assertSame(1, $rows->where('type', 'person_tag')->count());
    }

    public function test_the_feed_spans_tables_and_filters(): void
    {
        $h = $this->owner();
        $this->postJson('/api/tags', ['name' => 'VIP'], $h)->assertCreated();
        $this->postJson('/api/people', ['email' => 'a@x.com'], $h)->assertCreated();

        $all = collect($this->getJson('/api/history', $h)->assertOk()->json('data'));
        $this->assertEqualsCanonicalizing(['tag', 'person'], $all->pluck('type')->intersect(['tag', 'person'])->unique()->values()->all());
        $this->assertSame(['VIP'], collect($this->getJson('/api/history?type=tag', $h)->json('data'))->pluck('record_label')->all());
        $this->assertSame([], $this->getJson('/api/history?type=person&search=nobody', $h)->json('data'));
        $this->getJson('/api/history/nonsense/1', $h)->assertNotFound();
    }

    public function test_secrets_are_masked_and_counters_do_not_create_copies(): void
    {
        $h = $this->owner();
        $this->assertSame('••••••', DB::table('users_history')->value('password'));

        $form = $this->postJson('/api/forms', ['name' => 'F'], $h)->json();
        $before = DB::table('forms_history')->count();
        $this->get('/f/'.$form['slug'])->assertOk(); // only bumps the view counter
        $this->assertSame($before, DB::table('forms_history')->count());
    }

    public function test_public_submissions_are_recorded_as_public(): void
    {
        $h = $this->owner();
        $form = $this->postJson('/api/forms', ['name' => 'F'], $h)->json();
        $this->postJson('/api/public/forms/'.$form['slug'], ['email' => 'p@x.com'])->assertOk();

        $this->assertSame('public', DB::table('people_history')->where('email', 'p@x.com')->value('change_source'));
        $this->assertSame('public', DB::table('form_submissions_history')->value('change_source'));
    }

    public function test_history_is_private_to_each_company_and_read_only(): void
    {
        $h = $this->owner();
        $id = $this->postJson('/api/people', ['email' => 'a@x.com'], $h)->json('id');
        $this->app['auth']->forgetGuards();
        $h2 = $this->owner('p@y.com', 'Other');

        $this->assertSame([], $this->getJson("/api/history/person/$id", $h2)->assertOk()->json());
        $this->assertNotContains('a@x.com', collect($this->getJson('/api/history', $h2)->json('data'))->pluck('record_label')->all());
        $this->deleteJson("/api/history/person/$id", [], $h2)->assertStatus(405);
        $this->putJson("/api/history/person/$id", [], $h2)->assertStatus(405);
    }
    public function test_the_activity_pages_show_only_the_signed_in_persons_own_entries(): void
    {
        $h = $this->owner();
        $this->postJson('/api/people', ['email' => 'mine@x.com'], $h)->assertCreated();
        $companyId = Company::first()->id;
        $other = User::create(['name' => 'Other', 'email' => 'other@x.com', 'password' => 'password123', 'company_id' => $companyId]);
        $this->app['auth']->forgetGuards();
        $h2 = ['Authorization' => 'Bearer '.$other->createToken('t')->plainTextToken];
        $this->postJson('/api/people', ['email' => 'theirs@x.com'], $h2)->assertCreated();

        $feed = fn ($headers) => collect($this->getJson('/api/history?type=person', $headers)->assertOk()->json('data'))->pluck('record_label')->all();
        $this->assertSame(['theirs@x.com'], $feed($h2));
        $this->app['auth']->forgetGuards();
        $this->assertSame(['mine@x.com'], $feed($h));

        $actions = fn ($headers) => collect($this->getJson('/api/activity-logs', $headers)->assertOk()->json('data'))->pluck('user_name')->unique()->values()->all();
        $this->app['auth']->forgetGuards();
        $this->assertSame(['Other'], $actions($h2));
        $this->app['auth']->forgetGuards();
        $this->assertSame(['Olivia Owner'], $actions($h));
    }
}
