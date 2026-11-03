<?php

namespace Database\Seeders;

use App\Models\PeopleList;

/** people_list_members: every demo person is on the Newsletter list */
class PeopleListMemberSeeder extends DemoSeeder
{
    public function run(): void
    {
        $biz = $this->companyId();
        $list = PeopleList::withoutGlobalScopes()->where(['company_id' => $biz, 'name' => PeopleListSeeder::NEWSLETTER])->first();
        foreach (PersonSeeder::PEOPLE as [, , $email, $phone]) {
            PersonSeeder::find($biz, $email, $phone)?->lists()->syncWithoutDetaching([$list->id]);
        }
    }
}
