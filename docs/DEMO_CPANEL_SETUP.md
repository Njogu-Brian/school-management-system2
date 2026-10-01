# EduLynk Demo Academy — cPanel setup (`/demo`)

This guide deploys the **sanitized showcase database** and Laravel app to:

**https://edulynk.co.ke/demo**

Do **not** point this demo at any live school database. Do **not** overwrite the marketing site at `/` or any production ERP.

---

## What you should have locally first

| Item | Location / value |
|------|------------------|
| Sanitized MySQL dump | `storage/app/backups/demo_school_20260930.sql.gz` (~8.5 MB) |
| Local demo DB (optional) | `school_management_demo` |
| Rebuild sanitize | `DB_DATABASE=school_management_demo php artisan demo:sanitize-pii --force` |
| Gap-fill empty modules | `DB_DATABASE=school_management_demo php artisan db:seed --class=DemoGapFillSeeder --force` |

### Demo logins

| Role | Username | Password |
|------|----------|----------|
| Super Admin | `admin@demo.school` | `Demo@12345` |
| Staff (any scrubbed staff user) | e.g. `staff1@demo.school` | `Demo@12345` |
| Parent app | Parent email/phone from Users, **or** child admission + year | Password = `{admission}-2026` (e.g. `DEMO024-2026`) |

School name in-app: **EduLynk Demo Academy**. Admission numbers are `DEMO###`.

---

## Recommended server layout

Keep the marketing site untouched. Put Laravel **outside** `public_html`, and only expose `public` under `/demo`:

```text
~/edulynk-demo/                 # full Laravel app (not web-accessible)
  app/, bootstrap/, config/, vendor/, .env, ...
  public/
~/public_html/                  # existing https://edulynk.co.ke/ site — leave alone
  demo/                         # NEW folder (create this)
    index.php                   # bootstraps ~/edulynk-demo
    .htaccess                   # RewriteBase /demo/
    css/, js/, build/, ...      # copied/symlinked from edulynk-demo/public
```

---

## Step-by-step (cPanel)

### 1. Create a dedicated MySQL database

1. cPanel → **MySQL Databases**.
2. Create database, e.g. `edulynk_demo` (cPanel will prefix your username → e.g. `user_edulynk_demo`).
3. Create a MySQL user with a strong password.
4. Add the user to the database with **ALL PRIVILEGES**.
5. Note: host is usually `localhost`.

### 2. Create folders

1. File Manager (or SSH): create `~/edulynk-demo/`.
2. Create `~/public_html/demo/` (this folder does not exist yet).

### 3. Upload the application

1. Upload a release of this Laravel project into `~/edulynk-demo/`  
   (ZIP via File Manager, or `git clone` / `git pull` over SSH).
2. Upload `demo_school_20260930.sql.gz` somewhere reachable (e.g. `~/edulynk-demo/storage/app/backups/`).
3. **Do not** upload the full app tree into `public_html/demo` (that would expose `.env`).

### 4. Wire `/demo` to Laravel `public`

1. Copy contents of `~/edulynk-demo/public/` into `~/public_html/demo/`  
   (or symlink if your host allows).
2. Edit `~/public_html/demo/index.php` so paths point at the app root. Example (adjust depth if your home layout differs):

```php
<?php

use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// If you maintain the vendor/maintenance file under the app root:
if (file_exists($maintenance = __DIR__.'/../../edulynk-demo/storage/framework/maintenance.php')) {
    require $maintenance;
}

require __DIR__.'/../../edulynk-demo/vendor/autoload.php';

$app = require_once __DIR__.'/../../edulynk-demo/bootstrap/app.php';

$app->handleRequest(Request::capture());
```

3. Edit `~/public_html/demo/.htaccess` and ensure:

```apache
RewriteBase /demo/
```

(Keep the usual Laravel front-controller rewrite rules.)

### 5. Import the demo database

**Option A — phpMyAdmin**

1. Gunzip the dump locally to `.sql` if phpMyAdmin rejects `.gz`.
2. Select the new demo DB → **Import** → choose the SQL file → Go.  
   Raise upload limits if needed, or use SSH.

