<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppVersion extends Model
{
    protected $fillable = [
        'version',
        'title',
        'description',
        'logout_required',
        'refresh_required',
    ];

    protected $casts = [
        'logout_required' => 'boolean',
        'refresh_required' => 'boolean',
    ];
}
