<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Income extends Model
{
    use SoftDeletes;

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
}
