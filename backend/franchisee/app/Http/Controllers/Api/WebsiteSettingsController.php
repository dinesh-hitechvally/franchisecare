<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WebsiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WebsiteSettingsController extends Controller
{
    public function show()
    {
        $companyId = Auth::user()->company_id;
        
        $settings = WebsiteSetting::where('company_id', $companyId)->first();
        
        if (!$settings) {
            // Return defaults
            return response()->json([
                'site_title' => '',
                'tagline' => '',
                'contact_email' => '',
                'enable_online_booking' => true,
                'show_pricing' => true,
                'website_url' => '',
                'meta_title' => '',
                'meta_keywords' => '',
                'meta_description' => '',
            ]);
        }
        
        return response()->json($settings);
    }

    public function update(Request $request)
    {
        $companyId = Auth::user()->company_id;
        
        $validated = $request->validate([
            'site_title' => 'nullable|string|max:255',
            'tagline' => 'nullable|string|max:255',
            'contact_email' => 'nullable|email|max:255',
            'enable_online_booking' => 'boolean',
            'show_pricing' => 'boolean',
            'website_url' => 'nullable|string|max:255',
            'meta_title' => 'nullable|string|max:255',
            'meta_keywords' => 'nullable|string|max:500',
            'meta_description' => 'nullable|string|max:1000',
        ]);
        
        $settings = WebsiteSetting::updateOrCreate(
            ['company_id' => $companyId],
            $validated
        );
        
        return response()->json([
            'message' => 'Website settings saved successfully',
            'data' => $settings,
        ]);
    }
}
