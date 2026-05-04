<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Blockout extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'location',
        'start_date',
        'start_time',
        'end_date',
        'end_time',
        'is_recurring',
        'repeat_every',
        'repeat_on',
        'repeat_until',
        'notes',
        'active',
        'company_id',
    ];

    protected $casts = [
        'is_recurring' => 'boolean',
        'active' => 'boolean',
        'start_date' => 'date',
        'end_date' => 'date',
        'repeat_until' => 'date',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
