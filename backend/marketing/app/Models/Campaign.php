<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    use BelongsToCompany, SoftDeletes, Auditable;

    protected $guarded = ['id', 'company_id', 'status', 'started_at', 'completed_at'];

    protected function casts(): array
    {
        return [
            'design' => 'array', 'attachments' => 'array',
            'scheduled_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function list()
    {
        return $this->belongsTo(ContactList::class, 'contact_list_id');
    }

    public function segment()
    {
        return $this->belongsTo(Segment::class);
    }

    public function emailLogs()
    {
        return $this->hasMany(EmailLog::class);
    }

    public function smsLogs()
    {
        return $this->hasMany(SmsLog::class);
    }

    /** The log relation of this campaign's own channel: email campaigns send emails, SMS campaigns send texts. */
    public function logs()
    {
        return $this->type === 'sms' ? $this->smsLogs() : $this->emailLogs();
    }

    /** The model that stores this campaign's messages. */
    public function logModel(): string
    {
        return $this->type === 'sms' ? SmsLog::class : EmailLog::class;
    }

    /** Counts for both channels in one query; logTotals() then picks the ones for the campaign's own channel. */
    public static function logCountSpec(): array
    {
        return [
            'emailLogs as email_logs_count',
            'emailLogs as email_sent' => fn ($q) => $q->where('status', 'sent'),
            'emailLogs as email_failed' => fn ($q) => $q->where('status', 'failed'),
            'emailLogs as email_skipped' => fn ($q) => $q->where('status', 'skipped'),
            'emailLogs as email_logged' => fn ($q) => $q->where('status', 'logged'),
            'emailLogs as email_opened' => fn ($q) => $q->whereNotNull('opened_at'),
            'emailLogs as email_clicked' => fn ($q) => $q->where('clicks', '>', 0),
            'smsLogs as sms_logs_count',
            'smsLogs as sms_sent' => fn ($q) => $q->where('status', 'sent'),
            'smsLogs as sms_failed' => fn ($q) => $q->where('status', 'failed'),
            'smsLogs as sms_skipped' => fn ($q) => $q->where('status', 'skipped'),
            'smsLogs as sms_logged' => fn ($q) => $q->where('status', 'logged'),
        ];
    }

    public function scopeWithLogCounts($q)
    {
        return $q->withCount(self::logCountSpec());
    }

    /** logs_count, sent_count, failed_count, skipped_count, opened_count, clicked_count for this campaign's own channel. */
    public function logTotals(): static
    {
        $sms = $this->type === 'sms';
        $this->setAttribute('logs_count', (int) ($sms ? $this->sms_logs_count : $this->email_logs_count));
        $this->setAttribute('sent_count', (int) ($sms ? $this->sms_sent : $this->email_sent));
        $this->setAttribute('failed_count', (int) ($sms ? $this->sms_failed : $this->email_failed));
        $this->setAttribute('skipped_count', (int) ($sms ? $this->sms_skipped : $this->email_skipped));
        $this->setAttribute('logged_count', (int) ($sms ? $this->sms_logged : $this->email_logged)); // written to the log only, not delivered
        $this->setAttribute('opened_count', $sms ? 0 : (int) $this->email_opened);
        $this->setAttribute('clicked_count', $sms ? 0 : (int) $this->email_clicked);

        return $this->makeHidden(['email_logs_count', 'email_sent', 'email_failed', 'email_skipped', 'email_logged', 'email_opened', 'email_clicked', 'sms_logs_count', 'sms_sent', 'sms_failed', 'sms_skipped', 'sms_logged']);
    }
}
