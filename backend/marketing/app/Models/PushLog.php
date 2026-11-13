<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\IsSendLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/** One push notification the platform sent (or tried to), to one device. Table: push_logs. "log" as the service means Log only: nothing left the server. */
class PushLog extends Model
{
    use BelongsToCompany, IsSendLog, SoftDeletes;

    public const CHANNEL = 'push';

    protected $guarded = ['id'];

    protected $appends = ['channel'];

    protected function casts(): array
    {
        return ['source' => \App\Casts\Upper::class, 'sent_at' => 'datetime', 'clicked_at' => 'datetime'];
    }

    public function getChannelAttribute(): string
    {
        return self::CHANNEL;
    }

    public function device()
    {
        return $this->belongsTo(PushDevice::class, 'push_device_id');
    }
}
