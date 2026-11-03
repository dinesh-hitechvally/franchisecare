<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

/** One filled-in form: kept so person-style forms have an inbox, not just a person record. */
class FormSubmission extends Model
{
    use BelongsToCompany, SoftDeletes, Auditable;

    public function auditLabel(): string
    {
        return 'Submission to '.(Form::withoutGlobalScopes()->whereKey($this->form_id)->value('name') ?? 'form #'.$this->form_id);
    }


    const UPDATED_AT = null;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['data' => 'array', 'person_existed' => 'boolean']; // null stays null: no person was linked
    }

    public function form()
    {
        return $this->belongsTo(Form::class);
    }

    public function person()
    {
        return $this->belongsTo(Person::class);
    }
}
