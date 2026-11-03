<section id="overview" data-nav="Overview" data-group="Start">
  <h1>FranchiseCare Marketing</h1>
  <p class="lead">A multi-company email and SMS marketing platform. Collect people through forms and pages, group them with lists, tags and segments, send campaigns, automate follow-ups and see what worked. This page is the whole manual, from first run to the API.</p>

  <div class="stats">
    <div class="stat"><b>{{ $endpointCount }}</b><span>API endpoints</span></div>
    <div class="stat"><b>{{ $triggerCount }}</b><span>automation triggers</span></div>
    <div class="stat"><b>{{ $eventCount }}</b><span>webhook events</span></div>
    <div class="stat"><b>{{ $tableCount }}</b><span>data tables</span></div>
  </div>

  <div class="cards">
    <div class="card"><h3>People</h3><p>Everyone you market to, with one subscription flag, custom fields, lists and tags. Import and export CSV.</p></div>
    <div class="card"><h3>Campaigns</h3><p>Email and SMS to a list, tag, segment or everyone. Pre-send check, throttled sending, stop, clone, test.</p></div>
    <div class="card"><h3>Forms and pages</h3><p>A drag-and-drop form builder and a page builder. Hosted links, embeds, validation, captcha, thank-you message.</p></div>
    <div class="card"><h3>Automation</h3><p>Workflows started by a trigger: send an email or text, wait, tag, move to a list. Webhooks tell other systems.</p></div>
    <div class="card"><h3>Analytics</h3><p>Reports for email, SMS, delivery, automation, people and forms, plus per-campaign delivery reports.</p></div>
    <div class="card"><h3>An API</h3><p>A signed-in dashboard API, a small key-based integration API, public form endpoints and signed webhooks.</p></div>
  </div>

  <div class="note"><b>Companies are separate.</b> Everything belongs to a company. Signing up creates a company and its first user; every user of a company has full access to that company’s data, and no company can ever see another’s.</div>
  <p>Technology: <b>Laravel {{ $env['laravel'] }}</b> (PHP {{ $env['php'] }}, Sanctum tokens, MySQL, a database queue) for the API in <code>backend/marketing</code>, and a <b>Vue 3</b> single-page app (Vite, Pinia, vue-router) in <code>frontend/marketing</code>.</p>
</section>

@verbatim
<section id="quickstart" data-nav="Quick start" data-group="Start">
  <h2>Quick start <a class="anchor" href="#quickstart">#</a></h2>
  <h3>1. Start the backend</h3>
<pre><code>cd franchisecare/backend/marketing
composer install
cp .env.example .env &amp;&amp; php artisan key:generate     # then set DB_* (database name: marketing)
php artisan migrate --seed                          # --seed adds a demo company, people, forms, templates and campaigns
php artisan serve                                   # http://localhost:8000  (this page)</code></pre>
  <h3>2. Start the two background processes</h3>
  <p>Nothing is sent without them. Open two more terminals:</p>
<pre><code>php artisan queue:work        # sends emails and texts, runs workflow steps, delivers webhooks  (required)
php artisan schedule:work     # fires scheduled campaigns, resumes workflow waits, runs daily time triggers</code></pre>
  <div class="warn"><b>After changing PHP code</b> run <code>php artisan queue:restart</code>. A running worker keeps the old code in memory until it is restarted.</div>
  <h3>3. Start the frontend</h3>
