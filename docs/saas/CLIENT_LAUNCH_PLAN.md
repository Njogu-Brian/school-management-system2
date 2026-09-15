# Client launch plan — sell the ERP to a second school

**Status:** Ready to execute  
**Constraint:** You are solo. Royal Kings (Rocketings) stays live and must not break.  
**Goal:** Onboard one willing external school on their own host, with a combined app, without forking the repo.

This is the working plan. Product store name and tenant domain are still yours to lock (see Wave 0). Until then this document uses:

| Placeholder | Meaning |
|---|---|
| `PRODUCT` | Store / company brand (not Royal Kings, not Rocketings) |
| `control.PRODUCT.com` | Control-plane host |
| `{slug}.PRODUCT.com` | One school’s web + API |
| `CODE` | School code the app asks for once (e.g. `STM001`) |

Royal Kings remains tenant `#1` at `erp.royalkingsschools.sc.ke`, code `RKS001`.

---

## 1. Locked decisions

1. **One git repo. Many isolated deployments.** Never clone this project per school. Never keep a long-lived SaaS fork.
2. **Database + process isolation.** Each school gets its own EC2 (or equivalent) and its own MySQL. Not the Royal Kings box. Not a shared PHP process.
3. **Control plane is a dedicated small server** running this same codebase with `APP_ROLE=control_plane`. It owns `schools_registry` and `/api/schools/resolve`. It does **not** hold student/fee data.
4. **Same release everywhere.** A bugfix lands on `main`, is tagged, and is deployed to Royal Kings **and** every tenant.
5. **One combined store app** (Android + iOS) for the new brand. Parents, teachers, admins, director. Role + Work/Home after login. Royal Kings Play Store apps stay as they are.
6. **School code once**, persisted on the device. In-app name/logo/colours change. OS icon and home-screen name do **not** (store limitation).
7. **Help lives in the product.** PDF is an export of the same articles. No local LLM for v1.
8. **First 3 schools: you create the VM by hand. Software install is a script.** Auto-creating AWS instances comes later.
9. **Do not wait for Play/App Store review to take the client.** Web portal + a preview APK/TestFlight is go-live. Store listing runs in parallel.

---

## 2. Gaps this plan closes (reanalysis)

These would have blocked a real client if we only followed the earlier architecture notes.

| Gap | Why it matters | Fix in this plan |
|---|---|---|
| Control plane currently lives **on** Royal Kings | If RK is down, the new school’s app cannot resolve the code. You also mix operator data with a customer. | New small EC2 for control plane from day 1. |
| `scripts/deploy-production.sh` deploys the **Royal Kings website** and runs `WebsiteBrandElevationSeeder` | That must never run on a tenant. | New `scripts/deploy-tenant.sh` (ERP only). RK workflow stays unchanged. |
| GitHub Actions deploys **one** host (`DEPLOY_HOST`) | A push to `main` would only update RK, or worse, be pointed at the wrong box. | Keep RK workflow. Add a **manual** `deploy-tenant` workflow (you pick the school). |
| `DatabaseSeeder` → `Comprehensive2025Seeder` | That seeder creates students, invoices, RK-shaped demo data. | New `TenantBootstrapSeeder`: roles, COA, CBC catalog, settings, **zero** learners. |
| `schools:register` does not create the first admin or write tenant branding | You would have a code and an empty unusable site. | `tenant:bootstrap` creates Super Admin + branding + force-password. Then register. |
| Combined **Android** app folder does not exist | `mobile-app/apps/ios` already has `android.package`. Combined Android is a rebrand + EAS profile, not a rewrite. | Generalize `apps/ios` → `apps/combined` (or add Android EAS profiles there). New bundle IDs. |
| Play/App Store review is **weeks** | Client cannot wait. | Preview/internal APK + TestFlight first. Store in parallel. |
| `clearSchool()` has no UI | Wrong code = stuck forever. | “Change school” on login + settings before any external user installs. |
| Per-school secrets | M-Pesa, WhatsApp, S3, mail, Expo push are RK’s today. | Tenant `.env` is a checklist. Never copy RK keys. |
| Public `website/` is Royal Kings | Irrelevant for client 1. | Do not deploy Next.js on tenant hosts. |
| Help/manuals do not exist | Support load will land on you. | 20 critical-path articles before go-live; full corpus after. |
| Queue + scheduler + backups | Fees SMS, attendance reminders, statement jobs will silently fail. | Supervisor + cron + nightly mysqldump→S3 **per tenant**, copied from RK pattern. |

