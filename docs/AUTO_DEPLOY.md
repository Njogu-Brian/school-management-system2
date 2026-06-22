# Automatic deploy from GitHub (no manual SSH)

When you push to `main`, GitHub Actions SSHs into your server and runs `scripts/deploy-production.sh` (Laravel + Next.js website).

## Quick start (if `git pull` already works on the server)

You **do not** need to generate SSH keys on your Windows PC. Your server already pulls from GitHub over HTTPS.

1. On the server once (SSH in):
   ```bash
   cd /var/www/erp
   chmod +x scripts/deploy-production.sh scripts/deploy-ec2.sh scripts/deploy-public-website.sh
   ```
2. On GitHub: **Repo → Settings → Secrets and variables → Actions** — add:
   - `DEPLOY_HOST` = `13.245.211.78` (or `erp.royalkingsschools.sc.ke`)
   - `DEPLOY_USER` = `ubuntu`
   - `DEPLOY_SSH_KEY` = entire contents of your `erp-key.pem` file (the private key you use for `ssh school-erp`)
3. Push to `main` or run **Actions → Deploy Production → Run workflow**.

> **Windows note:** Do not run `ssh-keygen` in PowerShell for this unless you know you need it. The deploy key (if used) belongs **on the server**, not your laptop. PowerShell also breaks `-N ""` — use SSH into the server instead.

## One-time setup (full detail)

### 1. Server: allow `git pull` without a password

SSH in once and check the remote:

```bash
cd /var/www/erp
git remote -v
```

If `git pull origin main` already works (as on your EC2 box with HTTPS), **skip the deploy key** and go to step 2.

**Option A — Deploy key (only if `git fetch` fails without a password)**

Run these **on the server** after `ssh school-erp`, not on Windows:

```bash
ssh-keygen -t ed25519 -C "erp-deploy" -f ~/.ssh/erp_deploy -N ''
cat ~/.ssh/erp_deploy.pub
```

In GitHub: **Repo → Settings → Deploy keys → Add deploy key**  
Paste the public key, enable **read-only**.

Then on the server:

```bash
eval "$(ssh-agent -s)"
ssh-add ~/.ssh/erp_deploy
echo 'Host github.com
  IdentityFile ~/.ssh/erp_deploy
  IdentitiesOnly yes' >> ~/.ssh/config
chmod 600 ~/.ssh/config
git remote set-url origin git@github.com:Njogu-Brian/school-management-system2.git
git pull origin main
```

**Option B — HTTPS + Personal Access Token** (if fetch starts asking for credentials)

Create a GitHub PAT (repo read scope), then on the server:

```bash
git remote set-url origin https://<TOKEN>@github.com/Njogu-Brian/school-management-system2.git
```

### 2. Server: deploy script executable

```bash
chmod +x /var/www/erp/scripts/deploy-production.sh
chmod +x /var/www/erp/scripts/deploy-ec2.sh
chmod +x /var/www/erp/scripts/deploy-public-website.sh
```

Test manually once:

```bash
cd /var/www/erp && bash scripts/deploy-production.sh
```

### 3. GitHub: add Actions secrets

**Repo → Settings → Secrets and variables → Actions → New repository secret**

| Secret | Value |
|--------|--------|
| `DEPLOY_HOST` | `erp.royalkingsschools.sc.ke` or server IP |
| `DEPLOY_USER` | SSH user (`ubuntu` or whoever owns `/var/www/erp`) |
| `DEPLOY_SSH_KEY` | Full contents of your `erp-key.pem` (private key used for `ssh school-erp`) |

**Important when pasting the key on Windows:** copy the entire PEM including `BEGIN`/`END` lines. If deploy fails with "ssh: handshake failed", delete the secret and re-paste — GitHub sometimes stores Windows line endings (CRLF) that break SSH. Re-copy from:

```powershell
Get-Content "$env:USERPROFILE\.ssh\erp-key.pem" -Raw
```

Secret **names** must be exactly `DEPLOY_HOST`, `DEPLOY_USER`, `DEPLOY_SSH_KEY` — not `UBUNTU` or the IP address.
| `DEPLOY_PORT` | `22` (optional; omit if default) |

### 4. Push the workflow

After `deploy-production.yml` is on `main`, every push to `main` triggers deploy.

Manual deploy anytime: **Actions → Deploy Production → Run workflow**.

## What runs on each deploy

1. `git fetch` + `git reset --hard origin/main`
2. `scripts/deploy-ec2.sh` — composer, migrations, caches, queue restart
3. `scripts/deploy-public-website.sh` — Next.js build + PM2 restart

## Safety tips

- **Branch:** Only `main` auto-deploys. Use feature branches + PRs for daily work.
- **Migrations:** `migrate --force` runs automatically; review migrations before merging.
- **Downtime:** Usually under 1–2 minutes during cache rebuild / Next.js build.
- **Rollback:** SSH once and run `git reset --hard <previous-commit>` then `bash scripts/deploy-production.sh`.

## Troubleshooting

| Problem | Fix |
|---------|-----|
| `Missing secret DEPLOY_*` | Create all 3 secrets with exact names (not `UBUNTU`) |
| Deploy fails in ~3 seconds | Re-paste `DEPLOY_SSH_KEY` without CRLF; confirm `DEPLOY_HOST` = `13.245.211.78` |
| `ssh: handshake failed` | Delete and re-add `DEPLOY_SSH_KEY`; key must include BEGIN/END lines |
| Deploy fails on `git pull` | Fix deploy key / PAT on server (step 1) |
| Permission denied (publickey) | Check `DEPLOY_SSH_KEY` secret matches `erp-key.pem` |
| Website still old after deploy | PM2: `pm2 logs royal-kings-website`; re-run `deploy-public-website.sh` |
| CMS 404 | Run `bash scripts/fix-nginx-website-cms.sh` on server |

## Alternative: cron (not recommended)

```bash
# Every 5 minutes — simple but no build logs, runs even when nothing changed
*/5 * * * * cd /var/www/erp && git pull -q && bash scripts/deploy-production.sh >> /var/log/erp-deploy.log 2>&1
```

GitHub Actions is preferred: deploys only when you push, with a clear log in the Actions tab.
