<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\IsSendLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/** One email the platform sent (or tried to). Table: email_logs. */
class EmailLog extends Model
{
    use BelongsToCompany, IsSendLog, SoftDeletes;

    public const CHANNEL = 'email';

    protected $guarded = ['id'];

    protected $appends = ['channel'];

    protected function casts(): array
    {
        return ['sent_at' => 'datetime', 'opened_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::creating(fn ($l) => $l->track_token ??= Str::random(40));
    }

    public function getChannelAttribute(): string
    {
        return self::CHANNEL;
    }
}
