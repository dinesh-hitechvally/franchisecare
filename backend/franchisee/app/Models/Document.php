<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'user_id',
        'title',
        'description',
        'file_url',
        'file_type',
        'visibility',
        'category',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