---

## 3. Target shape (what “ready” looks like)

```
You (browser)
  → https://control.PRODUCT.com/operator
      create school, branding, first admin, status, school code

Phone (PRODUCT app)
  → GET https://control.PRODUCT.com/api/schools/resolve?code=STM001
  → then all traffic to https://{slug}.PRODUCT.com/api/...

School admin (browser)
  → https://{slug}.PRODUCT.com   (full ERP, their branding)

Royal Kings (unchanged)
  → https://erp.royalkingsschools.sc.ke
  → existing Admin + Users Play Store apps (no school code)
```

Three machines minimum:

| Machine | Size (AWS starting point) | Runs |
|---|---|---|
| **RK existing** | Current EC2 | Do not touch topology |
| **Control plane** | `t3.small` (2 vCPU / 2 GB), 30 GB gp3, Ubuntu 22.04 | Laravel (operator UI + resolve API) + MySQL (`schools_registry` + operator users + help articles) |
| **Client tenant** | `t3.medium` (2 vCPU / 4 GB), 40–80 GB gp3, Ubuntu 22.04 | Laravel ERP + MySQL + Redis optional + queue workers + scheduler |

Same AWS **account** is fine. Same **instance** is not.

---

## 4. Waves (build this order, do not skip)

Calendar assumption: you working focused alongside implementation. Store review is calendar time, not engineering time.

### Wave 0 — Guardrails and decisions (1–2 days, you + this repo)

**You do**

- [ ] Lock **product name** (store listing, app name on the home screen).
- [ ] Buy/point a domain: `PRODUCT.com` (or a subdomain you already own).
- [ ] Decide client hostname: `{slug}.PRODUCT.com` **or** `erp.theirschool.ac.ke` (CNAME to the tenant).
- [ ] Collect client intake (section 8). Do this **today** while build starts.
- [ ] Confirm AWS region (use the same region as RK, likely `af-south-1`).
- [ ] Confirm you have: AWS console, GitHub repo admin, Expo/EAS login, Apple Developer (if iOS this quarter), Google Play Console (if Android this quarter), a DNS panel.
- [ ] Create an S3 bucket for **this product’s** backups/uploads, separate from RK (`product-tenant-backups`, `product-tenant-uploads`). Never reuse RK bucket credentials on the new school.

**We build**

- [ ] Branch `epic/saas-launch` from current `main`. RK hotfixes stay on `main`.
- [ ] Document in-repo: this file is the source of truth (replace the old “next steps” list in `docs/saas/README.md` with a pointer here).

**Exit:** Name, domain, client intake started, branch exists. RK still deploys only from `main`.

---

### Wave 1 — Tenant factory (the client can actually log in) — ~5–8 working days

This wave is what unblocks the willing school. Control-plane **UI can be thin**; the scripts must be solid.

**We build**

1. **`APP_ROLE` split** in this codebase  
   - `control_plane`: operator routes + `/api/schools/resolve` + help CMS. No student modules.  
   - `tenant` (default): full ERP. `schools_registry` is not written here.

2. **`TenantBootstrapSeeder` + `php artisan tenant:bootstrap`**  
   Seeds only:
   - `CanonicalRolesAndPermissionsSeeder`
   - Chart of accounts, statutory payroll ruleset, payment methods
   - Votehead categories, document counters, grading schemes
   - CBC catalog (if they are a Kenyan CBC school — flag on the command)
   - Communication / attendance message templates (generic, not RK copy)
   - Settings: school name, contacts, colours, logo, timezone `Africa/Nairobi`, currency `KES`
   - One **Super Admin** user + Staff row, `must_change_password = true`  
   Does **not** seed students, invoices, RK bank, website CMS, demo exams.

