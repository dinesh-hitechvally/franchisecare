@verbatim
<section id="requirements" data-nav="Requirements" data-group="Install">
  <h2>Requirements <a class="anchor" href="#requirements">#</a></h2>
  <div class="table-wrap"><table>
    <thead><tr><th>You need</th><th>Notes</th></tr></thead>
    <tbody>
      <tr><td><b>PHP 8.3+</b></td><td>With the usual Laravel extensions: <code>mbstring</code>, <code>openssl</code>, <code>pdo_mysql</code>, <code>fileinfo</code>, <code>curl</code>, <code>ctype</code>, <code>json</code>, <code>tokenizer</code>, <code>xml</code>.</td></tr>
      <tr><td><b>Composer 2</b></td><td>Installs the PHP packages.</td></tr>
      <tr><td><b>MySQL 8</b> (or MariaDB 10.6+)</td><td>A database called <code>marketing</code>, character set <code>utf8mb4</code>. The tests do not need it (they use in-memory SQLite).</td></tr>
      <tr><td><b>Node 20+</b> and npm</td><td>For the Vue app (Vite).</td></tr>
      <tr><td>A way to see mail (optional)</td><td>On Windows, Laragon includes Mailpit (SMTP 1025, inbox at <code>http://localhost:8025</code>).</td></tr>
    </tbody>
  </table></div>
</section>

<section id="install" data-nav="Installation" data-group="Install">
  <h2>Installation <a class="anchor" href="#install">#</a></h2>

  <h3>1. The database</h3>
<pre><code>CREATE DATABASE marketing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;</code></pre>

  <h3>2. The backend</h3>
<pre><code>cd franchisecare/backend/marketing
composer install
cp .env.example .env
php artisan key:generate
# edit .env: DB_DATABASE=marketing, DB_USERNAME, DB_PASSWORD (see the settings table below)
php artisan migrate --seed        # --seed adds a demo company with people, templates, forms and draft campaigns
php artisan serve                 # http://localhost:8000  (the documentation, the API, hosted forms)</code></pre>

  <h3>3. The two background processes</h3>
  <p>Without them nothing is sent. Run each in its own terminal:</p>
<pre><code>php artisan queue:work        # sends emails and texts, runs workflow steps, delivers webhooks
php artisan schedule:work     # fires scheduled campaigns, resumes workflow waits, runs the daily time triggers</code></pre>
  <div class="warn"><b>After changing PHP code</b> run <code>php artisan queue:restart</code>. A running worker keeps the old code in memory until it is restarted.</div>

  <h3>4. The frontend</h3>
<pre><code>cd franchisecare/frontend/marketing
npm install
cp .env.example .env              # VITE_API_URL=http://localhost:8000/api
npm run dev                       # http://localhost:5173</code></pre>

  <h3>5. Check it works</h3>
  <ol>
    <li>Open <code>http://localhost:8000/</code>: this documentation. A JSON request to the same address returns <code>{"app": …}</code>.</li>
    <li>Open <code>http://localhost:5173</code> and sign in with <code>demo@franchisecare.test</code> / <code>password123</code>.</li>
    <li>Run the tests: <code>php artisan test</code> (about 200 tests, a few seconds).</li>
    <li>Send a test email from <b>Settings → Email sending</b> and watch it appear in <b>Analytics → Message logs</b>. If it stays <i>Queued</i>, the worker is not running.</li>
  </ol>
</section>

<section id="settings" data-nav="Settings (.env)" data-group="Install">
  <h2>Settings (.env) <a class="anchor" href="#settings">#</a></h2>
  <div class="table-wrap"><table>
    <thead><tr><th>Setting</th><th>Meaning</th></tr></thead>
    <tbody>
      <tr><td><code>APP_KEY</code>, <code>APP_URL</code>, <code>APP_ENV</code>, <code>APP_DEBUG</code></td><td>The usual Laravel settings. <code>APP_KEY</code> also encrypts company secrets and captcha tokens: changing it makes stored secrets unreadable.</td></tr>
      <tr><td><code>DB_*</code></td><td>The MySQL connection (database <code>marketing</code>).</td></tr>
      <tr><td><code>QUEUE_CONNECTION=database</code></td><td>Messages and workflow steps wait in the <code>jobs</code> table until <code>queue:work</code> picks them up.</td></tr>
      <tr><td><code>MAIL_MAILER</code> and <code>MAIL_*</code></td><td>The fallback mail service. <code>log</code> writes mail to <code>storage/logs/laravel.log</code>. Each company picks its own service in Settings (Google, Microsoft, SMTP, Log only).</td></tr>
      <tr><td><code>CACHE_STORE</code></td><td>Used for rate limits and the captcha’s used / wrong-try counters. The database store works.</td></tr>
      <tr><td><code>DOCS_ENABLED</code></td><td>These documentation pages. On everywhere except production; set <code>true</code> to publish them there.</td></tr>
      <tr><td><code>OUTBOUND_ALLOW_PRIVATE</code></td><td>Webhook URLs and SMTP hosts may not point at private or local addresses. Set <code>true</code> on a development machine only.</td></tr>
      <tr><td><code>UNSPLASH_ACCESS_KEY</code>, <code>PEXELS_API_KEY</code>, <code>PIXABAY_API_KEY</code></td><td>Optional extra sources for Media’s online images. Openverse and Wikimedia Commons need no keys.</td></tr>
      <tr><td><code>GOOGLE_MAIL_CLIENT_ID</code>, <code>GOOGLE_MAIL_CLIENT_SECRET</code>, <code>MICROSOFT_MAIL_CLIENT_ID</code>, <code>MICROSOFT_MAIL_CLIENT_SECRET</code>, <code>MICROSOFT_MAIL_TENANT</code>, <code>FRONTEND_URL</code></td><td>For the one-click “connect your Google / Microsoft account” mail sending: register one OAuth app per provider with the redirect address <code>APP_URL/api/oauth/mail/{google|microsoft}/callback</code>. <code>FRONTEND_URL</code> is where the browser returns afterwards (default <code>http://localhost:5173</code>).</td></tr>
    </tbody>
  </table></div>
</section>

<section id="build" data-nav="Building for production" data-group="Install">
  <h2>Building for production <a class="anchor" href="#build">#</a></h2>
<pre><code># frontend: static files in frontend/marketing/dist
cd franchisecare/frontend/marketing
VITE_API_URL=https://api.example.com/api npm run build

# backend
cd franchisecare/backend/marketing
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache &amp;&amp; php artisan route:cache &amp;&amp; php artisan view:cache</code></pre>
  <p>Serve <code>dist/</code> from any static host with every unknown path falling back to <code>index.html</code> (a single-page app). Serve the API from <code>backend/marketing/public</code>. Run the worker under Supervisor and the scheduler from cron; see <a href="#ops">Running it</a>.</p>
</section>
@endverbatim
