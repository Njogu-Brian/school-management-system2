#!/usr/bin/env bash
# Fix nginx so /website-cms routes to Laravel (not Next.js).
# "location /website" incorrectly matches /website-cms as a prefix.

set -euo pipefail

NGINX_SITE="${NGINX_SITE:-/etc/nginx/sites-available/erp}"

if [[ ! -f "$NGINX_SITE" ]]; then
  echo "Nginx site config not found: $NGINX_SITE"
  exit 1
fi

if grep -q 'location /website/' "$NGINX_SITE" && grep -q 'location = /website' "$NGINX_SITE"; then
  echo "Nginx already uses safe /website locations."
  exit 0
fi

SNIPPET="$(dirname "$0")/../docs/nginx-website-snippet.conf"
if [[ ! -f "$SNIPPET" ]]; then
  echo "Missing snippet: $SNIPPET"
  exit 1
fi

BACKUP="${NGINX_SITE}.bak.$(date +%Y%m%d%H%M%S)"
sudo cp "$NGINX_SITE" "$BACKUP"
echo "Backed up to $BACKUP"

# Remove old single-block proxy (if present)
sudo sed -i '/^location \/website {/,/^}/d' "$NGINX_SITE"

# Insert new blocks before "location / {"
sudo sed -i "/^[[:space:]]*location \/ {/i\\
$(sed 's/$/\\n/' "$SNIPPET" | tr -d '\n' | sed 's/\\n$//')
" "$NGINX_SITE"

sudo nginx -t
sudo systemctl reload nginx
echo "Nginx reloaded. /website-cms should now hit Laravel."
