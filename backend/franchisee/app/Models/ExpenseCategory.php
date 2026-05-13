<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExpenseCategory extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'description',
        'gst_inclusive',
        'is_active',
        'is_system',
    ];

    protected $casts = [
        'gst_inclusive' => 'boolean',
        'is_active' => 'boolean',
        'is_system' => 'boolean',
    ];

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }
}
