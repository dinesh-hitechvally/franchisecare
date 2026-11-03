@verbatim
<section id="overview" data-nav="Welcome" data-group="Start">
  <h1>Project manual</h1>
  <p class="lead">FranchiseCare Marketing lets you collect people through forms and pages, group them, send emails and texts to them, automate follow-ups, and see what worked. This manual explains every screen in plain language.</p>

  <div class="cards">
    <div class="card"><h3>People</h3><p>Everyone you market to, with one subscription status, custom fields, lists and tags.</p></div>
    <div class="card"><h3>Campaigns</h3><p>Email and SMS to a list, a tag, a segment or everyone, with a check before you send.</p></div>
    <div class="card"><h3>Forms and pages</h3><p>Build sign-up and contact forms and web pages without code.</p></div>
    <div class="card"><h3>Templates</h3><p>A designed welcome email and ready texts to start from, and a visual email designer.</p></div>
    <div class="card"><h3>Automation</h3><p>Send a welcome email when someone registers, wait, tag, follow up.</p></div>
    <div class="card"><h3>Reports</h3><p>Delivered, skipped, failed, opened, clicked: per campaign and over time.</p></div>
  </div>

  <div class="note"><b>Your data is yours alone.</b> Everything belongs to your company. Signing up creates a company and its first user; everyone you add to the company has full access to it, and nobody outside it can ever see it.</div>

  <h3>How this manual is organised</h3>
  <ol>
    <li><a href="#getting-started">Getting started</a>: set up the basics and send your first campaign.</li>
    <li><a href="#guide">Screens at a glance</a> and <a href="#concepts">Key concepts</a>: where things are and the ideas behind them.</li>
    <li><a href="#forms">Forms</a>, <a href="#messaging">Email, SMS and templates</a>, <a href="#workflows">Workflows</a>: the main tools in depth.</li>
    <li><a href="#reports">Reports and analytics</a>, <a href="#settings">Settings</a>, <a href="#problems">Common problems</a>.</li>
  </ol>
  <p>Developers: see <a href="/docs/developer">Install &amp; code</a>. Connecting another system: see the <a href="/docs/api">API documentation</a>.</p>
</section>

<section id="getting-started" data-nav="Getting started" data-group="Start">
  <h2>Getting started <a class="anchor" href="#getting-started">#</a></h2>
  <p>You can send your first campaign in about ten minutes.</p>

  <h3>Before you send anything</h3>
  <ol>
    <li><b>Sign in</b> (or choose <i>Create a company account</i>). In the demo, use <code>demo@franchisecare.test</code> / <code>password123</code>.</li>
    <li>Open <b>Settings → Company profile</b> and fill in the business name, address and website. The name and address appear in your emails’ footer, and <code>{{company_name}}</code> uses them.</li>
    <li>Open <b>Settings → Email sending</b> and choose how email is sent (see <a href="#settings">Settings</a>). For texts, <b>Settings → SMS sending</b>.</li>
    <li>Make sure the background sender is running. Messages wait in a queue until it picks them up; if they stay <i>Queued</i>, ask whoever runs the server to start it (see <a href="#problems">Common problems</a>).</li>
  </ol>
  <div class="tip"><b>Safe to try.</b> Choose the <i>Log only</i> email or SMS service while you learn: messages are written to a log and nothing reaches a real person.</div>

  <h3>Add people</h3>
  <p>Open <b>Audience → People</b>. Add one person with <i>Add person</i>, or import a CSV (the first row must be a header with <code>first_name, last_name, email, phone</code>). Create a <b>List</b> (for example “Newsletter”) and put people on it, or give them a <b>Tag</b>. A person who is unsubscribed is shown as such and is never sent to.</p>

  <h3>Collect people automatically</h3>
  <p>Open <b>Content → Forms → New form</b> and choose <i>Newsletter sign-up</i>. Share its link or embed it on your website. Everyone who fills it in becomes a person, with the list and tags you chose. See <a href="#forms">Forms</a>.</p>

  <h3>Send a campaign</h3>
  <ol>
    <li><b>Campaigns → New Campaign</b>. Give it a name and choose <b>Email</b> or <b>SMS</b>.</li>
    <li>Choose the <b>Audience</b>: all people, a list, a tag or a segment. The table below shows who it is; the <i>Subscription</i> dropdown only filters that preview.</li>
    <li>Write the message. For email, <i>Import from template</i> starts from a ready design (for example <b>Welcome email</b>), then <i>Edit design</i> to change it. The template itself never changes. Add attachments from Media if you need them.</li>
    <li>Send yourself a <b>test</b> first.</li>
    <li>Press <b>Send</b>. The app first <b>checks</b> the campaign and tells you how many will receive it and what to fix (an empty subject, a link to <code>localhost</code>, a missing sender address…). Confirm to send now, or schedule it for later.</li>
  </ol>

  <h3>See how it went</h3>
  <p>Open <b>Campaigns → Reports</b> and the campaign. You see delivered, skipped, failed and logged-only counts, opens and clicks, and a <b>Delivery Reports</b> list of every person that you can filter. Every message is also in <b>Analytics → Message logs</b>.</p>

  <h3>Welcome new people automatically</h3>
  <p>Open <b>Automation → Workflows</b>. A workflow such as <i>“Welcome new people”</i> starts when <b>a person is added</b>, sends the welcome email, waits three days and adds a tag. New workflows start as <i>draft</i>; switch one on when you are ready. See <a href="#workflows">Workflows</a>.</p>
</section>
@endverbatim
