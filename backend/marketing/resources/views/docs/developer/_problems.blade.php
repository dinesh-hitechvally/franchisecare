@verbatim
<section id="troubleshooting" data-nav="Troubleshooting" data-group="Operations">
  <h2>Troubleshooting <a class="anchor" href="#troubleshooting">#</a></h2>
  <div class="table-wrap"><table>
    <thead><tr><th>You see</th><th>Why</th><th>Do this</th></tr></thead>
    <tbody>
      <tr><td>Messages stay <b>Queued</b>; a workflow shows “in progress”</td><td>The queue worker is not running (or runs old code).</td><td><code>php artisan queue:work</code>. After changing PHP code, <code>php artisan queue:restart</code>. Look at <code>php artisan queue:failed</code> and <code>storage/logs/laravel.log</code>.</td></tr>
      <tr><td>A message <b>Failed</b> with “connection refused … 1025”</td><td>The mail service is Mailpit and Mailpit is not running.</td><td>Start Mailpit (Laragon → Menu → Mail). Mail appears at <code>http://localhost:8025</code>.</td></tr>
      <tr><td>Everything is <b>Logged only</b></td><td>The company’s email or SMS service is <i>Log only</i>.</td><td>Pick a real service in <b>Settings → Email / SMS sending</b>.</td></tr>
      <tr><td><code>SQLSTATE … Unknown database</code> / access denied</td><td>The database does not exist, or <code>DB_*</code> is wrong.</td><td>Create it (<code>CREATE DATABASE marketing …</code>) and fix <code>.env</code>; then <code>php artisan config:clear</code>.</td></tr>
      <tr><td>The app shows network errors / CORS errors</td><td><code>VITE_API_URL</code> is wrong, the API is not running, or the page origin is not allowed.</td><td>Check <code>frontend/marketing/.env</code> (<code>VITE_API_URL=http://localhost:8000/api</code>) and restart <code>npm run dev</code>.</td></tr>
      <tr><td>API: <b>401</b> <code>unauthenticated</code></td><td>No token, or it was revoked (signing out revokes it).</td><td>Sign in again and send <code>Authorization: Bearer …</code> with <code>Accept: application/json</code>.</td></tr>
      <tr><td>API: <b>403</b> with an integration key</td><td>Integration keys only work on <code>/api/v1</code>.</td><td>Use a signed-in token for the dashboard API.</td></tr>
      <tr><td>API: <b>422</b></td><td>The input is not valid, or the action is not possible right now.</td><td>Read <code>message</code> and, for validation, <code>errors</code>.</td></tr>
      <tr><td>A webhook delivery <b>failed</b></td><td>The address is private or local, the endpoint took longer than 10 seconds, or it did not answer 2xx.</td><td>Open <b>Webhook deliveries</b> for status and response. For a local test URL set <code>OUTBOUND_ALLOW_PRIVATE=true</code> (development only).</td></tr>
      <tr><td>A form says “The check has expired”</td><td>The captcha token is older than 6 hours, was used, or had 5 wrong tries.</td><td>Reload the page. The counters live in the cache; clearing the cache resets them.</td></tr>
      <tr><td>These pages show JSON or return 404</td><td>The request asked for JSON, or <code>DOCS_ENABLED</code> is off (production).</td><td>Open them in a browser; set <code>DOCS_ENABLED=true</code> to publish them in production. After editing the views run <code>php artisan view:clear</code>.</td></tr>
      <tr><td>A change to a seeded template is gone</td><td>Re-running <code>TemplateSeeder</code> restores the three demo templates.</td><td>Edit a copy (Clone) instead, or do not re-seed that table.</td></tr>
      <tr><td>Queued jobs fail after a rename or deploy</td><td>Their payload names a class or column that no longer exists.</td><td>Clear the old jobs (<code>php artisan queue:clear</code>), restart the worker, send again.</td></tr>
    </tbody>
  </table></div>
</section>
@endverbatim
