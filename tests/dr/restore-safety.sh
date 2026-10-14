#!/usr/bin/env bash
# Exercise restore-pitr.sh with a fake mysql client. No server is contacted.
# A dump containing USE must exit before that client is executed.

set -euo pipefail

ROOT=${1:?repository root}
REAL_PHP=${2:-php}
export REAL_PHP

WORK=$(mktemp -d)
cleanup() {
  rm -rf "$WORK"
}
trap cleanup EXIT

if command -v cygpath >/dev/null 2>&1; then
  cat > "$WORK/phpw" <<'EOF'
#!/usr/bin/env bash
args=()
for arg in "$@"; do
  if [[ "$arg" == /* ]]; then
    arg=$(cygpath -w "$arg")
  fi
  args+=("$arg")
done
exec "$REAL_PHP" "${args[@]}"
EOF
  chmod 700 "$WORK/phpw"
  PHP_BIN="$WORK/phpw"
else
  PHP_BIN=$REAL_PHP
fi
export PHP_BIN

PASSWORD='sec#ret"q\z'
cat > "$WORK/.env" <<EOF
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=school_management
DB_USERNAME=backup_user
DB_PASSWORD="sec#ret\\"q\\\\z"
EOF

mkdir -p "$WORK/bin"
cat > "$WORK/bin/mysql" <<'EOF'
#!/bin/sh
printf '%s\n' "$*" >> "$MYSQL_LOG"
if printf '%s' "$*" | grep -q 'information_schema.tables'; then
  echo 0
fi
exit 0
EOF
chmod 700 "$WORK/bin/mysql"

php_gzip() {
  "$PHP_BIN" -r 'file_put_contents($argv[1], gzencode($argv[2]));' "$1" "$2"
}

run_restore() {
  local dump="$1"
  local target="$2"
  local log="$3"
  MYSQL_LOG="$log"
  export MYSQL_LOG
  export PATH="$WORK/bin:$PATH"
  set +e
  output=$(bash "$ROOT/scripts/dr/restore-pitr.sh" \
    --dump "$dump" \
    --target-database "$target" \
    --confirm-restore \
    --env "$WORK/.env" 2>&1)
  local code=$?
  set -e
  printf '%s\n' "$output"
  printf '%s\n' "$PASSWORD" > "$WORK/secret"
  if grep -F -f "$WORK/secret" <<<"$output" >/dev/null; then
    echo "restore output contained the database password" >&2
    exit 1
  fi
  return "$code"
}

php_gzip "$WORK/malicious.sql.gz" $'SELECT 1;\nUSE school_management;\n'
php_gzip "$WORK/clean.sql.gz" $'CREATE TABLE restored_probe (id INT);\n'

if run_restore "$WORK/malicious.sql.gz" erp_restore "$WORK/malicious.log"; then
  echo "malicious dump was accepted" >&2
  exit 1
fi
if [[ -e "$WORK/malicious.log" ]]; then
  echo "mysql ran for a dump that contains USE" >&2
  exit 1
fi

for target in school_management mysql information_schema performance_schema sys; do
  if run_restore "$WORK/clean.sql.gz" "$target" "$WORK/refused-${target}.log"; then
    echo "restore accepted target ${target}" >&2
    exit 1
  fi
  if [[ -e "$WORK/refused-${target}.log" ]]; then
    echo "mysql ran for refused target ${target}" >&2
    exit 1
  fi
done

if ! run_restore "$WORK/clean.sql.gz" erp_restore "$WORK/clean.log"; then
  echo "clean dump did not reach the mysql client" >&2
  exit 1
fi
if [[ ! -s "$WORK/clean.log" ]]; then
  echo "clean dump did not execute mysql" >&2
  exit 1
fi
if grep -F -f "$WORK/secret" "$WORK/clean.log" >/dev/null; then
  echo "mysql arguments contained the database password" >&2
  exit 1
fi

echo "restore execution path passed"
