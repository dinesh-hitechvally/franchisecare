<?php

namespace Database\Seeders;

use App\Models\Tag;

/** tags */
class TagSeeder extends DemoSeeder
{
    public const VIP = 'VIP';

    public const WEBSITE_LEAD = 'Website lead';

    public function run(): void
    {
        $biz = $this->companyId();
        Tag::withoutGlobalScopes()->firstOrCreate(['company_id' => $biz, 'name' => self::VIP], ['color' => '#e91e63']);
        Tag::withoutGlobalScopes()->firstOrCreate(['company_id' => $biz, 'name' => self::WEBSITE_LEAD], ['color' => '#00897b']);
    }
}
