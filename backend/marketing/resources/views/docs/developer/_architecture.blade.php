@verbatim
<section id="architecture" data-nav="Architecture" data-group="Code">
  <h2>Architecture <a class="anchor" href="#architecture">#</a></h2>
<pre><code>franchisecare/
├─ backend/marketing/                 Laravel API (+ these docs, hosted forms and pages)
│  ├─ app/Models/                     one model per table; Concerns/: BelongsToCompany, Auditable, IsSendLog
│  ├─ app/Http/Controllers/           one controller per screen / resource
│  ├─ app/Http/Middleware/            WrapApiResponse, LogActivity, BlockIntegrationTokens
│  ├─ app/Services/                   MessageRenderer, SegmentQuery, Automation, FormFields, FormRenderer, Captcha,
│  │                                  History, Subscriptions, SendLog, MailSettings, SmsSettings, Sms/ (drivers)…
│  ├─ app/Jobs/                       SendCampaignJob, SendMessageJob, FinalizeCampaign, ProcessWorkflowRunJob,
│  │                                  SendWebhookJob, SendFormNotificationJob
│  ├─ app/Console/Commands/           campaigns:dispatch-scheduled, automation:process, automation:time-triggers…
│  ├─ app/Support/ApiResponse.php     the one response shape
│  ├─ database/migrations/            schema (history mirrors included)
│  ├─ database/seeders/               one seeder class per table, DatabaseSeeder calls them in order
│  ├─ resources/views/docs/           these documentation pages
│  ├─ routes/{api,web,console}.php    API routes; docs, hosted forms and pages; the scheduler
│  └─ tests/Feature/                  PHPUnit feature tests
└─ frontend/marketing/                Vue 3 single-page app
   ├─ src/views/                      one file per screen
   ├─ src/components/                 shared pieces: FilterBar, Pager, RowMenu, MultiSelect, EmailDesigner, PageDesigner, InfoTip…
   ├─ src/builder/                    formFields.js, formChecks.js, blocks.js (email), pageBlocks.js (pages)
   ├─ src/stores/                     Pinia: auth, ui
   ├─ src/api.js                      the HTTP client: token, envelope unwrapping, error text
   └─ src/router.js, App.vue          routes and the sidebar menu</code></pre>

  <h3>How a request travels</h3>
  <ol>
    <li>The Vue app calls <code>/api/…</code> with <code>Authorization: Bearer &lt;token&gt;</code> (Laravel Sanctum).</li>
    <li>Middleware authenticates, rejects integration keys, records the activity (<code>LogActivity</code>) and wraps the answer in the standard envelope (<code>WrapApiResponse</code>).</li>
    <li>The controller validates the input and works with models. A <b>global scope</b> limits every query to the signed-in company.</li>
    <li>Writes to audited models also write a full row into the table’s <code>_history</code> mirror (the <code>Auditable</code> trait).</li>
    <li>Anything slow (sending, workflow steps, webhooks) is put on the queue and the request returns at once.</li>
  </ol>

  <h3 id="tenancy">Multi-tenancy</h3>
  <p>Every business model uses the <code>BelongsToCompany</code> trait: a global scope named <code>company</code> adds <code>where company_id = &lt;signed-in company&gt;</code> and new rows are stamped with it. <code>company_id</code> is a guarded attribute. Code that runs <b>without</b> a signed-in user (jobs, hosted forms, tracking links, the scheduler) must say so explicitly: <code>Model::withoutGlobalScope('company')->where('company_id', $id)…</code>. A missing company filter there is the classic way to leak data between companies, so tests cover it.</p>

  <h3>The sending pipeline</h3>
<pre><code>POST /campaigns/{id}/send
  → Campaign status = sending, SendCampaignJob::dispatch
SendCampaignJob
  → audienceQuery()  (segment, tag, list or everyone)  in chunks of 200
  → no address: counted in campaigns.audience_skipped, nothing is written to the log
  → unsubscribed: not part of the send
  → otherwise one email_logs / sms_logs row (status queued) and a delayed SendMessageJob,
    spaced by the company's sending speed; campaigns.audience_total is saved
