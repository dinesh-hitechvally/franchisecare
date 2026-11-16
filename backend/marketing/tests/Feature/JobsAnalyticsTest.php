<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\EmailLog;
use App\Models\Company;
use App\Models\Integration;
use App\Models\IntegrationSync;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** Analytics → Jobs & queue: pending, completed and failed work of every kind, and the raw queue. */
class JobsAnalyticsTest extends TestCase
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

    public function test_every_kind_of_work_is_counted_as_pending_completed_or_failed(): void
    {
        $this->getJson('/api/lists', $this->h)->assertOk(); // signs the test in: the rows below belong to the company
        $company = Company::first()->id;
        $integration = (new Integration)->forceFill(['company_id' => $company, 'provider' => 'wix', 'name' => 'Test', 'store_url' => '', 'status' => 'connected', 'secrets' => [], 'settings' => []]);
        $integration->save();
        foreach (['RUNNING', 'SUCCESS', 'SUCCESS', 'FAILED'] as $status) {
            (new IntegrationSync)->forceFill(['company_id' => $company, 'integration_id' => $integration->id, 'trigger' => 'MANUAL', 'full' => false, 'status' => $status, 'started_at' => now()])->save();
        }
        Campaign::create(['name' => 'Later', 'type' => 'sms', 'body' => 'hi'])->forceFill(['status' => 'SCHEDULED'])->save();
        Campaign::create(['name' => 'Done', 'type' => 'sms', 'body' => 'hi'])->forceFill(['status' => 'SENT'])->save();
        foreach (['QUEUED', 'QUEUED', 'SENT', 'FAILED'] as $status) {
            EmailLog::create(['recipient' => 'a@x.com', 'status' => $status]);
        }

        $groups = collect($this->getJson('/api/analytics/jobs', $this->h)->assertOk()->json('groups'))->keyBy('key');
        $this->assertSame([1, 2, 1], [$groups['syncs']['pending'], $groups['syncs']['completed'], $groups['syncs']['failed']]);
        $this->assertSame([1, 1, 0], [$groups['campaigns']['pending'], $groups['campaigns']['completed'], $groups['campaigns']['failed']]);
        $this->assertSame([2, 1, 1], [$groups['messages']['pending'], $groups['messages']['completed'], $groups['messages']['failed']]);
        $this->assertSame(['email', 2, 1, 1], [$groups['messages']['by_channel'][0]['channel'], $groups['messages']['by_channel'][0]['pending'], $groups['messages']['by_channel'][0]['completed'], $groups['messages']['by_channel'][0]['failed']]);
        $this->assertSame(['pending', 'completed', 'failed'], array_keys(array_intersect_key($groups['workflows'], array_flip(['pending', 'completed', 'failed']))));
    }

    public function test_the_queue_shows_waiting_and_failed_jobs_and_says_when_no_worker_takes_them(): void
    {
        config(['queue.default' => 'database']);
        $payload = fn ($class) => json_encode(['displayName' => 'App\\Jobs\\'.$class]);
        DB::table('jobs')->insert([
            ['queue' => 'default', 'payload' => $payload('SyncIntegrationJob'), 'attempts' => 0, 'reserved_at' => null, 'available_at' => time() - 600, 'created_at' => time() - 600],
            ['queue' => 'default', 'payload' => $payload('SendMessageJob'), 'attempts' => 0, 'reserved_at' => null, 'available_at' => time() - 600, 'created_at' => time() - 600],
            ['queue' => 'default', 'payload' => $payload('SendMessageJob'), 'attempts' => 1, 'reserved_at' => time(), 'available_at' => time(), 'created_at' => time()],
            ['queue' => 'default', 'payload' => $payload('SendMessageJob'), 'attempts' => 0, 'reserved_at' => null, 'available_at' => time() + 3600, 'created_at' => time()],
        ]);
        DB::table('failed_jobs')->insert(['uuid' => 'u1', 'connection' => 'database', 'queue' => 'default', 'payload' => $payload('SendMessageJob'), 'exception' => 'boom', 'failed_at' => now()]);

        $q = $this->getJson('/api/analytics/jobs', $this->h)->assertOk()->json('queue');
        $this->assertSame([2, 1, 1, 1], [$q['pending'], $q['processing'], $q['delayed'], $q['failed']]);
        $this->assertGreaterThanOrEqual(9, $q['oldest_waiting_minutes']);
        $this->assertStringContainsString('queue:work', $q['worker_hint']);
        $byJob = collect($q['by_job'])->keyBy('job');
        $this->assertSame([1, 0], [$byJob['SyncIntegrationJob']['pending'], $byJob['SyncIntegrationJob']['failed']]);
        $this->assertSame(1, $byJob['SendMessageJob']['failed']);
    }
}
