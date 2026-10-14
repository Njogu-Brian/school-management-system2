#!/usr/bin/env bash
# Consistent InnoDB dump of one database, without CREATE DATABASE or USE.
# Binary log coordinates are required when binary logging is on.
# This script does not delete the hourly Laravel dumps in storage/app/backups.

set -euo pipefail

TIER="${1:-frequent}"
case "$TIER" in
  frequent|daily|weekly|monthly) ;;
  *)
    echo "Tier must be frequent, daily, weekly, or monthly." >&2
    exit 1
    ;;
esac

SCRIPT_DIR=$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)
APP_DIR=$(cd "$SCRIPT_DIR/../.." && pwd)
DR_PHP="$SCRIPT_DIR/dr.php"
# shellcheck source=lib.sh
source "$SCRIPT_DIR/lib.sh"

cd "$APP_DIR"
ENV_FILE="${DR_ENV_FILE:-$APP_DIR/.env}"
DB_DATABASE=$(dr_env "$ENV_FILE" DB_DATABASE)
PHP_BIN="${PHP_BIN:-php}"
BACKUP_ROOT="${BACKUP_ROOT:-/var/backups/erp}"
DEST_DIR="$BACKUP_ROOT/$TIER"
mkdir -p "$DEST_DIR"

exec 9>"$BACKUP_ROOT/.dump.lock"
if ! flock -n 9; then
  echo "$(date -Is) Another dump is still running." >&2
  exit 1
fi

avail_kb=$(df -Pk "$BACKUP_ROOT" | awk 'NR==2 {print $4}')
if [[ -n "${avail_kb}" && "${avail_kb}" -lt 512000 ]]; then
  echo "Less than 500 MiB free on ${BACKUP_ROOT}. Refusing to dump." >&2
  exit 1
fi

CNF=$(mktemp)
HELP=$(mktemp)
trap 'rm -f "$CNF" "$HELP"' EXIT
dr_write_cnf "$CNF" "$ENV_FILE"

STAMP=$(date -u +%Y%m%dT%H%M%SZ)
OUTFILE="$DEST_DIR/${DB_DATABASE}_${TIER}_${STAMP}.sql.gz"
MYSQL_VERSION=$(dr_mysql "$CNF" -e "SELECT VERSION();" | tr -d '\r')
LOG_BIN=$(dr_mysql "$CNF" -e "SHOW VARIABLES LIKE 'log_bin';" | awk '{print $2}')
LOG_BIN_FLAG=0
if [[ "$LOG_BIN" == "ON" ]]; then
  LOG_BIN_FLAG=1
fi

mysqldump --help >"$HELP" 2>&1 || true
mapfile -t DUMP_ARGS < <("$PHP_BIN" "$DR_PHP" dump-args --database "$DB_DATABASE" --defaults "$CNF" --log-bin "$LOG_BIN_FLAG" --help-file "$HELP")

echo "$(date -Is) Starting ${TIER} dump of ${DB_DATABASE}"
rm -f "$OUTFILE"
mysqldump "${DUMP_ARGS[@]}" | gzip -c > "$OUTFILE"
gzip -t "$OUTFILE"
"$PHP_BIN" "$DR_PHP" assert-dump --file "$OUTFILE"
if [[ "$LOG_BIN_FLAG" == "1" ]]; then
  "$PHP_BIN" "$DR_PHP" coordinates --file "$OUTFILE" >/dev/null
fi
echo "$(date -Is) gzip validation passed: $OUTFILE"

"$PHP_BIN" artisan backup:publish "$OUTFILE" \
  --tier="$TIER" \
  --mysql-version="$MYSQL_VERSION" \
  --database="$DB_DATABASE" \
  --no-interaction

case "$TIER" in
  frequent) retain_minutes=2880 ;;
  daily) retain_minutes=10080 ;;
  weekly) retain_minutes=20160 ;;
  monthly) retain_minutes=43200 ;;
esac

find "$DEST_DIR" -type f \( -name '*.sql.gz' -o -name '*.sql.gz.sha256' -o -name '*.sql.gz.manifest.json' \) -mmin "+${retain_minutes}" -delete

echo "$(date -Is) Finished ${TIER} dump"