SendMessageJob
  → MessageRenderer fills merge tags, adds tracking and the unsubscribe link
  → SendLog::run sends through the company's mail or SMS service and records sent / logged / failed
  → a temporary error is retried (1 and 5 minutes); anything else fails at once with a readable reason (SendErrors)
FinalizeCampaign::check
  → when nothing is queued any more: campaign = sent (or failed), webhook campaign.sent</code></pre>

  <h3>Background work</h3>
  <div class="table-wrap"><table>
    <thead><tr><th>Piece</th><th>Runs</th><th>What it does</th></tr></thead>
    <tbody>
      <tr><td><code>queue:work</code></td><td>always</td><td>Runs <code>SendCampaignJob</code>, <code>SendMessageJob</code>, <code>ProcessWorkflowRunJob</code> (one workflow step), <code>SendWebhookJob</code> and <code>SendFormNotificationJob</code>.</td></tr>
      <tr><td><code>campaigns:dispatch-scheduled</code></td><td>every minute</td><td>Starts campaigns whose scheduled time has arrived.</td></tr>
      <tr><td><code>automation:process</code></td><td>every minute</td><td>Resumes workflow runs whose wait is over.</td></tr>
      <tr><td><code>automation:time-triggers</code></td><td>daily at 00:15</td><td>Starts workflows for “a date arrives” and “has not opened emails”.</td></tr>
    </tbody>
  </table></div>

  <h3 id="responses">Responses</h3>
  <p>Every answer of <code>/api/*</code> (except the integration API’s successes, hosted forms, tracking, media files and downloads) has one shape, written by <code>App\Support\ApiResponse</code> and <code>WrapApiResponse</code>; exceptions are turned into the failure shape. See the <a href="/docs/api#envelope">API documentation</a>.</p>
<pre><code>return ApiResponse::ok($campaign, 'Sending stopped.');       // 200 with a sentence for the person
return ApiResponse::created($form);                           // 201
return ApiResponse::paginated($query->paginate(25));         // items as data, paging as meta
return ApiResponse::error('Sending failed.', 422, 'send_failed');
return ApiResponse::validation(['email' => ['Not valid.']]);  // 422 with field errors</code></pre>
  <p>A controller that simply returns a model, a list or a paginator is wrapped automatically. The frontend’s <code>src/api.js</code> unwraps successes, so a screen still reads <code>response.data</code> as the record or list; the sentence is <code>responseMessage(r)</code>. In tests, <code>json()</code> and <code>assertJson*()</code> see the payload inside the envelope.</p>

  <h3 id="history">Change history</h3>
  <p>A model that uses <code>Auditable</code> writes a copy of itself into <code>&lt;table&gt;_history</code> on create, update, delete and restore, through <code>App\Services\History</code>, with the user, source, IP and the changed columns. Pivot tables (<code>people_list_members</code>, <code>person_tag</code>) are audited as pivot models. Secrets are never shown: <code>History::scrub</code> masks encrypted company settings when history is displayed. <code>History::TYPES</code> maps a URL type (<code>person</code>, <code>campaign</code>…) to its table for <code>/api/history/{type}/{id}</code>. <code>php artisan history:backfill</code> writes a first history row for records that existed before history was added.</p>

  <h3>Code map</h3>
  <div class="table-wrap"><table>
    <thead><tr><th>Feature</th><th>Backend</th><th>Frontend</th></tr></thead>
    <tbody>
      <tr><td><b>People</b>, lists, tags, segments, custom fields, subscriptions</td><td><code>PersonController</code>, <code>PeopleListController</code>, <code>TagController</code>, <code>SegmentController</code>, <code>PersonFieldController</code>, <code>SubscriptionController</code>, <code>SegmentQuery</code>, <code>Subscriptions</code></td><td><code>People.vue</code>, <code>Lists.vue</code>, <code>Tags.vue</code>, <code>Segments.vue</code>, <code>CustomFields.vue</code>, <code>Subscriptions.vue</code></td></tr>
      <tr><td><b>Campaigns</b> and sending</td><td><code>CampaignController</code>, <code>Campaign::audienceQuery()</code>, <code>SendCampaignJob</code>, <code>SendMessageJob</code>, <code>FinalizeCampaign</code>, <code>SendLog</code>, <code>MessageRenderer</code>, <code>CompanyMailer</code>, <code>Sms/*</code></td><td><code>CampaignEditor.vue</code>, <code>Campaigns.vue</code>, <code>AudiencePreview.vue</code>, <code>EmailDesigner.vue</code>, <code>MessageEditor.vue</code></td></tr>
      <tr><td><b>Reports</b>, logs, analytics</td><td><code>ReportController</code> (incl. <code>deliveries</code>), <code>LogController</code>, <code>AnalyticsController</code></td><td><code>Reports.vue</code>, <code>ReportDetail.vue</code>, <code>DeliveryReport.vue</code>, <code>Logs.vue</code>, <code>Analytics*.vue</code></td></tr>
      <tr><td><b>Forms</b></td><td><code>FormController</code>, <code>PublicController</code>, <code>FormFields</code>, <code>FormRenderer</code>, <code>Captcha</code>, <code>FormSubmissionController</code></td><td><code>FormBuilder.vue</code>, <code>builder/formFields.js</code>, <code>builder/formChecks.js</code>, <code>Forms.vue</code>, <code>FormSubmissions.vue</code>, <code>InfoTip.vue</code></td></tr>
      <tr><td><b>Pages</b>, templates, media</td><td><code>LandingPageController</code>, <code>LayoutController</code>, <code>TemplateController</code>, <code>MediaController</code>, <code>OnlineImageImport</code>, <code>ImageSearch</code>, <code>Attachments</code></td><td><code>LandingPages.vue</code>, <code>PageDesigner.vue</code>, <code>Templates.vue</code>, <code>EmailDesigner.vue</code>, <code>builder/blocks.js</code>, <code>builder/pageBlocks.js</code>, <code>Media.vue</code></td></tr>
      <tr><td><b>Automation</b></td><td><code>WorkflowController</code>, <code>Automation</code>, <code>ProcessWorkflowRunJob</code>, the <code>automation:*</code> commands</td><td><code>Workflows.vue</code>, <code>Triggers.vue</code>, <code>AutomationHistory.vue</code></td></tr>
      <tr><td><b>Webhooks</b></td><td><code>WebhookController</code>, <code>WebhookDispatcher</code>, <code>SendWebhookJob</code>, <code>UrlGuard</code></td><td><code>Webhooks.vue</code>, <code>WebhookDeliveries.vue</code></td></tr>
      <tr><td><b>API keys</b> and integration API</td><td><code>ApiKeyController</code>, <code>ApiV1Controller</code>, <code>BlockIntegrationTokens</code></td><td><code>Integrations.vue</code></td></tr>
      <tr><td><b>Settings</b>, users, account</td><td><code>SettingsController</code>, <code>ConfigController</code>, <code>MailSettings</code>, <code>SmsSettings</code>, <code>MailOAuthController</code>, <code>UserController</code>, <code>AccountController</code></td><td><code>Settings*.vue</code>, <code>EmailConfig.vue</code>, <code>SmsConfig.vue</code>, <code>Users.vue</code>, <code>Account.vue</code></td></tr>
      <tr><td><b>History</b> and activity</td><td><code>Auditable</code>, <code>History</code>, <code>HistoryController</code>, <code>LogActivity</code>, <code>ActivityLogController</code></td><td><code>HistoryList.vue</code>, <code>HistoryTimeline.vue</code>, <code>HistoryVersion.vue</code>, <code>ActivityLogs.vue</code></td></tr>
    </tbody>
  </table></div>
</section>
@endverbatim
