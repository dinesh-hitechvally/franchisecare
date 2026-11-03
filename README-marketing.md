# FranchiseCare Marketing

Multi-tenant email + SMS marketing. `backend/marketing/` = Laravel 13 API (Sanctum tokens), `frontend/marketing/` = Vue 3 SPA (Vite, Pinia).

**Documentation** (run the backend, then open): `http://localhost:8000/` is a hub linking three separate guides: **`/docs/manual`** (the project manual: every screen, forms, templates, automation, reports, settings, common problems), **`/docs/developer`** (installation, settings, architecture, a map of the code, the data model, how to extend it, deployment, troubleshooting) and **`/docs/api`** (creating API keys in Settings → API keys, the integration API with examples, webhooks and signatures, public endpoints, the dashboard API and every endpoint). Endpoints, triggers, webhook events, merge tags, field types and tables on those pages are read from the running app. They are on everywhere except production; set `DOCS_ENABLED=true` to publish them there. API clients that ask for JSON at `/` still get `{"app": …}`.

## Run

```bash
# backend (MySQL database: marketing — set DB_* in .env)
cd franchisecare/backend/marketing
composer install && cp .env.example .env && php artisan key:generate
php artisan migrate --seed             # --seed adds a demo account + sample data
php artisan serve                      # http://localhost:8000
php artisan queue:work                 # sends messages, runs workflows & webhooks (required!)
php artisan schedule:work              # fires scheduled campaigns + resumes workflow waits

# frontend
cd franchisecare/frontend/marketing
npm install && cp .env.example .env    # VITE_API_URL=http://localhost:8000/api
npm run dev                            # http://localhost:5173
```

Demo login (after `--seed`): `demo@franchisecare.test` / `password123` (owner).
After changing PHP code, run `php artisan queue:restart` — a running worker keeps the old code in memory.
Production: run the worker under Supervisor and add `* * * * * php artisan schedule:run` to cron.

## Menu → features

**Audience** — **People** (the people you market to: search, filters, bulk actions, CSV import) · Lists · **Tags** (coloured labels) · **Custom Fields** (extra data on people beyond name/email/mobile: company, plan… — defined once, editable on the person, usable as merge tags, filled by forms and the API) ·
**Segments** (saved rule groups: tag, list, name/email/domain, source, date added, opened-in-last-N-days… match ALL/ANY, live count) ·
**Subscriptions** (per-channel opt-in/out, dates, bulk re-subscribe).

**Campaigns** — All / Email / SMS / Scheduled (cancel a schedule) · audience = a list or a segment · **Campaign Reports**
(delivery, open & click rates, opens/clicks over time, top links, failures, CSV export) · Message Logs.

**Content** — Email Templates (visual designer: blocks, ready-made & saved layouts, code editor) · SMS Templates ·
**Landing Pages** (a dedicated web-page builder, separate from the email designer: full-width responsive sections — navigation, hero, numbers, features, steps, testimonials, pricing, FAQ, sign-up form, footer, custom HTML — with web layouts such as Lead magnet, Product launch, Event, Coming soon and Service business; desktop/tablet/phone preview; free photos with automatic credits; the "Sign-up form" section marks where the attached form appears; own public link; older email-style pages still work) · **Forms** (full-screen form builder: drag-and-drop fields in a column layout, colours & fonts, hosted page + embed code). Pick a **form type** — Newsletter sign-up, Contact form or Other — then choose what happens on submit: save to people (and whether they are subscribed to marketing), add to lists/tags, **email the submission to up to 5 addresses**, send the person an **auto-reply**, show a thank-you or redirect. Person and Other forms start as an **inbox only** (no person is created) — turn on "Save them to my people" when you want one. When a form does save people, name/email/mobile fill the person's own columns and each extra question can be saved as a custom field (or kept in the inbox only). Every submission is kept in the form's **inbox** (view, delete, CSV export). Custom field names that would clash with a built-in tag get a `person_` prefix (e.g. `{{person_company_name}}`; `{{company_name}}` is always *your* company).

