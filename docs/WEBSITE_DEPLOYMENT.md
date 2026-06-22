# Public Website Deployment (Next.js)

The Royal Kings **public marketing site** is **not** part of Laravel routing. After `git pull` and migrations, you must also deploy the Next.js app in `website/`.

## URL map

| What | URL |
|------|-----|
| **Public website** (Next.js) | `https://erp.royalkingsschools.sc.ke/website` |
| **Website CMS** (staff admin) | `https://erp.royalkingsschools.sc.ke/website-cms` |
| **Public content API** | `https://erp.royalkingsschools.sc.ke/api/website/*` |
| **ERP login** | `https://erp.royalkingsschools.sc.ke/login` |

`/website` returning Laravel 404 means the Next.js process is not running or nginx is not proxying to it.

**`/website-cms` returning Next.js 404:** nginx `location /website` also matches `/website-cms`. Use the exact + prefix locations in `docs/nginx-website-snippet.conf` (`location = /website` and `location /website/` only).

## One-time server setup

```bash
sudo npm i -g pm2
```

Add to `/etc/nginx/sites-available/erp` **inside** the `server { ... }` block (before `location /`):

```nginx
    # Public marketing website (Next.js on port 3001)
    # Do NOT use "location /website" — it steals /website-cms from Laravel
    location = /website {
        proxy_pass http://127.0.0.1:3001;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_cache_bypass $http_upgrade;
    }
    location /website/ {
        proxy_pass http://127.0.0.1:3001;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_cache_bypass $http_upgrade;
    }
```

Then:

```bash
sudo nginx -t && sudo systemctl reload nginx
```

## Deploy after each pull

```bash
cd /var/www/erp
git pull
php artisan migrate --force
bash scripts/deploy-public-website.sh
```

Or manually:

```bash
php artisan db:seed --class=WebsiteCmsSeeder --force
php artisan db:seed --class=WebsiteSprints2130Seeder --force
cd website
npm ci && npm run build
PORT=3001 pm2 start npm --name royal-kings-website -- start -- -p 3001
pm2 save
```

## Verify

```bash
curl -s https://erp.royalkingsschools.sc.ke/api/website/settings | head -c 200
curl -s https://erp.royalkingsschools.sc.ke/api/website/homepage | head -c 200
curl -s -o /dev/null -w "%{http_code}\n" https://erp.royalkingsschools.sc.ke/website
```

- `settings` → JSON `200`
- `homepage` → JSON `200` (requires `WebsiteCmsSeeder` homepage)
- `/website` → `200` after Next.js + nginx proxy

**Important:** `WEBSITE_BASE_PATH` must be set at **build and runtime** (see `website/ecosystem.config.cjs`). Without runtime env, `/website` returns Next.js 404.

## Recommended long-term

Host the public site on its own domain (e.g. `www.royalkingsschools.sc.ke`) and keep `erp.*` for staff only. Set `NEXT_PUBLIC_BASE_PATH=` empty in that case.
