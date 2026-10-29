<?php

namespace App\Models;

use App\Models\Concerns\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Contact extends Model
{
    use BelongsToBusiness;

    protected $guarded = ['id', 'business_id', 'unsubscribe_token'];

    protected function casts(): array
    {
        return [
            'custom_fields' => 'array',
            'email_subscribed' => 'boolean',
            'sms_subscribed' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(fn ($c) => $c->unsubscribe_token ??= Str::random(40));
    }

    public function lists()
    {
        return $this->belongsToMany(ContactList::class, 'contact_contact_list');
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }
}
