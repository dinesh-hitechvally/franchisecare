<?php

namespace Database\Seeders;

use App\Models\Tag;

/** contact_tag: the tags of each demo contact (see ContactSeeder::PEOPLE) */
class ContactTagSeeder extends DemoSeeder
{
    public function run(): void
    {
        $biz = $this->companyId();
        $ids = Tag::withoutGlobalScopes()->where('company_id', $biz)->pluck('id', 'name');
        foreach (ContactSeeder::PEOPLE as [, , $email, $phone, , $tags]) {
            ContactSeeder::find($biz, $email, $phone)?->tags()->syncWithoutDetaching(collect($tags)->map(fn ($name) => $ids[$name])->all());
        }
    }
}