<pre><code>cd franchisecare/frontend/marketing
npm install &amp;&amp; cp .env.example .env     # VITE_API_URL=http://localhost:8000/api
npm run dev                              # http://localhost:5173</code></pre>
  <h3>4. Sign in</h3>
  <p>After seeding: <code>demo@franchisecare.test</code> / <code>password123</code>. Or choose <b>Create a company account</b> on the sign-in screen.</p>

  <h3>Addresses</h3>
  <div class="table-wrap"><table>
    <thead><tr><th>What</th><th>Address</th></tr></thead>
    <tbody>
      <tr><td>The app (dashboard)</td><td><code>http://localhost:5173</code></td></tr>
      <tr><td>The API and this documentation</td><td><code>http://localhost:8000</code> (the API is under <code>/api</code>)</td></tr>
      <tr><td>A hosted form / page</td><td><code>/f/{slug}</code> and <code>/p/{slug}</code> on the API address</td></tr>
      <tr><td>Test mail inbox (Laragon Mailpit)</td><td><code>http://localhost:8025</code> (SMTP on port 1025)</td></tr>
    </tbody>
  </table></div>

  <h3>Settings that matter (.env)</h3>
  <div class="table-wrap"><table>
    <thead><tr><th>Setting</th><th>Meaning</th></tr></thead>
    <tbody>
      <tr><td><code>DB_*</code></td><td>The MySQL database (<code>marketing</code>). Tests use an in-memory SQLite database instead and never touch it.</td></tr>
      <tr><td><code>QUEUE_CONNECTION=database</code></td><td>Messages and workflow steps wait in the <code>jobs</code> table until <code>queue:work</code> picks them up.</td></tr>
      <tr><td><code>MAIL_MAILER</code></td><td>The fallback mail service. <code>log</code> writes mail to <code>storage/logs/laravel.log</code>. Each company can choose its own service in Settings.</td></tr>
      <tr><td><code>DOCS_ENABLED</code></td><td>This page. On everywhere except production; set <code>true</code> to publish it there. Clients asking for JSON always get <code>{"app": …}</code>.</td></tr>
      <tr><td><code>OUTBOUND_ALLOW_PRIVATE</code></td><td>Webhook URLs and SMTP hosts may not point at private or local addresses. Set <code>true</code> on a development machine only.</td></tr>
      <tr><td><code>UNSPLASH_ACCESS_KEY</code>, <code>PEXELS_API_KEY</code>, <code>PIXABAY_API_KEY</code></td><td>Optional. Free stock images (Openverse, Wikimedia Commons) need no keys; these add more sources.</td></tr>
    </tbody>
  </table></div>
</section>

<section id="architecture" data-nav="Architecture" data-group="Start">
  <h2>Architecture <a class="anchor" href="#architecture">#</a></h2>
<pre><code>franchisecare/
├─ backend/marketing/              Laravel API
│  ├─ app/Models/                  one model per table; Concerns/ has BelongsToCompany and Auditable
│  ├─ app/Http/Controllers/        one controller per screen / resource
│  ├─ app/Services/                MessageRenderer, SegmentQuery, Automation, FormRenderer, History, Sms/ drivers…
│  ├─ app/Jobs/                    SendCampaignJob, SendMessageJob, ProcessWorkflowRunJob, SendWebhookJob…
│  ├─ app/Support/ApiResponse.php  the one response shape
│  ├─ database/migrations|seeders  schema, and one seeder class per table
│  ├─ resources/views/docs/        this documentation page
│  └─ tests/                       PHPUnit feature tests
└─ frontend/marketing/             Vue 3 app
   ├─ src/views/                   one file per screen
   ├─ src/components/              shared pieces (tables, pickers, designers, InfoTip…)
   ├─ src/builder/                 form fields, email blocks, page blocks and their rules
   └─ src/api.js                   the HTTP client: token, envelope unwrapping, error text</code></pre>

  <h3>How a request travels</h3>
  <ol>
    <li>The Vue app calls <code>/api/…</code> with <code>Authorization: Bearer &lt;token&gt;</code> (Laravel Sanctum).</li>
    <li>Middleware authenticates the user, records the activity, and wraps the answer in the standard <a href="#envelope">envelope</a>.</li>
    <li>Models carry a <b>company scope</b>: every query is automatically limited to the signed-in user’s company, and every new row is stamped with it. That is how companies stay separate.</li>
    <li>Writes to audited tables also write a full copy into the table’s <code>_history</code> mirror.</li>
    <li>Anything slow (sending, workflow steps, webhooks) is put on the queue and returns at once.</li>
  </ol>

  <h3>Background work</h3>
  <div class="table-wrap"><table>
    <thead><tr><th>Piece</th><th>Runs</th><th>What it does</th></tr></thead>
    <tbody>
      <tr><td><code>queue:work</code></td><td>always</td><td><code>SendCampaignJob</code> expands a campaign’s audience and queues one <code>SendMessageJob</code> per person at the company’s sending speed. <code>ProcessWorkflowRunJob</code> runs one workflow step. <code>SendWebhookJob</code> delivers an event. <code>SendFormNotificationJob</code> emails form submissions to your team.</td></tr>
      <tr><td><code>campaigns:dispatch-scheduled</code></td><td>every minute</td><td>Starts campaigns whose scheduled time has arrived.</td></tr>
      <tr><td><code>automation:process</code></td><td>every minute</td><td>Resumes workflow runs whose wait is over.</td></tr>
      <tr><td><code>automation:time-triggers</code></td><td>daily at 00:15</td><td>Starts workflows for “a date arrives” and “has not opened emails”.</td></tr>
    </tbody>
  </table></div>
  <p>In production run the worker under Supervisor and add <code>* * * * * php artisan schedule:run</code> to cron.</p>
</section>
@endverbatim
