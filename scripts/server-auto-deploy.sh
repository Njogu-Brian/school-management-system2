#!/usr/bin/env bash
# Poll GitHub for new commits on main and deploy when changed.
# Use when GitHub Actions billing blocks workflows.
#
# Install once on server:
#   sudo cp scripts/server-auto-deploy.sh /usr/local/bin/erp-auto-deploy
#   sudo chmod +x /usr/local/bin/erp-auto-deploy
#   sudo crontab -e
#   */5 * * * * /usr/local/bin/erp-auto-deploy >> /var/log/erp-auto-deploy.log 2>&1

set -euo pipefail

APP_ROOT="${APP_ROOT:-/var/www/erp}"
BRANCH="${BRANCH:-main}"
LOG_TAG="[erp-auto-deploy $(date -u +%Y-%m-%dT%H:%M:%SZ)]"

cd "$APP_ROOT"

LOCAL="$(git rev-parse HEAD)"
REMOTE="$(git ls-remote origin "refs/heads/$BRANCH" | awk '{print $1}')"

if [[ -z "$REMOTE" ]]; then
  echo "$LOG_TAG ERROR: could not read origin/$BRANCH"
  exit 1
fi

if [[ "$LOCAL" == "$REMOTE" ]]; then
  echo "$LOG_TAG up to date ($LOCAL)"
  exit 0
fi

echo "$LOG_TAG deploying $LOCAL -> $REMOTE"
git fetch origin "$BRANCH"
git reset --hard "origin/$BRANCH"
bash "$APP_ROOT/scripts/deploy-production.sh"
echo "$LOG_TAG done"
