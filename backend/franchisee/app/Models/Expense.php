<?php

namespace App\Models;

use App\Models\Concerns\HasSimpleAudit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{
    use SoftDeletes;
    use HasSimpleAudit;

    protected $fillable = [
        'company_id',
        'expense_category_id',
        'title',
        'description',
        'amount',
        'expense_date',
        'is_active',
        'recurring_expense_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'expense_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    public function recurringExpense()
    {
        return $this->belongsTo(RecurringExpense::class, 'recurring_expense_id');
    }

    protected function getAuditModelClass(): string
    {
        return ExpenseAudit::class;
    }

    protected function getAuditForeignKey(): string
    {
        return 'expense_id';
    }

    protected function getAuditSnapshotColumns(string $actionType): array
    {
        return [
            'expense_category_id' => $this->expense_category_id,
            'title' => $this->title,
            'description' => $this->description,
            'amount' => $this->amount,
            'expense_date' => $this->expense_date,
            'is_active' => (bool) $this->is_active,
            'recurring_expense_id' => $this->recurring_expense_id,
        ];
    }
}
