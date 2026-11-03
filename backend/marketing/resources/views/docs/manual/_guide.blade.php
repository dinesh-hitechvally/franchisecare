@verbatim
<section id="guide" data-nav="Screens at a glance" data-group="Using the app">
  <h2>Screens at a glance <a class="anchor" href="#guide">#</a></h2>
  <p>The sidebar groups the app into the areas below.</p>
  <div class="table-wrap"><table>
    <thead><tr><th>Menu</th><th>Screen</th><th>What you do there</th></tr></thead>
    <tbody>
      <tr><td><b>Dashboard</b></td><td>Dashboard</td><td>Totals, recent campaigns and messages, upcoming scheduled sends.</td></tr>
      <tr><td><b>Media</b></td><td>Media</td><td>Uploads and free online images in one library. Edit title, description and credit; choose the company logo.</td></tr>
      <tr><td rowspan="6"><b>Audience</b></td><td>People</td><td>Search and filter everyone, add, edit, import and export CSV, bulk tag / list / delete. Each person shows their subscription status.</td></tr>
      <tr><td>Lists</td><td>Named groups of people.</td></tr>
      <tr><td>Tags</td><td>Coloured labels. A person can have many.</td></tr>
      <tr><td>Segments</td><td>Saved rules (tag, list, email domain, subscription, dates…) that always show the matching people.</td></tr>
      <tr><td>Subscriptions</td><td>Who is subscribed; unsubscribe or re-subscribe one or many (re-subscribing asks you to confirm permission).</td></tr>
      <tr><td>Custom Fields</td><td>Extra data on a person (a birthday, a plan). Usable as merge tags and in forms.</td></tr>
      <tr><td rowspan="2"><b>Campaigns</b></td><td>Campaigns</td><td>Write or design a message, choose an audience, check, send or schedule. Stop a send, clone, send a test.</td></tr>
      <tr><td>Reports</td><td>One report per sent campaign: delivered, skipped, failed, logged only, opens, clicks, and a delivery list you can filter.</td></tr>
      <tr><td rowspan="3"><b>Content</b></td><td>Forms</td><td>Build sign-up and contact forms; see submissions; share a link or embed.</td></tr>
      <tr><td>Pages</td><td>Build hosted web pages from sections; choose a name, a page title and a slug.</td></tr>
      <tr><td>Templates</td><td>Email (visual designer or raw HTML) and SMS templates to start campaigns from.</td></tr>
      <tr><td rowspan="4"><b>Automation</b></td><td>Workflows</td><td>A trigger plus steps. Turn on when ready; new ones start as drafts.</td></tr>
      <tr><td>Triggers</td><td>All triggers and which workflows use them.</td></tr>
      <tr><td>Webhooks</td><td>Send events to other systems, with deliveries and histories.</td></tr>
      <tr><td>Automation History</td><td>People in flight (cancel one) and a step-by-step log.</td></tr>
      <tr><td><b>Analytics</b></td><td>Overview, Email, SMS, Delivery &amp; errors, Message logs, People, Forms &amp; pages</td><td>Reports over 7 to 90 days. <b>Message logs</b> lists every email and text that was sent or tried.</td></tr>
      <tr><td><b>Settings</b></td><td>Company profile, email and SMS sending, users, API keys, My activity</td><td>Sender details, which mail and SMS service to use, who can sign in, API keys, and what you did.</td></tr>
    </tbody>
  </table></div>
  <p>Most tables have a filter bar above them, a <b>History</b> button for the change history, and an actions menu. Grid views (Templates, Media) show the actions as buttons.</p>
</section>

