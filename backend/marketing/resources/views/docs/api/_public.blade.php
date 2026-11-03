@verbatim
<section id="public-api" data-nav="Public endpoints" data-group="Events and public endpoints">
  <h2>Public endpoints <a class="anchor" href="#public-api">#</a></h2>
  <p>These need no key or sign-in. They are what a visitor’s browser calls.</p>

  <h3>Submit a hosted form</h3>
<pre><code>POST /api/public/forms/{slug}          (20 requests a minute per address)
Content-Type: application/json

{ "first_name": "Ann", "email": "ann@example.com", "subscribe": true,
  "captcha": "11", "captcha_token": "eyJ…" }     // keys are the field keys of the form

200  { "message": "Thanks for subscribing!", "redirect": null, "existing": false }
422  { "success": false, "message": "Enter a valid email address.", "code": "validation_failed",
       "errors": { "email": ["Enter a valid email address."] } }</code></pre>
  <ul>
    <li><b>Which keys:</b> the form’s field keys (<code>first_name</code>, <code>last_name</code>, <code>email</code>, <code>phone</code>, custom field keys, <code>subscribe</code> for the newsletter tick box). Open the form in the builder to see each field’s key. The form’s own rules apply (required, length, format…), with its own error messages.</li>
    <li><b>Honeypot:</b> an extra field called <code>website</code> is a trap for robots; if it is filled in, the submission is silently ignored.</li>
    <li><b>Captcha:</b> a form with a captcha also needs <code>captcha</code> (the answer) and <code>captcha_token</code> (from the page that showed the sum). Each token works once, allows 5 wrong tries and expires after 6 hours.</li>
    <li><b>Result:</b> <code>existing</code> is <code>true</code> only when the form’s owner wrote a special message for people who are already in People; otherwise everybody gets the same answer, so a visitor cannot learn who is a contact. <code>redirect</code> is the address to send the visitor to afterwards, if one is set.</li>
    <li>Normally you just embed the form (an iframe from <b>Share / embed</b>), which does all of this for you.</li>
  </ul>

  <h3>Hosted pages</h3>
  <ul>
    <li><code>GET /f/{slug}</code>: a hosted form. <code>GET /p/{slug}</code>: a hosted page. Both 120 requests a minute; served with a strict Content-Security-Policy.</li>
  </ul>

  <h3>Links inside sent messages</h3>
  <ul>
    <li><code>GET /api/t/o/{token}.gif</code> records an open; <code>GET /api/t/c/{token}</code> records a click and redirects.</li>
    <li><code>GET /api/unsubscribe/{token}</code> shows the unsubscribe page; <code>POST /api/unsubscribe/{token}</code> unsubscribes (the one-click button in mail apps posts here; 30 a minute).</li>
    <li><code>GET /api/m/{token}/{name?}</code> serves a Media file by its unguessable token.</li>
  </ul>
</section>
@endverbatim
