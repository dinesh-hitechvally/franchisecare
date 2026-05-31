<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainingProgress extends Model
{
    protected $table = 'training_progress';

    protected $fillable = [
        'user_id',
        'training_item_id',
        'status', // 'not_started', 'in_progress', 'completed'
        'progress_percent',
        'started_at',
        'completed_at',
        'last_accessed_at',
    ];

    protected $casts = [
        'progress_percent' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'last_accessed_at' => 'datetime',
    ];

    /**
     * Get the user
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the training item
     */
    public function trainingItem(): BelongsTo
    {
        return $this->belongsTo(TrainingItem::class);
    }

    /**
     * Check if completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }
}
