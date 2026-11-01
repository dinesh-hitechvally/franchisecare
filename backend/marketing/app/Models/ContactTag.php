<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Relations\Pivot;

/** A tag on a contact. A pivot model so that tagging/untagging is recorded in the change history. */
class ContactTag extends Pivot
{
    use Auditable;

    protected $table = 'contact_tag';

    public $incrementing = false;

    public $timestamps = false;


    public function auditCompanyId(): ?int
    {
        return Contact::withoutGlobalScopes()->whereKey($this->contact_id)->value('company_id');
    }

    public function auditLabel(): string
    {
        $c = Contact::withoutGlobalScopes()->find($this->contact_id);
        $t = Tag::withoutGlobalScopes()->find($this->tag_id);

        return ($c ? ($c->email ?: $c->phone ?: trim($c->first_name.' '.$c->last_name)) : '#'.$this->contact_id).' → tag '.($t?->name ?? '#'.$this->tag_id);
    }
}
