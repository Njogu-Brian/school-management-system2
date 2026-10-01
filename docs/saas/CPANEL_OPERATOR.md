# EduLynk cPanel operator (DB-per-school)

Full-stack control plane for creating and billing schools on **cPanel**, separate from Royal Kings on AWS.

See also: [`CLIENT_LAUNCH_PLAN.md`](./CLIENT_LAUNCH_PLAN.md) (AWS-oriented; this file is the cPanel path), [`../DEMO_CPANEL_SETUP.md`](../DEMO_CPANEL_SETUP.md) (demo `/demo` wiring).

## Pieces

| URL | Role | Database |
|-----|------|----------|
| `https://edulynk.co.ke/` | Marketing (unchanged) | existing |
| `https://edulynk.co.ke/operator` | Operator portal | `edulynk_control` |
| `https://edulynk.co.ke/api/schools/resolve?code=DEMO001` | App school-code resolve | `edulynk_control` |
| `https://edulynk.co.ke/demo` | Demo ERP (tenant #1) | `edulynk_demo` |
| `https://edulynk.co.ke/{slug}` | Other school ERPs | `edulynk_{slug}` |

**Do not** point any of this at the Royal Kings AWS database.

## Architecture choices

- **One MySQL database per school** (isolation).
- **One shared Laravel ERP codebase** on the server; credentials come from `schools_registry`.
- Optional **path tenancy** (`EDULYNK_PATH_TENANCY=true`): first URL segment = slug → switch `tenant` connection.
- Or **dedicated docroot per slug** (`public_html/demo` → shared app) as in the demo setup guide, with `TENANT_SLUG=demo` in that vhost’s env if you prefer fixed binding.

## Control plane `.env` (host running operator)

```env
APP_ROLE=control_plane
APP_URL=https://edulynk.co.ke
EDULYNK_TENANT_BASE_URL=https://edulynk.co.ke

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=edulynk_control
DB_USERNAME=...
DB_PASSWORD=...

CONTROL_DB_HOST=127.0.0.1
CONTROL_DB_DATABASE=edulynk_control
CONTROL_DB_USERNAME=...
CONTROL_DB_PASSWORD=...

EDULYNK_OPERATOR_EMAILS=you@edulynk.co.ke
EDULYNK_DEFAULT_MONTHLY_FEE=5000

CPANEL_HOST=edulynk.co.ke
CPANEL_USER=cpanel_username
CPANEL_API_TOKEN=xxxxxxxx
CPANEL_PORT=2083
CPANEL_DB_PREFIX=cpaneluser_          # often account prefix
CPANEL_PUBLIC_HTML=/home/USER/public_html
CPANEL_ERP_ROOT=/home/USER/edulynk-erp
```

Create an API token in cPanel → **Manage API Tokens** with rights to manage MySQL.

## First-time setup

1. Create MySQL database `edulynk_control` in cPanel.
2. Deploy this codebase (e.g. `~/edulynk-erp`), point operator/API at it (docroot or path).
3. Run:

```bash
cd ~/edulynk-erp
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan migrate --force
# Create your operator user (must be in EDULYNK_OPERATOR_EMAILS or Super Admin):
php artisan tinker
# >>> User::create([...]) or promote existing user
```

4. Log in at `https://edulynk.co.ke/operator`.

## Demo school (tenant #1)

Upload `storage/app/backups/demo_school_20260930.sql.gz`, create empty MySQL `edulynk_demo` (or let UAPI create it), then:

```bash
# If UAPI works:
php artisan edulynk:provision-demo --create-db --import

# Or with manual DB (local/XAMPP style):
php artisan edulynk:provision-demo \
  --db-name=edulynk_demo \
  --db-user=root \
  --db-password= \
  --import \
  --dump=storage/app/backups/demo_school_20260930.sql.gz
```

Wire `/demo` as in [`DEMO_CPANEL_SETUP.md`](../DEMO_CPANEL_SETUP.md).  
Demo login: `admin@demo.school` / `Demo@12345` (or `DEMO_SCHOOL_ADMIN_PASSWORD`).  
App code: **`DEMO001`**.

Smoke:

```bash
curl "https://edulynk.co.ke/api/schools/resolve?code=DEMO001"
```

## Create another school (wizard)

Operator → **Create school** → name, colours, Super Admin → either:

- **cPanel UAPI** creates DB/user automatically, or  
- **Manual database** checkbox + paste credentials  

Provisioning runs migrations + `TenantBootstrapSeeder` (roles, COA, Grade 1–9 + streams A/B, Super Admin with forced password change), registers the school, opens the current month’s subscription.

CLI equivalent for empty tenant bootstrap (on a DB you already created and pointed `.env` / connection at):

```bash
php artisan tenant:bootstrap --admin-email=admin@school.test --school-name="St Mary"
```

## Billing

- Each school has `monthly_fee` and `school_subscriptions` (period `YYYY-MM`).
- Record payments on the school page; overdue/suspend from operator UI.
- Suspended schools fail `/api/schools/resolve` and path tenancy middleware.

## Mobile app

Point the EduLynk store build at the control plane:

```env
EXPO_PUBLIC_CONTROL_PLANE_BASE_URL=https://edulynk.co.ke/api
EXPO_PUBLIC_REQUIRE_SCHOOL_CODE=true
```

Users enter school code once (`DEMO001`, etc.).

## Artisan reference

| Command | Purpose |
|---------|---------|
| `edulynk:provision-demo` | Import dump + register DEMO001 |
| `tenant:bootstrap` | Empty tenant starter data |
| `schools:register` | Registry row only (no DB create) |

## Fallback if UAPI is blocked

1. cPanel → MySQL Databases → create DB + user + ALL PRIVILEGES.  
2. Operator wizard → **Use manually created MySQL database**.  
3. Continue.

## Safety

- Never reuse Royal Kings AWS `DB_*` credentials here.  
- Keep `APP_DEBUG=false` on cPanel.  
- Encrypts tenant DB passwords in `schools_registry.db_password_encrypted` (Laravel `Crypt` / `APP_KEY`).
