#!/usr/bin/env bash
# Archive closed MySQL binary logs named binlog.NNNNNN.
#
# Recovery point: the last closed log whose byte size was verified in S3.
# The cron interval is not that point. The active log is never uploaded, so a
# crash can lose commits made after the last verified closed log. A failed
# upload is a failure and is not recorded in the ledger.
#
# This script does not PURGE BINARY LOGS and does not delete hourly Laravel dumps.

set -euo pipefail

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
STAGING="$BACKUP_ROOT/binlogs/incoming"
LEDGER="$BACKUP_ROOT/binlogs/archived.ledger"
COVERAGE="$BACKUP_ROOT/binlogs/coverage_starts_at"
mkdir -p "$STAGING"
touch "$LEDGER"

exec 9>"$BACKUP_ROOT/.binlog.lock"
if ! flock -n 9; then
  echo "$(date -Is) Binary log archive is already running or the lock is held." >&2
  exit 1
fi

CNF=$(mktemp)
trap 'rm -f "$CNF"' EXIT
dr_write_cnf "$CNF" "$ENV_FILE"

LOG_BIN=$(dr_mysql "$CNF" -e "SHOW VARIABLES LIKE 'log_bin';" | awk '{print $2}')
if [[ "$LOG_BIN" != "ON" ]]; then
  echo "$(date -Is) Binary logging is off. No log was archived." >&2
  exit 1
fi

dr_mysql "$CNF" -e "FLUSH BINARY LOGS;"

mapfile -t ROWS < <(dr_mysql "$CNF" -e "SHOW BINARY LOGS;")
if [[ ${#ROWS[@]} -lt 2 ]]; then
  echo "$(date -Is) No closed binary log is available." >&2
  exit 1
fi
unset 'ROWS[-1]'

declare -A SIZES=()
CLOSED=()
for row in "${ROWS[@]}"; do
  name=$(printf '%s\n' "$row" | awk '{print $1}')
  size=$(printf '%s\n' "$row" | awk '{print $2}')
  if [[ ! "$name" =~ ^binlog\.[0-9]+$ || ! "$size" =~ ^[0-9]+$ ]]; then
    echo "Unexpected SHOW BINARY LOGS row: $row" >&2
    exit 1
  fi
  CLOSED+=("$name")
  SIZES["$name"]="$size"
done

if [[ ! -s "$COVERAGE" ]]; then
  printf '%s\n' "${CLOSED[0]}" > "$COVERAGE"
  echo "$(date -Is) Archive coverage starts at ${CLOSED[0]}. Earlier logs are not on this server and are not recoverable from this archive."
fi
COVERAGE_START=$(tr -d '\r' < "$COVERAGE")

VERIFIED=$(awk '{print $1}' "$LEDGER" | paste -sd, -)
CLOSED_CSV=$(IFS=,; echo "${CLOSED[*]}")
gap_args=(find-gaps --coverage "$COVERAGE_START" --closed "$CLOSED_CSV")
if [[ -n "$VERIFIED" ]]; then
  gap_args+=(--verified "$VERIFIED")
fi
if ! "$PHP_BIN" "$DR_PHP" "${gap_args[@]}"; then
  exit 1
fi

for log_name in "${CLOSED[@]}"; do
  if awk '{print $1}' "$LEDGER" | grep -qxF "$log_name"; then
    continue
  fi
  work=$(mktemp -d)
  if ! mysqlbinlog \
    --defaults-extra-file="$CNF" \
    --read-from-remote-server \
    --raw \
    --host="$(dr_env "$ENV_FILE" DB_HOST)" \
    --port="$(dr_env "$ENV_FILE" DB_PORT)" \
    --result-file="${work}/" \
    "$log_name"; then
    rm -rf "$work"
    echo "mysqlbinlog failed for $log_name. It was not recorded as archived." >&2
    exit 1
  fi
  if ! "$PHP_BIN" "$DR_PHP" assert-binlog --file "$work/$log_name" --size "${SIZES[$log_name]}"; then
    rm -rf "$work"
    exit 1
  fi
  if ! "$PHP_BIN" artisan backup:publish "$work/$log_name" \
    --tier=binlog \
    --binlog-name="$log_name" \
    --expected-size="${SIZES[$log_name]}" \
    --database="$DB_DATABASE" \
    --no-interaction; then
    rm -rf "$work"
    echo "Upload failed for $log_name. It was not recorded as archived." >&2
    exit 1
  fi
  if ! "$PHP_BIN" "$DR_PHP" record-verified \
    --ledger "$LEDGER" \
    --name "$log_name" \
    --size "${SIZES[$log_name]}" \
    --remote-size "${SIZES[$log_name]}"; then
    rm -rf "$work"
    exit 1
  fi
  mv "$work/$log_name" "$STAGING/$log_name"
  rm -rf "$work"
  echo "$(date -Is) Verified closed binary log $log_name (${SIZES[$log_name]} bytes)"
done

while IFS= read -r old_path; do
  base=$(basename "$old_path")
  key="backups/mysql/binlogs/$base"
  if remote=$("$PHP_BIN" artisan backup:object-size "$key" 2>/dev/null); then
    if "$PHP_BIN" "$DR_PHP" can-delete --ledger "$LEDGER" --name "$base" --remote-size "$remote"; then
      rm -f "$old_path" "$old_path.sha256" "$old_path.manifest.json"
    fi
  fi
done < <(find "$STAGING" -type f -name 'binlog.*' ! -name '*.sha256' ! -name '*.manifest.json' -mmin +2880)
