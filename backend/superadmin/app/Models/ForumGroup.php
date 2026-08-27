<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ForumGroup extends Model
{
    protected $fillable = [
        'name',
        'description',
        'permissions',
        'status',
    ];
}
