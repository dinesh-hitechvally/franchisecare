<?php

namespace Tests\Feature;

use App\Services\MessageRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DateStorageTest extends TestCase
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

    public function test_the_app_and_the_mysql_connection_both_run_on_utc(): void
    {
        $this->assertSame('UTC', config('app.timezone'));
        $this->assertSame('+00:00', config('database.connections.mysql.timezone')); // not the server's own clock
    }

    public function test_no_migration_uses_a_timestamp_column(): void
    {
        // TIMESTAMP columns are shown in each client's own time zone; DATETIME shows the stored UTC (or typed) value everywhere
        foreach (glob(database_path('migrations/*.php')) as $file) {
            $code = file_get_contents($file);
            $this->assertDoesNotMatchRegularExpression('/->(timestamp|timestamps|softDeletes)\(/', $code, basename($file).' still creates a TIMESTAMP column: use dateTime()');
        }
        $this->assertSame([], collect(Schema::getColumns('email_logs'))->filter(fn ($c) => str_contains(strtolower($c['type_name']), 'timestamp'))->all());
    }

    public function test_system_times_are_stored_as_utc(): void
    {
        $h = $this->owner();
        $this->travelTo(now()->setDate(2026, 11, 1)->setTime(8, 6, 13));
        $this->postJson('/api/people', ['email' => 'a@x.com'], $h)->assertCreated();

        $this->assertStringStartsWith('2026-11-01 08:06:13', (string) DB::table('people')->value('created_at')); // the UTC wall clock, as written
    }

    public function test_a_time_typed_by_the_user_is_stored_exactly_as_typed(): void
    {
        $h = $this->owner();
        $id = $this->postJson('/api/campaigns', ['name' => 'C', 'type' => 'email', 'subject' => 'S', 'body' => '<p>x</p>', 'scheduled_at' => '2999-12-25 06:00:00'], $h)->assertCreated()->json('id');

        // no conversion on the way in...
        $this->assertStringStartsWith('2999-12-25 06:00:00', (string) DB::table('campaigns')->where('id', $id)->value('scheduled_at'));
        // ...and none on the way out
        $this->assertStringStartsWith('2999-12-25T06:00:00', $this->getJson("/api/campaigns/$id", $h)->json('scheduled_at'));

        // typed with a zone, it is also kept as given (no shifting to the server's zone)
        $this->putJson("/api/campaigns/$id", ['name' => 'C', 'type' => 'email', 'subject' => 'S', 'body' => '<p>x</p>', 'scheduled_at' => '2999-12-25 18:30:00'], $h)->assertOk();
        $this->assertStringStartsWith('2999-12-25 18:30:00', (string) DB::table('campaigns')->where('id', $id)->value('scheduled_at'));
    }

    public function test_a_scheduled_campaign_goes_out_when_the_utc_clock_reaches_the_typed_time(): void
    {
        $h = $this->owner();
        $this->postJson('/api/people', ['email' => 'ann@x.com'], $h)->assertCreated();
        $this->travelTo(now()->setDate(2026, 11, 1)->setTime(8, 0, 0));
        $id = $this->postJson('/api/campaigns', ['name' => 'C', 'type' => 'email', 'subject' => 'S', 'body' => '<p>x</p>', 'scheduled_at' => '2026-11-01 09:00:00'], $h)->json('id');
        $this->postJson("/api/campaigns/$id/send", [], $h)->assertOk()->assertJsonPath('status', 'scheduled');

        $this->travelTo(now()->setTime(8, 59, 0));
        $this->artisan('campaigns:dispatch-scheduled')->assertSuccessful();
        $this->assertSame('scheduled', DB::table('campaigns')->where('id', $id)->value('status')); // 08:59 UTC: not yet

        $this->travelTo(now()->setTime(9, 0, 1));
        $this->artisan('campaigns:dispatch-scheduled')->assertSuccessful();
        $this->assertContains(DB::table('campaigns')->where('id', $id)->value('status'), ['sending', 'sent']); // 09:00 UTC: gone
    }
    public function test_sent_opened_and_click_times_are_all_saved_as_utc(): void
    {
        $h = $this->owner();
        $this->postJson('/api/people', ['email' => 'ann@x.com'], $h)->assertCreated();
        $id = $this->postJson('/api/campaigns', ['name' => 'C', 'type' => 'email', 'subject' => 'S', 'body' => '<a href="https://example.com/a">a</a>'], $h)->json('id');

        $this->travelTo(now()->setDate(2026, 11, 1)->setTime(8, 0, 0));
        $this->postJson("/api/campaigns/$id/send", [], $h)->assertOk();
        $log = DB::table('email_logs')->first();
        $this->assertStringStartsWith('2026-11-01 08:00:', (string) $log->sent_at);
        $this->assertStringStartsWith('2026-11-01 08:00:', (string) DB::table('campaigns')->where('id', $id)->value('completed_at'));

        $this->travelTo(now()->setTime(8, 5, 0));
        $this->get("/api/t/o/{$log->track_token}.gif")->assertOk();
        $this->assertStringStartsWith('2026-11-01 08:05:00', (string) DB::table('email_logs')->value('opened_at'));

        // a time MySQL itself fills in (DEFAULT CURRENT_TIMESTAMP) is UTC as well
        $this->get("/api/t/c/{$log->track_token}?".http_build_query(['u' => 'https://example.com/a', 's' => MessageRenderer::sign('https://example.com/a')]))->assertRedirect();
        $stored = strtotime(DB::table('click_events')->value('created_at').' UTC');
        $this->assertEqualsWithDelta(time(), $stored, 120, 'click_events.created_at must be the UTC clock, not local time');
    }
}
