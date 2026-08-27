<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ForumCategory extends Model
{
    protected $fillable = [
        'name',
        'description',
        'status',
    ];

    public function topics(): HasMany
    {
        return $this->hasMany(ForumTopic::class, 'category_id');
    }

    public function posts(): HasMany
    {
        return $this->hasMany(ForumPost::class, 'category_id');
    }
}
