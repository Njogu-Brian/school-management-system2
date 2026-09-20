# Performance & AWS cost work — 2026-09-16

Audit of why the AWS bill was ~$28/month against an expected $7–8, why the
Finance → Transactions page took so long to load, and what was changed.

**Status at a glance**

| Area | State |
|---|---|
| nginx compression + cache headers | **Deployed and verified in production** |
| Duplicate `public/images/images/` directory | **Retired in production** (reversible) |
| Pre-deploy database backup | **Taken and verified** |
| Code changes (migrations, controller, image optimiser, scheduler) | **Committed locally — NOT deployed.** Blocked, see [Outstanding](#outstanding) |
| AWS instance right-sizing / Savings Plan | Not started — needs console access |

---

## 1. Why the bill is ~$28

Nothing unexpected is running. Roughly 70% of the bill is one `t3.small`
instance in `af-south-1` (Cape Town), on demand, 24/7. EC2 bills per hour
regardless of load, and Cape Town is among the most expensive regions.

Reconstructed from af-south-1 list prices, and since **confirmed against the
server** (`free -m` showed 1,906 MB, `df -h` showed a 29 GB root volume):

| Line | What it is | Est./month |
|---|---|---|
| EC2 – Compute | `t3.small`, 730 hrs × $0.0271 | $19.78 |
| VPC | 1 public IPv4, 730 hrs × $0.005 | $3.65 |
| EC2 – Other | ~30 GB gp3 root volume | $3.14 |
| S3 + Secrets Manager | storage, requests, secrets | ~$1.60 |

There is no NAT gateway, load balancer, or forgotten RDS instance — the three
things that usually inflate a small bill. The architecture is already lean,
which means savings have to come from price optimisation and right-sizing.

**$7–8/month is not reachable on EC2 in af-south-1 with an always-on instance.**
The public IPv4 alone is $3.65. The realistic floor in Cape Town is $11–13.
Genuine $7 territory means an AWS Lightsail Micro bundle, which is not offered
in af-south-1 and so means hosting outside Africa.

Run `scripts/aws-cost-audit.sh` in AWS CloudShell for the real per-usage-type
breakdown, idle Elastic IPs, EBS details, and AWS's own Savings Plan
recommendation. It is read-only.

---

## 2. Why Transactions was slow

`BankStatementController@index` had four compounding problems.

1. **Pagination was done in PHP, not SQL.** Every bank statement transaction and
   every M-Pesa C2B transaction was hydrated into an Eloquent model with six
   eager-loaded relations each, concatenated, sorted in PHP, then sliced down to
   25 rows. The rest of the work was discarded on every page view.

2. **A correlated subquery that could not use an index.** The collected /
   partially paid / uncollected filters matched payments with
   `transaction_code LIKE CONCAT(bank_statement_transactions.reference_number, '-%')`.
   Because the pattern is built from another table's column, MySQL cannot use
   the index on `transaction_code` and re-scans the whole `payments` table once
   per bank row — nine times over, to draw the tab badges.

3. **An N+1 write loop on a GET request.** `checkCrossTypeDuplicates()` ran one
   query per C2B transaction, including a leading-wildcard `LIKE` on
   `phone_number`, and wrote when it found a match.

4. **Schema introspection per request.** `Schema::hasColumn()` /
   `hasTable()` query `information_schema` on every page load.

### Measured, on 6,000 bank transactions and 9,000 payments

| Page view | Before | After |
|---|---|---|
| 1 (cold) | 78,107 ms | 427 ms |
| 2 | 77,459 ms | 117 ms |
| 3 | 128 ms | 126 ms |
| 4 (page 2) | 117 ms | 163 ms |

The worst single query went from **36,948 ms to 46 ms**. Two `count(*)` queries
carrying the correlated subquery accounted for 78 of the original 78 seconds.

> Production currently holds 957 bank transactions, 2,603 payments and 1,591 C2B
> transactions, so today's real page is slow rather than unusable. The cost grows
> with the product of the table sizes, so it gets worse over time.

---

## 3. Changes made

### Database

`database/migrations/2026_09_16_210000_add_transactions_page_performance_indexes.php`

Composite indexes so the filter and `ORDER BY` can be served from one index:

- `bank_statement_transactions (is_archived, is_duplicate, transaction_type, transaction_date)`
- `payments (transaction_code, reversed, amount)` — covering, so `SUM(amount)` needs no row lookups
- `mpesa_c2b_transactions (is_duplicate, status, trans_time)`

`database/migrations/2026_09_16_211000_add_base_transaction_code_to_payments.php`

Adds `payments.base_transaction_code`, a **stored generated column** holding the
part of `transaction_code` before the first hyphen, plus an index on
`(base_transaction_code, reversed, amount)`. A code with no hyphen maps to
itself; a split code such as `UA66G2XJF7-145` maps to its parent
`UA66G2XJF7`. This converts the unindexable `LIKE CONCAT` into a plain indexed
equality.

Both migrations are defensive (they check for existing columns and indexes
before acting) because production schema has drifted from a clean migration run.

### Why the generated column is safe

Verified against the production dump at
`storage/certificates/term2-2026/_dbdump/backup.sql` (MySQL 8.0.46, 2026-07-29),
imported into a scratch schema and dropped afterwards:

| Check | Result |
|---|---|
| Bank references containing a hyphen | 0 of 841 |
| Payment codes containing a hyphen (split payments) | 347 of 2,108 |
| References that are a prefix of another reference | 0 |
| Rows where old and new sums disagree | 0 of 841 |
| Worst difference | 0.00 |
| Grand total, old vs new | 7,154,430.86 vs 7,154,430.86 |
| Disagreements on collected / uncollected / partially paid | 0 / 0 / 0 |

Re-confirmed against **live** production during recon: `hyphenated bank refs: 0`
across all 957 rows.

**Do not add an `OR transaction_code = reference_number` arm** to those queries
as a belt-and-braces exact match. It looks harmless and returns the plan to a
full scan — measured 36.4 s versus 40 ms — because the optimiser will not
index-merge inside a correlated subquery. There is a comment in the controller
saying so.

The single assumption polices itself: `warnOnHyphenatedReferences()` runs inside
the existing ten-minute housekeeping window and logs a warning with samples if a
hyphenated reference ever appears, so the failure mode cannot be silent.

### Application

| File | Change |
|---|---|
| `BankStatementController` | Two-pass pagination: a key-only scan to merge and sort both sources, then hydrate only the current page with relations |
| `BankStatementController` | `activePaymentsByReference()` — one query per page instead of two per row |
| `BankStatementController` | `syncCrossTypeDuplicates()` — the whole-table write loop is throttled to once per ten minutes instead of running on every view |
| `BankStatementController` | Tab counts cached behind a write-stamped key |
| `BankStatementController` | `orderBy('id', 'desc')` added as a final sort tiebreaker |
| `InvalidatesTransactionListingCounts` (new trait) | Bumps a cache stamp on any write to either transaction table, so badges still update instantly |
| `SendFeeRemindersJob` | `shouldRunNow()` extracted; the scheduler gates on it before dispatching |
| `routes/console.php` | Reminder job dispatched on 1 minute a day instead of 1,440 |
| `ImageOptimizer` (new service) | Downscales and recompresses uploads using GD |
| `SettingController`, `GalleryController` | Optimise branding and gallery uploads on the way in |
| `OptimizeExistingImages` (new command) | `php artisan images:optimize` for files already on disk |
| `app/helpers.php` | `public_images_path()` no longer doubles a trailing `images` segment |

### Two pre-existing bugs found and fixed

**The payment badge was double counting.**
`autoLinkBankTransactionsByReference()` populates `linked_payment_ids` from the
very payments the reference match already found, and the old code added both
sums together — so a fully-paid transaction displayed twice its real collected
amount. Totals are now merged by payment id.

**Pagination was non-deterministic.** Sorting only by
`(transaction_date, created_at)` is not unique for rows imported in the same
batch, so rows could appear on one page and vanish from another.

---

## 4. Deployed to production

### nginx compression and cache headers — done, verified

Installed as two new files. The existing `sites-available/erp` was **not**
replaced: it is managed by Certbot and also carries the Next.js `/website`
proxy. Only a single `include` line was added to it.

- `deploy/nginx/erp-gzip.conf` → `/etc/nginx/conf.d/erp-gzip.conf`
- `deploy/nginx/erp-static-cache.conf` → `/etc/nginx/snippets/erp-static-cache.conf`

Ubuntu's `nginx.conf` already sets `gzip on` but leaves `gzip_types` commented
out, so only `text/html` was ever compressed — that is why CSS and JS shipped
raw. The conf.d file therefore sets `gzip_types` and friends but deliberately
**not** `gzip on`, which would be a duplicate directive and fail `nginx -t`.

Verified over the wire against production:

| Asset | Plain | Gzipped | Saved |
|---|---|---|---|
| `adminlte.min.css` | 1,396,747 B | 129,238 B | **91%** |
| `jquery.js` | 288,580 B | 86,502 B | **70%** |

`/login`, `/images/logo.png` and `/website` all still return 200. Source maps
now return 403.

**Cache header blocks use `^~` prefix matching, not regex, deliberately.** This
server reverse-proxies Next.js at `/website`, which serves its own assets from
`/website/_next/static/*.css` and `*.js`. A regex location like
`~* \.(css|js)$` takes precedence over the `location /website/` proxy and would
try to serve those from disk — breaking the marketing site's styling entirely.

There is also **Cloudflare in front of the origin**, so these `Cache-Control`
headers now let Cloudflare cache assets at the edge, cutting origin bandwidth as
well as browser round-trips.

Rollback:

```bash
sudo cp -a /root/nginx-backup-20260916_203409Z/erp /etc/nginx/sites-available/erp
sudo rm -f /etc/nginx/conf.d/erp-gzip.conf /etc/nginx/snippets/erp-static-cache.conf
sudo nginx -t && sudo systemctl reload nginx
```

### Duplicate images directory — retired

`public/images/images/` held 13 byte-identical duplicates of files in
`public/images/`, plus 3 files referenced nowhere in settings or the gallery.
2.8 MB, moved rather than deleted:

```bash
# restore if ever needed
sudo mv /var/www/erp/storage/temp_backup/images-nested-20260916_203636Z \
        /var/www/erp/public/images/images
```

The likely origin is a recursive `scp`/`rsync` of `public/images` into itself
rather than a misconfiguration — `PUBLIC_WEB_ROOT` is **not** set in production.
The `public_images_path()` hardening is a safety net against the config form of
the same mistake, not a fix for what happened here.

### Pre-deploy database backup

```
/var/www/erp/storage/temp_backup/predeploy_20260916_202752Z.sql.gz   (11.93 MB)
```

Verified: gzip integrity OK, contains `payments` schema, `payments` data, and
`bank_statement_transactions` schema.

Credentials had to come from Laravel's resolved config rather than from parsing
`.env` — the file has CRLF line endings, and something on the host carries a
`[mysqldump]` section specifying `user=admin` which outranks `[client]`, so
`--defaults-file` (not `--defaults-extra-file`) is required to force the correct
user.

---

## Follow-up: image caching and notification polling

Two corrections to earlier findings in this document, both from a later audit.

### Mobile apps DO poll — earlier claim was wrong

An earlier pass concluded there was "no `refetchInterval` anywhere" in
`mobile-app/`. That was based on a truncated search. There are seven, and two ran
globally on every authenticated screen:

| Interval | Hook | Scope | Requests/device/day |
|---|---|---|---|
| 20s | `useInfiniteNotifications` | every screen, users + iOS | ~4,320 |
| 60s | `useUnreadNotificationCount` | every screen with header chrome | ~1,440 |
| 15s / 20s | `useStaffClock*` (4 hooks) | staff clock screens | ~15,800 while open |
| 5s | `useLiveBusForStudent` / `useLiveFleet` | transport tracking | ~17,280 while open |

The 20-second notification poll duplicated push notifications that were already
registered and delivering the same events. Both global polls are now removed:
`refetchInterval` is gone from the badge count, and on the list hook it became an
opt-in `refetchIntervalMs`. The push foreground callback in
`UsersPushNotifications` and `IosPushNotifications` now invalidates
`queryKeys.notifications.all`, so the badge and any open list still update
immediately — and the ~100 lines of seen-id diffing that existed only to emulate
push are gone.

Staff clock and live transport intervals are left alone for now; they only run
while those screens are open, and 5s transport tracking is arguably correct.

### Image caching was structurally impossible, and expo-image alone would not have fixed it

Student and staff photos resolve through
`Student::getPhotoUrlAttribute()` → `storage_public_url()` → `media_signed_url()`,
which called `URL::temporarySignedRoute(..., now()->addMinutes(10))`. Because
`now()` advances, **every API response returned a different URL for the same
photo** — a fresh `expires` and `signature` each time. Every image cache keys on
the URI, so browser cache, React Native's native cache, and `expo-image`'s disk
cache would all have missed on every single render. Adding `expo-image` would
have cost an EAS build and a store review and still re-downloaded every avatar.

It was also wrong in a second way: mobile persists API responses for 24 hours via
`PersistedQueryProvider`, so cached avatar URLs were already expired on arrival —
which is why avatars break after the app sits idle.

**Fix:** `media_signed_url()` now snaps the expiry to a fixed grid rather than
`now() + ttl`, so repeated calls for the same path return a byte-identical URL.
Remaining validity ranges from one to two full windows, so a URL issued at the
end of a window does not expire moments later. `storage_public_url()` defaults to
7 days (public assets only — `storage_private_url()` stays at 10 minutes for
documents), and the 60-minute cap became 30 days via
`MEDIA_SIGNED_URL_MAX_MINUTES`.

`MediaController::signedRedirect()` now also sets `Cache-Control` on the 302 and
honours the longer window. Previously every avatar render was a PHP request that
booted the framework just to re-issue the same redirect — scrolling a
40-student list meant 40 framework boots plus 40 S3 GETs.

Verified with a throwaway harness (9 assertions, since removed): repeated calls
identical, different paths differ, ~333 hours of validity, signature still
validates, tampering rejected, private documents still expire in 12 minutes.

**`expo-image` is now worth adding** — but it is a native dependency, so it needs
a real EAS build and store submission, not an OTA update.

### nginx: `/storage/` had no cache block — now deployed

The snippet deployed earlier covered `/images/`, `/build/`, `/vendor/`, `/css/`
and `/js/` but not `/storage/`, the one public asset path with no caching at all.
Added and deployed with the same backup/`nginx -t`/rollback procedure.

Verified live: `/images/logo.png` returns `public, max-age=2592000`, `/login`
stays `no-cache, private`, and `/website` keeps its Next.js caching. Note that
production has `FILESYSTEM_PUBLIC_DISK=s3_public`, so photos actually travel via
the `/media/` signed route rather than `/storage/` — the block is a safety net for
legacy local files. **The real photo win is the code change above, which is still
waiting on the git deploy.**

Rollback: `sudo cp /root/erp-static-cache.conf.bak.20260917_025908Z /etc/nginx/snippets/erp-static-cache.conf && sudo nginx -t && sudo systemctl reload nginx`

---

## Outstanding

### The code changes are not deployed

Deployment is `git push origin main`, which triggers
`.github/workflows/deploy-production.yml` → `scripts/deploy-production.sh` →
`deploy-ec2.sh`, including `php artisan migrate --force`.

**Every `git` index write in this repository fails** with
`fatal: unable to write new index file`. The same error blocks `git add`,
`git stash`, and therefore `git commit`. Established by testing:

- Plenty of disk space (145 GB free), no stale `index.lock`
- `.git/index` opens read-write from PowerShell, and `.git/index.lock` is creatable
- A brand-new repository in `%TEMP%` stages files fine, so git itself is healthy
- No `git` processes running, but 17 `Cursor` processes are

The cause is almost certainly the IDE holding a handle on `.git/index`. The fix
is on the workstation, not in this repo:

```powershell
# close Cursor, or disable its Git extension, then:
cd d:\Projects\school-management-system2\school-management-system2
git add -A
git commit -m "Speed up Transactions page; optimise uploaded images; trim scheduler waste"
git push origin main
```

Then watch the run at
<https://github.com/Njogu-Brian/school-management-system2/actions> and confirm
afterwards:

```bash
ssh school-erp "cd /var/www/erp && php artisan migrate:status | tail -5"
```

The code was **not** applied to the server by hand on purpose: the deploy does
`git reset --hard origin/main`, which would wipe it, and the migrations would
then be recorded as run against code absent from git.

### The login background is 404ing right now

`login_background` is set to `1772462317_20241024-IMG_1144.jpg`, which exists
**neither** on disk under `public/images/` nor on the `s3_public` disk. The login
page returns 200 but renders without its background. Re-upload it from
Settings → Branding, or clear the setting.

This is the silent-failure class the `public_images_path()` hardening guards
against: the upload appears to succeed and the image simply never appears, with
nothing in the logs.

### Not started

- Buy a 1-year Compute Savings Plan (~28% off compute, no downtime)
- Migrate `t3.small` → `t4g.small`, or `t4g.micro` now that CPU pressure is gone
- Right-size the 29 GB EBS volume (12 GB used)
- Delete unused Secrets Manager secrets; release any idle Elastic IP
- Move `CACHE_STORE` off `database` onto Redis (sessions are already `file`)
- Run `php artisan images:optimize` on production once the code ships

### Test suite is broken independently of this work

Roughly 40 tests fail on `User::factory()->create(['role' => 'Admin'])` because
`users` no longer has a `role` column, and others on a missing
`ClassroomFactory`. Until these are fixed the suite cannot tell you whether
anything regressed. All behaviour verification for this work was done with
purpose-built scripts asserting ordering, totals, pagination, sorting, eager
loading, and cache invalidation.
