<?php

namespace App\Services;

use Illuminate\Support\Str;

/**
 * The form builder's field model.
 *
 * A field is { id, key, type, label, placeholder, help, required, options }.
 *  - standard types (first_name, last_name, email, phone) fill the matching contact column
 *  - input types (text, textarea, number, date, dropdown, radio, checkbox) are saved on the contact as a custom field named `key`
 *  - content blocks (heading, paragraph) only display text
 * Forms made before the builder stored [{key, label, enabled, required}]; normalize() upgrades those on the fly.
 */
class FormFields
{
    public const STANDARD = ['first_name' => 'First name', 'last_name' => 'Last name', 'email' => 'Email', 'phone' => 'Mobile'];

    public const INPUTS = ['text', 'textarea', 'number', 'date', 'dropdown', 'radio', 'checkbox'];

    public const BLOCKS = ['heading', 'paragraph'];

    /** A tick box ("Subscribe me to the newsletter") that decides whether a new contact is subscribed. Not saved on the contact. */
    public const SUBSCRIBE = 'subscribe';

    /** What the form is for. It only picks sensible defaults; each action is its own setting. */
    public const FORM_TYPES = ['newsletter', 'contact', 'other'];

    /** Column layout: fields flow left to right and wrap, so two 'half' fields share a row. */
    public const WIDTHS = ['full', 'two_thirds', 'half', 'third', 'quarter'];

    /** Names a custom field may not use (they collide with contact columns or the honeypot). */
    public const RESERVED = ['first_name', 'last_name', 'full_name', 'email', 'phone', 'company_name', 'company_phone', 'company_website', 'company_email', 'company_logo', 'business_name', 'unsubscribe_url', 'subscribe', 'website', 'id', 'company_id'];

    public const FONTS = [
        'sans' => 'Arial, Helvetica, sans-serif',
        'serif' => 'Georgia, "Times New Roman", serif',
        'mono' => '"Courier New", monospace',
    ];

    public static function types(): array
    {
        return array_merge(array_keys(self::STANDARD), self::INPUTS, [self::SUBSCRIBE], self::BLOCKS);
    }

    public static function isInput(string $type): bool
    {
        return isset(self::STANDARD[$type]) || $type === self::SUBSCRIBE || in_array($type, self::INPUTS, true);
    }

    public static function field(array $f): array
    {
        $type = $f['type'] ?? 'text';

        return [
            'id' => $f['id'] ?? Str::lower(Str::random(8)),
            'key' => isset(self::STANDARD[$type]) || $type === self::SUBSCRIBE ? $type : ($f['key'] ?? null),
            'type' => $type,
            'label' => (string) ($f['label'] ?? ''),
            'placeholder' => (string) ($f['placeholder'] ?? ''),
            'help' => (string) ($f['help'] ?? ''),
            'required' => (bool) ($f['required'] ?? false),
            'options' => array_values($f['options'] ?? []),
            // headings and paragraphs always take the whole row; forms from before columns existed are all 'full'
            // standard fields always fill the contact's own columns; headings never; custom questions default to "yes" except long free text (usually a message, which belongs in the inbox)
            'to_contact' => isset(self::STANDARD[$type]) ? true : (in_array($type, [...self::BLOCKS, self::SUBSCRIBE], true) ? false : (bool) ($f['to_contact'] ?? $type !== 'textarea')),
            'width' => in_array($type, self::BLOCKS, true) || ! in_array($f['width'] ?? 'full', self::WIDTHS, true) ? 'full' : ($f['width'] ?? 'full'),
        ];
    }

    /** Upgrades legacy fields and fills defaults; always returns the current shape. */
    public static function normalize(?array $fields): array
    {
        $out = [];
        foreach ($fields ?? [] as $f) {
            if (isset($f['type'])) {
                $out[] = self::field($f);
            } elseif (isset($f['key'], self::STANDARD[$f['key']]) && ! empty($f['enabled'])) { // legacy: only switched-on fields existed
                $out[] = self::field(['type' => $f['key'], 'label' => $f['label'] ?? self::STANDARD[$f['key']], 'required' => $f['required'] ?? false]);
            }
        }

        return $out ?: self::defaults();
    }

    public static function defaults(): array
    {
        return [
            self::field(['type' => 'first_name', 'label' => 'First name']),
            self::field(['type' => 'email', 'label' => 'Email', 'required' => true]),
        ];
    }

    public static function defaultStyle(): array
    {
        return ['pageBg' => '#f4f5f7', 'cardBg' => '#ffffff', 'text' => '#333333', 'accent' => '#3f51b5', 'btnText' => '#ffffff', 'radius' => 6, 'font' => 'sans', 'width' => 480];
    }

    /** Merges saved style over the defaults and drops anything that isn't a valid colour/number, so it is safe to put in CSS. */
    public static function style(?array $s): array
    {
        $d = self::defaultStyle();
        $s = $s ?? [];
        foreach (['pageBg', 'cardBg', 'text', 'accent', 'btnText'] as $k) {
            if (isset($s[$k]) && preg_match('/^#[0-9a-fA-F]{6}$/', (string) $s[$k])) {
                $d[$k] = $s[$k];
            }
        }
        $d['radius'] = max(0, min(30, (int) ($s['radius'] ?? $d['radius'])));
        $d['width'] = max(320, min(720, (int) ($s['width'] ?? $d['width'])));
        $d['font'] = isset(self::FONTS[$s['font'] ?? '']) ? $s['font'] : 'sans';

        return $d;
    }

    /** Turns a label into a unique custom-field key, e.g. "Company name" -> company_name. */
    public static function keyFor(string $label, array $taken): string
    {
        $base = Str::slug($label, '_') ?: 'field';
        $base = preg_match('/^[a-z]/', $base) ? $base : 'f_'.$base;
        // a label like "Company name" would collide with the built-in {{company_name}} (your own company), so it becomes contact_company_name
        $base = in_array($base, self::RESERVED, true) ? 'contact_'.$base : $base;
        $base = mb_substr($base, 0, 36);
        $key = $base;
        for ($i = 2; in_array($key, $taken, true) || in_array($key, self::RESERVED, true); $i++) {
            $key = $base.'_'.$i;
        }

        return $key;
    }
}
