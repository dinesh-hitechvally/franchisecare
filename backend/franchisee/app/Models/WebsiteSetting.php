<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebsiteSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'site_title',
        'tagline',
        'contact_email',
        'enable_online_booking',
        'show_pricing',
        'website_url',
        'meta_title',
        'meta_keywords',
        'meta_description',
    ];

    protected $casts = [
        'enable_online_booking' => 'boolean',
        'show_pricing' => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
