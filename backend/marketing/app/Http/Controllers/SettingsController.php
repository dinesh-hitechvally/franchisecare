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
            // the logo is picked from the media library (images only, this company's own): never a typed address or a direct upload
            'profile.logo_media_id' => ['nullable', 'integer', \Illuminate\Validation\Rule::exists('media', 'id')->where('company_id', $request->user()->company_id)->where('kind', 'image')->whereNull('deleted_at')],
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
        unset($settings['profile']['logo_url']); // the old typed address is gone: the logo comes from the media library

        $company->update(['settings' => $settings] + collect($data)->only('name', 'from_name', 'from_email', 'reply_to', 'address')->all());

        return $this->payload($company->fresh());
    }

    protected function payload($company): array
    {
        $out = $company->only('name', 'from_name', 'from_email', 'reply_to', 'address');
        $out['logo_url'] = \App\Models\Media::logoUrl($company); // for the preview
        $out['profile'] = collect(['phone', 'website', 'legal_name', 'tax_id', 'industry', 'support_email', 'logo_media_id', 'city', 'state', 'postcode', 'country'])
            ->mapWithKeys(fn ($k) => [$k => $company->setting('profile.'.$k, '')])->all();

        return $out;
    }
}