3. **`scripts/provision-tenant.sh`** (run **on the new EC2** after clone)  
   Composer, npm build, key generate, migrate, `tenant:bootstrap`, queue worker, scheduler, storage perms. No website deploy.

4. **`scripts/deploy-tenant.sh`**  
   `git fetch` + `reset` to a **release tag**, composer, vite build, migrate, cache, restart workers. No RK website seeder.

5. **Operator UI (minimum)** on the control plane:
   - Login (you only)
   - Create school: name, slug, contacts, colours, logo, admin name/email/phone
   - Generates `CODE`
   - Status: `provisioning` → checklist → `active` / `suspended`
   - Shows the school code, tenant URL, “copy invite text for the admin”
   - Does **not** create AWS by itself. After you finish the EC2 cookbook, you paste `api_base_url` and mark active (or the provision script calls `schools:register` for you).

6. **GitHub Action `deploy-tenant.yml`**  
   `workflow_dispatch` with input `tenant` (`stm` / `rks` later). Maps to host via secrets. **Default push to `main` still only deploys RK.**

**You do (hosting) — follow section 6 exactly**

- [ ] Launch control-plane EC2 and first tenant EC2.
- [ ] DNS + TLS.
- [ ] Run provision script on the tenant.
- [ ] Smoke: web login as the new admin, force password change, Settings → branding visible.
- [ ] Smoke: `curl https://control.PRODUCT.com/api/schools/resolve?code=CODE`

**Exit:** You can create a school record, stand up a blank ERP on a separate host, and the school admin can sign in on the web. Royal Kings untouched.

---

### Wave 2 — Combined PRODUCT app (Android now, iOS in parallel) — ~4–6 working days + store calendar

**We build**

1. Combined app based on `mobile-app/apps/ios` (already merges admin + users shells).
   - New display name = `PRODUCT`
   - New bundle IDs, e.g. `com.PRODUCT.app` (replace; do not reuse `com.royalkingsschools.*`)
   - `EXPO_PUBLIC_REQUIRE_SCHOOL_CODE=true`
   - `EXPO_PUBLIC_CONTROL_PLANE_BASE_URL=https://control.PRODUCT.com/api`
   - EAS profiles: `preview` (internal) and `production` (store)
2. Persist school context (already in `@erp_school_context`). Wire **Change school** on the code screen and in Settings.
3. After resolve, load `/app-branding` from the **tenant** (already exists) so colours/logo/name apply.
4. Keep Work | Home behaviour exactly as the combined iOS app today.
5. Royal Kings `apps/admin` and `apps/users` EAS configs stay `REQUIRE_SCHOOL_CODE=false`.

**You do**

- [ ] Create Expo project / EAS app for the new bundle ID (do not hijack RK EAS project).
- [ ] Google Play: new app listing `PRODUCT` (can start as internal testing track — days, not weeks).
- [ ] Apple: new App ID + TestFlight if iOS is in the client contract.
- [ ] Install preview APK on your phone, enter `CODE`, log in as the tenant admin, as a teacher, as a parent test user.
- [ ] Give the client the internal-testing link, not a Play production listing, for week 1.

**Exit:** One APK/IPA. Code once → their branding → their database. Wrong code can be cleared. RK apps still open RK with no extra step.

---

### Wave 3 — Help that the client can search (starts during Wave 1, “enough” by go-live)

**We build**

- `help_articles` (title, slug, module, roles, body markdown, related slugs)
- Web: `/help` search + article. `?` on key screens.
- Mobile: Help tab / screen using the tenant (or control plane) help API so copy is not duplicated per school.
- `php artisan help:export-pdf` for a printable manual from the same rows.
- Seed the **critical 20** (section 9). Rest of the corpus is ongoing.

**No AI in Wave 3.** After 50+ articles exist, add a cloud RAG box that may only quote articles.

**Exit:** School admin can search “archive a student” and get steps for web + app.

---

### Wave 4 — After the first client is stable (do not start before)

- Second tenant = rerun cookbook + `tenant:bootstrap` + register (should be a half-day).
- Billing in the control plane (invoices/M-Pesa for *your* fee, not school fees).
- Optional auto-provision of EC2.
- AI help on the article corpus.
- Paid white-label binary (custom icon) only if a contract pays for it.

