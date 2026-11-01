<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Relations\Pivot;

/** A contact's membership of a list. A pivot model so that adding/removing a person is recorded in the change history. */
class ContactListMember extends Pivot
{
    use Auditable;

    protected $table = 'contact_list_members';

    public $incrementing = false;

    public $timestamps = false;


    public function auditCompanyId(): ?int
    {
        return Contact::withoutGlobalScopes()->whereKey($this->contact_id)->value('company_id');
    }

    public function auditLabel(): string
    {
        $c = Contact::withoutGlobalScopes()->find($this->contact_id);
        $l = ContactList::withoutGlobalScopes()->find($this->contact_list_id);

        return ($c ? ($c->email ?: $c->phone ?: trim($c->first_name.' '.$c->last_name)) : '#'.$this->contact_id).' → list '.($l?->name ?? '#'.$this->contact_list_id);
    }
}
