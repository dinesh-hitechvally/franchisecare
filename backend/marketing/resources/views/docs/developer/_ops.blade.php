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
      <tr><td><code>TemplateSeeder</code></td><td>A designed Welcome email, a Welcome SMS and a Promo SMS (re-running restores them). The email’s design and HTML are in <code>database/seeders/data/</code>.</td></tr>
      <tr><td><code>SegmentSeeder</code></td><td>“Gmail VIPs”.</td></tr>
      <tr><td><code>CampaignSeeder</code></td><td>Six <b>draft</b> campaigns, one for each kind of audience. Drafts only, so seeding never sends anything.</td></tr>
      <tr><td><code>FormSeeder</code></td><td>A newsletter sign-up and a contact form.</td></tr>
      <tr><td><code>WorkflowSeeder</code></td><td>A “Welcome new people” workflow, created as a draft.</td></tr>
    </tbody>
  </table></div>
  <p>Seed one table with <code>php artisan db:seed --class=PersonSeeder</code>, after the seeders it depends on. <code>php artisan migrate:fresh --seed</code> wipes the database and starts again.</p>

  <h3>Tests</h3>
<pre><code>cd franchisecare/backend/marketing
php artisan test                       # all
php artisan test --filter=FormFieldRulesTest</code></pre>
  <p>The suite uses an in-memory SQLite database refreshed for every test, so it never touches your MySQL data. Feature tests register a company through the real endpoints and read the real responses.</p>

  <h3>Production checklist</h3>
  <ul>
    <li>Run <code>queue:work</code> under Supervisor (or similar) and add <code>* * * * * php artisan schedule:run</code> to cron. Restart the worker on every deploy: <code>php artisan queue:restart</code>.</li>
    <li>Set <code>APP_ENV=production</code>, <code>APP_DEBUG=false</code>, a real <code>APP_KEY</code> and <code>APP_URL</code>, and the database and mail settings. These documentation pages are off in production unless <code>DOCS_ENABLED=true</code>.</li>
    <li>Run <code>php artisan migrate --force</code>. Each company sets its own email and SMS service in the app.</li>
    <li>Back up the database. Everything, including change history, lives in it; uploaded media is under <code>storage/</code>.</li>
    <li>After a code change that touches queued classes, restart the worker; old queued payloads that name a renamed class will fail.</li>
  </ul>

  <h3>Security in short</h3>
  <ul>
    <li><b>Separate companies:</b> a global scope limits every query to the signed-in company, and rows are stamped with it on create.</li>
    <li><b>Tokens:</b> Sanctum bearer tokens; integration keys carry the <code>integration</code> ability and are limited to <code>/api/v1</code>. Company settings that hold secrets (SMTP passwords, API keys) are stored encrypted and masked in history.</li>
    <li><b>Outbound calls:</b> webhook URLs and SMTP hosts are checked (<code>UrlGuard</code>) so they cannot target private or local networks; image imports are size-limited and content-sniffed.</li>
    <li><b>Public pages:</b> hosted forms and pages are served with a strict Content-Security-Policy; form submission has a honeypot, a rate limit and an optional captcha; webhooks are signed.</li>
    <li><b>CSV exports</b> neutralise cells that start with <code>=</code>, <code>+</code>, <code>-</code> or <code>@</code> so a spreadsheet will not run them as formulas.</li>
  </ul>
</section>
@endverbatim
