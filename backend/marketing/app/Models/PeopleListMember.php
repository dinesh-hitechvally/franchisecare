<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Relations\Pivot;

/** A person's membership of a list. A pivot model so that adding/removing a person is recorded in the change history. */
class PeopleListMember extends Pivot
{
    use Auditable;

    protected $table = 'people_list_members';

    public $incrementing = false;

    public $timestamps = false;


    public function auditCompanyId(): ?int
    {
        return Person::withoutGlobalScopes()->whereKey($this->person_id)->value('company_id');
    }

    public function auditLabel(): string
    {
        $c = Person::withoutGlobalScopes()->find($this->person_id);
        $l = PeopleList::withoutGlobalScopes()->find($this->people_list_id);

        return ($c ? ($c->email ?: $c->phone ?: trim($c->first_name.' '.$c->last_name)) : '#'.$this->person_id).' → list '.($l?->name ?? '#'.$this->people_list_id);
    }
}
