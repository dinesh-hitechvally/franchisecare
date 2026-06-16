<?php

namespace App\Services;

use App\Contracts\Services\WebsiteSettingsServiceInterface;
use App\Models\User;
use App\Models\WebsiteSetting;

class WebsiteSettingsService implements WebsiteSettingsServiceInterface
{
    public function show(User $user): array
    {
        $settings = WebsiteSetting::where('company_id', $user->company_id)->first();

        if (!$settings) {
            return [
                'site_title' => '',
                'tagline' => '',
                'contact_email' => '',
                'enable_online_booking' => true,
                'show_pricing' => true,
                'website_url' => '',
                'meta_title' => '',
                'meta_keywords' => '',
                'meta_description' => '',
            ];
        }

        return $settings->toArray();
    }

    public function update(User $user, array $data): array
    {
        $settings = WebsiteSetting::updateOrCreate(
            ['company_id' => $user->company_id],
            $data
        );

        return [
            'message' => 'Website settings saved successfully',
            'data' => $settings,
        ];
    }
}
