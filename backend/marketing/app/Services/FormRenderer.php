<?php

namespace App\Services;

use App\Models\Form;

/**
 * HTML for hosted sign-up forms and for forms embedded in landing pages.
 * Everything dynamic is escaped; colours/sizes only come from FormFields::style(), which validates them.
 */
class FormRenderer
{
    protected const HTML_TYPES = ['first_name' => 'text', 'last_name' => 'text', 'text' => 'text', 'email' => 'email', 'phone' => 'tel', 'number' => 'number', 'date' => 'date'];

    protected static function e($v): string
    {
        return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
    }

    protected static function field(array $f): string
    {
        $label = self::e($f['label']);
        $req = $f['required'] ? ' required' : '';
        $star = $f['required'] ? ' <span class="fc-req">*</span>' : '';
        $name = self::e($f['key'] ?? '');
        $ph = $f['placeholder'] !== '' ? ' placeholder="'.self::e($f['placeholder']).'"' : '';
        $help = $f['help'] !== '' ? '<small class="fc-help">'.self::e($f['help']).'</small>' : '';

        switch ($f['type']) {
            case 'heading':
                return '<h3 class="fc-h">'.$label.'</h3>';
            case 'paragraph':
                return '<p class="fc-p">'.nl2br($label).'</p>';
            case 'textarea':
                return '<label class="fc-l">'.$label.$star.'<textarea name="'.$name.'" rows="4"'.$ph.$req.'></textarea></label>'.$help;
            case 'dropdown':
                $opts = '<option value="">'.self::e($f['placeholder'] !== '' ? $f['placeholder'] : 'Select…').'</option>';
                foreach ($f['options'] as $o) {
                    $opts .= '<option value="'.self::e($o).'">'.self::e($o).'</option>';
                }

                return '<label class="fc-l">'.$label.$star.'<select name="'.$name.'"'.$req.'>'.$opts.'</select></label>'.$help;
            case 'radio':
                $rows = '';
                foreach ($f['options'] as $i => $o) {
                    $rows .= '<label class="fc-opt"><input type="radio" name="'.$name.'" value="'.self::e($o).'"'.($i === 0 ? $req : '').'> '.self::e($o).'</label>';
                }

                return '<fieldset class="fc-fs"><legend>'.$label.$star.'</legend>'.$rows.$help.'</fieldset>';
            case 'checkbox':
            case 'subscribe':
                return '<label class="fc-opt fc-chk"><input type="checkbox" name="'.$name.'"'.$req.'> <span>'.$label.$star.'</span></label>'.$help;
            default:
                $type = self::HTML_TYPES[$f['type']] ?? 'text';

                return '<label class="fc-l">'.$label.$star.'<input name="'.$name.'" type="'.$type.'"'.$ph.$req.'></label>'.$help;
        }
    }

    /** The <form> + its script. $nonce must match the page's Content-Security-Policy. */
    /** $suffix keeps element ids unique when a page shows several forms (or the same form twice). */
    public static function fragment(Form $form, string $nonce, string $suffix = ''): string
    {
        $rows = '';
        foreach ($form->builderFields() as $f) {
            $rows .= '<div class="fc-f fc-w-'.$f['width'].'">'.self::field($f).'</div>';
        }
        $slug = self::e($form->slug);

        return '<form id="fc-form'.$suffix.'" class="fc-form" novalidate>'
            .($form->title ? '<h2 class="fc-title">'.self::e($form->title).'</h2>' : '')
            .($form->description ? '<p class="fc-desc">'.self::e($form->description).'</p>' : '')
            .'<div class="fc-row">'.$rows.'</div>'
            .'<input name="website" class="fc-hp" tabindex="-1" autocomplete="off" aria-hidden="true">' // honeypot: bots fill it, people never see it
            .'<button type="submit">'.self::e($form->button_text).'</button><div id="fc-msg'.$suffix.'" class="fc-msg" role="status"></div></form>'
            .self::script($nonce, $suffix, $slug);
    }

