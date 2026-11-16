<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    use BelongsToCompany, SoftDeletes, Auditable;

    protected $guarded = ['id', 'company_id', 'status', 'started_at', 'completed_at', 'audience_skipped', 'audience_total'];

    protected function casts(): array
    {
        return [
            'type' => \App\Casts\Upper::class, 'push_platform' => \App\Casts\Upper::class,
            'design' => 'array', 'attachments' => 'array', 'push_all_devices' => 'boolean',
            'scheduled_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function list()
    {
        return $this->belongsTo(PeopleList::class, 'people_list_id');
    }

    public function segment()
    {
        return $this->belongsTo(Segment::class);
    }

    public function tag()
    {
        return $this->belongsTo(Tag::class);
    }

    public function emailLogs()
    {
        return $this->hasMany(EmailLog::class);
    }

    public function smsLogs()
    {
        return $this->hasMany(SmsLog::class);
    }

    public function pushLogs()
    {
        return $this->hasMany(PushLog::class);
    }

    public function socialLogs()
    {
        return $this->hasMany(SocialLog::class);
    }

    /** WhatsApp, Messenger or X: sent through a connected social account, to an id the person has on that platform. */
    public function isSocial(): bool
    {
        return in_array($this->type, SocialLog::PLATFORMS, true);
    }

    /** The connected account (Integration) this social campaign sends through. */
    public function socialAccount(): ?Integration
    {
        return $this->social_account_id ? Integration::withoutGlobalScope('company')->where('company_id', $this->company_id)->whereKey($this->social_account_id)->first() : null;
    }

    /** The log relation of this campaign's own channel: email campaigns send emails, SMS campaigns send texts, push campaigns send notifications. */
    public function logs()
    {
        return match (true) {
            $this->type === 'sms' => $this->smsLogs(),
            $this->type === 'push' => $this->pushLogs(),
            $this->isSocial() => $this->socialLogs(),
            default => $this->emailLogs(),
        };
    }

    /** Narrows a people query to those this campaign can reach ($has = true) or cannot reach ($has = false) for lack of an address on its channel. */
    public function whereAddress($query, bool $has)
    {
        if ($this->type === 'email' || $this->type === 'sms' || $this->type === 'whatsapp') {
            $col = $this->type === 'email' ? 'people.email' : 'people.phone';

            return $has ? $query->whereNotNull($col)->where($col, '!=', '') : $query->where(fn ($w) => $w->whereNull($col)->orWhere($col, ''));
        }
        $path = 'people.handles->'.$this->type; // messenger, x: the id stored on the person

        // reachable = an id typed on the person, or one recorded as a source on that platform
        $reachable = fn ($w) => $w->where(fn ($h) => $h->whereNotNull($path)->where($path, '!=', ''))
            ->orWhereExists(fn ($s) => $s->selectRaw('1')->from('person_sources')->whereColumn('person_sources.person_id', 'people.id')->where('person_sources.platform', $this->type)->where('person_sources.external_id', '!=', ''));

        return $has ? $query->where($reachable) : $query->whereNot($reachable);
    }

    /** Everyone this campaign goes to (before unsubscribed / missing-address people are left out): its segment, its list, or all people. */
    public function audienceQuery()
    {
        if ($this->segment_id && ($segment = Segment::withoutGlobalScope('company')->where('company_id', $this->company_id)->find($this->segment_id))) {
            return \App\Services\SegmentQuery::forSegment($segment);
        }

        if ($this->tag_id) {
            return Person::withoutGlobalScope('company')->where('company_id', $this->company_id)
                ->whereHas('tags', fn ($q) => $q->where('tags.id', $this->tag_id));
        }

        $query = $this->people_list_id
            ? Person::withoutGlobalScope('company')->whereHas('lists', fn ($q) => $q->where('people_lists.id', $this->people_list_id))
            : Person::withoutGlobalScope('company');

        $query->where('company_id', $this->company_id);
        // "All people with a device" (a push campaign with no list, segment or tag): the condition IS having a device, so people without one are not part of the audience
        // (they are not "skipped"). With a list, segment or tag, a person without a device is part of the audience and is counted as skipped.
        if ($this->type === 'push' && ! $this->people_list_id) {
            $query->whereHas('pushDevices', fn ($q) => $q->where('status', 'ACTIVE'));
        }

        return $query;
    }

    /** A push campaign to every registered device (linked to a person or not), not to an audience of people. */
    public function toAllDevices(): bool
    {
        return $this->type === 'push' && (bool) $this->push_all_devices;
    }

    /** The model that stores this campaign's messages. */
    public function logModel(): string
    {
        return match (true) {
            $this->type === 'sms' => SmsLog::class,
            $this->type === 'push' => PushLog::class,
            $this->isSocial() => SocialLog::class,
            default => EmailLog::class,
        };
    }

    /** Counts for both channels in one query; logTotals() then picks the ones for the campaign's own channel. */
    public static function logCountSpec(): array
    {
        return [
            'emailLogs as email_logs_count',
            'emailLogs as email_sent' => fn ($q) => $q->where('status', 'SENT'),
            'emailLogs as email_failed' => fn ($q) => $q->where('status', 'FAILED'),
            'emailLogs as email_skipped' => fn ($q) => $q->where('status', 'SKIPPED'),
            'emailLogs as email_logged' => fn ($q) => $q->where('status', 'LOGGED'),
            'emailLogs as email_opened' => fn ($q) => $q->whereNotNull('opened_at'),
            'emailLogs as email_clicked' => fn ($q) => $q->where('clicks', '>', 0),
            'smsLogs as sms_logs_count',
            'smsLogs as sms_sent' => fn ($q) => $q->where('status', 'SENT'),
            'smsLogs as sms_failed' => fn ($q) => $q->where('status', 'FAILED'),
            'smsLogs as sms_skipped' => fn ($q) => $q->where('status', 'SKIPPED'),
            'smsLogs as sms_logged' => fn ($q) => $q->where('status', 'LOGGED'),
            'pushLogs as push_logs_count',
            'pushLogs as push_sent' => fn ($q) => $q->where('status', 'SENT'),
            'pushLogs as push_failed' => fn ($q) => $q->where('status', 'FAILED'),
            'pushLogs as push_skipped' => fn ($q) => $q->where('status', 'SKIPPED'),
            'pushLogs as push_logged' => fn ($q) => $q->where('status', 'LOGGED'),
            'pushLogs as push_clicked' => fn ($q) => $q->where('clicks', '>', 0),
            'socialLogs as social_logs_count',
            'socialLogs as social_sent' => fn ($q) => $q->where('status', 'SENT'),
            'socialLogs as social_failed' => fn ($q) => $q->where('status', 'FAILED'),
            'socialLogs as social_skipped' => fn ($q) => $q->where('status', 'SKIPPED'),
            'socialLogs as social_logged' => fn ($q) => $q->where('status', 'LOGGED'),
        ];
    }

    public function scopeWithLogCounts($q)
    {
        return $q->withCount(self::logCountSpec());
    }

    /** logs_count, sent_count, failed_count, skipped_count, opened_count, clicked_count for this campaign's own channel. */
    public function logTotals(): static
    {
        $ch = in_array($this->type, ['sms', 'push'], true) ? $this->type : ($this->isSocial() ? 'social' : 'email'); // the counts of this campaign's own channel
        $n = fn (string $k) => (int) $this->getAttribute($ch.'_'.$k);
        $sms = in_array($ch, ['sms', 'social'], true); // no opens or clicks to count
        $this->setAttribute('logs_count', $n('logs_count'));
        $this->setAttribute('sent_count', $n('sent'));
        $this->setAttribute('failed_count', $n('failed'));
        $this->setAttribute('skipped_count', $n('skipped') + (int) $this->audience_skipped); // stopped-before-sending messages are logged, people left out at send time are only counted
        $this->setAttribute('logged_count', $n('logged')); // written to the log only, not delivered
        $this->setAttribute('opened_count', $ch === 'email' ? (int) $this->email_opened : 0);
        $this->setAttribute('clicked_count', $sms ? 0 : $n('clicked'));

        return $this->makeHidden(['social_logs_count', 'social_sent', 'social_failed', 'social_skipped', 'social_logged', 'push_logs_count', 'push_sent', 'push_failed', 'push_skipped', 'push_logged', 'push_clicked', 'email_logs_count', 'email_sent', 'email_failed', 'email_skipped', 'email_logged', 'email_opened', 'email_clicked', 'sms_logs_count', 'sms_sent', 'sms_failed', 'sms_skipped', 'sms_logged']);
    }
}
