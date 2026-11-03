<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_root_address_is_a_hub_linking_the_three_documents(): void
    {
        $html = $this->get('/')->assertOk()->getContent();

        foreach (['/docs/manual', '/docs/developer', '/docs/api', 'Project manual', 'Install &amp; code'] as $needle) {
            $this->assertStringContainsString($needle, $html);
        }
    }

    public function test_the_manual_explains_the_screens_in_plain_language_and_lists_the_triggers_from_the_app(): void
    {
        $html = $this->get('/docs/manual')->assertOk()->getContent();

        foreach (['Getting started', 'Screens at a glance', 'Key concepts', 'Delivery Reports', 'Common problems', 'API keys'] as $needle) {
            $this->assertStringContainsString($needle, $html);
        }
        $this->assertStringContainsString('Person is added', $html);               // a trigger, read from Automation::TRIGGERS
        $this->assertStringContainsString('&#123;&#123;first_name&#125;&#125;', $html); // merge tags, read from MessageRenderer
        $this->assertStringNotContainsString('@verbatim', $html);
        $this->assertStringNotContainsString('/api/people', $html); // technical detail belongs to the other documents
    }

    public function test_the_developer_guide_covers_installation_the_code_and_the_data_model(): void
    {
        $html = $this->get('/docs/developer')->assertOk()->getContent();

        foreach (['Requirements', 'composer install', 'php artisan migrate --seed', 'queue:work', 'Architecture', 'Code map', 'Extending it', 'DOCS_ENABLED'] as $needle) {
            $this->assertStringContainsString($needle, $html);
        }
        $this->assertStringContainsString('<code>people_list_members</code>', $html); // tables, read from the database
        $this->assertStringContainsString('<code>person</code>', $html);              // history types, read from History::TYPES
        $this->assertStringNotContainsString('@foreach', $html);
    }

    public function test_the_api_documentation_is_about_api_keys_and_lists_every_endpoint(): void
    {
        $html = $this->get('/docs/api')->assertOk()->getContent();

        foreach (['Creating an API key', 'Settings → API keys', 'Create key', 'shown once', '/api/v1/people', 'Bearer', 'X-Webhook-Signature', 'person.created'] as $needle) {
            $this->assertStringContainsString($needle, $html);
        }
        // the endpoint reference is read from the running app
        $this->assertStringContainsString('/api/reports/campaigns/{campaign}/deliveries', $html);
        $this->assertStringContainsString('/api/api-keys', $html);
        $this->assertStringNotContainsString('@verbatim', $html);
    }

    public function test_api_clients_asking_for_json_at_the_root_still_get_the_small_json_answer(): void
    {
        $this->getJson('/')->assertOk()->assertJsonPath('app', 'FranchiseCare Marketing API')->assertJsonPath('docs', url('/'));
    }

    public function test_the_documents_can_be_switched_off(): void
    {
        config(['app.docs_enabled' => false]);

        $this->get('/')->assertOk()->assertJson(['app' => 'FranchiseCare Marketing API'])->assertJsonMissing(['docs' => url('/')]);
        foreach (['/docs/manual', '/docs/developer', '/docs/api'] as $path) {
            $this->get($path)->assertNotFound();
        }
    }
}
