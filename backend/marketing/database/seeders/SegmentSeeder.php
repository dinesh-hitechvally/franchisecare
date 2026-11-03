<?php

namespace Database\Seeders;

use App\Models\Segment;
use App\Models\Tag;

/** segments */
class SegmentSeeder extends DemoSeeder
{
    public function run(): void
    {
        $biz = $this->companyId();
        $vip = Tag::withoutGlobalScopes()->where(['company_id' => $biz, 'name' => TagSeeder::VIP])->first();

        Segment::withoutGlobalScopes()->firstOrCreate(['company_id' => $biz, 'name' => 'Gmail VIPs'], [
            'description' => 'VIP-tagged contacts on gmail',
            'match' => 'all',
            'rules' => [['field' => 'tag', 'op' => 'in', 'value' => $vip->id], ['field' => 'email_domain', 'op' => 'equals', 'value' => 'gmail.com']],
        ]);
    }
}
