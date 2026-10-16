# Public Website Deployment (Next.js)

The Royal Kings **public marketing site** is **not** part of Laravel routing. After `git pull` and migrations, you must also deploy the Next.js app in `website/`.

## URL map

| What | URL |
|------|-----|
| **Public website** (Next.js) | `https://royalkingsschools.sc.ke/` |
| **Website CMS** (staff admin) | `https://erp.royalkingsschools.sc.ke/website-cms` |
| **Public content API** | `https://erp.royalkingsschools.sc.ke/api/website/*` |
| **ERP login** | `https://erp.royalkingsschools.sc.ke/login` |
| **Old `/website` path** | 301 to `https://royalkingsschools.sc.ke/` (media files under `/website/` are still served) |

`/website-cms` stays on Laravel. Do not use a nginx `location /website` prefix — it also matches `/website-cms`. Use `docs/nginx-website-snippet.conf`.

The public vhost is `docs/nginx-public-website.conf`. It proxies `/` to Next.js on port 3001, serves archived Mobirise files at `/assets/images/` and `/assets/files/`, and redirects the old `.html` pages.

## One-time server setup

```bash
sudo npm i -g pm2
```

1. Install the public vhost from `docs/nginx-public-website.conf` as `/etc/nginx/sites-available/royalkings-public` and enable it.
2. Put a certificate at `/etc/ssl/cloudflare/royalkingsschools.sc.ke.pem` and `.key` (Cloudflare Origin Certificate, or a temporary self-signed cert until that exists).
3. Replace the old `/website` proxy inside `/etc/nginx/sites-available/erp` with `docs/nginx-website-snippet.conf`.

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
- `https://erp.royalkingsschools.sc.ke/website` → `301` to the public site
- `https://royalkingsschools.sc.ke/` → `200` after Next.js + the public nginx vhost

**Important:** `WEBSITE_BASE_PATH` must be empty at **build and runtime** (see `website/ecosystem.config.cjs`) so the site is served at `/`.

## Import the Mobirise site

```bash
php artisan website:import-mobirise /var/www/erp/storage/app/mobirise-import/mobirise-site.tar.gz
```

This copies images into `public/website/imported` and `public/legacy-mobirise`, then fills settings, pages, sections, testimonials, and the media library. It is safe to run again. `--dry-run` parses without writing the database.

## Cloudflare DNS cutover

The zone is already on Cloudflare (`aida.ns.cloudflare.com`, `jasper.ns.cloudflare.com`). Email is Google Workspace. Do not change MX or TXT records.

1. DNS → Records. Export the current records first (Import and Export → Export).
2. Edit the **A** record for `royalkingsschools.sc.ke` (`@`) to `13.245.211.78`. Keep **Proxied**. TTL Auto.
3. Point **www** at the same address (A record, or CNAME to `royalkingsschools.sc.ke`), Proxied. Delete AAAA records for `@` and `www` that are not Cloudflare's.
4. Leave `erp`, every **MX** record (`ASPMX.L.GOOGLE.COM` and the ALT records), and every TXT record (SPF, DKIM, DMARC) unchanged.
5. SSL/TLS → Overview: **Full (strict)** after the origin certificate is installed. Use **Full** with the temporary self-signed cert. Never use **Flexible**.
6. Caching → Configuration → Purge Everything.
7. Check: `curl -I https://royalkingsschools.sc.ke` should show `x-powered-by: Next.js`.

**Rollback:** set the `@` and `www` A records back to the DirectAdmin server `84.16.254.2` (Proxied). The change is nearly immediate. Keep that hosting account for about two weeks after cutover.

Confirm `13.245.211.78` is an Elastic IP in the EC2 console before relying on it. A normal public IP changes when the instance stops.

## Google Search Console

After the Next.js app is deployed, these URLs are live on the public site:

- `https://royalkingsschools.sc.ke/sitemap.xml`
- `https://royalkingsschools.sc.ke/robots.txt`

In Search Console, open the property for `royalkingsschools.sc.ke` → Sitemaps → submit `sitemap.xml`. Do not submit the staff API sitemap (`erp.royalkingsschools.sc.ke/api/website/sitemap.xml`); that file lists the ERP host, not the public site.
