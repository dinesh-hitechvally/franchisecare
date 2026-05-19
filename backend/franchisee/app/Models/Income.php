<?php

namespace App\Models;

use App\Models\Concerns\HasSimpleAudit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Income extends Model
{
    use SoftDeletes;
    use HasSimpleAudit;

    protected $fillable = [
        'company_id',
        'income_category_id',
        'booking_id',
        'title',
        'description',
        'amount',
        'income_date',
        'is_active',
        'recurring_income_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'income_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(IncomeCategory::class, 'income_category_id');
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function recurringIncome()
    {
        return $this->belongsTo(RecurringIncome::class, 'recurring_income_id');
    }

    protected function getAuditModelClass(): string
    {
        return IncomeAudit::class;
    }

    protected function getAuditForeignKey(): string
    {
        return 'income_id';
    }

    protected function getAuditSnapshotColumns(string $actionType): array
    {
        return [
            'income_category_id' => $this->income_category_id,
            'booking_id' => $this->booking_id,
            'title' => $this->title,
            'description' => $this->description,
            'amount' => $this->amount,
            'income_date' => $this->income_date,
            'is_active' => (bool) $this->is_active,
            'recurring_income_id' => $this->recurring_income_id,
        ];
    }
}
