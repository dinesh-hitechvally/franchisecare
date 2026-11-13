<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

/** A browser or app that can receive push notifications. The token is a secret of the device: it is never sent to the dashboard. */
class PushDevice extends Model
{
    use Auditable, BelongsToCompany;

    protected $guarded = ['id', 'company_id'];

    protected $hidden = ['token', 'keys'];

    protected function casts(): array
    {
        return ['platform' => \App\Casts\Upper::class, 'status' => \App\Casts\Upper::class, 'keys' => 'array', 'last_seen_at' => 'datetime'];
    }

    /** How a change reads in the history: "Chrome on Windows · ann@example.com". */
    public function auditLabel(): string
    {
        $who = $this->person_id ? $this->person()->withoutGlobalScopes()->value('email') : null;

        return trim(($this->label ?: ucfirst((string) $this->platform)).($who ? ' · '.$who : ' · not linked'));
    }

    public static function hashOf(string $token): string
    {
        return sha1($token);
    }

    public function person()
    {
        return $this->belongsTo(Person::class);
    }

    public function scopeActive($q)
    {
        return $q->where('status', 'ACTIVE');
    }
}
