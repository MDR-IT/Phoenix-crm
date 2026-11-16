# Phoenix Capital CRM

Omnichannel lead management and call centre CRM for Phoenix Capital (microfinance).

**Phase 4 (this branch):** Digital lead integrations — WhatsApp Business Cloud API, Meta (Facebook Lead Ads + Messenger), Instagram (supported Meta APIs only), and website form capture with a shared idempotent ingestion pipeline, encrypted credentials, health dashboard, and webhook monitoring. Stacked on Phase 3 follow-ups (and earlier phases).

## Stack

- PHP 8.3+ / Laravel 13
- MySQL 8 or MariaDB (XAMPP on Windows is fine)
- Laravel session auth + [spatie/laravel-permission](https://github.com/spatie/laravel-permission)
- Frontend: Blade + Bootstrap 5 (CDN) with Phoenix Capital branding
- Queues: database driver (`jobs` / `failed_jobs`) for webhook processing + retries

## Windows + XAMPP local setup

1. **Prerequisites**
   - XAMPP with PHP and MySQL/MariaDB running
   - Composer ([getcomposer.org](https://getcomposer.org/))
   - Git

2. **Add PHP to PATH** (PowerShell session example):

   ```powershell
   $env:Path = "C:\xampp\php;C:\xampp\mysql\bin;C:\Users\ADMIN\AppData\Local\Composer;" + $env:Path
   ```

   Or from the project root: `. .\use-php.ps1`

3. **Clone / open the project**

   ```powershell
   cd C:\Users\ADMIN\Desktop\Phoenix-crm
   composer install
   copy .env.example .env
   php artisan key:generate
   ```

4. **Create the database** (XAMPP MySQL, default empty root password):

   ```powershell
   mysql -u root -e "CREATE DATABASE IF NOT EXISTS phoenix_crm CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
   ```

5. **Configure `.env`** (already templated in `.env.example`):

   ```
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=phoenix_crm
   DB_USERNAME=root
   DB_PASSWORD=
   QUEUE_CONNECTION=database
   ```

6. **Migrate and seed**

   ```powershell
   php artisan migrate --seed
   php artisan serve
   ```

7. Open [http://127.0.0.1:8000](http://127.0.0.1:8000) — you will be redirected to login.

8. **Queue worker** (for async webhook jobs in non-test environments):

   ```powershell
   php artisan queue:work
   ```

### Default seeded admin (dev only)

| Field | Value |
|-------|-------|
| Email | `admin@phoenixcapital.local` |
| Password | `ChangeMeNow!123` |

**Change this password immediately** after first login.

---

## Phase 4 — Digital lead integrations

Without live credentials, every channel runs in **sandbox / pending configuration** mode. The UI and API responses say so explicitly — they never claim “live” without secrets.

### Common ingestion pipeline

`validate → auth / signature → normalize → source → duplicate check → customer/lead → channel interaction → assign → notify → webhook log`

- Idempotent via `webhook_events.idempotency_key` / provider external IDs  
- Queued with `ProcessWebhookEventJob`; failed events retried by `phoenix:retry-failed-webhooks`  
- Credentials encrypted in `integration_settings` (Laravel `Crypt`)

### Receiver endpoints (Phoenix CRM)

| Method | Path | Purpose |
|--------|------|---------|
| GET/POST | `/api/webhooks/whatsapp` | WhatsApp Cloud API webhook verify + receive |
| GET/POST | `/api/webhooks/meta` | Facebook Lead Ads + Messenger |
| GET/POST | `/api/webhooks/instagram` | Instagram Messaging (supported Meta webhooks only) |
| POST | `/api/leads/website` | Website form (API key + rate limit + honeypot) |

Outbound Graph calls use official hosts only: `https://graph.facebook.com/{version}/…` (WhatsApp messages, Lead Ads field fetch). No unofficial WhatsApp scraping.

### Staff screens

Under **Integrations** (permission `integrations.view` / `integrations.manage`):

- Integration health dashboard  
- Encrypted integration settings  
- WhatsApp interactions  
- Social / digital leads  
- Webhook monitor (+ retry failed)

### Configure WhatsApp Business number

1. Create a Meta App with **WhatsApp** product; add a Business phone number.  
2. Copy **Phone number ID**, permanent **access token**, **App secret**.  
3. Set a **verify token** of your choosing.  
4. In CRM → Integration settings → WhatsApp, paste credentials, set mode **Live**, enable.  
   Or set env: `WHATSAPP_ACCESS_TOKEN`, `WHATSAPP_PHONE_NUMBER_ID`, `WHATSAPP_APP_SECRET`, `WHATSAPP_VERIFY_TOKEN`.  
5. Point Meta webhook callback to `https://YOUR-CRM/api/webhooks/whatsapp` (subscribe to `messages`).  
6. Re-seed or save settings — health should show **Live** only when required secrets are present.

### Configure Meta app (Facebook + Instagram)

1. Meta App → add **Webhooks** for your Page (fields: `leadgen`, `messages`) and Instagram Messaging as needed.  
2. Generate a Page access token with lead retrieval permissions; note **Page ID**, **App secret**, verify token.  
3. Callback URLs:  
   - Facebook / Messenger: `https://YOUR-CRM/api/webhooks/meta`  
   - Instagram Messaging: `https://YOUR-CRM/api/webhooks/instagram`  
4. Save under Integration settings → Meta / Instagram, or env (`META_*`, `INSTAGRAM_*`).  
5. **Unsupported (by design):** organic comment scraping, story scraping without official webhooks, unofficial Instagram private APIs / cookie login.

### Website form embed (phoenixcapital site)

1. Set `WEBSITE_LEADS_API_KEY` in `.env` or generate/store a key under Integration settings → Website; mode **Live**.  
2. Sample embed: `public/embeds/phoenixcapital-lead-form.html` and helper `public/embeds/phoenixcapital-lead-form.js`.  
3. Point the form at `POST /api/leads/website` with header `X-Phoenix-Api-Key: <key>`.  
4. Include honeypot fields `website_url` and `company_fax` (must stay empty). Rate limit: `WEBSITE_LEADS_RATE_LIMIT` (default 30/min/IP).

### Phase 4 env vars (optional — prefer encrypted admin settings)

```
PHOENIX_INTEGRATION_USER_EMAIL=integrations@phoenixcapital.local

WHATSAPP_GRAPH_VERSION=v21.0
WHATSAPP_ACCESS_TOKEN=
WHATSAPP_PHONE_NUMBER_ID=
WHATSAPP_BUSINESS_ACCOUNT_ID=
WHATSAPP_APP_SECRET=
WHATSAPP_VERIFY_TOKEN=

META_GRAPH_VERSION=v21.0
META_ACCESS_TOKEN=
META_APP_ID=
META_APP_SECRET=
META_VERIFY_TOKEN=
META_PAGE_ID=

INSTAGRAM_GRAPH_VERSION=v21.0
INSTAGRAM_ACCESS_TOKEN=
INSTAGRAM_APP_SECRET=
INSTAGRAM_VERIFY_TOKEN=

WEBSITE_LEADS_API_KEY=
WEBSITE_LEADS_RATE_LIMIT=30
```

### Scheduler

```powershell
php artisan phoenix:process-follow-up-reminders
php artisan phoenix:retry-failed-webhooks --sync
```

Register `php artisan schedule:run` every minute (reminders hourly; webhook retries every five minutes).

---

## Earlier phases (still included)

| Phase | Scope |
|-------|--------|
| 1 | Auth, RBAC, branded shell, users/roles |
| 2 | Customers, leads, duplicates/merge, assignment, Kanban, export |
| 3 | Timeline, notes, tasks, follow-ups, reminders, notifications |

Permissions added in Phase 4: `integrations.view`, `integrations.manage` (Marketing Officer + Super Admin manage; managers/supervisors can view).

## Tests

```powershell
php artisan test
```

Uses in-memory SQLite. Phase 4 coverage: website submission, WhatsApp webhook, Meta webhook, idempotency, API key / signature security, integration screens.

## Out of scope

Yeastar P560 telephony (Phase 5), reporting suites (Phase 6). Do not use unofficial WhatsApp clients or scrapers.

## Git

Remote: https://github.com/MDR-IT/Phoenix-crm.git
