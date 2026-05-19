<?php

namespace App\Models;

use App\Models\Concerns\HasSimpleAudit;
use Illuminate\Database\Eloquent\Model;

class BookingDetail extends Model
{
    use HasSimpleAudit;

    protected $fillable = [
        'company_id',
        'booking_id',
        'service_id',
        'item_id',
        'price',
        'duration',
    ];

    public function booking()
    {
        return $this->belongsTo(Booking::class);
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
        return BookingDetailAudit::class;
    }

    protected function getAuditForeignKey(): string
    {
        return 'booking_detail_id';
    }

    protected function getAuditExtraColumns(): array
    {
        return [
            'booking_id' => $this->booking_id,
        ];
    }

    protected function getAuditSnapshotColumns(string $actionType): array
    {
        return [
            'service_id' => $this->service_id,
            'item_id' => $this->item_id,
            'price' => $this->price,
            'duration' => $this->duration,
        ];
    }
}
