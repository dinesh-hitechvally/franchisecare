<?php

namespace App\Http\Controllers;

use App\Models\Form;
use App\Services\FormEmbed;
use Illuminate\Http\Request;

/**
 * The one-script embed, like the ones email-marketing tools give out. A website adds the script once, and a placeholder wherever a form should appear:
 *   <script async src="https://APP/api/embed/forms.js?c=COMPANYKEY"></script>
 *   <div class="mate-form" data-form-id="12"></div>
 * The script fetches the form of that id and draws it in the placeholder, with the form's own colours, and sends the answers to this app.
 */
class FormEmbedController extends Controller
{
    /** The form to draw: only an active form of the company the key belongs to. */
    public function show(string $key, int $id)
    {
        $company = FormEmbed::company($key);
        abort_unless($company, 404, 'Unknown key.');
        $form = Form::withoutGlobalScope('company')->where('company_id', $company->id)->where('active', true)->find($id);
        abort_unless($form, 404, 'This form is not available.');
        $form->increment('views');

        return FormEmbed::payload($form);
    }

    public function script()
    {
        return response($this->source(), 200, ['Content-Type' => 'application/javascript; charset=utf-8', 'Cache-Control' => 'public, max-age=300']);
    }

    protected function source(): string
    {
        return <<<'JS'
/* Shows your forms on any website. Add this script once, and a <div class="mate-form" data-form-id="12"></div> where each form should appear. */
(function () {
  var script = document.currentScript || document.querySelector('script[src*="/api/embed/forms.js"]')
  if (!script) return
  var url = new URL(script.src), origin = url.origin, key = url.searchParams.get('c')
  if (!key || (window.MateForms && window.MateForms.key === key)) return

  function unwrap(j) { return j && j.data !== undefined && j.success !== undefined ? j.data : j } // successful answers come as { success, data }
  function el(tag, cls, text) { var e = document.createElement(tag); if (cls) e.className = cls; if (text) e.textContent = text; return e }

  function mount(host) {
    if (host.getAttribute('data-mate-ready')) return
    var id = host.getAttribute('data-form-id')
    if (!/^[0-9]+$/.test(id || '')) return
    host.setAttribute('data-mate-ready', '1')
    fetch(origin + '/api/embed/forms/' + encodeURIComponent(key) + '/' + id, { headers: { Accept: 'application/json' } })
      .then(function (r) { if (!r.ok) throw new Error('not available'); return r.json() })
      .then(function (j) {
        var d = unwrap(j)
        if (!document.getElementById('mate-form-css-' + d.id)) { var st = document.createElement('style'); st.id = 'mate-form-css-' + d.id; st.textContent = d.css; document.head.appendChild(st) }
        host.innerHTML = d.html
        wire(host, d)
      })
      .catch(function () { host.removeAttribute('data-mate-ready') }) // not available (paused or removed): the placeholder stays empty
  }

  // what happens when the form is sent: a clear "sending", a thank-you panel, or a visible message that says what to fix
  function wire(host, d) {
    var f = host.querySelector('form'), m = host.querySelector('.fc-msg'), b = f.querySelector('button[type=submit]'), label = b.textContent
    function say(cls, text) { m.className = 'fc-msg' + (cls ? ' ' + cls : ''); m.textContent = text }
    function idle() { b.disabled = false; b.textContent = label }
    function done(j) {
      var box = el('div', 'fc-done'); box.setAttribute('role', 'status')
      box.appendChild(el('div', 'fc-check', '✓'))
      box.appendChild(el('p', 'fc-done-msg', j.message || 'Thank you!'))
      box.appendChild(el('p', 'fc-done-sub', j.existing ? 'We already have you in our records.' : (j.redirect ? 'Your form has been submitted. Taking you on…' : 'Your form has been submitted successfully.')))
      if (!j.redirect) {
        var again = el('button', 'fc-again', 'Send another response'); again.type = 'button'
        again.addEventListener('click', function () { box.remove(); f.reset(); f.style.display = ''; say('', '') })
        box.appendChild(again)
      }
      f.style.display = 'none'
      f.parentNode.insertBefore(box, f.nextSibling)
      if (j.redirect && /^https?:\/\//i.test(j.redirect)) setTimeout(function () { location.href = j.redirect }, 1500)
    }
    f.addEventListener('submit', function (ev) {
      ev.preventDefault(); say('', '')
      var data = {}
      Array.prototype.forEach.call(f.elements, function (e) {
        if (!e.name) return
        if (e.type === 'checkbox') data[e.name] = e.checked
        else if (e.type === 'radio') { if (e.checked) data[e.name] = e.value }
        else data[e.name] = e.value
      })
      b.disabled = true; b.textContent = 'Sending…'
      fetch(origin + '/api/public/forms/' + encodeURIComponent(d.slug), { method: 'POST', headers: { 'Content-Type': 'application/json', Accept: 'application/json' }, body: JSON.stringify(data) })
        .then(function (r) { return r.json().then(function (j) { return [r.ok, j] }, function () { return [r.ok, {}] }) })
        .then(function (x) {
          var j = x[1]; idle()
          if (x[0]) { done(unwrap(j)); return }
          var first = j.errors ? Object.keys(j.errors)[0] : null
          say('err', j.errors ? Object.values(j.errors)[0][0] : (j.message || 'Something went wrong. Please try again.'))
          if (first && f.elements[first]) { try { f.elements[first].focus() } catch (e) {} }
        })
        .catch(function () { idle(); say('err', 'We could not send your form. Please check your connection and try again.') })
    })
  }

  function run() { Array.prototype.forEach.call(document.querySelectorAll('.mate-form[data-form-id]'), mount) }
  window.MateForms = { key: key, render: run }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', run); else run()
  // sites that draw their pages with JavaScript: look again when something is added
  if (window.MutationObserver) new MutationObserver(run).observe(document.documentElement, { childList: true, subtree: true })
})()
JS;
    }
}
