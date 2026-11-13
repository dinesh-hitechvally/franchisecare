@verbatim
<section id="integration-api" data-nav="Integration API (/api/v1)" data-group="API keys">
  <h2>Integration API <a class="anchor" href="#integration-api">#</a></h2>
  <p>Five endpoints, all authenticated with an <a href="#keys">API key</a>. A <b>successful</b> answer is plain JSON (shown below). A <b>failure</b> uses the standard <a href="#errors">error shape</a> with a <code>success: false</code>, a <code>message</code> and a <code>code</code>.</p>
  <div class="table-wrap"><table>
    <thead><tr><th>Endpoint</th><th>Purpose</th></tr></thead>
    <tbody>
      <tr><td><code>POST /api/v1/people</code></td><td><a href="#v1-upsert">Create or update a person</a> by email or phone.</td></tr>
      <tr><td><code>GET /api/v1/people?email=</code></td><td><a href="#v1-lookup">Look up a person</a>, with their lists and tags.</td></tr>
      <tr><td><code>POST /api/v1/people/unsubscribe</code></td><td><a href="#v1-unsubscribe">Opt a person out.</a></td></tr>
      <tr><td><code>POST /api/v1/push/devices</code></td><td><a href="#push-devices">Register an Android / iOS app</a> for push notifications.</td></tr>
      <tr><td><code>DELETE /api/v1/push/devices</code></td><td><a href="#push-devices">Remove it</a> again.</td></tr>
    </tbody>
  </table></div>

  <h3 id="v1-upsert">Create or update a person</h3>
  <p><code>POST /api/v1/people</code>: finds the person by <b>email</b> (or by <b>phone</b> when you send no email). If there is none, a new person is created; otherwise their details are updated. Lists and tags are matched <b>by name</b> and created when they do not exist.</p>
  <div class="table-wrap"><table>
    <thead><tr><th>Field</th><th>Type</th><th>Notes</th></tr></thead>
    <tbody>
      <tr><td><code>email</code></td><td>string</td><td>A valid address, up to 255 characters. <b>Required unless you send <code>phone</code>.</b> Stored in lower case.</td></tr>
      <tr><td><code>phone</code></td><td>string</td><td>Up to 30 characters. <b>Required unless you send <code>email</code>.</b></td></tr>
      <tr><td><code>first_name</code>, <code>last_name</code></td><td>string</td><td>Up to 100 characters each. Only fills what you send.</td></tr>
      <tr><td><code>lists</code></td><td>array of strings</td><td>At most 20 list names. The person is added to each; a missing list is created.</td></tr>
      <tr><td><code>tags</code></td><td>array of strings</td><td>At most 20 tag names (up to 60 characters). A missing tag is created.</td></tr>
      <tr><td><code>custom_fields</code></td><td>object</td><td>At most 30 entries: field name ⇒ text or number (up to 2000 characters). A name that is new becomes a text custom field.</td></tr>
    </tbody>
  </table></div>
<pre><code>curl -X POST http://localhost:8000/api/v1/people \
  -H "Authorization: Bearer $API_KEY" -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{
        "email": "jane@example.com",
        "first_name": "Jane",
        "tags": ["website"],
        "lists": ["Newsletter"],
        "custom_fields": { "Plan": "Pro" }
      }'</code></pre>
  <p>Answer: <b>201 Created</b> for a new person, <b>200 OK</b> when an existing person was updated. The body is the person (trimmed here):</p>
<pre><code>{
  "id": 17, "first_name": "Jane", "last_name": null, "email": "jane@example.com", "phone": null,
  "source": "api", "is_subscribed": true, "subscribed_at": "2026-11-03T09:29:39.000000Z",
  "created_at": "2026-11-03T09:29:39.000000Z", "updated_at": "2026-11-03T09:29:39.000000Z",
  "custom": { "plan": "Pro" },
  "lists": [ { "id": 1, "name": "Newsletter" } ],
  "tags":  [ { "id": 3, "name": "website" } ]
}</code></pre>
  <ul>
    <li><b>Subscription:</b> a person created through the API is <b>subscribed</b>. Updating an existing person <b>never changes</b> their subscription, so an upsert cannot re-subscribe someone who opted out. Use the unsubscribe endpoint to opt out.</li>
    <li><b>Existing details</b> are only changed by what you send; fields you leave out stay as they are.</li>
    <li><b>Source</b> is recorded as <code>api</code>. Adding to a list or tag can start workflows that are triggered by it, and a new person fires the <i>person is added</i> trigger and the <code>person.created</code> webhook.</li>
    <li>The answer has a few more fields than shown (such as <code>company_id</code> and an unsubscribe token); rely only on the ones listed.</li>
  </ul>
  <p>The same call from code:</p>
<pre><code>// PHP (Laravel Http client)
$response = Http::withToken($apiKey)->acceptJson()->post('https://your-server/api/v1/people', [
    'email' => 'jane@example.com', 'first_name' => 'Jane', 'tags' => ['website'], 'lists' => ['Newsletter'],
]);
$person = $response->json();            // $response->status() is 201 or 200

