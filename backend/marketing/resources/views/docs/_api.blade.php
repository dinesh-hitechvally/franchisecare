@verbatim
<section id="api-auth" data-nav="Authentication" data-group="API">
  <h2>API: authentication <a class="anchor" href="#api-auth">#</a></h2>
  <p>The dashboard API lives under <code>/api</code> and uses Laravel Sanctum bearer tokens. Always send <code>Accept: application/json</code>.</p>
<pre><code># 1. create a company and its first user (or skip and use the demo login)
curl -X POST http://localhost:8000/api/register \
  -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"company_name":"Acme","name":"Owner","email":"owner@acme.com","password":"password123","password_confirmation":"password123"}'

# 2. sign in: the answer holds the token
curl -X POST http://localhost:8000/api/login \
  -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"email":"demo@franchisecare.test","password":"password123"}'
# {"success":true,"message":null,"data":{"token":"2|abc…","user":{…},"company":{…}}}

# 3. use it
curl http://localhost:8000/api/people?per_page=5 \
  -H "Accept: application/json" -H "Authorization: Bearer 2|abc…"</code></pre>
  <ul>
    <li><code>GET /api/me</code> returns the signed-in user and company; <code>POST /api/logout</code> revokes the token.</li>
    <li><code>register</code> and <code>login</code> are limited to 10 requests a minute.</li>
    <li>A login email belongs to exactly one company. Every user of a company has full access to it; there are no roles.</li>
    <li><b>Integration keys</b> (below) can only call <code>/api/v1</code>. They are refused on the dashboard API.</li>
  </ul>
</section>

<section id="envelope" data-nav="Response format" data-group="API">
  <h2>API: response format <a class="anchor" href="#envelope">#</a></h2>
  <p>Every answer of the dashboard API has one shape, so a client can show the server’s sentence and rely on a short machine-readable code.</p>
<pre><code>// success    (a list also has "meta": { current_page, last_page, per_page, total, … } and "data" is the items)
{ "success": true,  "message": "Sending stopped." , "data": { … } }

// failure    ("code" is a short reason; "errors" appears for validation only)
{ "success": false, "message": "The name field is required.", "code": "validation_failed",
  "errors": { "name": ["The name field is required."] } }</code></pre>
  <div class="table-wrap"><table>
    <thead><tr><th>HTTP</th><th>code</th><th>When</th></tr></thead>
    <tbody>
      <tr><td>422</td><td><code>validation_failed</code></td><td>The input is not valid; see <code>errors</code>.</td></tr>
      <tr><td>422</td><td><code>unprocessable</code></td><td>The request is fine but cannot be done (for example “Campaign already sent.”).</td></tr>
      <tr><td>401</td><td><code>unauthenticated</code></td><td>No token, or it was revoked.</td></tr>
      <tr><td>403</td><td><code>forbidden</code></td><td>Not allowed (for example an integration key on the dashboard API).</td></tr>
      <tr><td>404</td><td><code>not_found</code></td><td>The record does not exist, or belongs to another company.</td></tr>
      <tr><td>405</td><td><code>method_not_allowed</code></td><td>Wrong HTTP method for that path.</td></tr>
      <tr><td>409</td><td><code>conflict</code></td><td>It clashes with the current state.</td></tr>
      <tr><td>429</td><td><code>too_many_requests</code></td><td>Rate limit; wait and retry.</td></tr>
      <tr><td>500</td><td><code>server_error</code></td><td>Something broke on our side.</td></tr>
    </tbody>
  </table></div>
  <div class="note"><b>Not wrapped:</b> the integration API (<code>/api/v1</code>), hosted forms (<code>/api/public</code>), tracking links, media files, and downloads or CSV exports. Other systems read those directly, so they keep their own plain shapes.</div>
  <h3>In a controller</h3>
