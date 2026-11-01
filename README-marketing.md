# FranchiseCare Marketing

Multi-tenant email + SMS marketing. `backend/marketing/` = Laravel 13 API (Sanctum tokens), `frontend/marketing/` = Vue 3 SPA (Vite, Pinia).

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

**Audience** — Contacts (search, filters, bulk actions, CSV import) · Contact Lists · **Tags** (coloured labels) · **Custom Fields** (extra data on contacts beyond name/email/mobile: company, plan… — defined once, editable on the contact, usable as merge tags, filled by forms and the API) ·
**Segments** (saved rule groups: tag, list, name/email/domain, source, date added, opened-in-last-N-days… match ALL/ANY, live count) ·
**Subscriptions** (per-channel opt-in/out, dates, bulk re-subscribe).

**Campaigns** — All / Email / SMS / Scheduled (cancel a schedule) · audience = a list or a segment · **Campaign Reports**
(delivery, open & click rates, opens/clicks over time, top links, failures, CSV export) · Message Logs.

**Content** — Email Templates (visual designer: blocks, ready-made & saved layouts, code editor) · SMS Templates ·
**Landing Pages** (a dedicated web-page builder, separate from the email designer: full-width responsive sections — navigation, hero, numbers, features, steps, testimonials, pricing, FAQ, sign-up form, footer, custom HTML — with web layouts such as Lead magnet, Product launch, Event, Coming soon and Service business; desktop/tablet/phone preview; free photos with automatic credits; the "Sign-up form" section marks where the attached form appears; own public link; older email-style pages still work) · **Forms** (full-screen form builder: drag-and-drop fields in a column layout, colours & fonts, hosted page + embed code). Pick a **form type** — Newsletter sign-up, Contact form or Other — then choose what happens on submit: save to contacts (and whether they are subscribed to marketing), add to lists/tags, **email the submission to up to 5 addresses**, send the person an **auto-reply**, show a thank-you or redirect. Contact and Other forms start as an **inbox only** (no contact is created) — turn on "Save them to my contacts" when you want one. When a form does save contacts, name/email/mobile fill the contact's own columns and each extra question can be saved as a custom field (or kept in the inbox only). Every submission is kept in the form's **inbox** (view, delete, CSV export). Custom field names that would clash with a built-in tag get a `contact_` prefix (e.g. `{{contact_company_name}}`; `{{company_name}}` is always *your* company).

**Automation** — **Workflows** (trigger → send email/SMS, wait, add/remove tag, add to list) · **Triggers** (catalogue) ·
**Automation History** (contacts in flight with cancel, and a step-by-step log).
Triggers: contact added, joins a list, tag added, form submitted. Workflows start as drafts; activate them when ready.

**Analytics** — Campaign Analytics · Contact Analytics (growth, subscriptions, engagement, sources) · Conversion Reports (form/page views → sign-ups).

**System Settings** — Company Profile · **Email Configuration** (sender + your own SMTP, test send) ·
**SMS Configuration** (Twilio or test mode, test send) · **API Integrations** · **Webhooks** · **Users** · **Activity Logs**.

## Companies and users

- Signing up (**Create a company account**) creates a new company and its first user.
- A company has many users. Every user has full access — there are no roles — and all of them see the same company data.
- **System Settings → Users** adds people to *your* company: a new user always joins the company of the person who creates them.
- Companies are completely separate: one company can never see another's contacts, campaigns, templates, users or settings.
- A login email belongs to exactly one company.

## Integrations

- **API keys** (Settings → API Integrations): `POST /api/v1/contacts` upserts a contact (tags/lists matched by name),
  `GET /api/v1/contacts?email=`, `POST /api/v1/contacts/unsubscribe`. A key only works on `/api/v1`.
- **Webhooks**: `contact.created`, `contact.unsubscribed`, `campaign.sent`, `form.submitted`, `message.failed`, `workflow.completed`.
  Each POST carries `X-Webhook-Signature: sha256=<HMAC of the raw body>` keyed with the webhook's secret (shown once).
- Webhook URLs and SMTP hosts may not point at private/local addresses unless `OUTBOUND_ALLOW_PRIVATE=true` (dev only).

## Notes

- Free stock images need no keys (Openverse + Wikimedia Commons). Unsplash / Pexels / Pixabay appear if you set their keys once in `.env`.
- SMS drivers live in `app/Services/Sms/`; implement `SmsDriver` and register it in `SmsManager` for other providers.
- Hosted forms/pages are served with a strict Content-Security-Policy so page HTML can't run its own scripts.
- Tests: `cd franchisecare/backend/marketing && php artisan test`.

## Deleting is soft

Deleting a contact, list, tag, segment, template, campaign, form, landing page, workflow, webhook, custom field, user… only sets `deleted_at`; the row stays in the database and is hidden everywhere in the app (restore with `Model::onlyTrashed()->find($id)->restore()`). A deleted email, tag name or custom-field key can be used again (uniqueness only applies to rows that are not deleted, via the generated `email_active` / `name_active` / `key_active` columns). Queued messages whose campaign or contact was deleted are marked *skipped*. Contacts' list memberships live in `contact_list_members`; list/tag memberships and framework tables (jobs, cache, tokens) are not soft-deleted.

