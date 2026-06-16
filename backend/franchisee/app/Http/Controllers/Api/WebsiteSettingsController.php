<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Contracts\Services\WebsiteSettingsServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebsiteSettingsController extends Controller
{
    public function __construct(
        protected WebsiteSettingsServiceInterface $websiteSettingsService
    ) {}

    public function show(Request $request): JsonResponse
    {
        return response()->json($this->websiteSettingsService->show($request->user()));
    }

    public function update(Request $request): JsonResponse
    {
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

        return response()->json($this->websiteSettingsService->update($request->user(), $validated));
    }
}
