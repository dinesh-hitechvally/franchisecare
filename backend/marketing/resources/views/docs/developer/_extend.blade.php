@verbatim
<section id="extend" data-nav="Extending it" data-group="Code">
  <h2>Extending it <a class="anchor" href="#extend">#</a></h2>
  <p>Recipes for the common changes. Each ends with a test: the suite signs in through the real endpoints and reads the real responses, so a feature is only done when a feature test covers it.</p>

  <h3>Add a new kind of record (table, model, API, screen)</h3>
  <ol>
    <li><b>Migration</b> in <code>database/migrations</code>: a table with <code>company_id</code> (foreign key to <code>companies</code>, cascade), your columns, and <code>created_at</code>, <code>updated_at</code>, <code>deleted_at</code> as nullable <code>dateTime</code>. Add a <code>&lt;table&gt;_history</code> mirror (the same columns, all nullable and without constraints, plus <code>history_id</code>, <code>change_action</code>, <code>changed_at</code>, <code>changed_by</code>, <code>changed_by_name</code>, <code>change_source</code>, <code>change_ip</code>, <code>changed_fields</code>, <code>record_label</code>, <code>history_company_id</code>); copy an existing pair such as <code>tags</code> / <code>tags_history</code>.</li>
    <li><b>Model</b> in <code>app/Models</code> with <code>use BelongsToCompany, SoftDeletes, Auditable;</code>, <code>$guarded = ['id', 'company_id']</code> and an <code>auditLabel()</code> that names a row in the history. Register it in <code>History::TYPES</code> (URL type ⇒ table) and in the <code>BackfillHistory</code> model list.</li>
    <li><b>Controller</b> and route: <code>Route::apiResource('things', ThingController::class)</code> inside the signed-in group of <code>routes/api.php</code>. Validate with <code>$request->validate([...])</code>, return the model or a paginator (the envelope is added for you) and use <code>ApiResponse</code> when you want a sentence for the person.</li>
    <li><b>Screen</b>: a view in <code>src/views</code>, a route in <code>src/router.js</code>, a menu entry in the <code>sections</code> list of <code>App.vue</code>. List screens use <code>FilterBar</code>, <code>Pager</code> and <code>RowMenu</code> (which also gives the History entry).</li>
    <li><b>Test</b> in <code>tests/Feature</code> with <code>RefreshDatabase</code>: register a company through <code>/api/register</code>, call your endpoints with the returned token, and assert another company cannot see the record.</li>
  </ol>

  <h3>Add an SMS provider</h3>
  <p>Implement <code>App\Services\Sms\SmsDriver</code> (copy a small one such as <code>LogDriver</code> or <code>MessageMediaDriver</code>), register it in <code>SmsManager</code> and describe its settings fields in <code>SmsProviders</code>; the SMS configuration screen builds its form from that description.</p>

  <h3>Add an automation trigger</h3>
  <ol>
    <li>Add an entry to <code>Automation::TRIGGERS</code> (key ⇒ group, label, description, and which setting it needs: <code>list</code>, <code>tag</code>, <code>form</code>, <code>url</code>, <code>date</code> or <code>days</code>). The Triggers and Workflows screens and the manual’s trigger table read this list.</li>
    <li>Where the thing happens call <code>Automation::fire('your_key', $person, $context)</code>. Instant triggers fire from the place that causes them; a time trigger is added to <code>runTimeTriggers()</code>.</li>
    <li>Avoid loops: a step that changes data must not fire the trigger that started it (see how <code>remove_tag</code> only fires when a tag was really removed).</li>
  </ol>

  <h3>Add a webhook event</h3>
  <p>Add it to <code>WebhookDispatcher::EVENTS</code> (name ⇒ label) and call <code>WebhookDispatcher::dispatch($companyId, 'thing.happened', $data)</code> where it happens. The webhook screen and the API documentation list the events from that constant.</p>

  <h3>Add a form field type</h3>
  <ol>
    <li>Server: add it to <code>FormFields</code> (<code>types()</code>, <code>isInput()</code> and, if it needs one, a fixed key as <code>subscribe</code> and <code>captcha</code> have), render it in <code>FormRenderer::field()</code>, and validate it in <code>PublicController::submit()</code>.</li>
    <li>Builder: add it to <code>FIELD_TYPES</code> in <code>src/builder/formFields.js</code> (label, icon, group, hint, default), show it in the canvas of <code>FormBuilder.vue</code> and, if it takes rules, in <code>src/builder/formChecks.js</code>.</li>
    <li>Test it end to end: save a form with the field, read the hosted page, submit valid and invalid answers.</li>
  </ol>

  <h3>Add a merge tag</h3>
  <p>Add the name to <code>MessageRenderer::TAGS</code> and its value to <code>MessageRenderer::vars()</code>. Custom fields need no code: every person field is a tag automatically.</p>

  <h3>Conventions worth keeping</h3>
  <ul>
    <li>Say <b>people / person</b> in code, tables and text. The words “contact(s)” remain only in the <b>Contact form</b> form type and the company’s “contact details”.</li>
    <li>One response shape for <code>/api/*</code>; never hand-roll a JSON error.</li>
    <li>A new table gets <code>company_id</code>, soft deletes and a history mirror. Public and background code states the company explicitly.</li>
    <li>Seeders: one class per table, added to <code>DatabaseSeeder</code> in dependency order; they must be safe to run twice.</li>
    <li>User-facing explanations longer than about 60 characters go in an <code>InfoTip</code> after the label that they explain.</li>
    <li>Update the <a href="/docs/manual">manual</a> and <a href="/docs/api">API</a> pages when behaviour changes; the generated parts (endpoints, triggers, events, tables, merge tags) update themselves.</li>
  </ul>
</section>
@endverbatim
