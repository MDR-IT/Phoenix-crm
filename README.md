# Phoenix Capital CRM

Omnichannel lead management and call centre CRM for Phoenix Capital (microfinance).

**Phase 2 (this branch):** Core CRM — customers, lead register, manual capture, duplicate detection, assignment (manual / round-robin / product / region / team), Kanban pipeline, audit history, CSV/Excel export, in-app assignment notifications. Stacked on Phase 1 foundation (auth, Spatie RBAC, branded Blade + Bootstrap 5 shell).

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

## Phase 2 features

| Area | What you get |
|------|----------------|
| Customers | Register + profile; normalised Kenyan phone (`2547…`) |
| Leads | Register with search/filters/pagination; soft-delete archive |
| Capture | Full manual form; Save / Cancel / Save and another |
| Duplicates | Phone/email match on create; confirm to proceed; merge for permitted roles |
| Assignment | Manual, round-robin, by product / region / team; history + in-app notify |
| Pipeline | Kanban stages (New → … → Converted / Lost); drag or Move; status history |
| Audit | Field-level old/new, user, timestamp on lead changes |
| Export | CSV and Excel-compatible download for export-permitted roles |
| Scope | Agents see assigned leads only unless they have `leads.view_all` |

Seeded lookups: counties/regions, products, lead sources, teams, pipeline statuses (config).

## Roles & permissions (Phase 2)

Super Administrator, CEO, Sales Manager, Call Centre Supervisor, Call Centre Agent, Sales Agent, Marketing Officer, Compliance Officer, Auditor.

New / expanded permissions: `customers.*`, `leads.create`, `leads.update`, `leads.archive`, `leads.assign`, `leads.export`, `leads.merge`, `leads.view_all` (plus Phase 1 grants). Editable under **Roles & permissions**.

## Tests

```powershell
php artisan test
```

Uses in-memory SQLite (`phpunit.xml`). Phase 2 coverage includes manual create, edit/audit, assignment, duplicate detection, status changes, agent scoping, and merge.

## Out of scope (later phases)

WhatsApp / Meta / Yeastar, full follow-up module (Phase 3), reporting dashboards (Phase 6) — navigation stubs remain labelled for later.

## Git

Remote: https://github.com/MDR-IT/Phoenix-crm.git
