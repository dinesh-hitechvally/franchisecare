<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Form;
use Illuminate\Support\Str;

/** The one-script way to put a form on any website: <script async src=".../api/embed/forms.js?c=KEY"></script> and <div class="mate-form" data-form-id="12"></div>. */
class FormEmbed
{
    /** The company's public key, made on first use. */
    public static function key(Company $company): string
    {
        if (! $company->public_key) {
            $company->forceFill(['public_key' => Str::random(40)])->save();
        }

        return $company->public_key;
    }

    public static function company(?string $key): ?Company
    {
        return $key && strlen($key) === 40 ? Company::where('public_key', $key)->first() : null;
    }

    public static function scriptUrl(Company $company): string
    {
        return url('/api/embed/forms.js').'?c='.self::key($company);
    }

    /** What the script needs to show one form: its markup (without any script), its styles scoped to this form, and where to send it. */
    public static function payload(Form $form): array
    {
        $scope = 'fcs-e'.$form->id;
        $style = $form->resolvedStyle();
        $card = 'max-width:'.$style['width'].'px;margin:0 auto;background:'.$style['cardBg'].';color:'.$style['text'].';padding:28px;border-radius:'.$style['radius'].'px;box-shadow:0 2px 12px rgba(0,0,0,.12);text-align:left;box-sizing:border-box';

        return [
            'id' => $form->id, 'slug' => $form->slug,
            'css' => FormRenderer::css($style, '.'.$scope),
            'html' => '<div class="'.$scope.'"><div style="'.$card.'">'.FormRenderer::markup($form, '-e'.$form->id, true).'</div></div>',
        ];
    }
}
