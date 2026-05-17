<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VersionUpdate extends Model
{
    protected $fillable = [
        'version_number',
        'month',
        'year',
        'changes',
        'release_date',
        'is_published',
        'sort_order',
    ];

    protected $casts = [
        'changes' => 'array',
        'release_date' => 'date',
        'is_published' => 'boolean',
        'year' => 'integer',
        'sort_order' => 'integer',
    ];
}
