@verbatim
<section id="ops" data-nav="Running it" data-group="Operations">
  <h2>Running it <a class="anchor" href="#ops">#</a></h2>

  <h3>Demo data</h3>
  <p><code>php artisan migrate --seed</code> (or <code>php artisan db:seed</code> at any time; it is safe to repeat) creates a demo company with one seeder class per table, in dependency order:</p>
  <div class="table-wrap"><table>
    <thead><tr><th>Seeder</th><th>Creates</th></tr></thead>
    <tbody>
      <tr><td><code>CompanySeeder</code>, <code>UserSeeder</code></td><td>“Demo Company” and the owner <code>demo@franchisecare.test</code> / <code>password123</code>.</td></tr>
      <tr><td><code>PeopleListSeeder</code>, <code>TagSeeder</code></td><td>A Newsletter list; tags VIP and Website lead.</td></tr>
      <tr><td><code>PersonSeeder</code></td><td>14 people covering every case: email only, phone only, both; subscribed and unsubscribed.</td></tr>
      <tr><td><code>PeopleListMemberSeeder</code>, <code>PersonTagSeeder</code></td><td>Puts every person on the list and gives them tags.</td></tr>
      <tr><td><code>TemplateSeeder</code></td><td>A designed Welcome email, a Welcome SMS and a Promo SMS (re-running restores them).</td></tr>
      <tr><td><code>SegmentSeeder</code></td><td>“Gmail VIPs”.</td></tr>
      <tr><td><code>CampaignSeeder</code></td><td>Six <b>draft</b> campaigns, one for each kind of audience. Drafts only, so seeding never sends anything.</td></tr>
      <tr><td><code>FormSeeder</code></td><td>A newsletter sign-up and a contact form.</td></tr>
      <tr><td><code>WorkflowSeeder</code></td><td>A “Welcome new people” workflow, created as a draft.</td></tr>
    </tbody>
  </table></div>
  <p>Seed one table on its own with <code>php artisan db:seed --class=PersonSeeder</code>, after the seeders it depends on.</p>

  <h3>Tests</h3>
<pre><code>cd franchisecare/backend/marketing
php artisan test</code></pre>
  <p>The suite uses an in-memory SQLite database and refreshes it for every test, so it never touches your MySQL data. Feature tests sign in through the real endpoints and read the real responses.</p>

  <h3>Production checklist</h3>
  <ul>
    <li>Run <code>queue:work</code> under Supervisor (or similar) and add <code>* * * * * php artisan schedule:run</code> to cron. Restart the worker on every deploy: <code>php artisan queue:restart</code>.</li>
    <li>Set <code>APP_ENV=production</code>, <code>APP_DEBUG=false</code>, a real <code>APP_KEY</code>, a real <code>APP_URL</code>, and the database and mail settings. This page is off in production unless <code>DOCS_ENABLED=true</code>.</li>
    <li>Run <code>php artisan migrate --force</code>. Each company sets its own email and SMS service in Settings.</li>
    <li>Back up the database. Everything, including change history, lives in it; uploaded media is under <code>storage/</code>.</li>
  </ul>

  <h3>Security in short</h3>
  <ul>
    <li><b>Separate companies:</b> a global scope limits every query to the signed-in company, and rows are stamped with it on create.</li>
    <li><b>Tokens:</b> Sanctum bearer tokens; integration keys are limited to <code>/api/v1</code>. Company settings that hold secrets (SMTP passwords, API keys) are stored encrypted and masked in history.</li>
    <li><b>Outbound calls:</b> webhook URLs and SMTP hosts are checked so they cannot target private or local networks; image imports are size-limited and sniffed.</li>
    <li><b>Public pages:</b> hosted forms and pages are served with a strict Content-Security-Policy; form submission has a honeypot, a rate limit and an optional captcha; webhooks are signed.</li>
    <li><b>CSV exports</b> neutralise cells that start with <code>=</code>, <code>+</code>, <code>-</code> or <code>@</code> so a spreadsheet will not run them as formulas.</li>
  </ul>
</section>

<section id="troubleshooting" data-nav="Troubleshooting" data-group="Operations">
  <h2>Troubleshooting <a class="anchor" href="#troubleshooting">#</a></h2>
  <div class="table-wrap"><table>
    <thead><tr><th>You see</th><th>Why</th><th>Do this</th></tr></thead>
    <tbody>
      <tr><td>Messages stay <b>Queued</b>; a workflow shows “in progress” and nothing is sent</td><td>The queue worker is not running.</td><td>Run <code>php artisan queue:work</code> and leave it open. After changing PHP code, <code>php artisan queue:restart</code>.</td></tr>
      <tr><td>A message <b>Failed</b> with “connection refused … 1025”</td><td>The mail service is Mailpit and Mailpit is not running.</td><td>Laragon → Menu → Mail → Start Mailpit. Sent mail appears at <code>http://localhost:8025</code> and never reaches real inboxes.</td></tr>
      <tr><td>Everything is <b>Logged only</b></td><td>The email or SMS service in use is <i>Log only</i>, a test mode that writes to a log and sends nothing.</td><td>Choose a real service in <b>Settings → Email / SMS sending</b>, then send again.</td></tr>
      <tr><td>A campaign shows a <b>Skipped</b> count</td><td>Those people have no email address (or phone number for SMS).</td><td>Open the campaign’s report: the Delivery Report lists them by name. Unsubscribed people are not counted as skipped; they are just not sent to.</td></tr>
      <tr><td>The campaign page shows fewer people than the People screen</td><td>A sent campaign shows its audience as it was when sent.</td><td>Nothing is wrong; people added later are not part of that send.</td></tr>
      <tr><td>A form says “The check has expired”</td><td>The captcha sum is older than 6 hours, was already used, or was answered wrongly 5 times.</td><td>Reload the page to get a new sum.</td></tr>
      <tr><td>A form shows “That answer is not right”</td><td>The captcha answer is wrong.</td><td>Answer the sum again; there are 5 tries per sum.</td></tr>
      <tr><td>API: <b>401</b> <code>unauthenticated</code></td><td>No token, or it was revoked (signing out revokes it).</td><td>Sign in again and send <code>Authorization: Bearer …</code> with <code>Accept: application/json</code>.</td></tr>
      <tr><td>API: <b>403</b> with an integration key</td><td>Integration keys only work on <code>/api/v1</code>.</td><td>Use a signed-in token for the dashboard API.</td></tr>
      <tr><td>API: <b>422</b></td><td>The input is not valid, or the action is not possible right now.</td><td>Read <code>message</code> and, for validation, <code>errors</code>.</td></tr>
      <tr><td>A webhook delivery <b>failed</b></td><td>The address is private or local, the endpoint took longer than 10 seconds, or it did not answer 2xx.</td><td>Open <b>Webhook deliveries</b> for the status and response. For a local test URL set <code>OUTBOUND_ALLOW_PRIVATE=true</code> (development only).</td></tr>
      <tr><td>This page shows JSON instead</td><td>The request asked for JSON, or the page is off (production).</td><td>Open it in a browser; set <code>DOCS_ENABLED=true</code> to publish it in production.</td></tr>
      <tr><td>Times look hours off</td><td>Times are stored in UTC and a schedule is kept exactly as typed.</td><td>Enter schedules in UTC, or convert before you type.</td></tr>
    </tbody>
  </table></div>
</section>
@endverbatim
