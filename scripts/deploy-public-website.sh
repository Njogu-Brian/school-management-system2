#!/usr/bin/env bash
# Deploy Royal Kings public Next.js website on the ERP server.
# Serves the marketing site at https://erp.royalkingsschools.sc.ke/website
# API remains at /api/website/* (Laravel).

set -euo pipefail

APP_ROOT="${APP_ROOT:-/var/www/erp}"
WEB_ROOT="$APP_ROOT/website"
API_URL="${API_URL:-https://erp.royalkingsschools.sc.ke/api}"
ERP_LOGIN_URL="${ERP_LOGIN_URL:-https://erp.royalkingsschools.sc.ke/login}"
BASE_PATH="${BASE_PATH:-/website}"
PORT="${WEBSITE_PORT:-3001}"
PM2_NAME="${PM2_NAME:-royal-kings-website}"

echo "==> Seeding CMS content (homepage + settings) if missing..."
cd "$APP_ROOT"
php artisan db:seed --class=WebsiteCmsSeeder --force
php artisan db:seed --class=WebsiteCmsPageSectionsSeeder --force
php artisan db:seed --class=WebsiteSprints2130Seeder --force

echo "==> Building Next.js website..."
cd "$WEB_ROOT"
cat > .env.production <<EOF
WEBSITE_BASE_PATH=$BASE_PATH
NEXT_PUBLIC_API_URL=$API_URL
NEXT_PUBLIC_ERP_LOGIN_URL=$ERP_LOGIN_URL
NEXT_PUBLIC_BASE_PATH=$BASE_PATH
EOF
cp .env.production .env.production.local

npm ci
WEBSITE_BASE_PATH="$BASE_PATH" NEXT_PUBLIC_BASE_PATH="$BASE_PATH" npm run build

echo "==> Starting with PM2 on port $PORT..."
if command -v pm2 >/dev/null 2>&1; then
  pm2 delete "$PM2_NAME" 2>/dev/null || true
  cd "$WEB_ROOT"
  WEBSITE_BASE_PATH="$BASE_PATH" NEXT_PUBLIC_API_URL="$API_URL" NEXT_PUBLIC_ERP_LOGIN_URL="$ERP_LOGIN_URL" \
    pm2 start ecosystem.config.cjs --update-env
  pm2 save
else
  echo "PM2 not installed. Install: sudo npm i -g pm2"
  echo "Then run: PORT=$PORT npm start -- -p $PORT"
  exit 1
fi

echo "==> Done. Ensure nginx proxies $BASE_PATH to http://127.0.0.1:$PORT"
echo "    Test API: curl -s $API_URL/website/homepage | head"
echo "    Test site: https://erp.royalkingsschools.sc.ke$BASE_PATH"
