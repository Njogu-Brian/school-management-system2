# Deploy EduLynk Demo ERP on DirectAdmin (`royalce1`)

Server facts:

- User: `royalce1` @ `rs2-da`
- Marketing: `~/domains/edulynk.co.ke/public_html` (Laravel + React) · DB `royalce1_edulynk`
- Demo ERP DB: `royalce1_edulynk_erp` / user `royalce1_edulynk_erp`
- ERP app tree: `~/laravel-app/demo`
- Tools: PHP 8.2, composer, git, mysql CLI

Do **not** import the school dump into `royalce1_edulynk` (marketing).

**URL layout (important):**

| URL | Serves |
|-----|--------|
| `https://edulynk.co.ke/demo` | Marketing “Book a demo” page (keep Laravel marketing SPA) |
| `https://edulynk.co.ke/school-demo` | Demo ERP (Laravel school app) |

Do **not** symlink `public_html/demo` to the ERP — that replaces the marketing `/demo` route.

---

## Step 1 — Inspect existing ERP (Terminal)

```bash
ls -la ~/laravel-app/demo | head -30
test -f ~/laravel-app/demo/artisan && echo 'demo has artisan'
head -5 ~/laravel-app/demo/.env 2>/dev/null
ls -la ~/domains/edulynk.co.ke/public_html/demo 2>/dev/null
ls -la ~/domains/edulynk.co.ke/public_html/school-demo 2>/dev/null
```

If `public_html/demo` is a symlink to the ERP, remove it (Step 4) so marketing `/demo` works again.

---

## Step 2 — Configure `~/laravel-app/demo`

Edit `.env` — set at least:

```env
APP_NAME="EduLynk Demo Academy"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://edulynk.co.ke/school-demo
ASSET_URL=https://edulynk.co.ke/school-demo

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=royalce1_edulynk_erp
DB_USERNAME=royalce1_edulynk_erp
DB_PASSWORD=YOUR_PASSWORD_HERE

SESSION_DRIVER=database
SESSION_PATH=/school-demo
SESSION_DOMAIN=edulynk.co.ke

APP_ROLE=tenant
TENANT_SLUG=demo
EDULYNK_PATH_TENANCY=false

# Demo-only login UI (quick role buttons). Never enable on Royal Kings.
DEMO_QUICK_LOGIN=true
```

Then:

```bash
cd ~/laravel-app/demo
php artisan key:generate --force
composer install --no-dev --optimize-autoloader
php artisan storage:link 2>/dev/null || true
chmod -R ug+rwx storage bootstrap/cache
```

---

## Step 3 — Import sanitized demo dump

Upload `demo_school_20260930.sql.gz` to  
`~/laravel-app/demo/storage/app/backups/demo_school_20260930.sql.gz`

```bash
gunzip -c ~/laravel-app/demo/storage/app/backups/demo_school_20260930.sql.gz \
  | mysql -h 127.0.0.1 -u royalce1_edulynk_erp -p royalce1_edulynk_erp
```

If you see `ERROR 1045 (28000): Access denied`, reset the MySQL user password in DirectAdmin (MySQL Management) to match `.env`, or create the user with **All** privileges on `royalce1_edulynk_erp`.

Smoke check:

```bash
mysql -h 127.0.0.1 -u royalce1_edulynk_erp -p -e \
  "SELECT COUNT(*) AS students FROM students; SELECT \`value\` FROM settings WHERE \`key\`='school_name' LIMIT 1;" \
  royalce1_edulynk_erp
```

Expect ~462 students and `EduLynk Demo Academy`.  
Login: `admin@demo.school` / `Demo@12345`

---

## Step 4 — Wire `https://edulynk.co.ke/school-demo`

Restore marketing `/demo` if the ERP was mounted there:

```bash
# Remove ERP symlink from /demo (marketing SPA owns /demo again)
rm -f ~/domains/edulynk.co.ke/public_html/demo
# If marketing used a real demo folder from git deploy, redeploy marketing or restore from git — do not leave a broken symlink.
```

Mount ERP at `/school-demo`:

```bash
rm -rf ~/domains/edulynk.co.ke/public_html/school-demo
ln -sfn ~/laravel-app/demo/public ~/domains/edulynk.co.ke/public_html/school-demo
```

In `~/laravel-app/demo/public/.htaccess`, set:

```apache
RewriteBase /school-demo/
```

(near `RewriteEngine On`).

If the marketing root `.htaccess` rewrites everything to `index.php`, ensure `/school-demo` is not swallowed (physical symlink directory usually wins).

```bash
cd ~/laravel-app/demo
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Visit: **https://edulynk.co.ke/school-demo/login**  
Marketing demo page: **https://edulynk.co.ke/demo**

---

## Step 5 — Marketing “Login to sandbox” CTA

In `D:\Projects\Edulynk`, set production `.env`:

```env
EDULYNK_PORTAL_URL=https://edulynk.co.ke/school-demo/login
```

Deploy marketing (your `edulynk.git` hook / script). The `/demo` page and navbar **Sign In** use this URL.

Build assets locally if needed:

```bash
cd D:/Projects/Edulynk
npm run build
```

---

## Step 6 — Updates later

```bash
cd ~/laravel-app/demo
git pull   # if this tree is a git checkout
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

---

## Quick fix checklist (current server)

1. Remove `public_html/demo` → ERP symlink; confirm `https://edulynk.co.ke/demo` shows the request-demo form.
2. Symlink ERP to `public_html/school-demo`; update `.env` + `.htaccess` `RewriteBase`.
3. Fix MySQL 1045 if import failed; re-run import.
4. Set `EDULYNK_PORTAL_URL` on marketing and deploy.
5. Confirm login title shows **EduLynk Demo Academy**, not “Elementary School” (wrong DB or import not applied).
