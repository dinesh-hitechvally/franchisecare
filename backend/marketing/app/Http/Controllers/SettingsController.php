<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function show(Request $request)
    {
        return $this->payload($request->user()->company);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'from_name' => 'nullable|string|max:255',
            'from_email' => 'nullable|email',
            'reply_to' => 'nullable|email',
            'address' => 'nullable|string|max:255',
            'profile.phone' => 'nullable|string|max:40',
            'profile.website' => 'nullable|url:http,https|max:255',
            'profile.legal_name' => 'nullable|string|max:255',
            'profile.tax_id' => 'nullable|string|max:60',
            'profile.industry' => 'nullable|string|max:120',
            'profile.support_email' => 'nullable|email|max:255',
            'profile.logo_url' => 'nullable|url:http,https|max:500',
            'profile.city' => 'nullable|string|max:120',
            'profile.state' => 'nullable|string|max:120',
            'profile.postcode' => 'nullable|string|max:20',
            'profile.country' => 'nullable|string|max:120',
        ]);

        $company = $request->user()->company;
        $settings = $company->settings ?? [];

        foreach ($data['profile'] ?? [] as $k => $v) {
            data_set($settings, "profile.$k", $v);
        }

        $company->update(['settings' => $settings] + collect($data)->only('name', 'from_name', 'from_email', 'reply_to', 'address')->all());

        return $this->payload($company->fresh());
    }

    protected function payload($company): array
    {
        $out = $company->only('name', 'from_name', 'from_email', 'reply_to', 'address');
        $out['profile'] = collect(['phone', 'website', 'legal_name', 'tax_id', 'industry', 'support_email', 'logo_url', 'city', 'state', 'postcode', 'country'])
            ->mapWithKeys(fn ($k) => [$k => $company->setting('profile.'.$k, '')])->all();

        return $out;
    }
}
