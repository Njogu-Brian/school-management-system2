# Automatic deploy from GitHub (no manual SSH)

When you push to `main`, GitHub Actions SSHs into your server and runs `scripts/deploy-production.sh` (Laravel + Next.js website).

## One-time setup (about 15 minutes)

### 1. Server: allow `git pull` without a password

SSH in once and check the remote:

```bash
cd /var/www/erp
git remote -v
```

**Option A — Deploy key (recommended)**

On the server:

```bash
ssh-keygen -t ed25519 -C "erp-deploy" -f ~/.ssh/erp_deploy -N ""
cat ~/.ssh/erp_deploy.pub
```

In GitHub: **Repo → Settings → Deploy keys → Add deploy key**  
Paste the public key, enable **read-only**.

Then on the server:

```bash
eval "$(ssh-agent -s)"
ssh-add ~/.ssh/erp_deploy
# Persist for future pulls (ubuntu user):
echo 'Host github.com
  IdentityFile ~/.ssh/erp_deploy
  IdentitiesOnly yes' >> ~/.ssh/config
chmod 600 ~/.ssh/config

# Point origin at SSH (if it uses HTTPS today):
git remote set-url origin git@github.com:Njogu-Brian/school-management-system2.git
git pull origin main   # should work without typing a password
```

**Option B — HTTPS + Personal Access Token**

Create a GitHub PAT (repo read scope), then:

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
| `DEPLOY_SSH_KEY` | Full contents of your `erp-key.pem` (private key) |
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
