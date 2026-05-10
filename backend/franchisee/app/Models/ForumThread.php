<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ForumThread extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'content',
        'author_id',
        'topic',
        'group_id',
        'is_pinned',
        'likes_count',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
        'likes_count' => 'integer',
    ];

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function comments()
    {
        return $this->hasMany(ForumComment::class, 'thread_id');
    }

    public function group()
    {
        return $this->belongsTo(ForumGroup::class, 'group_id');
    }

    public function likedByUsers()
    {
        return $this->belongsToMany(User::class, 'forum_thread_likes', 'forum_thread_id', 'user_id')
            ->withTimestamps();
    }
}