---

## 5. Git so a Rocketings bugfix reaches the new school

```
main                    Royal Kings production. Hotfixes land here.
epic/saas-launch        Control plane, tenant scripts, combined app, help.
release/x.y.z           Optional tags once two schools are live.
```

**Rules**

- Do not merge unfinished control-plane work onto `main` until tenant migrate + bootstrap has been smoked on a staging box.
- When SaaS waves are stable, merge `epic/saas-launch` → `main`. From that day, **both** RK and the client run the same tag.
- Hotfix after that: branch from `main` → PR → tag `vX.Y.Z` → GitHub Action deploys RK automatically; you dispatch `deploy-tenant` for the client (or both, if the fix is shared).
- Never copy files into a second repo “to be safe.” Isolation is the server, not the source.

Mobile: one commit can ship RK Admin/Users **and** PRODUCT combined, because they are different EAS profiles in the same monorepo.

---

## 6. Hosting cookbook (AWS EC2 — first client)

Use this if you stay on AWS. If you later pick another host (Hetzner, DigitalOcean, Lightsail), the **software steps are identical**; only the VM create + firewall UI changes.

### 6.1 Create the two new instances (AWS console)

For **each** of Control plane and Tenant:

1. EC2 → Launch instance.
2. Name: `product-control` / `product-{slug}`.
3. AMI: Ubuntu Server 22.04 LTS.
4. Instance type: `t3.small` (control) / `t3.medium` (tenant).
5. Key pair: create a **new** key `product-erp.pem` (do not reuse RK key on a shared laptop forever; you may reuse in AWS if you already protect `erp-key.pem`).
6. Network: same VPC as RK is OK.
7. Security group **new** (do not reuse RK’s if it is wide open):
   - SSH `22` from **your IP only**
   - HTTP `80` from `0.0.0.0/0`
   - HTTPS `443` from `0.0.0.0/0`
   - **No** MySQL `3306` to the world
8. Storage: 30 GB control / 60 GB tenant, gp3, encrypted.
9. Elastic IP: allocate and associate (so DNS does not move on stop/start).
10. Note the public IPs.

### 6.2 DNS

```
control.PRODUCT.com     A     <control Elastic IP>
{slug}.PRODUCT.com      A     <tenant Elastic IP>
```

If the school wants `erp.school.ac.ke`, they create a CNAME to `{slug}.PRODUCT.com` (or an A record to the tenant EIP).

Wait until `dig control.PRODUCT.com` returns the EIP before issuing TLS.

### 6.3 Base packages (both machines)

SSH: `ssh -i product-erp.pem ubuntu@<EIP>`

```bash
sudo apt update && sudo apt -y upgrade
sudo apt install -y nginx mysql-server redis-server unzip git curl \
  php8.2-fpm php8.2-mysql php8.2-mbstring php8.2-xml php8.2-curl \
  php8.2-zip php8.2-gd php8.2-bcmath php8.2-intl php8.2-redis \
  supervisor certbot python3-certbot-nginx python3 python3-pip
curl -sS https://getcomposer.org/installer | php && sudo mv composer.phar /usr/local/bin/composer
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
```

Lock MySQL to localhost. Create a **unique** database and user **per machine**. Never reuse RK’s `DB_PASSWORD`.

```bash
sudo mysql
```

```sql
CREATE DATABASE erp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'erp'@'localhost' IDENTIFIED BY 'GENERATE_A_LONG_SECRET';
GRANT ALL ON erp.* TO 'erp'@'localhost';
FLUSH PRIVILEGES;
```

### 6.4 App code (both machines)

```bash
sudo mkdir -p /var/www/erp
sudo chown ubuntu:ubuntu /var/www/erp
cd /var/www/erp
git clone -b epic/saas-launch <your-repo-url> .
# after first merge to main, clone main instead
```

Server must be able to `git fetch` (HTTPS PAT or deploy key), same as RK (`docs/AUTO_DEPLOY.md`).

### 6.5 Nginx + TLS