<pre><code>return ApiResponse::ok($campaign, 'Sending stopped.');       // 200 with a sentence for the person
return ApiResponse::created($form);                           // 201
return ApiResponse::message('Test message sent.');            // 200, a sentence and no data
return ApiResponse::paginated($query->paginate(25));         // items as data, paging as meta
return ApiResponse::error('Sending failed.', 422, 'send_failed');
return ApiResponse::validation(['email' => ['Not valid.']]);  // 422 with field errors</code></pre>
  <p>A controller that simply returns a model, a list or a paginator is wrapped automatically by the <code>WrapApiResponse</code> middleware, and every exception, including <code>abort()</code>, <code>findOrFail()</code> and validation, becomes a failure envelope. The frontend’s <code>src/api.js</code> unwraps successes, so screens still work with <code>response.data</code> as the record or list.</p>
</section>

<section id="conventions" data-nav="Conventions and examples" data-group="API">
  <h2>API: conventions and examples <a class="anchor" href="#conventions">#</a></h2>
  <ul>
    <li><b>Lists are paginated:</b> <code>?page=2&amp;per_page=25</code> (at most 100). The answer has <code>data</code> (the items) and <code>meta</code> (paging).</li>
    <li><b>Search and filters</b> are query parameters. People: <code>search</code>, <code>list_id</code>, <code>tag_id</code>, <code>segment_id</code>, <code>status=subscribed|unsubscribed</code>. Message logs: <code>channel</code>, <code>source</code>, <code>service</code>, <code>status</code>, <code>campaign_id</code>, <code>from</code>, <code>to</code> (dates as <code>YYYY-MM-DD</code>). Campaign deliveries: <code>status</code> (<code>sent</code>, <code>skipped</code>, <code>failed</code>, <code>logged</code>, <code>queued</code>) and <code>search</code>.</li>
    <li><b>Bodies are JSON.</b> Deleted records are not returned (deleting is soft).</li>
    <li><b>Times</b> are UTC.</li>
  </ul>
  <h3>Add a person with tags and lists</h3>
<pre><code>curl -X POST http://localhost:8000/api/people \
  -H "Authorization: Bearer $TOKEN" -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"email":"ann@example.com","first_name":"Ann","is_subscribed":true,"list_ids":[1],"tag_ids":[1]}'</code></pre>
  <h3>Check, then send a campaign</h3>
<pre><code># how many will receive it, and anything worth fixing first
curl http://localhost:8000/api/campaigns/12/check -H "Authorization: Bearer $TOKEN" -H "Accept: application/json"
# {"data":{"audience":14,"reach":9,"unsubscribed":4,"skipped":5,"warnings":[]}}

curl -X POST http://localhost:8000/api/campaigns/12/send -H "Authorization: Bearer $TOKEN" -H "Accept: application/json"</code></pre>
  <h3>Read a campaign’s delivery list</h3>
<pre><code>curl "http://localhost:8000/api/reports/campaigns/12/deliveries?status=skipped" \
  -H "Authorization: Bearer $TOKEN" -H "Accept: application/json"</code></pre>
</section>

<section id="public-api" data-nav="Public endpoints" data-group="API">
  <h2>API: public endpoints <a class="anchor" href="#public-api">#</a></h2>
  <p>These need no sign-in. They are what a visitor’s browser calls.</p>
  <h3>Submit a hosted form</h3>
<pre><code>POST /api/public/forms/{slug}          (20 requests a minute)
{ "first_name": "Ann", "email": "ann@example.com", "subscribe": true,
  "captcha": "11", "captcha_token": "eyJ…" }                       // keys are the field keys of the form

200  { "message": "Thanks for subscribing!", "redirect": null, "existing": false }
422  { "message": "…", "errors": { "email": ["Enter a valid email address."] } }</code></pre>
  <p>An extra field <code>website</code> is a honeypot: if it is filled in, the submission is silently ignored. A form with a captcha also sends <code>captcha</code> (the answer) and <code>captcha_token</code> (from the page). <code>existing</code> is only <code>true</code> when the form’s owner wrote a message for people who are already in People.</p>
  <h3>Hosted pages and tracking links</h3>
  <ul>
    <li><code>GET /f/{slug}</code> and <code>GET /p/{slug}</code>: a hosted form and a hosted page (120 a minute).</li>
    <li><code>GET /api/t/o/{token}.gif</code> opens, <code>GET /api/t/c/{token}</code> clicks (redirects), <code>GET|POST /api/unsubscribe/{token}</code> unsubscribes (the one-click button in mail apps posts here).</li>
    <li><code>GET /api/m/{token}/{name?}</code> serves a Media file by its unguessable token.</li>
  </ul>
