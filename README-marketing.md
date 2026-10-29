# FranchiseCare Marketing

Multi-tenant email + SMS marketing. `backend/marketing/` = Laravel 13 API (Sanctum tokens), `frontend/marketing/` = Vue 3 SPA (Vite, Pinia).

## Run

```bash
# backend (MySQL database: marketing)
cd franchisecare/backend/marketing
composer install && cp .env.example .env && php artisan key:generate
php artisan migrate
php artisan serve                      # http://localhost:8000
php artisan queue:work                 # sends the messages  (required!)
php artisan schedule:work              # fires scheduled campaigns

# frontend
cd franchisecare/frontend/marketing
npm install && cp .env.example .env    # VITE_API_URL=http://localhost:8000/api
npm run dev                            # http://localhost:5173
```

Open the app → "Create a business account". Each business only ever sees its own data.

## Features
- **Contacts / Lists** – CRUD, search, CSV import (`first_name,last_name,email,phone`), per-channel unsubscribe.
- **Templates** – separate email (HTML) and SMS templates with merge tags (`{{first_name}}`, `{{business_name}}`, `{{unsubscribe_url}}`…), live preview.
- **Free stock images** – the editor searches **Openverse** and **Wikimedia Commons** with no API key ("All free sources" is the default and skips any source that is down). Unsplash / Pexels / Pixabay appear automatically if you set `UNSPLASH_ACCESS_KEY`, `PEXELS_API_KEY` or `PIXABAY_API_KEY` once in `backend/marketing/.env` - businesses never enter keys. Photographer credit and licence are added under each inserted image.
- **Campaigns** – send now or schedule, pick a list, send a test message first, progress and results per campaign.
- **Logs** – every message with status, error, rendered content, opens (tracking pixel) and link clicks.
- **Settings** – sender name/email/address, SMS provider (`log` for testing, or Twilio).

## Email delivery
Email goes through Laravel's mailer: set `MAIL_*` in `backend/marketing/.env` (default `MAIL_MAILER=log` writes to `storage/logs`). The *From email* in Settings must be allowed by your mail provider (SES, Postmark, SMTP…).

## Notes
- SMS drivers live in `app/Services/Sms/`; add a class implementing `SmsDriver` and register it in `SmsManager` for other providers.
- Tests: `cd franchisecare/backend/marketing && php artisan test`.
- In production run the queue worker under Supervisor and add `* * * * * php artisan schedule:run` to cron.