`server_name` is `control.PRODUCT.com` or `{slug}.PRODUCT.com`. Root is `/var/www/erp/public`. Copy the existing nginx pattern from `docs/EC2_DEPLOYMENT.md` (PHP-FPM, `client_max_body_size 12M`). Then:

```bash
sudo certbot --nginx -d control.PRODUCT.com
# or
sudo certbot --nginx -d {slug}.PRODUCT.com
```

### 6.6 Control plane `.env` (only that box)

```
APP_NAME=PRODUCT Control
APP_ENV=production
APP_DEBUG=false
APP_URL=https://control.PRODUCT.com
APP_ROLE=control_plane

DB_DATABASE=erp
DB_USERNAME=erp
DB_PASSWORD=...

# no M-Pesa, no school WhatsApp, no RK S3
```

Then: `composer install --no-dev`, `php artisan key:generate`, `php artisan migrate --force`, create **your** operator user, `php artisan config:cache`.

No queue-heavy workload required on control beyond mail if you send the admin invite from here.

### 6.7 Tenant `.env` (client box) — never copy RK `.env`

Must be unique per school:

| Key | Notes |
|---|---|
| `APP_URL` | `https://{slug}.PRODUCT.com` |
| `APP_ROLE` | `tenant` |
| `DB_*` | This machine only |
| `MAIL_*` | Their mail (or your SES with a from-address you control) |
| M-Pesa / Jenga | **Their** shortcode and keys, when finance goes live |
| WhatsApp / Wasender | **Their** session, or disabled until they buy it |
| `AWS_*` | Product backup/upload bucket, prefix `{slug}/` |
| Expo push | Tokens for the **PRODUCT** app, not RK Admin/Users |
| `BACKUP_UPLOAD_TO_S3` | `true` |
| `PARENT_CLAIM_ENABLED` | Agree with the client before turning on |

Then run `scripts/provision-tenant.sh` (Wave 1). That script will migrate, bootstrap admin, set branding.

### 6.8 Queue + schedule (tenant only)

Supervisor: copy `config/supervisor-erp-worker.conf` but keep paths `/var/www/erp`.

Cron as `www-data`:

```
* * * * * cd /var/www/erp && php artisan schedule:run >> /dev/null 2>&1
```

### 6.9 Backups (tenant only, from night one)

Nightly `mysqldump` of `erp` → S3 prefix `{slug}/db/`. Keep 14 days. Test a restore onto a throwaway database once before the client enters real fees.

### 6.10 How the pieces hook together

1. Control plane row: `code=STM001`, `api_base_url=https://{slug}.PRODUCT.com/api`, `status=active`, branding snapshot.
2. Combined app resolve URL is **always** `https://control.PRODUCT.com/api`.
3. After resolve, Axios base URL becomes the tenant. Login (`/api/login`) never hits the control plane.
4. Web users bookmark `https://{slug}.PRODUCT.com`. They do not use a school code on the web (the hostname **is** the school).
5. To suspend a school: set registry `status=suspended`. App resolve returns 403. Optionally stop nginx on the tenant. RK is unaffected.

### 6.11 Do not

- Put the tenant PHP app on `13.245.211.78`.
- Open `3306` to `0.0.0.0/0`.
- Run `scripts/deploy-production.sh` on a tenant (RK website seeder).
- Point GitHub `DEPLOY_HOST` at the new school.
- Copy RK `.env`, M-Pesa keys, or Wasender session.
- Run `Comprehensive2025Seeder` / `DatabaseSeeder` on the tenant.

---

## 7. App strategy (so the client is not blocked by stores)

| Track | When | Who uses it |
|---|---|---|
| Web ERP | End of Wave 1 | School admin, finance, secretaries (desktop) |
| Internal/preview combined APK | End of Wave 2 | Teachers, you, a few parents for UAT |
| Play internal testing | Days after preview | Wider Android staff |
| Play production + App Store | 1–3 weeks review | Everyone |

Royal Kings parents keep downloading **Royal Kings Users**. They never see a school code.

PRODUCT users type `CODE` once.

If iOS is required in the contract, start Apple enrollment **in Wave 0**. It is often the longest wait.

---

## 8. Client intake (send this now)

You cannot provision blindly. Get written answers:

