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

        $this->postJson('/api/people', ['email' => 'ok@x.com', 'first_name' => 'Okay', 'is_subscribed' => true], $h)->assertCreated();
        $this->postJson('/api/people', ['email' => 'off@x.com', 'first_name' => 'Opted', 'last_name' => 'Out', 'is_subscribed' => false], $h)->assertCreated();
        $this->postJson('/api/people', ['phone' => '+61400000001', 'first_name' => 'No', 'last_name' => 'Email', 'is_subscribed' => true], $h)->assertCreated();

        $id = $this->postJson('/api/campaigns', ['name' => 'C', 'type' => 'email', 'subject' => 'Hi', 'body' => '<p>x</p>'], $h)->assertCreated()->json('id');
        $this->postJson("/api/campaigns/$id/send", [], $h)->assertOk();

        // only the one real recipient is in the message log
        $this->assertSame(1, EmailLog::withoutGlobalScopes()->count());
        $this->assertSame(1, Campaign::withoutGlobalScopes()->find($id)->audience_skipped); // only the person without an email: the unsubscribed one is simply not sent to

        // the size of the audience is kept when it is sent: people added later do not change it
        $this->assertSame(3, Campaign::withoutGlobalScopes()->find($id)->audience_total);
        $this->postJson('/api/people', ['email' => 'late@x.com', 'is_subscribed' => true], $h)->assertCreated();
        $this->getJson("/api/campaigns/$id", $h)->assertJsonPath('audience_total', 3);

        // the campaigns report counts them ...
        $row = collect($this->getJson('/api/reports/campaigns', $h)->assertOk()->json('data'))->firstWhere('id', $id);
        $this->assertSame(1, $row['skipped_count']);

        // ... and the campaign's own report counts them
        $this->assertSame(1, $this->getJson("/api/reports/campaigns/$id", $h)->assertOk()->json('stats.skipped'));

        // Delivery Reports lists everyone, newest filters first: the sent message, then the person without an email
        $all = $this->getJson("/api/reports/campaigns/$id/deliveries", $h)->assertOk()->json();
        $this->assertSame(2, $all['total']);
        $this->assertSame(['sent', 'skipped'], collect($all['data'])->pluck('status')->all());

        $skipped = $this->getJson("/api/reports/campaigns/$id/deliveries?status=skipped", $h)->json();
        $this->assertSame(1, $skipped['total']);
        $this->assertSame('No Email', $skipped['data'][0]['name']);
        $this->assertSame('No email address found for this person', $skipped['data'][0]['error']);

        $this->assertSame(1, $this->getJson("/api/reports/campaigns/$id/deliveries?status=sent", $h)->json('total'));
        $this->assertSame(0, $this->getJson("/api/reports/campaigns/$id/deliveries?status=failed", $h)->json('total'));
        $this->assertSame('ok@x.com', $this->getJson("/api/reports/campaigns/$id/deliveries?search=ok@", $h)->json('data.0.recipient'));
    }
}
