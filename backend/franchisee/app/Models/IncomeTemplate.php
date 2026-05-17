<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IncomeTemplate extends Model
{
    protected $fillable = [
        'company_id',
        'income_title_template',
        'invoice_statement_template',
    ];
}
