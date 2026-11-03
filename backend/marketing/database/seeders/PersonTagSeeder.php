<?php

namespace Database\Seeders;

use App\Models\Tag;

/** person_tag: the tags of each demo person (see PersonSeeder::PEOPLE) */
class PersonTagSeeder extends DemoSeeder
{
    public function run(): void
    {
        $biz = $this->companyId();
        $ids = Tag::withoutGlobalScopes()->where('company_id', $biz)->pluck('id', 'name');
        foreach (PersonSeeder::PEOPLE as [, , $email, $phone, , $tags]) {
            PersonSeeder::find($biz, $email, $phone)?->tags()->syncWithoutDetaching(collect($tags)->map(fn ($name) => $ids[$name])->all());
        }
    }
}
