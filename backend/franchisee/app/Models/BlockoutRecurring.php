<?php

namespace App\Models;

use App\Models\Concerns\HasSimpleAudit;
use Illuminate\Database\Eloquent\Model;

class BlockoutRecurring extends Model
{
    use HasSimpleAudit;

    protected $fillable = [
        'company_id',
        'title',
        'location',
        'start_date',
        'start_time',
        'end_date',
        'end_time',
        'repeat_every',
        'repeat_on',
        'repeat_until',
        'notes',
        'active',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'repeat_until' => 'date',
        'active' => 'boolean',
    ];

    public function blockouts()
    {
        return $this->hasMany(Blockout::class, 'recurring_id');
    }

    protected function getAuditModelClass(): string
    {
        return BlockoutRecurringAudit::class;
    }

    protected function getAuditForeignKey(): string
    {
        return 'blockout_recurring_id';
    }

    protected function getAuditSnapshotColumns(string $actionType): array
    {
        return [
            'title' => $this->title,
            'location' => $this->location,
            'start_date' => $this->start_date,
            'start_time' => $this->start_time,
            'end_date' => $this->end_date,
            'end_time' => $this->end_time,
            'repeat_every' => $this->repeat_every,
            'repeat_on' => $this->repeat_on,
            'repeat_until' => $this->repeat_until,
            'notes' => $this->notes,
            'active' => (bool) $this->active,
        ];
    }
}