<section id="concepts" data-nav="Key concepts" data-group="Using the app">
  <h2>Key concepts <a class="anchor" href="#concepts">#</a></h2>

  <h3>People and subscription</h3>
  <ul>
    <li>A person has <b>one</b> subscription flag (<code>is_subscribed</code>, with <code>subscribed_at</code>). It covers email and SMS together.</li>
    <li><b>Unsubscribed people are never sent to</b>, whatever audience you choose. They are simply not part of the send.</li>
    <li>Every email carries an unsubscribe link and a one-click <code>List-Unsubscribe</code> header; a template without an unsubscribe link gets one added in a footer.</li>
    <li>Only a person (or you, in <b>Subscriptions</b>) can subscribe someone again. Forms and imports never re-subscribe someone who opted out.</li>
  </ul>

  <h3>Choosing an audience</h3>
  <p>A campaign goes to <b>one source</b>: a segment, a tag, a list, or all people. If more than one is somehow set, a segment wins over a tag, and a tag over a list. In the editor the <b>Subscription</b> dropdown (All Status, Subscribed, Unsubscribed) only narrows the preview below it; it is not saved. Once a campaign is sent, its page shows the audience size <i>at the time it was sent</i>, so adding people later does not change it.</p>

  <h3>What the message statuses mean</h3>
  <div class="table-wrap"><table>
    <thead><tr><th>Status</th><th>Meaning</th></tr></thead>
    <tbody>
      <tr><td><b>Queued</b></td><td>Waiting for the worker. If it stays queued, <code>queue:work</code> is not running.</td></tr>
      <tr><td><b>Sent</b> (shown as Delivered)</td><td>Handed to the mail or SMS service successfully.</td></tr>
      <tr><td><b>Logged only</b></td><td>The service in use is <i>Log only</i>: the message was written to a log and nothing was delivered. A test mode.</td></tr>
      <tr><td><b>Failed</b></td><td>The service refused it. The reason is shown, with advice where the cause is recognised (wrong password, Mailpit not running…). Temporary problems are retried after 1 and 5 minutes first.</td></tr>
      <tr><td><b>Skipped</b></td><td>On a campaign: the person has <b>no email address or phone number</b>, so nothing could be sent. These are counted (Reports, the campaign page) and listed by name in the campaign’s Delivery Report, but not written to the message log. A message waiting when you press <i>Stop sending</i> is also skipped, and that one is logged.</td></tr>
    </tbody>
  </table></div>

  <h3>Sending safety</h3>
  <ul>
    <li><b>Check before sending</b> shows how many will receive it and warns about an empty or long subject, a link to <code>localhost</code>, a missing sender address, or a long SMS.</li>
    <li>Messages go out at the company’s <b>sending speed</b> (messages per minute), so a mail account’s limits are not hit.</li>
    <li><b>Stop sending</b> halts a campaign that is sending: what went out stays sent, the rest is skipped.</li>
    <li>Email carries a plain-text part and list-unsubscribe headers; clicks and opens are tracked.</li>
  </ul>

  <h3>Times, deleting and history</h3>
  <ul>
    <li>Times are stored in UTC and a schedule is kept exactly as typed: the server sends it when its UTC clock reaches that time.</li>
    <li>Deleting is <b>soft</b>: the row only gets a <code>deleted_at</code> and is hidden.</li>
    <li>Every change to an audited record is mirrored in a history table: who, when, what. The <b>History</b> screens show a timeline, and the page, form, template and campaign editors can open any earlier version read-only.</li>
  </ul>

  <div class="note"><b>People, not Contacts.</b> This screen used to be called Contacts. Everything now says <b>people</b> / <b>person</b>: the menu, the reports and the documentation. Old web addresses that contain <code>/contacts</code> still work and take you to People. A form of type <b>Contact form</b> is a kind of form (a message from a visitor), not the People screen, so it keeps its name.</div>
</section>

