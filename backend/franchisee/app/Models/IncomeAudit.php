<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IncomeAudit extends Model
{
    protected $fillable = [
        'income_id',
        'company_id',
        'action_type',
        'action_at',
        'income_category_id',
        'booking_id',
        'title',
        'description',
        'amount',
        'income_date',
        'is_active',
        'recurring_income_id',
        'performed_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'income_date' => 'date',
        'is_active' => 'boolean',
    ];
}
