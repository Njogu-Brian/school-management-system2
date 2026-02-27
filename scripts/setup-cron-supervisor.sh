#!/bin/bash
# Setup cron + Supervisor for Laravel on EC2/Ubuntu
# Run on the server as root or with sudo
# Usage: sudo bash scripts/setup-cron-supervisor.sh
# Or: APP_USER=ubuntu sudo -E bash scripts/setup-cron-supervisor.sh

set -e
APP_DIR="${APP_DIR:-/var/www/erp}"
# User for cron (who owns app); worker runs as www-data for file permissions
CRON_USER="${CRON_USER:-www-data}"

echo "=========================================="
echo "  Laravel Cron + Supervisor Setup"
echo "  App dir: $APP_DIR"
echo "=========================================="

# 1. Laravel Scheduler (cron)
CRON_LINE="* * * * * cd $APP_DIR && php artisan schedule:run >> /dev/null 2>&1"
if crontab -u "$CRON_USER" -l 2>/dev/null | grep -q "schedule:run"; then
    echo "[1/3] Laravel scheduler already in crontab for $CRON_USER."
else
    (crontab -u "$CRON_USER" -l 2>/dev/null || true; echo "$CRON_LINE") | crontab -u "$CRON_USER" -
    echo "[1/3] Added Laravel scheduler to crontab for $CRON_USER."
fi

# 2. Install Supervisor if needed
if ! command -v supervisorctl &>/dev/null; then
    echo "[2/3] Installing Supervisor..."
    apt-get update -qq && apt-get install -y -qq supervisor
else
    echo "[2/3] Supervisor already installed."
fi

# 3. Queue worker config
SUP_CONF="/etc/supervisor/conf.d/erp-worker.conf"
if [ -f "$APP_DIR/config/supervisor-erp-worker.conf" ]; then
    echo "[3/3] Installing queue worker config..."
    sed "s|/var/www/erp|$APP_DIR|g" "$APP_DIR/config/supervisor-erp-worker.conf" > "$SUP_CONF"
    supervisorctl reread 2>/dev/null || true
    supervisorctl update 2>/dev/null || true
    supervisorctl start erp-worker:* 2>/dev/null || true
    echo "Queue worker config installed at $SUP_CONF"
else
    echo "[3/3] config/supervisor-erp-worker.conf not found. Skipping."
fi

echo ""
echo "=========================================="
echo "  Setup complete"
echo "=========================================="
echo ""
echo "Cron runs Laravel scheduler every minute."
echo "Supervisor keeps queue workers running (process jobs)."
echo "Check status: supervisorctl status erp-worker:*"
echo ""
