<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class PersonFieldValue extends Model
{
    use SoftDeletes, Auditable;
    public function auditCompanyId(): ?int
    {
        return Person::withoutGlobalScopes()->whereKey($this->person_id)->value('company_id');
    }

    public function auditLabel(): string
    {
        $c = Person::withoutGlobalScopes()->find($this->person_id);
        $f = PersonField::withoutGlobalScopes()->find($this->person_field_id);

        return ($c ? ($c->email ?: $c->phone ?: '#'.$c->id) : '#'.$this->person_id).' · '.($f?->label ?? 'field #'.$this->person_field_id);
    }


    protected $guarded = ['id'];

    public function field()
    {
        return $this->belongsTo(PersonField::class, 'person_field_id');
    }

    public function person()
    {
        return $this->belongsTo(Person::class);
    }
}
