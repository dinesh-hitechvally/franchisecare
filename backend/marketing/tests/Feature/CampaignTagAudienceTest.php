<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Person;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampaignTagAudienceTest extends TestCase
{
    use RefreshDatabase;

    protected array $h;

    protected function setUp(): void
    {
        parent::setUp();
        $token = $this->postJson('/api/register', [
            'company_name' => 'Acme', 'name' => 'Owner', 'email' => 'o@x.com', 'password' => 'password123', 'password_confirmation' => 'password123',
        ])->assertOk()->json('token');
        $this->h = ['Authorization' => 'Bearer '.$token, 'Accept' => 'application/json'];
    }

    protected function person(string $email, bool $subscribed, ?Tag $tag = null): Person
    {
        $id = $this->postJson('/api/people', ['email' => $email, 'is_subscribed' => $subscribed], $this->h)->assertCreated()->json('id');
        $c = Person::withoutGlobalScopes()->find($id);
        $tag && $c->tags()->attach($tag->id);

        return $c;
    }

    public function test_a_campaign_can_target_a_tag_and_only_its_people_are_counted(): void
    {
        $tagId = $this->postJson('/api/tags', ['name' => 'VIP'], $this->h)->assertCreated()->json('id');
        $tag = Tag::withoutGlobalScopes()->find($tagId);
        $this->person('a@x.com', true, $tag);
        $this->person('b@x.com', false, $tag);
        $this->person('c@x.com', true); // not tagged

        $id = $this->postJson('/api/campaigns', ['name' => 'C', 'type' => 'email', 'subject' => 'Hi', 'body' => 'x', 'tag_id' => $tagId], $this->h)->assertCreated()->assertJsonPath('tag_id', $tagId)->json('id');

        $check = $this->getJson("/api/campaigns/$id/check", $this->h)->assertOk()->json();
        $this->assertSame([2, 1, 1], [$check['audience'], $check['reach'], $check['unsubscribed']]);
        $this->assertSame(2, Campaign::withoutGlobalScopes()->find($id)->audienceQuery()->count());
        $this->assertSame('VIP', collect($this->getJson('/api/campaigns', $this->h)->json('data'))->first()['tag']['name']);
    }

    public function test_only_one_audience_source_is_kept_and_a_tag_from_elsewhere_is_refused(): void
    {
        $tagId = $this->postJson('/api/tags', ['name' => 'VIP'], $this->h)->json('id');
        $listId = $this->postJson('/api/lists', ['name' => 'L'], $this->h)->json('id');

        $this->postJson('/api/campaigns', ['name' => 'C', 'type' => 'sms', 'body' => 'x', 'tag_id' => $tagId, 'people_list_id' => $listId], $this->h)
            ->assertCreated()->assertJsonPath('tag_id', $tagId)->assertJsonPath('people_list_id', null);
        $this->postJson('/api/campaigns', ['name' => 'C', 'type' => 'sms', 'body' => 'x', 'tag_id' => 99999], $this->h)->assertStatus(422);
    }
}
