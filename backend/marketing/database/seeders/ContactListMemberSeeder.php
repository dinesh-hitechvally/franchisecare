<?php

namespace Database\Seeders;

use App\Models\ContactList;

/** contact_list_members: every demo contact is on the Newsletter list */
class ContactListMemberSeeder extends DemoSeeder
{
    public function run(): void
    {
        $biz = $this->companyId();
        $list = ContactList::withoutGlobalScopes()->where(['company_id' => $biz, 'name' => ContactListSeeder::NEWSLETTER])->first();
        foreach (ContactSeeder::PEOPLE as [, , $email, $phone]) {
            ContactSeeder::find($biz, $email, $phone)?->lists()->syncWithoutDetaching([$list->id]);
        }
    }
}