// JavaScript (server side, Node 18+)
const res = await fetch('https://your-server/api/v1/people', {
  method: 'POST',
  headers: { Authorization: `Bearer ${process.env.API_KEY}`, Accept: 'application/json', 'Content-Type': 'application/json' },
  body: JSON.stringify({ email: 'jane@example.com', first_name: 'Jane', tags: ['website'] }),
})
const person = await res.json()</code></pre>

  <h3 id="v1-lookup">Look up a person</h3>
  <p><code>GET /api/v1/people?email=jane@example.com</code> (the <code>email</code> parameter is required). Answer <b>200</b>: the person with their <code>lists</code> and <code>tags</code>, including <code>is_subscribed</code> and <code>subscribed_at</code>. If nobody has that address the answer is <b>404</b> with the error shape.</p>
<pre><code>{ "id": 17, "first_name": "Jane", "last_name": "Doe", "email": "jane@example.com", "phone": null,
  "source": "api", "is_subscribed": true, "subscribed_at": "2026-11-03T09:29:39.000000Z",
  "lists": [ { "id": 1, "name": "Newsletter" } ], "tags": [ { "id": 3, "name": "website" } ] }</code></pre>

  <h3 id="v1-unsubscribe">Unsubscribe a person</h3>
  <p><code>POST /api/v1/people/unsubscribe</code> with <code>{"email": "jane@example.com"}</code>. The person is opted out of all marketing (email and SMS together): campaigns and workflows never send to them again. Answer <b>200</b> <code>{"ok": true}</code>; <b>404</b> if the address is unknown. A <code>channel</code> field (<code>email</code> or <code>sms</code>) is still accepted so older integrations keep working, but it unsubscribes the person as a whole. Unsubscribing fires the <i>person unsubscribes</i> trigger and the <code>person.unsubscribed</code> webhook.</p>
  <p>There is deliberately no endpoint to subscribe someone again: a person who opted out must opt in themselves (for example through a form with a Subscribe box), or you re-subscribe them in the app’s <b>Subscriptions</b> screen after confirming permission.</p>

  <h3>Limits and errors</h3>
  <ul>
    <li><b>120 requests a minute</b> per key. Every answer carries <code>X-RateLimit-Limit</code> and <code>X-RateLimit-Remaining</code>; over the limit the answer is <b>429</b> <code>too_many_requests</code>. Wait a minute and retry.</li>
    <li><b>401</b> <code>unauthenticated</code>: no key, a wrong key, or a revoked one. <b>422</b> <code>validation_failed</code>: see <code>errors</code>, for example <i>“The email field is required when phone is not present.”</i></li>
    <li>Calls are not idempotent by request, but they are by person: sending the same upsert twice creates one person and adds the same lists and tags once.</li>
  </ul>
</section>
<section id="push-devices" data-nav="Push devices" data-group="API keys">
  <h2>Register an app for push notifications <a class="anchor" href="#push-devices">#</a></h2>
  <p>Your Android or iOS app gets a <b>Firebase registration token</b> from the Firebase SDK. Send it here, with the person it belongs to, so push campaigns can reach them. Call it again whenever the token changes: the same token never creates a second device. (Browsers register themselves with the website snippet, not this API.)</p>
  <pre>curl -X POST https://YOUR-APP/api/v1/push/devices \
  -H "Authorization: Bearer YOUR_API_KEY" -H "Content-Type: application/json" \
  -d '{"token":"FIREBASE_TOKEN","platform":"android","email":"ann@example.com","label":"Pixel 8"}'

// 201
{ "id": 12, "linked": true }</pre>
  <div class="table-wrap"><table>
    <thead><tr><th>Field</th><th>Meaning</th></tr></thead>
    <tbody>
      <tr><td><code>token</code> (required)</td><td>The Firebase registration token of this installation.</td></tr>
      <tr><td><code>platform</code> (required)</td><td><code>android</code> or <code>ios</code>.</td></tr>
      <tr><td><code>email</code>, <code>phone</code> or <code>person_id</code></td><td>Who it belongs to. <code>linked</code> in the answer tells whether a person was found. A device that is not linked cannot be reached by a campaign.</td></tr>
      <tr><td><code>create_person</code></td><td><code>true</code> creates the person from <code>email</code> when they are not known yet.</td></tr>
      <tr><td><code>label</code></td><td>A name for the device, shown in Audience → Devices.</td></tr>
      <tr><td><code>device_id</code></td><td>The app's own install id (8 to 64 letters, digits, dashes). Send it every time: when Firebase gives the installation a new token, the same device is updated instead of a second one being added.</td></tr>
    </tbody>
  </table></div>
  <p>To stop notifications on an installation (log out, uninstall): <code>DELETE /api/v1/push/devices</code> with <code>{"token":"…"}</code>. It answers <code>204</code>.</p>
</section>
@endverbatim