**Automation** — **Workflows** (trigger → send email/SMS, wait, add/remove tag, add to list) · **Triggers** (catalogue) · **Webhooks** (send events to other systems; moved here from Settings, the old `/settings/webhooks` and `/webhooks` addresses redirect) ·
**Automation History** (people in flight with cancel, and a step-by-step log).
Triggers (Automation → Triggers, a table with filters; each row has **Create workflow**):
- **Person:** is added · joins a list · leaves a list · tag is added · tag is removed · submits a form · unsubscribes (workflows never email or text unsubscribed people, so use this to tag them or move lists).
- **Email activity:** clicks a link (any link, or only links containing some text) · opens an email (the first open of each message).
- **Time** (checked once a day at 00:15 server time, command `automation:time-triggers`, needs the scheduler running): a date arrives (a Date custom field such as a birthday or renewal: on the day, N days before or after, once or every year; dates are matched as YYYY-MM-DD) · has not opened emails for N days (received our email at least N days ago, opened none since, still subscribed; not started again within N days).

A person is never in the same workflow twice at once, a date trigger never starts the same person twice in a day, and a workflow that removes the tag it listens for cannot loop. Workflows start as drafts; activate them when ready.

**Analytics** — seven reports, each with a 7 / 14 / 30 / 60 / 90 day period: **Overview** (headline numbers compared with the previous period) · **Email** (opens, clicks, per-campaign table, top links) · **SMS** (sent, failed, by provider, per-campaign table) · **Delivery & errors** (sent / failed / skipped, failure and skip reasons grouped, success rate per service, latest failures) · **People** (growth, subscriptions, engagement, sources) · **Forms & pages** (views → submissions) · **Automation** (workflow runs, completion, messages sent, step results). Tables have an Export CSV button. Per-campaign detail stays under Campaigns → Reports. API: `GET /api/analytics/{overview,email,sms,delivery,automation,people,conversions}?days=`.

**System Settings** — Company Profile · **Email Configuration** (sender + your own SMTP, test send) ·
**SMS Configuration** (Twilio and other SMS services, or Log only; test send) · **API Integrations** · **Webhooks** · **Users** · **Activity Logs**.

## Companies and users

- Signing up (**Create a company account**) creates a new company and its first user.
- A company has many users. Every user has full access — there are no roles — and all of them see the same company data.
- **System Settings → Users** adds people to *your* company: a new user always joins the company of the person who creates them.
- Companies are completely separate: one company can never see another's people, campaigns, templates, users or settings.
- A login email belongs to exactly one company.

## Integrations

- **API keys** (Settings → API Integrations): `POST /api/v1/people` upserts a person (tags/lists matched by name),
  `GET /api/v1/people?email=`, `POST /api/v1/people/unsubscribe`. A key only works on `/api/v1`.
- **Webhooks**: `person.created`, `person.unsubscribed`, `campaign.sent`, `form.submitted`, `message.failed`, `workflow.completed`.
  Each POST carries `X-Webhook-Signature: sha256=<HMAC of the raw body>` keyed with the webhook's secret (shown once).
- Webhook URLs and SMTP hosts may not point at private/local addresses unless `OUTBOUND_ALLOW_PRIVATE=true` (dev only).

## Notes

- Free stock images need no keys (Openverse + Wikimedia Commons). Unsplash / Pexels / Pixabay appear if you set their keys once in `.env`.
- SMS drivers live in `app/Services/Sms/`; implement `SmsDriver` and register it in `SmsManager` for other providers.
- Hosted forms/pages are served with a strict Content-Security-Policy so page HTML can't run its own scripts.
- Tests: `cd franchisecare/backend/marketing && php artisan test`.

## Deleting is soft