<section id="forms" data-nav="Forms" data-group="Using the app">
  <h2>Forms <a class="anchor" href="#forms">#</a></h2>
  <p>Create a form from <b>Content → Forms</b>. Choose a type first: <b>Newsletter sign-up</b> (people are saved and subscribed), <b>Contact form</b> (you get the message by email, the person gets a confirmation, nobody is added unless you turn that on) or <b>Other</b> (answers stay in the form’s submissions). The type only picks sensible defaults; every action is its own setting.</p>

  <h3>The builder</h3>
  <p>The left panel lists fields to click or drag in; the middle is a live preview; the right sidebar has three tabs that always mean the same thing:</p>
  <div class="table-wrap"><table>
    <thead><tr><th>Tab</th><th>Nothing selected: the whole form</th><th>A field selected: just that field</th></tr></thead>
    <tbody>
      <tr><td><b>General</b></td><td>Heading, description, button text, thank-you message.</td><td>Label, placeholder, options, <b>default value</b>, help text, required.</td></tr>
      <tr><td><b>Design</b></td><td>Colours, corner radius, form width, font.</td><td>Width in the row (½, ⅓ … so fields can share a row).</td></tr>
      <tr><td><b>Settings</b></td><td>Form type, redirect, what happens on submit (save to People, lists, tags), email me submissions, auto-reply, active.</td><td><b>Validation</b> and whether the answer is also saved on the person.</td></tr>
    </tbody>
  </table></div>
  <p>Click the form card or the page background to go back to the whole form. A small <b>i</b> after a label explains it on hover; explanations longer than 60 characters are shown that way instead of as text.</p>

  <h3>Fields</h3>
  <p>People fields fill the person’s own record. <b>Subscribe to newsletter</b> is a tick box that decides whether a new person is subscribed (only people who tick it are; without the box the form type’s default applies). Other fields are kept in the submission, and can also be saved on the person as a custom field. <b>Captcha</b> is a small sum (<code>7 + 4 = ?</code>) with no outside service: the answer travels in an encrypted token that expires after 6 hours, allows 5 wrong tries and works once. It is always required, only one per form, and never stored with the answers.</p>
  <p>Field and form types known to the server:</p>
  <p>
  @endverbatim
  @foreach ($fieldTypes as $t)<code>{{ $t }}</code> @endforeach
  </p>
  <p>Form types: @foreach ($formTypes as $t)<code>{{ $t }}</code> @endforeach</p>
  @verbatim

  <h3>Validation</h3>
  <p>Select a field and open <b>Settings → Validation</b>. The rules are saved with the form and checked again by the server when someone submits (with your own error message if you wrote one). The hosted form passes length and number or date limits to the browser too.</p>
  <ul>
    <li><b>Length</b> (text, names, mobile): minimum and maximum characters. <b>Number</b>: smallest and largest. <b>Date</b>: earliest and latest.</li>
    <li><b>Format</b> (text, names, mobile, email): pick a ready-made pattern or <b>Custom pattern…</b> and type a regular expression without slashes.</li>
    <li><b>Allowed email domains</b>: for example <code>acme.com, acme.org</code>.</li>
    <li><b>Try a value</b> shows at once whether a sample answer passes.</li>
  </ul>
  <div class="table-wrap"><table>
    <thead><tr><th>Ready-made format</th><th>Accepts, for example</th></tr></thead>
    <tbody>
      <tr><td>Email address (stricter)</td><td><code>name@company.com</code></td></tr>
      <tr><td>Phone, international</td><td><code>+61400000000</code></td></tr>
      <tr><td>Phone, Australian mobile</td><td><code>0412 345 678</code>, <code>+61412345678</code></td></tr>
      <tr><td>Phone, digits only (8 to 15)</td><td><code>0412345678</code></td></tr>
      <tr><td>Postcode, Australia (4 digits)</td><td><code>2000</code></td></tr>
      <tr><td>ZIP code, USA</td><td><code>90210</code>, <code>90210-1234</code></td></tr>
      <tr><td>Letters only (names)</td><td><code>Mary-Jane O'Neil</code></td></tr>
      <tr><td>Numbers only</td><td><code>12345</code></td></tr>
      <tr><td>Letters and numbers only</td><td><code>ABC123</code></td></tr>
      <tr><td>Web address</td><td><code>https://example.com</code></td></tr>
    </tbody>
  </table></div>

  <h3>After someone submits</h3>
  <ul>
    <li>The form is replaced by a <b>thank-you panel</b> (a tick, your thank-you message, “Send another response”), or the visitor is redirected after a moment if you set a redirect address. The button says “Sending…” and cannot be pressed twice. A problem shows a red message and focuses the field to fix.</li>
    <li>A form that saves people matches an existing person by <b>email</b> (or phone when there is no email): no duplicate is made, blanks are filled, nothing is overwritten, subscription is never changed, and the form’s lists and tags are added.</li>
    <li>An optional <b>message for people who are already in People</b> is shown instead of the thank-you. Left empty, everybody sees the same thank-you, so a visitor can never learn who is in your people. The submissions list always shows <b>New</b> or <b>Existing</b>.</li>
    <li>Submissions are kept in the form’s inbox, can be emailed to up to 5 addresses, answered with an auto-reply, exported, and start <code>form_submitted</code> workflows and <code>form.submitted</code> webhooks.</li>
  </ul>

  <h3>Sharing</h3>
  <p>A saved form has a hosted link <code>/f/{slug}</code> and an embed snippet (an iframe) under <b>Share / embed</b>. Pages can include forms too. Hosted pages are served with a strict Content-Security-Policy, so page HTML cannot run its own scripts.</p>
