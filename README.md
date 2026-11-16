# Phoenix Capital CRM

Omnichannel lead management and call centre CRM for Phoenix Capital (microfinance).

**Phase 3 (this branch):** Follow-ups & activities — lead/customer activity timeline, notes, tasks, callbacks, dashboard widgets (today / overdue / upcoming), uncontacted-lead reminders, in-app notifications, configurable email/SMS provider stubs. Stacked on Phase 2 core CRM (and Phase 1 foundation).

## Stack

- PHP 8.3+ / Laravel 13
- MySQL 8 or MariaDB (XAMPP on Windows is fine)
- Laravel session auth + [spatie/laravel-permission](https://github.com/spatie/laravel-permission)
- Frontend: Blade + Bootstrap 5 (CDN) with Phoenix Capital branding

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
   ```

6. **Migrate and seed**

   ```powershell
   php artisan migrate --seed
   php artisan serve
   ```

7. Open [http://127.0.0.1:8000](http://127.0.0.1:8000) — you will be redirected to login.

### Default seeded admin (dev only)

| Field | Value |
|-------|-------|
| Email | `admin@phoenixcapital.local` |
| Password | `ChangeMeNow!123` |

Override via `.env` before seeding:

```
ADMIN_NAME="Super Administrator"
ADMIN_EMAIL=admin@phoenixcapital.local
ADMIN_PASSWORD=ChangeMeNow!123
```

**Change this password immediately** after first login (Profile → Update password), or re-seed with a stronger `ADMIN_PASSWORD`.

## Phase 3 features

| Area | What you get |
|------|----------------|
| Activity timeline | Chronological lead timeline: creation, source, notes, status, assignment, tasks, meetings/follow-ups; Phase 4 channel placeholder |
| Notes | Notes on leads and customers; pinned option; timeline entries |
| Tasks | Create, assign, deadlines, complete; overdue list + filters |
| Follow-ups | Schedule callbacks / follow-ups / meetings; worklist buckets: today, overdue, upcoming |
| Dashboard | Widgets for today’s / overdue / upcoming follow-ups, overdue tasks, uncontacted leads |
| Reminders | Hourly scheduler command reminds assignees of uncontacted leads (`PHOENIX_UNCONTACTED_REMINDER_HOURS`, default 24) |
| Notifications | In-app database notifications for task assign/complete, follow-up schedule/due, uncontacted leads |
| Messaging stubs | Email/SMS via `OutboundMessenger` + null providers; credentials only from env (`PHOENIX_EMAIL_*`, `PHOENIX_SMS_*`) — never hardcoded |

### Scheduler (reminders)

```powershell
php artisan phoenix:process-follow-up-reminders
```

Register Laravel’s scheduler (cron / Task Scheduler) to run `php artisan schedule:run` every minute so the hourly reminder job fires.

## Phase 2 features (still included)

Customers, lead register, manual capture, duplicates/merge, assignment strategies, Kanban pipeline, audit history, CSV/Excel export, agent scoping.

## Roles & permissions (Phase 3)

New permissions: `activities.view`, `notes.*`, `tasks.view|create|update|complete|view_all`, `follow_ups.view|create|update|complete|view_all`.

Agents get create/complete on their own items; managers/supervisors get `*_view_all`. Editable under **Roles & permissions**.

## Tests

```powershell
php artisan test
```

Uses in-memory SQLite (`phpunit.xml`). Phase 3 coverage includes follow-up create/complete, notes, tasks, worklist scoping, uncontacted reminders, dashboard widgets, and messaging stubs.

## Out of scope (later phases)

WhatsApp / Meta / website auto-capture (Phase 4), Yeastar telephony (Phase 5), reporting dashboards (Phase 6). Timeline already reserves a channel-interaction placeholder for Phase 4.

## Git

Remote: https://github.com/MDR-IT/Phoenix-crm.git
