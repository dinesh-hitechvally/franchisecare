<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Contact;
use App\Models\EmailLog;

/**
 * Turns a template body into the final message for one contact:
 * merge tags, unsubscribe link, open pixel and click tracking.
 */
class MessageRenderer
{
    public const TAGS = ['first_name', 'last_name', 'full_name', 'email', 'phone', 'company_name', 'unsubscribe_url'];

    public function vars(Company $company, ?Contact $contact, string $channel): array
    {
        $vars = [
            'first_name' => $contact?->first_name ?? 'there',
            'last_name' => $contact?->last_name ?? '',
            'full_name' => $contact?->full_name ?: 'there',
            'email' => $contact?->email ?? '',
            'phone' => $contact?->phone ?? '',
            'company_name' => $company->name,
            'business_name' => $company->name, // legacy alias: templates written before the rename keep working
            'company_phone' => (string) $company->setting('profile.phone', ''),
            'company_website' => (string) $company->setting('profile.website', ''),
            'company_email' => (string) $company->setting('profile.support_email', ''),
            'unsubscribe_url' => $contact
                ? url("/api/unsubscribe/{$contact->unsubscribe_token}?channel={$channel}")
                : '#',
        ];

        foreach (($contact?->customMap() ?? []) as $k => $v) {
            if (is_scalar($v)) {
                $vars[$k] ??= (string) $v;
            }
        }

        return $vars;
    }

    public function replace(string $text, array $vars): string
    {
        return preg_replace_callback('/\{\{\s*([\w.]+)\s*\}\}/', fn ($m) => $vars[$m[1]] ?? '', $text);
    }

    public function renderEmail(string $subject, string $html, Company $company, ?Contact $contact, ?EmailLog $log = null): array
    {
        $vars = $this->vars($company, $contact, 'email');
        $escaped = array_map(fn ($v) => e($v), $vars);
        $escaped['unsubscribe_url'] = $vars['unsubscribe_url'];

        $usesUnsub = str_contains($html, 'unsubscribe_url');
        $html = $this->replace($html, $escaped);

        if (! $usesUnsub) {
            $place = trim(implode(' ', array_filter([$company->setting('profile.city'), $company->setting('profile.state'), $company->setting('profile.postcode')])));
            $lines = array_filter([$company->address, $place, $company->setting('profile.country')]);
            $addr = ($lines ? e(implode(', ', $lines)).'<br>' : '');
            $tax = $company->setting('profile.tax_id') ? ' &middot; '.e($company->setting('profile.tax_id')) : '';
            $html .= '<div style="margin-top:32px;padding-top:16px;border-top:1px solid #e5e7eb;font:12px Arial,sans-serif;color:#6b7280;text-align:center">'
                .$addr.e($company->setting('profile.legal_name') ?: $company->name).$tax
                .' &middot; <a href="'.e($vars['unsubscribe_url']).'" style="color:#6b7280">Unsubscribe</a></div>';
        }

        if ($log) {
            $html = $this->trackClicks($html, $log);
            $html .= '<img src="'.url("/api/t/o/{$log->track_token}.gif").'" width="1" height="1" alt="" style="display:none">';
        }

        return [$this->replace($subject, $vars), $html];
    }

    public function renderSms(string $body, Company $company, ?Contact $contact): string
    {
        return $this->replace($body, $this->vars($company, $contact, 'sms'));
    }

    protected function trackClicks(string $html, EmailLog $log): string
    {
        return preg_replace_callback('/href="(https?:\/\/[^"]+)"/i', function ($m) use ($log) {
            $url = html_entity_decode($m[1]);
            // never wrap the unsubscribe link
            if (str_contains($url, '/api/unsubscribe/')) {
                return $m[0];
            }
            $link = url("/api/t/c/{$log->track_token}").'?'.http_build_query(['u' => $url, 's' => self::sign($url)]);

            return 'href="'.e($link).'"';
        }, $html);
    }

    public static function sign(string $url): string
    {
        return hash_hmac('sha256', $url, config('app.key'));
    }
}