Form builder: the left panel has **Custom fields** (right under Contact details; the other inputs are under **Other fields**) — drag one in and the question is tied to that field (same merge tag, same choices); questions you invent still create a custom field automatically.

## Change history (data mirroring)

Every tracked table has a mirror, `<table>_history` (`contacts_history`, `campaigns_history`, …): after each create, update, delete or restore a **full copy of the row** is written there, plus who changed it (`changed_by`, `changed_by_name`), where from (`change_source`: dashboard / api / public / system), when (`changed_at`) and which columns differ from the previous copy (`changed_fields`). Tracked: companies, users, contacts, lists, list memberships, tags, contact tags, segments, templates, layouts, campaigns, forms, landing pages, workflows, webhooks, custom fields and their values, form submissions. Message logs, click events, workflow runs, webhook deliveries and the activity log are logs themselves and are not mirrored.

* **In the app:** every table has an **Actions** column with a ⋮ menu; its last item, **Change history**, shows that row's full timeline (old → new). System Settings → Activity Logs → **Change history** is the feed across all tables, with filters.
* Passwords, keys and other secrets are never copied (shown as ••••••); history can't be edited or removed through the app; view counters don't create copies.
* A model joins by using the `Auditable` trait and having a `<table>_history` table whose columns include all of the source table's columns — a test fails if a column is added to a tracked table without adding it to its history table. Changes made with a plain query (no model events) call `History::record()` explicitly.
* `php artisan history:backfill` gives rows that existed before history was switched on a first "Existing record" copy.

## Filters

Every table page has a filter bar **above** the table card (search plus the filters that make sense for that table, and a "Clear filters" link when any is active). Short lists filter in the browser (`composables/useFilters.js`); long, paged lists (contacts, campaigns, reports, logs, form inbox, subscriptions, change history) filter on the server. New table pages should use `components/FilterBar.vue` the same way.

## Settings

One **Settings** entry in the sidebar opens a settings hub with a section list: **My account** (name, job title, phone, sign-in email, password), **Company profile** (business names and tax number, industry, public email, phone, website, logo, full postal address; merge tags `{{company_name}}`, `{{company_email}}`, `{{company_phone}}`, `{{company_website}}`), **Email sending**, **SMS sending**, **Users**, **API keys**, **Webhooks** and **Activity logs**. The old addresses (`/config/email`, `/users`, `/webhooks`, …) redirect to the matching section.

### Sending email through Google / Microsoft (one click)

Settings → Email sending → SMTP offers **Sign in with Google / Microsoft**: the person approves access once and mail is sent through their own mailbox using OAuth (SMTP XOAUTH2; no password is stored, the refresh token is kept encrypted and masked in the history). The platform needs one OAuth app per provider; until its keys are in `.env` the buttons show "Not set up on this platform yet".

1. **Google:** Google Cloud Console → OAuth consent screen + OAuth client (Web). Scope `https://mail.google.com/` (a restricted scope: Google requires app verification before more than 100 users can connect).
2. **Microsoft:** Entra ID → App registrations → New (web). Permission `SMTP.Send` (delegated), plus `offline_access`; create a client secret. Mailboxes need SMTP AUTH enabled.
3. Redirect URIs: `APP_URL/api/oauth/mail/google/callback` and `APP_URL/api/oauth/mail/microsoft/callback`.
4. In `backend/marketing/.env`: `GOOGLE_MAIL_CLIENT_ID`, `GOOGLE_MAIL_CLIENT_SECRET`, `MICROSOFT_MAIL_CLIENT_ID`, `MICROSOFT_MAIL_CLIENT_SECRET`, optional `MICROSOFT_MAIL_TENANT` (default `common`), and `FRONTEND_URL`.

The SMTP section also has a **Setup guide**: it suggests the matching service from the From address (known mailbox domains directly, otherwise the domain's MX records, e.g. Google Workspace or Microsoft 365), shows a four-step checklist (choose a service → sign-in details → save → send a test) with the next step highlighted, and lists service-specific steps for getting the username/password.

### Several mail services, one in use

Settings → Email sending lists every service (Default, Google, Microsoft, Gmail, Outlook, Yahoo, Zoho, SendGrid, Mailgun, Amazon SES, Brevo, Postmark, Mandrill, Custom server). Each can be **connected** at the same time and keeps its own saved login; exactly one is **In use** for sending. Connecting does not switch sending: use "Use for sending" (or "Connect and use for sending"). Disconnecting the one in use falls back to Default. Settings are stored per company as `email.active` + `email.connections.{service}` (`App\Services\MailSettings`); companies saved with the older single server are read as one connection and converted on their next change.

## Send history

**Campaigns → Send history** (`/logs`) lists every email and SMS the platform sent or tried to send, in the database (two tables: `email_logs` and `sms_logs`): campaigns, automations, form auto-replies, **test messages** (Email / SMS configuration pages and "Send test" in the campaign editor) and staff notifications for form submissions. Each row has a kind (`source`), the service that carried it (`service`: a connected mail/SMS service, `default` = platform mail, `log` = SMS test mode, where nothing leaves the server), its status and any error. Test messages and staff notifications are listed but never counted in campaign reports or the dashboard. Filter by text, channel, kind, service, status and dates. The Email and SMS configuration pages also show their last five test messages.
