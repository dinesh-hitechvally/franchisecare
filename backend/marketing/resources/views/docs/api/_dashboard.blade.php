@verbatim
<section id="dashboard-api" data-nav="Dashboard API (token)" data-group="Dashboard API">
  <h2>Dashboard API <a class="anchor" href="#dashboard-api">#</a></h2>
  <p>The API the app itself uses, for building your own tools. It needs a signed-in user’s <b>token</b>, not an API key (a key is refused here with <code>403</code>). Everything the app can do has an endpoint; they are all listed in the <a href="#endpoints">endpoint reference</a> below.</p>

  <h3>Sign in</h3>
<pre><code># create a company and its first user (skip if you already have an account)
curl -X POST http://localhost:8000/api/register -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"company_name":"Acme","name":"Owner","email":"owner@acme.com","password":"password123","password_confirmation":"password123"}'

# sign in: the answer holds the token
curl -X POST http://localhost:8000/api/login -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"email":"demo@franchisecare.test","password":"password123"}'
# {"success":true,"message":null,"data":{"token":"2|abc…","user":{…},"company":{…}}}

# use it
curl "http://localhost:8000/api/people?per_page=5" -H "Accept: application/json" -H "Authorization: Bearer 2|abc…"</code></pre>
  <ul>
    <li><code>GET /api/me</code> returns the signed-in user and company. <code>POST /api/logout</code> revokes the token. A login token does not expire by itself.</li>
    <li><code>register</code> and <code>login</code> are limited to 10 requests a minute.</li>
    <li>A login email belongs to exactly one company. Every user of a company has full access to it; there are no roles.</li>
  </ul>

  <h3 id="envelope">Response format</h3>
  <p>Every answer of the dashboard API has one shape, so a client can show the server’s sentence and rely on a short machine-readable code. The same <b>error</b> shape is used by every endpoint, including the integration API.</p>
<pre><code>// success    (a list also has "meta": { current_page, last_page, per_page, total, … } and "data" is the items)
{ "success": true,  "message": "Sending stopped.", "data": { … } }

// failure    ("code" is a short reason; "errors" appears for validation only)
{ "success": false, "message": "The name field is required.", "code": "validation_failed",
  "errors": { "name": ["The name field is required."] } }</code></pre>

  <h3 id="errors">Errors</h3>
  <div class="table-wrap"><table>
    <thead><tr><th>HTTP</th><th>code</th><th>When</th></tr></thead>
    <tbody>
      <tr><td>422</td><td><code>validation_failed</code></td><td>The input is not valid; see <code>errors</code>.</td></tr>
      <tr><td>422</td><td><code>unprocessable</code></td><td>The request is fine but cannot be done (for example “Campaign already sent.”).</td></tr>
      <tr><td>401</td><td><code>unauthenticated</code></td><td>No token or key, or it was revoked.</td></tr>
      <tr><td>403</td><td><code>forbidden</code></td><td>Not allowed (for example an API key on the dashboard API).</td></tr>
      <tr><td>404</td><td><code>not_found</code></td><td>The record does not exist, or belongs to another company.</td></tr>
      <tr><td>405</td><td><code>method_not_allowed</code></td><td>Wrong HTTP method for that path.</td></tr>
      <tr><td>409</td><td><code>conflict</code></td><td>It clashes with the current state.</td></tr>
      <tr><td>429</td><td><code>too_many_requests</code></td><td>Rate limit; wait and retry.</td></tr>
      <tr><td>500</td><td><code>server_error</code></td><td>Something broke on our side.</td></tr>
    </tbody>
  </table></div>
  <div class="note"><b>Success bodies not in the envelope:</b> the integration API (<code>/api/v1</code>) answers with the plain person; hosted-form submission answers <code>{message, redirect, existing}</code>; tracking links, media files and CSV exports are files or redirects.</div>

  <h3>Conventions</h3>
  <ul>
    <li><b>Lists are paginated:</b> <code>?page=2&amp;per_page=25</code> (at most 100). The answer has <code>data</code> (the items) and <code>meta</code> (paging).</li>
    <li><b>Search and filters</b> are query parameters. People: <code>search</code>, <code>list_id</code>, <code>tag_id</code>, <code>segment_id</code>, <code>status=subscribed|unsubscribed</code>. Message logs: <code>channel</code>, <code>source</code>, <code>service</code>, <code>status</code>, <code>campaign_id</code>, <code>from</code>, <code>to</code> (dates as <code>YYYY-MM-DD</code>). A campaign’s deliveries: <code>status</code> (<code>sent</code>, <code>skipped</code>, <code>failed</code>, <code>logged</code>, <code>queued</code>) and <code>search</code>.</li>
    <li><b>Bodies are JSON.</b> Deleted records are not returned (deleting is soft). Times are UTC.</li>
  </ul>

  <h3>Examples</h3>
<pre><code># add a person to a list and tag (by id)
curl -X POST http://localhost:8000/api/people -H "Authorization: Bearer $TOKEN" -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"email":"ann@example.com","first_name":"Ann","is_subscribed":true,"list_ids":[1],"tag_ids":[1]}'

# check a campaign, then send it
curl http://localhost:8000/api/campaigns/12/check -H "Authorization: Bearer $TOKEN" -H "Accept: application/json"
# {"data":{"audience":14,"reach":9,"unsubscribed":4,"skipped":5,"warnings":[]}}
curl -X POST http://localhost:8000/api/campaigns/12/send -H "Authorization: Bearer $TOKEN" -H "Accept: application/json"

# who was skipped, and why
curl "http://localhost:8000/api/reports/campaigns/12/deliveries?status=skipped" -H "Authorization: Bearer $TOKEN" -H "Accept: application/json"</code></pre>
</section>
@endverbatim
