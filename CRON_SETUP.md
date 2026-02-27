# Cron & Supervisor Setup

This document explains how to run Laravel scheduler and queue workers **automatically** on your server, without manually starting them in the terminal.

## What Runs Automatically

| Component | Purpose |
|-----------|---------|
| **Laravel Scheduler** (cron) | Runs `schedule:run` every minute → triggers scheduled communications, fee reminders, payment plan updates, backups |
| **Queue Worker** (Supervisor) | Runs `queue:work` continuously → processes queued jobs (WhatsApp bulk sends, etc.) |

## One-Time Setup (on the server)

SSH into your server and run:

```bash
cd /var/www/erp
sudo bash scripts/setup-cron-supervisor.sh
```

This will:

1. Add the Laravel scheduler to crontab (runs every minute)
2. Install Supervisor if not present
3. Install the queue worker config and start workers

## Verify

```bash
# Check cron (for www-data user)
sudo crontab -u www-data -l

# Check Supervisor
sudo supervisorctl status erp-worker:*
```

## After Deploy

The deploy script (`scripts/deploy-ec2.sh`) already restarts queue workers when Supervisor is configured. No manual steps needed.
