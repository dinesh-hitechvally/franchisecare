<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RecurringIncome extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'income_category_id',
        'start_date',
        'frequency',
        'status',
        'auto_extend',
        'total',
        'notes',
        'cancelled_date',
        'cancellation_reason',
        'repeat_until',
    ];

    protected $casts = [
        'start_date' => 'date',
        'total' => 'decimal:2',
        'auto_extend' => 'boolean',
        'cancelled_date' => 'date',
        'repeat_until' => 'date',
    ];

    public function category()
    {
        return $this->belongsTo(IncomeCategory::class, 'income_category_id');
    }

    public function incomes()
    {
        return $this->hasMany(Income::class, 'recurring_income_id');
    }
}
