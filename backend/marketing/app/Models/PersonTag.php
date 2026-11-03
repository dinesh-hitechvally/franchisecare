<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Relations\Pivot;

/** A tag on a person. A pivot model so that tagging/untagging is recorded in the change history. */
class PersonTag extends Pivot
{
    use Auditable;

    protected $table = 'person_tag';

    public $incrementing = false;

    public $timestamps = false;


    public function auditCompanyId(): ?int
    {
        return Person::withoutGlobalScopes()->whereKey($this->person_id)->value('company_id');
    }

    public function auditLabel(): string
    {
        $c = Person::withoutGlobalScopes()->find($this->person_id);
        $t = Tag::withoutGlobalScopes()->find($this->tag_id);

        return ($c ? ($c->email ?: $c->phone ?: trim($c->first_name.' '.$c->last_name)) : '#'.$this->person_id).' → tag '.($t?->name ?? '#'.$this->tag_id);
    }
}
