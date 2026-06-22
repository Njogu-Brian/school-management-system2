#!/usr/bin/env bash
# Full production deploy: Laravel ERP + public Next.js website.
# Run on the server after git pull, or via GitHub Actions SSH.

set -euo pipefail

APP_ROOT="${APP_ROOT:-/var/www/erp}"
cd "$APP_ROOT"

echo "=========================================="
echo "  Royal Kings ERP — Production Deploy"
echo "=========================================="

bash "$APP_ROOT/scripts/deploy-ec2.sh"

if [[ -f "$APP_ROOT/scripts/deploy-public-website.sh" ]]; then
  echo ""
  echo "==> Deploying public website (Next.js)..."
  bash "$APP_ROOT/scripts/deploy-public-website.sh"
fi

echo ""
echo "Production deploy finished at $(date -u +%Y-%m-%dT%H:%M:%SZ)"
