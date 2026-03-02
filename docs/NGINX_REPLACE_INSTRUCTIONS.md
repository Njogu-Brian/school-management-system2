# How to Replace the Nginx Default Config (and Fix 413 Upload Error)

## What you're doing
Replacing `/etc/nginx/sites-available/default` on the server with a version that includes `client_max_body_size 12M;` so background/logo uploads work.

---

## Step 1: Upload the new config to the server

From your **local machine** (PowerShell, in the project folder):

```powershell
scp -i erp-key.pem docs/nginx-default.conf ubuntu@YOUR_SERVER_IP:/tmp/nginx-default-new.conf
```

Replace `YOUR_SERVER_IP` with your EC2 instance IP (e.g. `13.245.211.78`).

---

## Step 2: SSH into the server

```powershell
ssh -i erp-key.pem ubuntu@YOUR_SERVER_IP
```

---

## Step 3: Backup the current config

```bash
sudo cp /etc/nginx/sites-available/default /etc/nginx/sites-available/default.backup.$(date +%Y%m%d)
```

---

## Step 4: Replace the config

```bash
sudo cp /tmp/nginx-default-new.conf /etc/nginx/sites-available/default
```

---

## Step 5: Test and reload Nginx

```bash
sudo nginx -t && sudo systemctl reload nginx
```

If you see `syntax is ok` and `test is successful`, the replacement worked.

---

## Step 6: Remove the temp file (optional)

```bash
rm /tmp/nginx-default-new.conf
```

---

## Rollback (if something goes wrong)

```bash
sudo cp /etc/nginx/sites-available/default.backup.YYYYMMDD /etc/nginx/sites-available/default
sudo nginx -t && sudo systemctl reload nginx
```

Replace `YYYYMMDD` with the backup date (e.g. `20260302`).
