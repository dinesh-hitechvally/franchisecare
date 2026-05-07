<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ForumGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'type',
        'icon',
        'color',
        'created_by',
        'is_public',
    ];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    protected $appends = ['members_count', 'threads_count'];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function members()
    {
        return $this->belongsToMany(User::class, 'forum_group_members', 'group_id', 'user_id')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function threads()
    {
        return $this->hasMany(ForumThread::class, 'group_id');
    }

    public function isMember($userId)
    {
        return $this->members()->where('user_id', $userId)->exists();
    }

    public function getMembersCountAttribute()
    {
        return $this->members()->count();
    }

    public function getThreadsCountAttribute()
    {
        return $this->threads()->count();
    }
}
