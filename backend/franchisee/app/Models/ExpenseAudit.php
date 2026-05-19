<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExpenseAudit extends Model
{
    protected $fillable = [
        'expense_id',
        'company_id',
        'action_type',
        'action_at',
        'expense_category_id',
        'title',
        'description',
        'amount',
        'expense_date',
        'is_active',
        'recurring_expense_id',
        'performed_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'expense_date' => 'date',
        'is_active' => 'boolean',
    ];
}
