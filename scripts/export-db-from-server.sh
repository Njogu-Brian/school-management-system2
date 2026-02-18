#!/bin/bash
# Run this script ON THE SERVER (after SSH) to export the database.
# Usage: cd /var/www/erp && bash scripts/export-db-from-server.sh
#
# Reads DB_* from .env on the server. To download the dump to your PC, run the
# scp command printed at the end (from your local machine).

set -e
cd /var/www/erp

# Load DB_* from .env (handles values with = in them)
if [ -f .env ]; then
  DB_HOST=$(grep '^DB_HOST=' .env | cut -d= -f2-)
  DB_PORT=$(grep '^DB_PORT=' .env | cut -d= -f2-)
  DB_DATABASE=$(grep '^DB_DATABASE=' .env | cut -d= -f2-)
  DB_USERNAME=$(grep '^DB_USERNAME=' .env | cut -d= -f2-)
  DB_PASSWORD=$(grep '^DB_PASSWORD=' .env | cut -d= -f2-)
fi

DB_HOST=${DB_HOST:-127.0.0.1}
DB_PORT=${DB_PORT:-3306}
DB_DATABASE=${DB_DATABASE:-school_management}
DB_USERNAME=${DB_USERNAME:-root}

if [ -z "$DB_PASSWORD" ]; then
  echo "DB_PASSWORD not set in .env. Run: mysqldump -h $DB_HOST -P $DB_PORT -u $DB_USERNAME -p $DB_DATABASE > dump.sql"
  exit 1
fi

OUTPUT="school_export_$(date +%Y%m%d_%H%M).sql"

echo "Exporting database: $DB_DATABASE (user: $DB_USERNAME @ $DB_HOST:$DB_PORT)"
mysqldump -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" -p"$DB_PASSWORD" \
  --single-transaction \
  --quick \
  --lock-tables=false \
  "$DB_DATABASE" > "$OUTPUT"

echo "Done. File: $OUTPUT ($(du -h "$OUTPUT" | cut -f1))"
echo ""
echo "Download to your PC (run from your LOCAL machine in the project folder):"
echo "  scp -i erp-key.pem ubuntu@13.245.211.78:/var/www/erp/$OUTPUT ."