</section>

<section id="integration-api" data-nav="Integration API (keys)" data-group="API">
  <h2>API: integration API <a class="anchor" href="#integration-api">#</a></h2>
  <p>For other systems (your website, Zapier, a CRM). Create a key under <b>Settings → API Integrations</b> (it is shown once). A key only works on <code>/api/v1</code>, at 120 requests a minute, and always acts inside its own company. Answers are plain JSON, not the dashboard envelope.</p>
<pre><code># add or update a person by email (or phone); lists and tags are matched by name and created when missing
curl -X POST http://localhost:8000/api/v1/people \
  -H "Authorization: Bearer $API_KEY" -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"email":"ann@example.com","first_name":"Ann","tags":["VIP"],"lists":["Newsletter"],"custom_fields":{"Plan":"Pro"}}'
# 201 when created, 200 when updated; the body is the person with lists, tags and custom fields

curl "http://localhost:8000/api/v1/people?email=ann@example.com" -H "Authorization: Bearer $API_KEY"

curl -X POST http://localhost:8000/api/v1/people/unsubscribe \
  -H "Authorization: Bearer $API_KEY" -H "Content-Type: application/json" -d '{"email":"ann@example.com"}'
# {"ok": true}</code></pre>
  <p>Fields on create/update: <code>email</code> or <code>phone</code> (one is required), <code>first_name</code>, <code>last_name</code>, <code>lists[]</code> (at most 20 names), <code>tags[]</code> (at most 20), <code>custom_fields</code> (up to 30; a new key becomes a text custom field). Existing details are filled but a person’s subscription is never changed by an upsert.</p>
</section>
@endverbatim

<section id="webhooks" data-nav="Webhooks" data-group="API">
  <h2>Webhooks <a class="anchor" href="#webhooks">#</a></h2>
  <p>Under <b>Automation → Webhooks</b> add an address and choose events. When one happens the app sends a <code>POST</code> with a JSON body. These are the events:</p>
  <div class="table-wrap"><table>
    <thead><tr><th>Event</th><th>Sent when</th></tr></thead>
    <tbody>
    @foreach ($events as $event => $label)
      <tr><td><code>{{ $event }}</code></td><td>{{ $label }}</td></tr>
    @endforeach
    </tbody>
  </table></div>
  @verbatim
  <p>Every call carries two headers: <code>X-Webhook-Event</code> (the event name) and <code>X-Webhook-Signature: sha256=&lt;hex&gt;</code>, an HMAC-SHA256 of the <b>raw body</b> keyed with the webhook’s secret (shown once when you create it). Check it before trusting the call:</p>
<pre><code>// PHP
$expected = 'sha256=' . hash_hmac('sha256', file_get_contents('php://input'), $secret);
if (! hash_equals($expected, $_SERVER['HTTP_X_WEBHOOK_SIGNATURE'] ?? '')) { http_response_code(401); exit; }

// Node
const expected = 'sha256=' + crypto.createHmac('sha256', secret).update(rawBody).digest('hex')
if (!crypto.timingSafeEqual(Buffer.from(expected), Buffer.from(req.headers['x-webhook-signature'] || ''))) return res.sendStatus(401)</code></pre>
  <p>The body is always <code>{ "event", "created_at", "data" }</code>. For example <code>person.created</code>:</p>
<pre><code>{ "event": "person.created", "created_at": "2026-11-03T09:15:00+00:00",
  "data": { "id": 14, "first_name": "Ann", "last_name": "Lee", "email": "ann@example.com", "phone": null, "is_subscribed": true, "source": "form" } }</code></pre>
  <ul>
    <li>Answer with any 2xx within 10 seconds to say it arrived. Redirects are not followed and a failed call is not retried; the result is recorded.</li>
    <li><b>Webhook deliveries</b> lists every call, its status and the response; <b>Webhook histories</b> shows changes to the webhook itself.</li>
    <li>Addresses may not point at private or local networks unless <code>OUTBOUND_ALLOW_PRIVATE=true</code> (development only).</li>
  </ul>
  @endverbatim
</section>
