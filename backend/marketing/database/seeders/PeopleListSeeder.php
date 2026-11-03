<?php

namespace Database\Seeders;

use App\Models\PeopleList;

/** people_lists */
class PeopleListSeeder extends DemoSeeder
{
    public const NEWSLETTER = 'Newsletter';

    public function run(): void
    {
        PeopleList::withoutGlobalScopes()->firstOrCreate(['company_id' => $this->companyId(), 'name' => self::NEWSLETTER], ['description' => 'Sample list']);
    }
}
