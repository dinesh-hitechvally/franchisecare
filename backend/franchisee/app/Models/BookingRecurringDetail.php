<?php

namespace App\Models;

use App\Models\Concerns\HasSimpleAudit;
use Illuminate\Database\Eloquent\Model;

class BookingRecurringDetail extends Model
{
    use HasSimpleAudit;

    protected $fillable = [
        'company_id',
        'customer_id',
        'recurring_id',
        'item_id',
        'service_id',
        'price',
        'duration',
    ];

    public function bookingRecurring()
    {
        return $this->belongsTo(BookingRecurring::class, 'recurring_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function item()
    {
        return $this->belongsTo(CustomerItem::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    protected function getAuditModelClass(): string
    {
        return BookingRecurringDetailAudit::class;
    }

    protected function getAuditForeignKey(): string
    {
        return 'booking_recurring_detail_id';
    }

    protected function getAuditExtraColumns(): array
    {
        return [
            'recurring_id' => $this->recurring_id,
            'customer_id' => $this->customer_id,
        ];
    }

    protected function getAuditSnapshotColumns(string $actionType): array
    {
        return [
            'item_id' => $this->item_id,
            'service_id' => $this->service_id,
            'price' => $this->price,
            'duration' => $this->duration,
        ];
    }
}
