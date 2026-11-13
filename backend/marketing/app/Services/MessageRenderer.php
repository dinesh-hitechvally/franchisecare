<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Person;
use App\Models\EmailLog;
use App\Models\Media;

/**
 * Turns a template body into the final message for one person:
 * merge tags, unsubscribe link, open pixel and click tracking.
 */
class MessageRenderer
{
    public const TAGS = ['first_name', 'last_name', 'full_name', 'email', 'phone', 'company_name', 'unsubscribe_url'];

    public function vars(Company $company, ?Person $person, string $channel): array
    {
        $vars = [
            'first_name' => $person?->first_name ?? 'there',
            'last_name' => $person?->last_name ?? '',
            'full_name' => $person?->full_name ?: 'there',
            'email' => $person?->email ?? '',
            'phone' => $person?->phone ?? '',
            'company_name' => $company->name,
            'business_name' => $company->name, // legacy alias: templates written before the rename keep working
            'company_phone' => (string) $company->setting('profile.phone', ''),
            'company_website' => (string) $company->setting('profile.website', ''),
            'company_email' => (string) $company->setting('profile.support_email', ''),
            'company_logo' => (string) Media::logoUrl($company), // the logo chosen from Media (Settings → Company profile)
            'unsubscribe_url' => $person
                ? url("/api/unsubscribe/{$person->unsubscribe_token}?channel={$channel}")
                : '#',
        ];

        foreach (($person?->customMap() ?? []) as $k => $v) {
            if (is_scalar($v)) {
                $vars[$k] ??= (string) $v;
            }
        }

        return $vars;
    }

    /**
     * Every variable a template may use, for showing a template without sending it:
     * kind "setting" = comes from Settings (the real value, `set` false when Settings has none yet),
     * kind "dynamic" = depends on the person (a sample value stands in), kind "custom" = a custom field of the person (a sample of its type).
     *
     * @return array<string, array{kind: string, value: string, set: bool, label: string}>
     */
    public static function previewTags(Company $company): array
    {
        $r = new self;
        $real = $r->vars($company, null, 'email');
        $tag = fn (string $kind, string $value, string $label, bool $set = true) => ['kind' => $kind, 'value' => $value, 'set' => $set, 'label' => $label];
        $tags = [];
        foreach ([
            'company_name' => 'Company name', 'business_name' => 'Company name (old name)', 'company_phone' => 'Company phone', 'company_website' => 'Company website',
            'company_email' => 'Company email', 'company_logo' => 'Company logo',
        ] as $key => $label) {
            $tags[$key] = $tag('setting', (string) $real[$key], $label, trim((string) $real[$key]) !== '');
        }
        foreach ([
            'first_name' => ['Alex', 'First name'], 'last_name' => ['Taylor', 'Last name'], 'full_name' => ['Alex Taylor', 'Full name'], 'email' => ['alex@example.com', 'Email'],
            'phone' => ['+61 400 000 000', 'Phone'], 'unsubscribe_url' => ['#', 'Unsubscribe link'],
        ] as $key => [$value, $label]) {
            $tags[$key] = $tag('dynamic', $value, $label);
        }
        foreach (\App\Models\PersonField::all() as $f) {
            $options = array_values((array) $f->options);
            $tags[$f->key] ??= $tag('custom', match ($f->type) {
                'number' => '42', 'date' => now()->format('Y-m-d'), 'checkbox' => 'Yes', 'dropdown', 'radio' => (string) ($options[0] ?? 'Option 1'),
                'textarea' => 'Sample text for '.$f->label, default => 'Sample '.mb_strtolower($f->label),
            }, $f->label);
        }

        return $tags;
    }

    /**
     * Fills the variables that are not plain person data, for a preview: custom fields get a sample, a setting that is still empty and a
     * variable that does not exist are replaced by a readable note (no braces, so nothing else touches it and it can sit inside a link).
     */
    public static function previewFill(string $text, array $tags, bool $html): string
    {
        $esc = fn (string $s) => $html ? e($s) : $s;

        return preg_replace_callback('/\{\{\s*([\w.]+)\s*\}\}/', function ($m) use ($tags, $esc) {
            $t = $tags[$m[1]] ?? null;
            if (! $t) {
                return '['.$m[1].': this variable does not exist]';
            }
            if ($t['kind'] === 'setting' && ! $t['set']) {
                return '['.$m[1].': not set in Settings yet]';
            }

            return $t['kind'] === 'custom' ? $esc($t['value']) : $m[0]; // company and person variables are filled by the normal renderer
        }, $text);
    }

    public function replace(string $text, array $vars): string
    {
        return preg_replace_callback('/\{\{\s*([\w.]+)\s*\}\}/', fn ($m) => $vars[$m[1]] ?? '', $text);
    }

    public function renderEmail(string $subject, string $html, Company $company, ?Person $person, ?EmailLog $log = null): array
    {
        $vars = $this->vars($company, $person, 'email');
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

    /** A readable plain-text copy of an HTML email, sent beside the HTML so text-only readers and spam filters are happy. */
    public static function plainText(string $html): string
    {
        $t = preg_replace('~<(head|style|script)\b.*?</\1>|<!--.*?-->|<img\b[^>]*>~is', '', $html);
        // a link shows its address after the words, unless they are the address
        $t = preg_replace_callback('~<a\b[^>]*href=["\']([^"\']+)["\'][^>]*>(.*?)</a>~is', function ($m) {
            $label = trim(strip_tags($m[2]));
            $url = html_entity_decode($m[1]);

            return ($label === '' || $label === $url) ? $url : $label.' ('.$url.')';
        }, $t);
        $t = preg_replace(['~<br\s*/?>~i', '~</(p|div|h[1-6]|tr|table|ul|ol|section)>~i', '~<li\b[^>]*>~i', '~</td>~i'], ["\n", "\n\n", "\n- ", ' '], $t);
        $t = html_entity_decode(strip_tags($t), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $t = preg_replace(["~[ \t\x{00A0}]+~u", "~ *\n *~", "~\n{3,}~"], [' ', "\n", "\n\n"], $t);

        return trim($t);
    }

    public function renderSms(string $body, Company $company, ?Person $person): string
    {
        return $this->replace($body, $this->vars($company, $person, 'sms'));
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
