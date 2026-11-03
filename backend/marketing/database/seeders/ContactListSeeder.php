<?php

namespace Database\Seeders;

use App\Models\ContactList;

/** contact_lists */
class ContactListSeeder extends DemoSeeder
{
    public const NEWSLETTER = 'Newsletter';

    public function run(): void
    {
        ContactList::withoutGlobalScopes()->firstOrCreate(['company_id' => $this->companyId(), 'name' => self::NEWSLETTER], ['description' => 'Sample list']);
    }
}
