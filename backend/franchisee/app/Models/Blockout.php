<?php

namespace App\Models;

use App\Models\Concerns\HasSimpleAudit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Blockout extends Model
{
    use HasFactory;
    use HasSimpleAudit;

    protected $fillable = [
        'title',
        'location',
        'start_date',
        'start_time',
        'end_date',
        'end_time',
        'recurring_id',
        'notes',
        'active',
        'company_id',
    ];

    protected $casts = [
        'active' => 'boolean',
        'start_date' => 'date:Y-m-d',
        'end_date' => 'date:Y-m-d',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function recurringBlockout()
    {
        return $this->belongsTo(BlockoutRecurring::class, 'recurring_id');
    }

    protected function getAuditModelClass(): string
    {
        return BlockoutAudit::class;
    }

    protected function getAuditForeignKey(): string
    {
        return 'blockout_id';
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
            'recurring_id' => $this->recurring_id,
            'notes' => $this->notes,
            'active' => (bool) $this->active,
        ];
    }
}
