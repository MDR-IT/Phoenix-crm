# Phoenix Capital CRM

Omnichannel lead management and call centre CRM for Phoenix Capital (microfinance).

**Phase 1 (this branch):** Laravel foundation, authentication, RBAC, branded Blade + Bootstrap 5 shell, dashboard placeholders, and basic user/role administration.

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

## Roles (Phase 1)

Super Administrator, CEO, Sales Manager, Call Centre Supervisor, Call Centre Agent, Sales Agent, Marketing Officer, Compliance Officer, Auditor.

Permissions are seeded and editable under **Roles & permissions** for users with `roles.update`.

## Tests

```powershell
php artisan test
```

Uses in-memory SQLite (`phpunit.xml`).

## Out of scope (later phases)

Leads, Kanban, WhatsApp/Meta/Yeastar integrations, call centre tooling, reporting — navigation stubs only.

## Git

Remote: https://github.com/MDR-IT/Phoenix-crm.git
