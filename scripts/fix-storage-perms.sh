#!/usr/bin/env bash
# Fix Laravel storage/bootstrap permissions after deploy or manual artisan runs.
# Usage (on server): cd /var/www/erp && sudo bash scripts/fix-storage-perms.sh

set -euo pipefail

APP_ROOT="${APP_ROOT:-/var/www/erp}"
WEB_USER="${WEB_USER:-www-data}"

cd "$APP_ROOT"

if [ "$(id -u)" -ne 0 ]; then
  echo "Re-run with sudo: sudo bash scripts/fix-storage-perms.sh"
  exit 1
fi

mkdir -p storage/framework/{cache,sessions,views,testing} storage/logs bootstrap/cache

chown -R "${WEB_USER}:${WEB_USER}" storage bootstrap/cache
chmod -R ug+rwx storage bootstrap/cache

echo "Ownership set to ${WEB_USER} for storage/ and bootstrap/cache/"

if command -v sudo >/dev/null 2>&1 && id "${WEB_USER}" >/dev/null 2>&1; then
  sudo -u "${WEB_USER}" php artisan view:clear
  sudo -u "${WEB_USER}" php artisan view:cache
  echo "View cache rebuilt as ${WEB_USER}"
else
  php artisan view:clear
  php artisan view:cache
  chown -R "${WEB_USER}:${WEB_USER}" storage/framework/views
  echo "View cache rebuilt; re-owned storage/framework/views"
fi

echo "Done. Reload the page in your browser."