Deleting a person, list, tag, segment, template, campaign, form, landing page, workflow, webhook, custom field, user… only sets `deleted_at`; the row stays in the database and is hidden everywhere in the app (restore with `Model::onlyTrashed()->find($id)->restore()`). A deleted email, tag name or custom-field key can be used again (uniqueness only applies to rows that are not deleted, via the generated `email_active` / `name_active` / `key_active` columns). Queued messages whose campaign or person was deleted are marked *skipped*. People' list memberships live in `people_list_members`; list/tag memberships and framework tables (jobs, cache, tokens) are not soft-deleted.

Form builder: the left panel has **Custom fields** (right under Contact details; the other inputs are under **Other fields**) — drag one in and the question is tied to that field (same merge tag, same choices); questions you invent still create a custom field automatically.

## Change history (data mirroring)

Every tracked table has a mirror, `<table>_history` (`people_history`, `campaigns_history`, …): after each create, update, delete or restore a **full copy of the row** is written there, plus who changed it (`changed_by`, `changed_by_name`), where from (`change_source`: dashboard / api / public / system), when (`changed_at`) and which columns differ from the previous copy (`changed_fields`). Tracked: companies, users, people, lists, list memberships, tags, person tags, segments, templates, layouts, campaigns, forms, landing pages, workflows, webhooks, custom fields and their values, form submissions. Message logs, click events, workflow runs, webhook deliveries and the activity log are logs themselves and are not mirrored.

* **In the app:** every table has an **Actions** column with a ⋮ menu; its last item, **Change history**, shows that row's full timeline (old → new). System Settings → Activity Logs → **Change history** is the feed across all tables, with filters.
* Passwords, keys and other secrets are never copied (shown as ••••••); history can't be edited or removed through the app; view counters don't create copies.
* A model joins by using the `Auditable` trait and having a `<table>_history` table whose columns include all of the source table's columns — a test fails if a column is added to a tracked table without adding it to its history table. Changes made with a plain query (no model events) call `History::record()` explicitly.
* `php artisan history:backfill` gives rows that existed before history was switched on a first "Existing record" copy.

## Filters

Every table page has a filter bar **above** the table card (search plus the filters that make sense for that table, and a "Clear filters" link when any is active). Short lists filter in the browser (`composables/useFilters.js`); long, paged lists (people, campaigns, reports, logs, form inbox, subscriptions, change history) filter on the server. New table pages should use `components/FilterBar.vue` the same way.

## Settings

One **Settings** entry in the sidebar opens a settings hub with a section list: **My account** (name, job title, phone, sign-in email, password), **Company profile** (business names and tax number, industry, public email, phone, website, logo, full postal address; merge tags `{{company_name}}`, `{{company_email}}`, `{{company_phone}}`, `{{company_website}}`), **Email sending**, **SMS sending**, **Users**, **API keys** and **Activity logs**. The old addresses (`/config/email`, `/users`, `/webhooks`, …) redirect to the matching section.

### Sending email through Google / Microsoft (one click)

Settings → Email sending → SMTP offers **Sign in with Google / Microsoft**: the person approves access once and mail is sent through their own mailbox using OAuth (SMTP XOAUTH2; no password is stored, the refresh token is kept encrypted and masked in the history). The platform needs one OAuth app per provider; until its keys are in `.env` the buttons show "Not set up on this platform yet".

1. **Google:** Google Cloud Console → OAuth consent screen + OAuth client (Web). Scope `https://mail.google.com/` (a restricted scope: Google requires app verification before more than 100 users can connect).
2. **Microsoft:** Entra ID → App registrations → New (web). Permission `SMTP.Send` (delegated), plus `offline_access`; create a client secret. Mailboxes need SMTP AUTH enabled.
3. Redirect URIs: `APP_URL/api/oauth/mail/google/callback` and `APP_URL/api/oauth/mail/microsoft/callback`.
4. In `backend/marketing/.env`: `GOOGLE_MAIL_CLIENT_ID`, `GOOGLE_MAIL_CLIENT_SECRET`, `MICROSOFT_MAIL_CLIENT_ID`, `MICROSOFT_MAIL_CLIENT_SECRET`, optional `MICROSOFT_MAIL_TENANT` (default `common`), and `FRONTEND_URL`.

