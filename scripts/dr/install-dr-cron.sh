#!/usr/bin/env bash
# Install local backup directories and cron entries for the disaster-recovery jobs.
# Does not restart MySQL, does not change MySQL configuration, and does not
# change the hourly Laravel backup schedule.
#
# The five-minute line only starts an archive attempt. It is not a five-minute
# recovery point. The recoverable point is the last closed binlog.NNNNNN file
# whose byte size was verified in S3. The active log is not uploaded.
#
# Usage on the EC2 instance, only after review: sudo bash scripts/dr/install-dr-cron.sh

set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/erp}"
CRON_USER="${CRON_USER:-www-data}"
BACKUP_ROOT="${BACKUP_ROOT:-/var/backups/erp}"

if [[ "$(id -u)" -ne 0 ]]; then
  echo "Run this on the server with sudo so it can create ${BACKUP_ROOT} and install cron." >&2
  exit 1
fi

install -d -m 750 -o "$CRON_USER" -g "$CRON_USER" \
  "$BACKUP_ROOT/frequent" \
  "$BACKUP_ROOT/daily" \
  "$BACKUP_ROOT/weekly" \
  "$BACKUP_ROOT/monthly" \
  "$BACKUP_ROOT/binlogs/incoming"

touch "$BACKUP_ROOT/binlogs/archived.ledger"
chown "$CRON_USER:$CRON_USER" "$BACKUP_ROOT/binlogs/archived.ledger"
chmod 640 "$BACKUP_ROOT/binlogs/archived.ledger"

if crontab -u "$CRON_USER" -l 2>/dev/null | grep -q 'mysqldump'; then
  echo "Warning: ${CRON_USER} crontab already mentions mysqldump."
  echo "After the new jobs have uploaded a verified dump, remove the older dump cron so backups are not taken twice."
fi

EXISTING=$(crontab -u "$CRON_USER" -l 2>/dev/null || true)
if grep -q 'scripts/dr/mysql-dump.sh' <<<"$EXISTING"; then
  echo "Disaster-recovery cron entries are already installed for ${CRON_USER}."
else
  {
    printf '%s\n' "$EXISTING"
    cat <<EOF

# erp-dr-backup — database dumps and binary log archive
*/30 * * * * cd $APP_DIR && bash scripts/dr/mysql-dump.sh frequent >> $APP_DIR/storage/logs/backup-dr.log 2>&1
15 2 * * * cd $APP_DIR && bash scripts/dr/mysql-dump.sh daily >> $APP_DIR/storage/logs/backup-dr.log 2>&1
20 3 * * 0 cd $APP_DIR && bash scripts/dr/mysql-dump.sh weekly >> $APP_DIR/storage/logs/backup-dr.log 2>&1
30 4 1 * * cd $APP_DIR && bash scripts/dr/mysql-dump.sh monthly >> $APP_DIR/storage/logs/backup-dr.log 2>&1
2-57/5 * * * * cd $APP_DIR && bash scripts/dr/archive-binlogs.sh >> $APP_DIR/storage/logs/backup-dr.log 2>&1
EOF
  } | crontab -u "$CRON_USER" -
  echo "Installed disaster-recovery cron for ${CRON_USER}."
fi

echo
echo "Next steps, still on this server:"
echo "  1. bash $APP_DIR/scripts/dr/mysql-inspect.sh"
echo "  2. Compare the output with deploy/dr/mysql-pitr.cnf.example before any MySQL restart."
echo "  3. Create the backup user in deploy/dr/create-backup-user.sql if the application user cannot read binary logs."
echo "  4. Leave the existing in-app backup schedule in place until a frequent dump and a daily dump have both uploaded."
echo "  5. Leave BACKUP_HEALTH_REQUIRED unset. backup:health is not on the scheduler."
echo "The five-minute archive cron is not a five-minute recovery point."
echo
echo "This installer did not restart MySQL and did not enable S3 Object Lock."
