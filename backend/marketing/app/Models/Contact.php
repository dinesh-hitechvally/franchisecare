<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Contact extends Model
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
            'email_subscribed' => 'boolean',
            'sms_subscribed' => 'boolean',
            'email_unsubscribed_at' => 'datetime',
            'sms_unsubscribed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(fn ($c) => $c->unsubscribe_token ??= Str::random(40));
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'contact_tag')->using(ContactTag::class);
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
        return $this->belongsToMany(ContactList::class, 'contact_list_members')->using(ContactListMember::class);
    }

    public function fieldValues()
    {
        return $this->hasMany(ContactFieldValue::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    /** This contact's custom field answers as [field key => value] (also what merge tags use). */
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
     * otherwise an empty value clears the answer (used when a person edits the contact).
     */
    public function setCustom(array $answers, bool $blanksOnly = false): void
    {
        if (! $answers) {
            return;
        }
        $defs = ContactField::withoutGlobalScope('company')->where('company_id', $this->company_id)->whereIn('key', array_keys($answers))->get()->keyBy('key');
        $existing = $this->fieldValues()->pluck('value', 'contact_field_id');

        foreach ($answers as $key => $value) {
            $def = $defs[$key] ?? null;
            if (! $def) {
                continue;
            }
            $value = trim((string) $value);
            if ($value === '') {
                if (! $blanksOnly) {
                    $this->fieldValues()->where('contact_field_id', $def->id)->forceDelete(); // clearing an answer, not deleting a record: keeps (contact, field) free to be filled again
                }

                continue;
            }
            if ($blanksOnly && ($existing[$def->id] ?? '') !== '') {
                continue;
            }
            ContactFieldValue::updateOrCreate(['contact_id' => $this->id, 'contact_field_id' => $def->id], ['value' => $value]);
        }
        $this->unsetRelation('fieldValues');
    }
}