</section>

<section id="messaging" data-nav="Email, SMS and templates" data-group="Using the app">
  <h2>Email, SMS and templates <a class="anchor" href="#messaging">#</a></h2>

  <h3>Templates and the designer</h3>
  <p>A template is only a starting point. Importing one into a campaign <i>copies</i> its content and attachments; editing the campaign afterwards never changes the template. Email can be built visually from blocks, or written as raw HTML:</p>
  <p><code>header</code> <code>hero</code> <code>heading</code> <code>text</code> <code>image</code> <code>button</code> <code>form</code> <code>features</code> <code>coupon</code> <code>split</code> <code>divider</code> <code>spacer</code> <code>social</code> <code>footer</code> <code>html</code></p>
  <p>SMS templates are plain text. A single message holds 160 characters of ordinary text; emoji or accents shorten each part to 70, and longer texts are sent as several parts and may cost more. The campaign check tells you.</p>
  <p>The demo data includes a designed <b>Welcome email</b> (for people who just registered), a <b>Welcome SMS</b> and a <b>Promo SMS</b> to start from.</p>

  <h3>Merge tags</h3>
  <p>Write a tag in a subject or body and it is replaced for each person.</p>
  @endverbatim
  <p>
  @foreach ($tags as $t)<code>&#123;&#123;{{ $t }}&#125;&#125;</code> @endforeach
  <code>&#123;&#123;company_phone&#125;&#125;</code> <code>&#123;&#123;company_website&#125;&#125;</code> <code>&#123;&#123;company_email&#125;&#125;</code> <code>&#123;&#123;company_logo&#125;&#125;</code>
  </p>
  @verbatim
  <p>Any <b>custom field</b> is also a tag, using its key. A person with no first name gets “there”. The company logo tag shows the logo chosen in Media.</p>

  <h3>Email services</h3>
  <p>Each company chooses how its email is sent under <b>Settings → Email sending</b>: the server’s own mail, an SMTP account, <b>Google</b> or <b>Microsoft</b> (one-click sign-in), several saved connections with one in use, or <b>Log only</b> (nothing is sent, messages are only written to a log). The sender name and address come from the company profile.</p>
  <h3>SMS providers</h3>
  <p>Twilio, Vonage, MessageBird, MessageMedia, ClickSend, Sinch, Plivo, Telnyx, or <b>Log only</b>. Developers can add another provider; see the <a href="/docs/developer">Install &amp; code</a> guide.</p>
  <h3>Tracking</h3>
  <p>An invisible pixel records opens, and links are rewritten to record clicks (<code>/api/t/o/…</code>, <code>/api/t/c/…</code>). Open and click rates appear in Reports and Analytics.</p>
</section>

<section id="workflows" data-nav="Workflows" data-group="Automation">
  <h2>Workflows <a class="anchor" href="#workflows">#</a></h2>
  <p>A workflow is a <b>trigger</b> followed by <b>steps</b>. Each person who triggers it becomes a <i>run</i> that moves through the steps. New workflows start as <b>draft</b>; seeding never starts sending by itself.</p>
  <div class="table-wrap"><table>
    <thead><tr><th>Step</th><th>What it does</th></tr></thead>
    <tbody>
      <tr><td><code>send_email</code> / <code>send_sms</code></td><td>Sends a template to the person through the normal pipeline, so it appears in Message logs. Skipped (and logged as such in the run) when the person is unsubscribed or has no address.</td></tr>
      <tr><td><code>wait</code></td><td>Pauses for an amount of minutes, hours, days or weeks. The scheduler resumes it.</td></tr>
      <tr><td><code>add_tag</code> / <code>remove_tag</code></td><td>Changes the person’s tags. Removing a tag only triggers workflows if the tag was really removed, so workflows cannot loop.</td></tr>
      <tr><td><code>add_to_list</code></td><td>Adds the person to a list.</td></tr>
    </tbody>
  </table></div>
  <p>Switching a workflow on starts it for new matches only; people already inside keep their place when you pause it. <b>Automation History</b> shows people in flight (you can cancel one) and a step-by-step log.</p>
</section>
@endverbatim
