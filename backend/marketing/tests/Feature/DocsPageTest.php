<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_root_address_shows_the_single_page_documentation(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        foreach (['FranchiseCare Marketing', 'Quick start', 'Key concepts', 'Endpoint reference', 'Webhooks', 'Troubleshooting', 'queue:work'] as $needle) {
            $this->assertStringContainsString($needle, $html);
        }
        // generated from the app, so it cannot drift: real routes, triggers, events, merge tags and tables
        $this->assertStringContainsString('/api/people', $html);
        $this->assertStringContainsString('/api/reports/campaigns/{campaign}/deliveries', $html);
        $this->assertStringContainsString('person_created', $html);
        $this->assertStringContainsString('person.created', $html);
        $this->assertStringContainsString('&#123;&#123;first_name&#125;&#125;', $html);
        $this->assertStringContainsString('<code>people_list_members</code>', $html);
        // nothing of the template leaked
        $this->assertStringNotContainsString('@verbatim', $html);
        $this->assertStringNotContainsString('@foreach', $html);
    }

    public function test_api_clients_asking_for_json_still_get_the_small_json_answer(): void
    {
        $this->getJson('/')->assertOk()->assertJsonPath('app', 'FranchiseCare Marketing API')->assertJsonPath('docs', url('/'));
    }

    public function test_the_page_can_be_switched_off_and_then_only_the_json_answer_remains(): void
    {
        config(['app.docs_enabled' => false]);

        $this->get('/')->assertOk()->assertJson(['app' => 'FranchiseCare Marketing API'])->assertJsonMissing(['docs' => url('/')]);
    }
}
