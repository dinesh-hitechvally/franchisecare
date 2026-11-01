<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\IsSendLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/** One SMS the platform sent (or tried to). Table: sms_logs. "log" as the service means Log only: nothing left the server. */
class SmsLog extends Model
{
    use BelongsToCompany, IsSendLog, SoftDeletes;

    public const CHANNEL = 'sms';

    protected $guarded = ['id'];

    protected $appends = ['channel'];

    protected function casts(): array
    {
        return ['sent_at' => 'datetime'];
    }

    public function getChannelAttribute(): string
    {
        return self::CHANNEL;
    }
}
