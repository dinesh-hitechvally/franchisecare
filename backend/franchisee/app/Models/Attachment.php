<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Attachment extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'attachments';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'company_id',
        'file_name',
        'module',
        'file_path',
        'origional_name',   // note: typo in migration; keep as is
        'file_size',
        'mime_type',
        'extension',
        'uploaded_by',
        'description',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'metadata' => 'array',
        'file_size' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the user who uploaded this file.
     */
    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Get the company that owns this attachment (if multi‑tenant).
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the full URL to the file (for public storage).
     *
     * @return string
     */
    public function getFileUrlAttribute(): string
    {
        return Storage::url($this->file_path);
    }

    /**
     * Scope a query to only include attachments for a given module.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $module
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForModule($query, string $module)
    {
        return $query->where('module', $module);
    }

    /**
     * Scope a query to only include attachments for a specific company.
     */
    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }
}
