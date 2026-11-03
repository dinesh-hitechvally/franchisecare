<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Person extends Model
{
    use BelongsToCompany, SoftDeletes, Auditable;
    public function auditLabel(): string
    {
        return $this->email ?: ($this->phone ?: (trim($this->first_name.' '.$this->last_name) ?: '#'.$this->getKey()));
    }


    protected $guarded = ['id', 'company_id', 'unsubscribe_token'];

    protected function casts(): array
    {
        return [
            'is_subscribed' => 'boolean',
            'subscribed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function ($c) {
            $c->unsubscribe_token ??= Str::random(40);
            // a new person is subscribed unless said otherwise; note when
            $c->is_subscribed ??= true;
            $c->subscribed_at ??= $c->is_subscribed ? now() : null;
        });
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'person_tag')->using(PersonTag::class);
    }

    public function emailLogs()
    {
        return $this->hasMany(EmailLog::class);
    }

    public function smsLogs()
    {
        return $this->hasMany(SmsLog::class);
    }

    public function lists()
    {
        return $this->belongsToMany(PeopleList::class, 'people_list_members')->using(PeopleListMember::class);
    }

    public function fieldValues()
    {
        return $this->hasMany(PersonFieldValue::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    /** This person's custom field answers as [field key => value] (also what merge tags use). */
    public function customMap(): array
    {
        if (! $this->exists) {
            return [];
        }
        $this->loadMissing('fieldValues.field');

        return $this->fieldValues->filter(fn ($v) => $v->field)->mapWithKeys(fn ($v) => [$v->field->key => $v->value])->all();
    }

    /**
     * Saves answers for custom fields, given as [key => value]. Keys that are not defined fields are skipped.
     * $blanksOnly keeps what we already know (used when a public form is submitted again);
     * otherwise an empty value clears the answer (used when a person edits the person).
     */
    public function setCustom(array $answers, bool $blanksOnly = false): void
    {
        if (! $answers) {
            return;
        }
        $defs = PersonField::withoutGlobalScope('company')->where('company_id', $this->company_id)->whereIn('key', array_keys($answers))->get()->keyBy('key');
        $existing = $this->fieldValues()->pluck('value', 'person_field_id');

        foreach ($answers as $key => $value) {
            $def = $defs[$key] ?? null;
            if (! $def) {
                continue;
            }
            $value = trim((string) $value);
            if ($value === '') {
                if (! $blanksOnly) {
                    $this->fieldValues()->where('person_field_id', $def->id)->forceDelete(); // clearing an answer, not deleting a record: keeps (person, field) free to be filled again
                }

                continue;
            }
            if ($blanksOnly && ($existing[$def->id] ?? '') !== '') {
                continue;
            }
            PersonFieldValue::updateOrCreate(['person_id' => $this->id, 'person_field_id' => $def->id], ['value' => $value]);
        }
        $this->unsetRelation('fieldValues');
    }
}
