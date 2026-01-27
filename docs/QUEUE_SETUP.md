# Queue setup – SMS, email, WhatsApp without a worker

**This app uses Option A (sync) by default:** receipts and notifications (SMS, email, WhatsApp) run in the same request when payments are created. No queue worker or cron needed.

Receipts and notifications are triggered when payments are created. They run **without a queue worker** by default (sync), or in the background with Supervisor if you set `QUEUE_CONNECTION=database` and run a worker.

---

## Option A: Run in the same request (no queue, no worker) — **default**

SMS, email, and WhatsApp run **as soon as** the action happens, in the same HTTP request. No queue worker, no cron.

**Default:** The app uses `sync` when `QUEUE_CONNECTION` is not set. No change needed. To be explicit, set in `.env`: `QUEUE_CONNECTION=sync` then run `php artisan config:cache`.

**What happens:** When a payment is created (e.g. from a bank statement), the app runs receipt generation and `sendPaymentNotifications()` (SMS, email, WhatsApp) **inline**. The user waits a few extra seconds for that request, but nothing runs in the background and no worker is needed.

**Pros:** No queue, no worker, no cron. Notifications run automatically when the action happens.  
**Cons:** The request is blocked for a few seconds (typically 2–10 s per payment). Bulk operations can be slow or hit timeouts.

Use this when you want notifications to “just run” when something happens and you’re okay with the request taking a bit longer.

---

## Option B: Run in the background with Supervisor

A worker runs 24/7 and processes jobs within **seconds** of when they’re queued. The HTTP request returns quickly; receipts and notifications run in the background.

### 1. Use the database queue

In `.env` set:

```env
QUEUE_CONNECTION=database
```

### 2. Install and configure Supervisor

Example config (e.g. `/etc/supervisord.d/laravel-worker.ini`):

```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /home2/royalce1/laravel-app/school-management-system/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=royalce1
numprocs=1
redirect_stderr=true
stdout_logfile=/home2/royalce1/laravel-app/school-management-system/storage/logs/worker.log
stopwaitsecs=60
```

Replace the paths and `user` with your app path and Linux user.

### 3. Start the worker

```bash
sudo supervisorctl reread && sudo supervisorctl update && sudo supervisorctl start laravel-worker:*
```

**Pros:** Requests are fast; notifications run in the background within seconds.  
**Cons:** Requires Supervisor (or another way to keep `queue:work` running).

---

## Why not “run the queue every minute” in the scheduler?

Running `queue:work --stop-when-empty` every minute would:

- Start a new PHP process **every minute** even when the queue is empty.
- Use CPU and memory for no benefit when there are no jobs.
- At most process jobs once per minute, so they are not “immediate.”

So that approach is wasteful and not recommended. Use either **sync** (no worker) or **Supervisor** (one long‑running worker).

---

## Summary

| Setup        | SMS/email/WhatsApp run…           | Needs              |
|-------------|------------------------------------|--------------------|
| **sync** (default) | In the same request, right away | Nothing (or `QUEUE_CONNECTION=sync`) |
| **Supervisor** | In background, within seconds  | `QUEUE_CONNECTION=database` + Supervisor |

- **No worker, no cron (Option A):** default is `sync`. Notifications run automatically when the action happens. No `.env` change needed unless you overrode it.
- **Background (Option B):** set `QUEUE_CONNECTION=database` and run a queue worker via Supervisor.
