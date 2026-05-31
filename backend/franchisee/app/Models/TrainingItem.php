<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingItem extends Model
{
    protected $fillable = [
        'category_id',
        'title',
        'slug',
        'description',
        'content',
        'type', // 'course', 'video', 'document', 'link'
        'thumbnail',
        'video_url',
        'document_url',
        'external_url',
        'duration',
        'duration_minutes',
        'instructor',
        'highlights',
        'sort_order',
        'is_featured',
        'is_active',
    ];

    protected $casts = [
        'highlights' => 'array',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'duration_minutes' => 'integer',
        'sort_order' => 'integer',
    ];

    /**
     * Get the category
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(TrainingCategory::class, 'category_id');
    }

    /**
     * Get user progress records
     */
    public function progress(): HasMany
    {
        return $this->hasMany(TrainingProgress::class, 'training_item_id');
    }

    /**
     * Scope to get active items
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get featured items
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope to filter by type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
