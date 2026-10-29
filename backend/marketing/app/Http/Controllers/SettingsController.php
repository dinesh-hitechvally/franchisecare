<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SettingsController extends Controller
{
    /** Secrets are never returned to the browser: only whether they are set. */
    protected const SECRETS = ['sms.twilio_token'];

    public function show(Request $request)
    {
        return $this->payload($request->user()->business);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'from_name' => 'nullable|string|max:255',
            'from_email' => 'nullable|email',
            'reply_to' => 'nullable|email',
            'address' => 'nullable|string|max:255',
            'sms.provider' => 'nullable|in:log,twilio',
            'sms.twilio_sid' => 'nullable|string',
            'sms.twilio_token' => 'nullable|string',
            'sms.from' => 'nullable|string',
        ]);

        $business = $request->user()->business;
        $settings = $business->settings ?? [];

        foreach (['sms'] as $group) {
            foreach ($data[$group] ?? [] as $k => $v) {
                // blank secret = keep the stored value
                if ($v === null || $v === '') {
                    if (in_array("$group.$k", self::SECRETS)) {
                        continue;
                    }
                }
                data_set($settings, "$group.$k", $v);
            }
        }

        $business->update(['settings' => $settings] + collect($data)->only('name', 'from_name', 'from_email', 'reply_to', 'address')->all());

        return $this->payload($business->fresh());
    }

    protected function payload($business): array
    {
        $out = $business->only('name', 'from_name', 'from_email', 'reply_to', 'address');
        $out['sms'] = ['provider' => $business->setting('sms.provider', 'log'), 'twilio_sid' => $business->setting('sms.twilio_sid'), 'from' => $business->setting('sms.from')];
        foreach (self::SECRETS as $s) {
            [$g, $k] = explode('.', $s);
            $out[$g][$k.'_set'] = (bool) $business->setting($s);
        }

        return $out;
    }
}
