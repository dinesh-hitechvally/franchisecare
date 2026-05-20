<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Document extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'description',
        'file_path',
        'file_name',
        'file_size',
        'file_type',
        'category',
        'status',
    ];

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
