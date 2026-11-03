<?php

namespace Database\Seeders;

use App\Models\Tag;
use App\Models\Template;
use App\Models\Workflow;

/** workflows: created paused (draft) so seeding never starts sending mail by itself */
class WorkflowSeeder extends DemoSeeder
{
    public function run(): void
    {
        $biz = $this->companyId();
        $welcome = Template::withoutGlobalScopes()->where(['company_id' => $biz, 'name' => TemplateSeeder::WELCOME_EMAIL])->first();
        $web = Tag::withoutGlobalScopes()->where(['company_id' => $biz, 'name' => TagSeeder::WEBSITE_LEAD])->first();

        Workflow::withoutGlobalScopes()->firstOrCreate(['company_id' => $biz, 'name' => 'Welcome new people'], [
            'trigger_type' => 'person_created', 'trigger_config' => [],
            'status' => 'draft',
            'steps' => [
                ['type' => 'send_email', 'template_id' => $welcome->id],
                ['type' => 'wait', 'amount' => 3, 'unit' => 'days'],
                ['type' => 'add_tag', 'tag_id' => $web->id],
            ],
        ]);
    }
}