**Option B — SSH**

```bash
cd ~/edulynk-demo
gunzip -c storage/app/backups/demo_school_20260930.sql.gz | mysql -u YOUR_DB_USER -p YOUR_DB_NAME
```

### 6. Configure `.env` (`~/edulynk-demo/.env`)

```env
APP_NAME="EduLynk Demo Academy"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://edulynk.co.ke/demo
ASSET_URL=https://edulynk.co.ke/demo

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_cpanel_prefixed_edulynk_demo
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

SESSION_DRIVER=database
SESSION_PATH=/demo
SESSION_DOMAIN=edulynk.co.ke

LOG_CHANNEL=stack
QUEUE_CONNECTION=database
CACHE_STORE=database
```

Generate `APP_KEY` if empty (see next step). Keep `APP_DEBUG=false`.

### 7. PHP version and extensions

cPanel → **MultiPHP Manager**: set **PHP 8.2+** for the `demo` path (or the whole domain if needed).

Enable: `mbstring`, `openssl`, `pdo_mysql`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo`, `gd` (or imagick), `zip`, `curl`.

### 8. Install dependencies and cache (SSH Terminal)

```bash
cd ~/edulynk-demo
composer install --no-dev --optimize-autoloader
php artisan key:generate   # only if APP_KEY is empty
php artisan storage:link   # then ensure public_html/demo/storage points at edulynk-demo/storage/app/public
php artisan config:cache
php artisan route:cache
php artisan view:cache
chmod -R 775 storage bootstrap/cache
```

If SSH is unavailable: upload a `vendor/` built locally with the same PHP 8.2 + `composer install --no-dev`, then run Artisan when Terminal is available.

**Storage link for subdirectory:** if `php artisan storage:link` links into `edulynk-demo/public/storage`, also create/copy that link or folder under `public_html/demo/storage`.

### 9. Permissions

Ensure the cPanel user can write:

- `~/edulynk-demo/storage`
- `~/edulynk-demo/bootstrap/cache`

### 10. Verify

1. Open https://edulynk.co.ke/demo — login page should load.
2. Sign in as `admin@demo.school` / `Demo@12345`.
3. Spot-check: Students, Fee payments, Bank statements, Transport trips, Library, POS.
4. Confirm https://edulynk.co.ke/ (marketing) still works.
5. Confirm no real school name / `RKS` admission numbers appear in student lists.

---

## Hard rules

- Never point `.env` at a real school production database.
- Never run `demo:sanitize-pii` on production.
- Do not deploy this dump over an existing live ERP database.
- Optional: protect `/demo` with cPanel Directory Privacy (HTTP auth) for private demos.

---

## Local rebuild cheat sheet (Windows)

```powershell
# Import raw backup into a clone DB (never use the live DB name)
& C:\xampp\mysql\bin\mysql.exe --host=127.0.0.1 -uroot -e "CREATE DATABASE IF NOT EXISTS school_management_demo CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
# ... import SQL into school_management_demo ...

$env:DB_DATABASE='school_management_demo'
php artisan demo:sanitize-pii --force
php artisan db:seed --class=DemoGapFillSeeder --force
```

Then re-export with `mysqldump` + gzip into `storage/app/backups/demo_school_YYYYMMDD.sql.gz`.

---

## Troubleshooting

| Symptom | Likely fix |
|---------|------------|
| Assets 404 / CSS broken | Set `APP_URL` and `ASSET_URL` to `https://edulynk.co.ke/demo`; clear config cache |
| Routes 404 under `/demo` | `RewriteBase /demo/` in `public_html/demo/.htaccess` |
| Login loop / session lost | `SESSION_PATH=/demo`; clear browser cookies for the domain |
| 500 after deploy | Check `storage/logs/laravel.log`; fix permissions on `storage` |
| Forms post to HTTP | Force HTTPS; `APP_URL` must be `https://…` |
| Marketing site broken | You overwrote `public_html` root — restore from backup; demo must live only under `public_html/demo` |