    /**
     * What happens when the form is sent. The person must always be able to tell what happened:
     *  - while sending, the button says so and cannot be pressed again (no double submissions);
     *  - on success the form is replaced by a clear "thank you" panel (a tick, the form's own message, and a line saying it was submitted),
     *    with a way to send another response, or a short pause before the redirect the form asks for;
     *  - on a problem a visible message says what to fix, and the first field that needs attention is focused.
     * The server's wording is only ever put in with textContent, never as HTML.
     */
    protected static function script(string $nonce, string $suffix, string $slug): string
    {
        $js = <<<'JS'
(function(){
var f=document.getElementById('fc-form__SUFFIX__'),m=document.getElementById('fc-msg__SUFFIX__'),b=f.querySelector('button[type=submit]'),label=b.textContent;
function say(cls,text){m.className='fc-msg'+(cls?' '+cls:'');m.textContent=text}
function idle(){b.disabled=false;b.textContent=label}
function done(j){
  var box=document.createElement('div');box.className='fc-done';box.setAttribute('role','status');
  var c=document.createElement('div');c.className='fc-check';c.textContent='\u2713';
  var t=document.createElement('p');t.className='fc-done-msg';t.textContent=j.message||'Thank you!';
  var s=document.createElement('p');s.className='fc-done-sub';s.textContent=j.existing?'We already have you in our records.':(j.redirect?'Your form has been submitted. Taking you on\u2026':'Your form has been submitted successfully.');
  box.appendChild(c);box.appendChild(t);box.appendChild(s);
  if(!j.redirect){var a=document.createElement('button');a.type='button';a.className='fc-again';a.textContent='Send another response';a.addEventListener('click',function(){box.remove();f.reset();f.style.display='';say('','')});box.appendChild(a)}
  f.style.display='none';f.parentNode.insertBefore(box,f.nextSibling);
  try{box.scrollIntoView({behavior:'smooth',block:'nearest'})}catch(e){}
  if(j.redirect){setTimeout(function(){location.href=j.redirect},1500)}
}
f.addEventListener('submit',function(ev){
  ev.preventDefault();say('','');
  var d={};
  Array.prototype.forEach.call(f.elements,function(el){if(!el.name)return;
    if(el.type==='checkbox')d[el.name]=el.checked;else if(el.type==='radio'){if(el.checked)d[el.name]=el.value}else d[el.name]=el.value});
  b.disabled=true;b.textContent='Sending\u2026';
  fetch('/api/public/forms/__SLUG__',{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json'},body:JSON.stringify(d)})
  .then(function(r){return r.json().then(function(j){return[r.ok,j]},function(){return[r.ok,{}]})})
  .then(function(x){
    var j=x[1];idle();
    if(x[0]){done(j);return}
    var first=j.errors?Object.keys(j.errors)[0]:null;
    say('err',j.errors?Object.values(j.errors)[0][0]:(j.message||'Something went wrong. Please try again.'));
    if(first&&f.elements[first]){try{f.elements[first].focus()}catch(e){}}
  })
  .catch(function(){idle();say('err','We could not send your form. Please check your connection and try again.')});
});
})();
JS;

        return '<script nonce="'.$nonce.'">'.str_replace(['__SUFFIX__', '__SLUG__'], [$suffix, $slug], $js).'</script>';
    }

    /** Stylesheet for the form; $style must come from FormFields::style() (validated). */
    public static function css(array $style, string $scope = ''): string
    {
        $css = self::rules($style);
        if ($scope === '') {
            return $css;
        }

        // prefix every selector so several forms with different styles can share one page
        return preg_replace_callback('/(@media[^{]+\{)?([^{}]+)\{([^{}]*)\}/', fn ($m) => $m[1].implode(',', array_map(fn ($sel) => $scope.' '.trim($sel), explode(',', $m[2]))).'{'.$m[3].'}', $css);
    }

    protected static function rules(array $style): string
    {
        $s = $style;
        $font = FormFields::FONTS[$s['font']];

        return ".fc-form{max-width:{$s['width']}px;margin:0 auto;font-family:{$font};color:{$s['text']}}"
            .".fc-row{display:flex;flex-wrap:wrap;column-gap:14px}.fc-f{box-sizing:border-box;width:100%;min-width:0}.fc-w-two_thirds{width:calc(66.667% - 4.667px)}.fc-w-half{width:calc(50% - 7px)}.fc-w-third{width:calc(33.333% - 9.333px)}.fc-w-quarter{width:calc(25% - 10.5px)}@media(max-width:480px){.fc-f{width:100%!important}}.fc-form .fc-title{margin:0 0 6px;font-size:24px;line-height:1.25;font-weight:700;letter-spacing:0}.fc-form .fc-desc{margin:0 0 18px;opacity:.75;font-size:15px;line-height:1.5;max-width:none}.fc-form .fc-h{margin:18px 0 8px;font-size:18px;line-height:1.3;font-weight:700;letter-spacing:0}.fc-form .fc-p{margin:0 0 14px;line-height:1.5}"
            .".fc-form .fc-l{display:block;font-size:14px;margin-bottom:14px}.fc-form .fc-req{color:#d32f2f}"
            .".fc-form input:not([type=checkbox]):not([type=radio]),.fc-form select,.fc-form textarea{display:block;width:100%;box-sizing:border-box;margin-top:5px;padding:11px;border:1px solid #c9ccd3;border-radius:{$s['radius']}px;font:inherit;font-size:15px;color:inherit;background:#fff}"
            .".fc-form input:focus,.fc-form select:focus,.fc-form textarea:focus{outline:2px solid {$s['accent']}55;border-color:{$s['accent']}}"
            .".fc-form .fc-fs{border:0;padding:0;margin:0 0 14px}.fc-form .fc-fs legend{font-size:14px;padding:0;margin-bottom:6px}"
            .".fc-form .fc-opt{display:flex;gap:8px;align-items:flex-start;font-size:14px;margin-bottom:8px}.fc-form .fc-opt input{margin-top:3px;accent-color:{$s['accent']}}.fc-form .fc-chk{margin-bottom:14px}"
            .".fc-form .fc-help{display:block;margin:-8px 0 14px;font-size:12px;opacity:.65}"
            .".fc-form button{width:100%;padding:13px;border:0;border-radius:{$s['radius']}px;background:{$s['accent']};color:{$s['btnText']};font:inherit;font-size:16px;font-weight:700;cursor:pointer}"
            .'.fc-hp{position:absolute!important;left:-9999px!important;width:1px!important;height:1px!important;opacity:0}'
            .'.fc-form button:disabled{opacity:.65;cursor:wait}'
            .'.fc-msg{font-size:14px;text-align:center}.fc-msg.err{margin-top:12px;padding:10px 12px;border:1px solid #f5c2c0;border-radius:6px;background:#fdecea;color:#b71c1c;text-align:left}'
            .'.fc-done{max-width:'.$s['width'].'px;margin:0 auto;padding:32px 16px;text-align:center;font-family:'.$font.';color:'.$s['text'].'}'
            .'.fc-check{width:60px;height:60px;margin:0 auto 16px;border-radius:50%;background:#e8f5e9;color:#2e7d32;font-size:32px;line-height:60px;font-weight:700}'
            .'.fc-done-msg{margin:0 0 6px;font-size:22px;font-weight:700}.fc-done-sub{margin:0 0 18px;font-size:14px;opacity:.7}'
            .'.fc-again{background:none;border:0;padding:4px;font:inherit;font-size:14px;color:'.$s['accent'].';text-decoration:underline;cursor:pointer}';
    }

    /** Full standalone page for /f/{slug}. */
    public static function page(Form $form, string $nonce): string
    {
        $s = $form->resolvedStyle();
        $title = self::e($form->title ?: $form->name);

        return '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>'.$title.'</title>'
            ."<style>body{margin:0;background:{$s['pageBg']};padding:32px 16px}.card{background:{$s['cardBg']};border-radius:{$s['radius']}px;padding:28px;max-width:".($s['width'] + 56)."px;margin:0 auto;box-shadow:0 2px 8px rgba(0,0,0,.1)}".self::css($s).'</style></head>'
            .'<body><div class="card">'.self::fragment($form, $nonce).'</div></body></html>';
    }
}
