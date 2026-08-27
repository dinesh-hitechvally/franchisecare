<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $fillable = [
        'name',
        'description',
        'category_id',
        'base_price',
        'duration',
        'status',
        'icon',
        'sort_order',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
        'duration' => 'integer',
        'sort_order' => 'integer',
    ];

    public function category()
    {
        return $this->belongsTo(ServiceCategory::class, 'category_id');
    }
}
