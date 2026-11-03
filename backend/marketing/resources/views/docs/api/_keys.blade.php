@verbatim
<section id="keys" data-nav="Creating an API key" data-group="API keys">
  <h2>Creating an API key <a class="anchor" href="#keys">#</a></h2>
  <p>Each system that connects gets its own key, so you can see which one is in use and switch one off without touching the others.</p>

  <h3>In the app: Settings → API keys</h3>
  <ol>
    <li>Open <b>Settings</b> (bottom of the sidebar), then <b>API keys</b> (under <i>Developers</i>). The page is called <i>API Integrations</i>.</li>
    <li>Under <b>Create an API key</b> type a name that tells you what uses it, for example <i>Website</i> or <i>Zapier</i> (up to 80 characters), and press <b>Create key</b>.</li>
    <li>A window shows the key once. <b>Copy it now.</b> For security it is never shown again and cannot be retrieved later; if you lose it, revoke it and create a new one.</li>
    <li>The table lists each key’s <b>name</b>, when it was <b>created</b> and when it was <b>last used</b> (“never” until the first call), with a <b>Revoke</b> button.</li>
    <li>The same page has a short <b>Using the API</b> section with the endpoints and a ready-made curl example filled in with your server’s address.</li>
  </ol>
  <p>A key looks like <code>10|k3J9vQ2mX7pLz5tN8bR1cW4yH6dF0sGuAeIo2TnY</code>: a number, a bar and a long random text.</p>

  <h3>Using a key</h3>
  <p>Send it as a bearer token. The base address is your API address followed by <code>/api</code>, for example <code>http://localhost:8000/api</code>:</p>
<pre><code>curl "http://localhost:8000/api/v1/people?email=jane@example.com" \
  -H "Authorization: Bearer 10|k3J9vQ2mX7pLz5tN8bR1cW4yH6dF0sGuAeIo2TnY" \
  -H "Accept: application/json"</code></pre>

  <h3>What a key can and cannot do</h3>
  <ul>
    <li>It can call the three <a href="#integration-api">integration endpoints</a> under <code>/api/v1</code> only, at <b>120 requests a minute</b>.</li>
    <li>It acts inside the company that created it. It cannot open the dashboard or call any other endpoint: those answer <code>403</code> with <i>“This API key can only be used with the /api/v1 endpoints.”</i></li>
    <li>It never expires on its own. <b>Revoke</b> it and anything using it stops at once (the next call answers <code>401</code>).</li>
  </ul>

  <div class="warn"><b>Treat a key like a password.</b> Keep it on your server (an environment variable, your automation tool’s secret store), never in browser code, a public repository or a page. If it may have leaked, revoke it. Give each system its own key.</div>

  <h3>Managing keys from code</h3>
  <p>The dashboard API can do what the screen does. These need a signed-in token (<a href="#dashboard-api">see below</a>), not an API key:</p>
<pre><code>GET    /api/api-keys               →  { "success": true, "data": [ { "id": 10, "name": "Website", "last_used_at": null, "created_at": "…" } ] }
POST   /api/api-keys   {"name": "Website"}   →  201, { "data": { "id": 10, "name": "Website", "key": "10|…" } }   // the key, once
DELETE /api/api-keys/{id}                   →  204 (the key is revoked)</code></pre>
</section>
@endverbatim