The SMTP section also has a **Setup guide**: it suggests the matching service from the From address (known mailbox domains directly, otherwise the domain's MX records, e.g. Google Workspace or Microsoft 365), shows a four-step checklist (choose a service → sign-in details → save → send a test) with the next step highlighted, and lists service-specific steps for getting the username/password.

### Several mail services, one in use

Settings → Email sending lists every service (Default, Google, Microsoft, Gmail, Outlook, Yahoo, Zoho, SendGrid, Mailgun, Amazon SES, Brevo, Postmark, Mandrill, Custom server). Each can be **connected** at the same time and keeps its own saved login; exactly one is **In use** for sending. Connecting does not switch sending: use "Use for sending" (or "Connect and use for sending"). Disconnecting the one in use falls back to Default. Settings are stored per company as `email.active` + `email.connections.{service}` (`App\Services\MailSettings`); companies saved with the older single server are read as one connection and converted on their next change.

## API responses

Every answer of the dashboard API (`/api/*`) has one shape.

```json
// success   (a list also has "meta": { current_page, last_page, per_page, total, ... } and "data" is the items)
{ "success": true,  "message": "Sending stopped." | null, "data": { ... } }

// failure   ("code" is a short machine-readable reason; "errors" appears for validation only)
{ "success": false, "message": "The name field is required.", "code": "validation_failed", "errors": { "name": ["The name field is required."] } }
```

Codes: `validation_failed` (422), `unauthenticated` (401), `forbidden` (403), `not_found` (404), `method_not_allowed` (405), `conflict` (409), `unprocessable` (other 422s, e.g. `abort(422, '…')`), `too_many_requests` (429, with `retry_after` and a `Retry-After` header), `server_error` (500). An unexpected error never shows its details, only a calm sentence; with `APP_DEBUG=true` a `debug` block (exception, file, line, trace) is added.

**In a controller** use `App\Support\ApiResponse`:

```php
return ApiResponse::ok($campaign, 'Sending stopped.');          // 200, with a sentence for the person
return ApiResponse::created($form);                              // 201
return ApiResponse::message('Test message sent.');               // 200, a sentence and no data
return ApiResponse::paginated($query->paginate(25));            // items as data, paging as meta
return ApiResponse::accepted();                                  // 202, working on it in the background
return ApiResponse::noContent();                                 // 204 (after a delete)
return ApiResponse::error('Sending failed.', 422, 'send_failed', null, ['hint' => '…']); // any failure; extra keys are allowed
return ApiResponse::validation(['email' => ['Not valid.']]);     // 422 with field errors
return ApiResponse::notFound('That page no longer exists.');     // also unauthenticated(), forbidden(), conflict(), tooManyRequests(), serverError()
```

You do not have to: a controller that simply returns a model, a list or a paginator is wrapped into the success shape by `WrapApiResponse`, and every exception, including `abort()`, `findOrFail()` and validation, is turned into the failure shape by `ApiResponse::fromException` (registered in `bootstrap/app.php`).

**Left exactly as they are** because other systems read them: the public API v1 (`/api/v1/*`), hosted forms (`/api/public/*`), tracking links, media files, downloads and streams (CSV exports). Their failures use the standard failure shape.

**Frontend:** `src/api.js` unwraps a successful answer, so screens still work with `response.data` as the record, list or paginator; the server's sentence is `response.message` (`responseMessage(r)`). `errorMessage(err)` reads a failure.

**Tests:** `json()` and `assertJson*()` see the payload inside the envelope. To check the envelope itself read the raw body: `json_decode($response->getContent(), true)` (see `ApiResponseTest`).

## Forms: what the visitor sees, and people who are already in People

- **After sending** the hosted form is replaced by a thank-you panel (a tick, the form's own thank-you message, and "Your form has been submitted successfully."), with **Send another response**, or a short pause before the redirect when the form has one. The button says "Sending…" and cannot be pressed twice. A problem shows a red message and focuses the field to fix.
- **A form that saves people** matches an existing person by email (or phone when there is no email): no duplicate is made, names, phone and custom fields are only filled where empty, subscription is never changed, and the form's lists and tags are added. The submission is linked to that person.
- **Form builder sidebar:** three tabs, **General**, **Design** and **Settings**. With nothing selected they edit the whole form (General: heading, description, button, thank-you message; Design: colours, font, width; Settings: type, redirect, what happens on submit, notifications, auto-reply, active). Click a field in the form and the same tabs edit just that field (General: label, placeholder, **default value**, options, help, required; Design: width in the row; Settings: save the answer on the person). Click the form card or the page background to go back to the whole form. A **default value** is filled in when the form opens: text, a chosen option, or a ticked box.
- **Validation:** select a field and open **Settings → Validation**: minimum / maximum length and a **Format** (text, mobile, names, email): pick a ready-made one (stricter email, three phone formats, Australian postcode, US ZIP, letters only, numbers only, letters and numbers, web address) or choose *Custom pattern* and type your own regular expression, smallest / largest number, earliest / latest date, allowed email domains, and one custom error message. **Try a value** shows at once whether a sample answer passes. The rules are saved with the form, checked again by the server when someone submits (with your custom message), and the hosted form passes the limits to the browser. The left palette group of fields that fill the person's record is called **People fields**.
- **Captcha** (form builder → Other fields): a small built-in human check, a sum such as `7 + 4 = ?`, with no outside service and no keys. The answer travels inside an encrypted token that expires after 6 hours, allows 5 wrong tries and works once; it is always required, only one per form, and it is never stored with the answers or on the person. A hidden trap field and rate limits still work as before. For a stricter service (reCAPTCHA, Turnstile) a site key would be needed, which this does not use.
- **Subscription:** there is no separate "subscribe them" switch any more. With a **Subscribe to newsletter** box on the form, only people who tick it are subscribed (even on a form that would not subscribe by default). Without a box, a newsletter sign-up subscribes new people and other form types do not.
- **Help text:** a field's help text is shown under the field; when it is longer than 60 characters it becomes an **i** icon beside the label and the text appears on hover or keyboard focus (builder preview and hosted form).
- **Demo templates (seeder):** `TemplateSeeder` creates a designed **Welcome email** for people who just registered (hero, what to expect, a `WELCOME10` gift code, sign-off, footer with unsubscribe; it opens in the visual designer), a **Welcome SMS** and a **Promo SMS** (`SAVE10`), both under 160 characters with an opt-out. The email's design and rendered HTML are in `database/seeders/data/`. Re-running the seeder restores these three templates. The Templates grid shows sample names in its previews instead of raw merge tags.
- **Sidebar notes:** in the builder's side panels an explanation longer than 60 characters is an **i** icon placed right after the label or title it explains (never on a line of its own); the text appears on hover or keyboard focus and always stays inside the panel. A short explanation (60 characters or fewer) stays as text. Clicking the icon never ticks the checkbox it sits next to.
- **Hover help:** the **i** next to *People fields*, *Custom people fields*, *Other fields* and *Content* in the form builder's left panel explains what each group is for.
- **Subscribe to newsletter** (form builder → Contact details) is a tick box that decides whether a new person is subscribed: only people who tick it are. Leave it required on a sign-up form, or optional to let people save their details without subscribing. It is kept in the submission, not on the person, and it never re-subscribes someone who opted out. A form can have one.
- **A notice for people who are already in People** is optional: write it under the form's Settings → *Message for people who are already in People*. It is shown instead of the thank-you (with "We already have you in our records."). Left empty, everybody sees the same thank-you, so a visitor can never find out who is in your people. In the submissions lists the **From** column always shows **New person** or **Existing person** under the person's name.

## Campaign audience: lists, tags, segments and subscription

- The campaign's **Audience** dropdown offers *All people*, **Lists**, **Tags** and **Segments**. One source is used at a time (segment, then tag, then list).
- Next to Audience, a **Subscription** dropdown (All, Subscribed, Unsubscribed) narrows the preview below it. The table lists name, email (and phone for SMS), and a **Subscription** status per person. The five-row preview and *Show all* follow the dropdown.
- Unsubscribed people are never sent to, whichever audience is chosen: they are simply not sent to. People with no email address or phone number cannot be sent to either: those are **Skipped** (a count kept on the campaign, listed with their names in the campaign report). Nothing is written to Message logs for either.

## Sending safety

- **Sending speed** (Settings → Email sending): campaigns go out at N messages per minute (default 60, 0 = unlimited), email and SMS, so a mail account's limits are not hit.
- **Retries:** a temporary problem (timeout, "try again later", rate limit, 421/429/45x/50x) is retried after 1 and 5 minutes; anything else (wrong password, invalid number) fails at once. While waiting, the message stays queued and shows "Will try again".
- **Stop sending:** a campaign that is sending can be stopped (Campaigns list menu, or the campaign page). What went out stays sent; everything still waiting is skipped; the status is shown as "stopped".
- **Every email** carries a plain-text copy beside the HTML, and `List-Unsubscribe` / `List-Unsubscribe-Post` headers (the one-click Unsubscribe button in Gmail, Apple Mail and others; `POST /api/unsubscribe/{token}`).
- **Before sending**, the campaign page checks the audience (how many will receive it, how many are skipped) and warns about a long subject, a localhost link, a missing sender address, or an SMS that is split into several parts. Nobody to send to blocks the send.
- **Duplicate** a campaign or a template from its menu; **Export CSV** on People downloads the people the list is showing (with their custom fields).

## Message logs

**Analytics → Message logs** (`/message-logs`; the old `/logs` address redirects) lists every email and SMS the platform sent or tried to send, in the database (two tables: `email_logs` and `sms_logs`): campaigns, automations, form auto-replies, **test messages** (Email / SMS configuration pages and "Send test" in the campaign editor) and staff notifications for form submissions. Each row has a kind (`source`), the service that carried it (`service`: a connected mail/SMS service, `default` = platform mail, `log` = Log only for SMS, where nothing leaves the server), its status and any error. Test messages and staff notifications are listed but never counted in campaign reports or the dashboard. Filter by text, channel, kind, service, status and dates. The Email and SMS configuration pages also show their last five test messages.

**Logged only.** A message sent through a service that only writes to a log gets the status **logged** ("logged only"), never "sent": the **Log only** service for email (Settings → Email sending) and for SMS (Settings → SMS sending). Logged-only messages are kept out of the "sent" counts everywhere (dashboard, reports, analytics); campaigns and reports show them as a separate number. The **Default** mail service is the server itself: it hands each message to the server's own mail program (PHP's `sendmail_path`, e.g. Postfix on a Linux host, or Mailpit on Laragon; override with `MAIL_SENDMAIL_PATH` in `.env`), not an SMTP login, and it never just logs: the message goes out, or the send fails with the reason (mail program not found, Mailpit not running, …). If `MAIL_MAILER` in `.env` is a real mailer (smtp, ses, …) Default uses that instead. On Laragon the mail program is Mailpit, which keeps mail in its own inbox at http://localhost:8025 and never reaches real inboxes; start it from Laragon (Menu → Mail) first.

Failed and skipped messages show their error in the **Error / reason** column of Message logs; click it (or **View**) for the full text and, where we recognise the problem (wrong Gmail password, Mailpit not running, invalid phone number, …), a **What to do** note. The same notes appear under a failed test on the Email and SMS pages.

## Dates and times

**Shown** as **Y-m-d** (`2026-11-01`) and **Y-m-d h:i A** (`2026-11-01 01:28 PM`). One helper does it (`src/format.js`): templates use `$d(value)`, `$dt(value)` and `$ds(value)`, scripts import `fmtDate` / `fmtDateTime` / `fmtStored`. The date entry boxes (`DateInput`, `DateTimeInput`) read and accept the same formats.

**Stored** in UTC. The app runs on UTC and the MySQL connection is pinned to it (`'timezone' => '+00:00'` in `config/database.php`, `DB_TIMEZONE`), so the server's own clock never leaks in. Every date column is `DATETIME` (never `TIMESTAMP`), so any database tool shows the same UTC value. System times (created, sent, opened …) are converted to the viewer's time zone when displayed.

**Typed by a person** (the campaign schedule): stored exactly as typed and shown exactly as stored, with no conversion (`$ds`, `fmtStored`). The scheduler sends it when the server's UTC clock reaches that time, so the schedule box shows "UTC now". Dates typed into custom fields are kept as typed too.

A database written before this (TIMESTAMP columns, MySQL on a non-UTC server) can be repaired once with `php artisan timestamps:fix-utc --dry-run` then `php artisan timestamps:fix-utc`, before converting the columns to DATETIME.

## Media library

**Media** (sidebar) is the company's file library: upload images (PNG, JPG, GIF, WebP) and documents (PDF, Office, CSV, TXT, ZIP) once, by dragging them in or with **Upload files**, up to 8 MB each. Search, filter by type and date, rename, copy a file's link, open it, delete it (soft delete; the link then stops working) and see its change history from the ⋮ menu. Files are kept in private storage and served at `/api/m/{token}/{file name}` by their long random token, so an image can be used in an email or page without signing in; only images are shown in the browser, other files download.

Things that need an image **pick it from Media** instead of asking for a web address or a direct upload. First user: **Settings → Company profile → Logo → Choose from media** (images only, this company's own; shown beside the company name in the sidebar). A file that is the current logo cannot be deleted until another is chosen.

**Free online images.** Media has tabs: **All**, **Uploaded**, **From free sources** (copies saved from online) and **Find free images** (live search of Openverse and Wikimedia, plus Unsplash / Pexels / Pixabay when the server has their keys). *Add to library* saves a copy with the photographer's credit, licence and source link. The logo picker has the same two tabs (*My media* / *Free online images*); *Use this image* saves the copy and selects it. API: `GET /api/media?origin=uploaded|online&source=…`, `POST /api/media/import`.

The server downloads these images, so imports are restricted: only addresses the image search just returned for that company (remembered 1 hour) can be imported, hosts must be public (also after redirects), downloads are capped at 8 MB, and the bytes must really be PNG/JPG/GIF/WebP. Adding the same photo twice returns the existing copy.

Tables: `media` (+ `media_history`, so every change to a file is mirrored like the other tables). Columns `source` (`upload` or the free source), `credit`, `credit_url`, `license`, `source_url` describe where an image came from.

> **Naming:** the people you market to used to be called "contacts". Everything now says **people / person**: the `people` table (and `people_lists`, `people_list_members`, `person_tag`, `person_fields`, `person_field_values`, with `person_id` / `people_list_id` columns), the `Person` model, the `/api/people` and `/api/v1/people` paths and the `/people` address. The old `/contacts` front-end addresses redirect. Integrations that called `/api/v1/contacts`, or read `contact` in webhook payloads and `contact.created` / `contact.unsubscribed` events, must switch to `people`, `person`, `person.created` and `person.unsubscribed`. A form of type **Contact form** is a kind of form, not the people list, so it keeps its name.