**Identity:** legal name, short name, logo (PNG), primary + secondary colour hex, postal address, phone, email, website.

**People:** first admin name/email/phone; who is director vs finance vs secretary.

**School:** CBC or other; campuses; approximate enrolment; term dates; whether they need transport, hostel, POS in term 1.

**Hostname:** happy with `{slug}.PRODUCT.com` or they have `erp.school.ac.ke`.

**Payments:** existing M-Pesa paybill/till or later; bank name.

**Comms:** they provide a WhatsApp sender, or SMS only, or none at go-live.

**Devices:** Android / iOS mix for parents and teachers.

**Data:** empty start vs they will send an Excel of students/parents/fee structures (you will import; you will **not** copy RK data).

**Legal:** they accept that data lives on an isolated server you operate; they own the data; you need a DPA.

---

## 9. Help articles that must exist before go-live (the 20)

Write these as task articles (Who / Web steps / App steps / Warnings / Related), not module essays.

1. Sign in and change password  
2. Enter school code (app) and change school  
3. Set school branding (logo, colours)  
4. Create academic year and terms  
5. Add classes and streams  
6. Add a staff member and assign a role  
7. Add a new student  
8. Add a sibling / link a family  
9. Archive a student  
10. Restore an archived student  
11. Online admission: apply, review, enroll  
12. Build a fee structure and post charges  
13. Collect fees for one student  
14. Collect / allocate fees when a parent has siblings (share money)  
15. Send a communication to parents with a fee balance  
16. Mark class attendance  
17. Review attendance / absences  
18. Record a payment / issue a receipt  
19. Teacher: markbook / speed test (if in scope)  
20. Work vs Home mode (staff who are also parents)

PDF of these 20 is the “welcome manual.” Everything else is filled after they are live.

---

## 10. Day-1 runbook (the day the client is provisioned)

You:

1. Create VMs + DNS + TLS (section 6) if not already done.  
2. In control plane: create school → get `CODE`.  
3. On tenant: `provision-tenant.sh` with name, colours, admin email.  
4. Register `api_base_url`, set `active`.  
5. Log in as admin, force password, confirm branding.  
6. Walk them through articles 3–7 only (structure before learners).  
7. Import or type classes, then students/families.  
8. Fee structure for the current term **before** they collect money.  
9. Install preview app, enter `CODE`, verify admin + one teacher + one test parent.  
10. Enable M-Pesa only after a 1 KES test on **their** shortcode.  
11. Confirm last-night backup job created an object in S3.  
12. Hand them: URL, `CODE`, admin login, PDF of the 20 articles, WhatsApp to you for 14-day hypercare.

Them (first week): learners, parents, fee posting, attendance trial on one class, one official communication.

You (14-day hypercare): daily check worker logs, disk, queue, backup. Do not add features during hypercare unless they are blocked.

---

## 11. Definition of ready to take money from this client

All of these true:

- [ ] Tenant is not on the RK EC2  
- [ ] RK production still deploys only from `main` to the old host  
- [ ] Control plane resolve returns this school and not RK by accident  
- [ ] Admin must-change-password works  
- [ ] Branding shows on web login and in the app after code  
- [ ] Change school works  
- [ ] Queue worker running; `schedule:run` in cron  
- [ ] Nightly DB backup restored successfully once  
- [ ] No RK secrets in tenant `.env`  
- [ ] Preview app: admin, teacher, parent paths smoke-tested  
- [ ] Twenty help articles published  
- [ ] Written intake + DPA  
- [ ] M-Pesa off **or** tested on their till  

---

## 12. What you do vs what we implement

| You | This engineering track |
|---|---|
| Product name, domain, DNS, AWS VMs, TLS, Play/Apple accounts, client intake, DPA, M-Pesa application | `APP_ROLE`, operator UI, `tenant:bootstrap`, provision/deploy-tenant scripts, GitHub dispatch, combined app rebrand + school code + change school, help module + 20 articles, RK-safe deploy split |
| Hypercare, training the school admin | Fixes that come out of UAT |

When Wave 0 checkboxes are done (especially **product name** and **domain**), implementation of Wave 1 should start immediately on `epic/saas-launch`.
