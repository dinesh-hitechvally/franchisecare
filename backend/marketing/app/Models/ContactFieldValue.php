<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class ContactFieldValue extends Model
{
    use SoftDeletes, Auditable;
    public function auditCompanyId(): ?int
    {
        return Contact::withoutGlobalScopes()->whereKey($this->contact_id)->value('company_id');
    }

    public function auditLabel(): string
    {
        $c = Contact::withoutGlobalScopes()->find($this->contact_id);
        $f = ContactField::withoutGlobalScopes()->find($this->contact_field_id);

        return ($c ? ($c->email ?: $c->phone ?: '#'.$c->id) : '#'.$this->contact_id).' · '.($f?->label ?? 'field #'.$this->contact_field_id);
    }


    protected $guarded = ['id'];

    public function field()
    {
        return $this->belongsTo(ContactField::class, 'contact_field_id');
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }
}
