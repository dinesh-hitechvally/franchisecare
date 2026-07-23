<?php

namespace App\Models;

use App\Models\Concerns\HasSimpleAudit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BookingRecurring extends Model
{
    use SoftDeletes;
    use HasSimpleAudit;

    protected $fillable = [
        'company_id',
        'customer_id',
        'start_date',
        'repeat_time',
        'frequency',
        'repeat_day',
        'status',
        'auto_extend',
        'total',
        'duration',
        'color',
        'notes',
        'cancelled_date',
        'cancellation_reason',
        'repeat_until',
    ];

    protected $casts = [
        'start_date' => 'date:Y-m-d',
        'total' => 'decimal:2',
        'auto_extend' => 'boolean',
        'cancelled_date' => 'date:Y-m-d',
        'repeat_until' => 'date:Y-m-d',
        'frequency' => 'integer',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function details()
    {
        return $this->hasMany(BookingRecurringDetail::class, 'recurring_id');
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'recurring_id');
    }

    protected function getAuditModelClass(): string
    {
        return BookingRecurringAudit::class;
    }

    protected function getAuditForeignKey(): string
    {
        return 'booking_recurring_id';
    }

    protected function getAuditExtraColumns(): array
    {
        return [
            'customer_id' => $this->customer_id,
        ];
    }

    protected function getAuditSnapshotColumns(string $actionType): array
    {
        return [
            'previous_status' => $actionType === 'updated' ? ($this->getOriginal('status') ?: null) : null,
            'status' => $this->status,
            'start_date' => $this->start_date,
            'repeat_time' => $this->repeat_time,
            'frequency' => $this->frequency,
            'repeat_day' => $this->repeat_day,
            'auto_extend' => (bool) $this->auto_extend,
            'total' => $this->total,
            'duration' => $this->duration,
            'color' => $this->color,
            'notes' => $this->notes,
            'cancelled_date' => $this->cancelled_date,
            'cancellation_reason' => $this->cancellation_reason,
            'repeat_until' => $this->repeat_until,
        ];
    }
}
