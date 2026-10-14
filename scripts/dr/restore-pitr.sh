#!/usr/bin/env bash
# Restore a dump into an empty database that is not production.
# The dump is rejected before any SQL runs if it contains USE, CREATE DATABASE,
# or DROP DATABASE. Binary log replay is rewritten onto the target database and
# rejected if it still selects any other database.
#
#   bash scripts/dr/restore-pitr.sh \
#     --dump /var/backups/erp/daily/school_daily.sql.gz \
#     --target-database erp_restore \
#     --confirm-restore

set -euo pipefail

SCRIPT_DIR=$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)
APP_DIR=$(cd "$SCRIPT_DIR/../.." && pwd)
DR_PHP="$SCRIPT_DIR/dr.php"
# shellcheck source=lib.sh
source "$SCRIPT_DIR/lib.sh"

DUMP=""
TARGET=""
CONFIRM=0
STOP_POSITION=""
STOP_DATETIME=""
BINLOGS=()
ENV_FILE="${ENV_FILE:-$APP_DIR/.env}"
REPLAY=""
CNF=""

while [[ $# -gt 0 ]]; do
  case "$1" in
    --dump) DUMP="$2"; shift 2 ;;
    --target-database) TARGET="$2"; shift 2 ;;
    --confirm-restore) CONFIRM=1; shift ;;
    --stop-position) STOP_POSITION="$2"; shift 2 ;;
    --stop-datetime) STOP_DATETIME="$2"; shift 2 ;;
    --binlog) BINLOGS+=("$2"); shift 2 ;;
    --env) ENV_FILE="$2"; shift 2 ;;
    *)
      echo "Unknown argument: $1" >&2
      exit 1
      ;;
  esac
done

if [[ "$CONFIRM" -ne 1 ]]; then
  echo "Refusing to restore without --confirm-restore." >&2
  exit 1
fi
if [[ -z "$DUMP" || ! -f "$DUMP" ]]; then
  echo "Pass an existing --dump file." >&2
  exit 1
fi

DB_DATABASE=$(dr_env "$ENV_FILE" DB_DATABASE)
PHP_BIN="${PHP_BIN:-php}"
trap 'if [[ -n "$CNF" ]]; then rm -f "$CNF"; fi; if [[ -n "$REPLAY" ]]; then rm -f "$REPLAY"; fi' EXIT
"$PHP_BIN" "$DR_PHP" assert-target --target "$TARGET" --production "$DB_DATABASE"

gzip -t "$DUMP"
"$PHP_BIN" "$DR_PHP" assert-dump --file "$DUMP"

REPLAY=""
if [[ ${#BINLOGS[@]} -gt 0 ]]; then
  if [[ -z "$STOP_POSITION" && -z "$STOP_DATETIME" ]]; then
    echo "Pass --stop-position or --stop-datetime. Replaying to the current end can reapply the damage." >&2
    exit 1
  fi
  coord=$("$PHP_BIN" "$DR_PHP" coordinates --file "$DUMP")
  START_FILE=${coord%% *}
  START_POS=${coord##* }
  first_log=$(basename "${BINLOGS[0]}")
  if [[ "$first_log" != "$START_FILE" ]]; then
    echo "The first --binlog file must be ${START_FILE}, the position recorded in this dump." >&2
    exit 1
  fi
  REPLAY=$(mktemp)
  replay_cmd=(mysqlbinlog --rewrite-db="${DB_DATABASE}->${TARGET}" --start-position="$START_POS")
  if [[ -n "$STOP_POSITION" ]]; then
    replay_cmd+=(--stop-position="$STOP_POSITION")
  fi
  if [[ -n "$STOP_DATETIME" ]]; then
    replay_cmd+=(--stop-datetime="$STOP_DATETIME")
  fi
  replay_cmd+=("${BINLOGS[@]}")
  "${replay_cmd[@]}" > "$REPLAY"
  "$PHP_BIN" "$DR_PHP" assert-replay --file "$REPLAY" --target "$TARGET"
fi

CNF=$(mktemp)
dr_write_cnf "$CNF" "$ENV_FILE"

existing=$(dr_mysql "$CNF" -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='${TARGET}';")
if [[ "${existing:-0}" != "0" ]]; then
  echo "Target database ${TARGET} already has tables. It was not changed." >&2
  exit 1
fi

echo "Creating empty database ${TARGET}"
dr_mysql "$CNF" -e "CREATE DATABASE \`${TARGET}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

echo "Loading dump into ${TARGET}"
gzip -dc "$DUMP" | mysql --defaults-extra-file="$CNF" --batch "$TARGET"

if [[ -n "$REPLAY" ]]; then
  echo "Replaying binary logs into ${TARGET} only"
  mysql --defaults-extra-file="$CNF" --batch "$TARGET" < "$REPLAY"
fi

echo "Restore into ${TARGET} finished. Compare counts and financial totals before using this database."
