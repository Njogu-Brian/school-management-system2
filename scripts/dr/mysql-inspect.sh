#!/usr/bin/env bash
# Print MySQL facts needed before any recovery change.
# Read-only: it does not change MySQL, cron, or S3.
# Run on the server: bash scripts/dr/mysql-inspect.sh

set -euo pipefail

SCRIPT_DIR=$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)
APP_DIR=$(cd "$SCRIPT_DIR/../.." && pwd)
DR_PHP="$SCRIPT_DIR/dr.php"
# shellcheck source=lib.sh
source "$SCRIPT_DIR/lib.sh"

cd "$APP_DIR"
ENV_FILE="${DR_ENV_FILE:-$APP_DIR/.env}"
DB_DATABASE=$(dr_env "$ENV_FILE" DB_DATABASE)

CNF=$(mktemp)
trap 'rm -f "$CNF"' EXIT
dr_write_cnf "$CNF" "$ENV_FILE"

echo "=== OS ==="
if [[ -f /etc/os-release ]]; then
  # shellcheck disable=SC1091
  . /etc/os-release
  echo "${PRETTY_NAME:-unknown}"
else
  uname -a
fi

echo
echo "=== mysql client ==="
mysql --version

echo
echo "=== SELECT VERSION() ==="
dr_mysql "$CNF" -e "SELECT VERSION();"

echo
echo "=== replication and durability variables ==="
dr_mysql "$CNF" -e "SHOW VARIABLES WHERE Variable_name IN ('log_bin','binlog_format','server_id','sync_binlog','innodb_flush_log_at_trx_commit','binlog_expire_logs_seconds','max_binlog_size');"

echo
echo "=== binary logs ==="
if dr_mysql "$CNF" -e "SHOW VARIABLES LIKE 'log_bin';" | awk '{print $2}' | grep -qx ON; then
  dr_mysql "$CNF" -e "SHOW BINARY LOGS;" || true
else
  echo "Binary logging is OFF. SHOW BINARY LOGS was skipped."
fi

echo
echo "=== non-InnoDB tables in ${DB_DATABASE} ==="
dr_mysql "$CNF" -e "SELECT table_name, engine FROM information_schema.tables WHERE table_schema='${DB_DATABASE}' AND table_type='BASE TABLE' AND engine IS NOT NULL AND engine <> 'InnoDB' ORDER BY table_name;"

echo
echo "Disk free on the MySQL data directory:"
DATA_DIR=$(dr_mysql "$CNF" -e "SELECT @@datadir;")
df -h "$DATA_DIR" || true

echo
echo "No configuration was changed."
