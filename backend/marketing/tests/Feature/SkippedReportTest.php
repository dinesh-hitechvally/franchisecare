<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\EmailLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SkippedReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_people_left_out_are_counted_and_listed_in_the_reports_without_any_log_rows(): void
    {
        $token = $this->postJson('/api/register', [
            'company_name' => 'Acme', 'name' => 'Owner', 'email' => 'o@x.com', 'password' => 'password123', 'password_confirmation' => 'password123',
        ])->assertOk()->json('token');
        $h = ['Authorization' => 'Bearer '.$token, 'Accept' => 'application/json'];

        $this->postJson('/api/contacts', ['email' => 'ok@x.com', 'first_name' => 'Okay', 'is_subscribed' => true], $h)->assertCreated();
        $this->postJson('/api/contacts', ['email' => 'off@x.com', 'first_name' => 'Opted', 'last_name' => 'Out', 'is_subscribed' => false], $h)->assertCreated();
        $this->postJson('/api/contacts', ['phone' => '+61400000001', 'first_name' => 'No', 'last_name' => 'Email', 'is_subscribed' => true], $h)->assertCreated();

        $id = $this->postJson('/api/campaigns', ['name' => 'C', 'type' => 'email', 'subject' => 'Hi', 'body' => '<p>x</p>'], $h)->assertCreated()->json('id');
        $this->postJson("/api/campaigns/$id/send", [], $h)->assertOk();

        // only the one real recipient is in the message log
        $this->assertSame(1, EmailLog::withoutGlobalScopes()->count());
        $this->assertSame(2, Campaign::withoutGlobalScopes()->find($id)->audience_skipped);

        // the campaigns report counts them ...
        $row = collect($this->getJson('/api/reports/campaigns', $h)->assertOk()->json('data'))->firstWhere('id', $id);
        $this->assertSame(2, $row['skipped_count']);

        // ... and the campaign's own report names them with the reason
        $report = $this->getJson("/api/reports/campaigns/$id", $h)->assertOk()->json();
        $this->assertSame(2, $report['stats']['skipped']);
        $skipped = collect($report['problems'])->where('status', 'skipped')->keyBy('name');
        $this->assertSame('Contact unsubscribed', $skipped['Opted Out']['error']);
        $this->assertSame('off@x.com', $skipped['Opted Out']['recipient']);
        $this->assertSame('No email address found for this contact', $skipped['No Email']['error']);
    }
}
