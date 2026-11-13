<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\IsSendLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/** One WhatsApp, Messenger or X message the platform sent (or tried to). Table: social_logs; `platform` is the channel. */
class SocialLog extends Model
{
    use BelongsToCompany, IsSendLog, SoftDeletes;

    /** The campaign types that send through a connected social account. */
    public const PLATFORMS = ['whatsapp', 'messenger', 'x'];

    protected $guarded = ['id'];

    protected $appends = ['channel'];

    protected function casts(): array
    {
        return ['source' => \App\Casts\Upper::class, 'platform' => \App\Casts\Upper::class, 'sent_at' => 'datetime'];
    }

    public function getChannelAttribute(): string
    {
        return (string) $this->platform;
    }
}
