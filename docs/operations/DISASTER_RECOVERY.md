# Disaster recovery

This runbook is for the EC2 host that runs Laravel and MySQL together. The hourly Laravel backup stays as it is: `BackupRestoreController::runScheduledIfDue` on the scheduler, files in `storage/app/backups`, uploaded under `private/backups/mysql/`. The jobs below are a separate recovery path. They write only under `/var/backups/erp` and under the tier prefixes `private/backups/mysql/frequent/`, `daily/`, `weekly/`, `monthly/`, and `binlogs/`.

## What you can recover

| Copy | When the job starts | S3 key prefix | How far back |
| --- | --- | --- | --- |
| Frequent dump | Every 30 minutes | `private/backups/mysql/frequent/` | 48 hours locally; lifecycle draft expires at 2 days |
| Daily dump | 02:15 | `private/backups/mysql/daily/` | 30 days |
| Weekly dump | Sunday 03:20 | `private/backups/mysql/weekly/` | 12 weeks |
| Monthly dump | 1st of the month 04:30 | `private/backups/mysql/monthly/` | 12 months |
| Closed binary logs | Every 5 minutes | `private/backups/mysql/binlogs/` | 14 days after a log is verified in S3 |

The recovery point is the last closed log named `binlog.NNNNNN` whose byte size was checked against `SHOW BINARY LOGS` and then verified in S3. A cron line every five minutes is only how often the archive is attempted. It is not a five-minute recovery point.

The active log is never uploaded. A crash, a failed upload, or an EC2 disk loss before the next successful verification also loses any closed log that is not yet recorded in the archive ledger. The first archive run records `coverage_starts_at` as the oldest closed log still on the server and reports that earlier logs are outside coverage. A later missing sequence number fails the archive. An expired local log is not skipped quietly.

A dump restores the database to the binary-log position recorded in that dump. Replay after that position is limited to logs that were actually archived. Logs are eligible to expire after 14 days once a lifecycle rule is applied, so point-in-time recovery cannot extend past the oldest verified log still in S3. A weekly or monthly dump older than the archived logs restores only to the dump itself.

The hourly Laravel files (`backup_mysql_*.sql.gz`) are pruned only from `storage/app/backups`. They are not deleted by `scripts/dr/mysql-dump.sh`. A lifecycle rule must not use the parent prefix `private/backups/mysql/`, or it would expire those hourly objects.

## 1. Inspect the server before changing MySQL

On the EC2 instance:

```bash
cd /var/www/erp
bash scripts/dr/mysql-inspect.sh
```

Check the MySQL version, whether `log_bin` is already ON, the `server_id`, and that application tables are InnoDB. The example file `deploy/dr/mysql-pitr.cnf.example` is a starting point for a maintenance window. It is not something to paste over the current configuration. Restart MySQL only after you have read the inspect output, and take a new daily dump once the server is back.

Do not shorten `binlog_expire_logs_seconds` until closed logs are appearing in S3. The archive script does not run `PURGE BINARY LOGS` and does not delete a local log until `backup:object-size` matches the ledger.

The application database user currently cannot run `SHOW BINARY LOGS`. The archive and coordinate flags need `REPLICATION CLIENT`, `REPLICATION SLAVE`, and `RELOAD`. `deploy/dr/create-backup-user.sql` is the grant to review before creating that account. Point the recovery scripts at that account with `DR_ENV_FILE`, a mode `0600` file outside the application `.env`. Do not replace `DB_USERNAME` in `/var/www/erp/.env`. The jobs read the selected env file through `scripts/dr/dr.php`, which writes a mode `0600` option file and escapes `#`, quotes, and backslashes. The password is not passed on the process command line. If binary logging is on and the client cannot record `--source-data=2`, the dump fails. It is not retried without coordinates, routines, events, or triggers. The dump does not use `--databases`, so it does not emit `CREATE DATABASE` or `USE`.

## 2. Install the jobs

```bash
sudo APP_DIR=/var/www/erp bash scripts/dr/install-dr-cron.sh
```

That creates `/var/backups/erp` and cron entries for the `www-data` user. It does not restart MySQL. If crontab already runs `mysqldump`, leave the old line until one new frequent dump and one daily dump have uploaded, then remove the old line so the database is not dumped twice.

The uploader checks that gzip can read a dump, records SHA-256, uploads the file, and checks the object size. A failed check leaves a non-zero cron status and does not update `storage/app/backups/dr-status.json`.

## 3. Protect the bucket

Use a backup IAM user that is separate from `ERP-S3-Access`. `deploy/dr/backup-uploader-policy.json` only allows list, read, and write under `private/backups/mysql/`. It does not deny `s3:DeleteObject` on the bucket, so it does not block deletion of application documents. Versioning, lifecycle, Object Lock, and deletion under that same prefix belong in `deploy/dr/backup-bucket-admin-policy.json`. Do not attach the admin policy to `ERP-S3-Access` or to the backup uploader.

Neither policy has been applied. The preview below does not change the bucket. Applying it later requires both `APPLY=1` and `CONFIRM_BUCKET_CHANGE=yes`, and the script refuses any prefix other than `private/backups/mysql`.

```bash
# preview only
bash scripts/dr/apply-s3-protection.sh
```

Versioning keeps an overwritten or deleted object as a previous version. Lifecycle rules expire frequent dumps after 2 days, daily after 30 days, weekly after 84 days, monthly after 365 days, and binary logs after 14 days. Noncurrent versions are expired as well.

Object Lock is off unless you run the same command with `ENABLE_OBJECT_LOCK=1`. Governance retention then blocks ordinary deletes for 14 days. Turn that on only for a bucket you have chosen for it, because locked versions are not removed just because a lifecycle date arrives.

A second copy is optional. Set `AWS_BACKUP_*`, `BACKUP_S3_DISK=s3_backup`, and `BACKUP_RECOVERY_DISK` only after that bucket exists. Daily, weekly, monthly, and binary-log objects are copied there. Frequent dumps stay on the primary backup prefix.

## 4. Prove a restore

Restore into an empty database on a disposable MySQL instance. The script rejects `school_management` and the system schemas `mysql`, `information_schema`, `performance_schema`, and `sys`. Before it writes a defaults file or starts `mysql`, it rejects a dump that contains `USE`, `CREATE DATABASE`, or `DROP DATABASE`. Replay uses `mysqlbinlog --rewrite-db` and is rejected if the rewritten SQL still selects another database. A failed check does not run the SQL.

```bash
bash scripts/dr/restore-pitr.sh \
  --dump /var/backups/erp/daily/your_dump.sql.gz \
  --target-database erp_restore \
  --confirm-restore
```

To stop at a chosen event, inspect the closed log first:

```bash
mysqlbinlog --base64-output=DECODE-ROWS --verbose binlog.000012 | less
```

Then replay from the position written in the dump through the position before the damage:

```bash
bash scripts/dr/restore-pitr.sh \
  --dump /var/backups/erp/daily/your_dump.sql.gz \
  --target-database erp_restore \
  --confirm-restore \
  --stop-position 1540 \
  --binlog /var/backups/erp/binlogs/incoming/binlog.000012
```

The first `--binlog` file must be the log file named in that dump. The script starts at the dump's recorded position so events already inside the dump are not applied again. Choose `--stop-position` from the inspected events. `--stop-datetime` is available, but a verified position is the safer stop for a deleted payment or a bad deployment.

After the load, compare student counts, invoice totals, payment allocations, and attendance with the source you expect. Payments that arrived after the stop position have to be entered again from the bank or M-Pesa record.

The Backup & Restore screen loads a file into the database the site is using. Use the script above for disaster recovery.

## 5. Turn on the health check

`php artisan backup:health` is not on the scheduler. Leave `BACKUP_HEALTH_REQUIRED` unset until a dump and a verified binary log have both been restored on a separate database. Turning it on before that only changes the command's exit code. It does not create a recovery point.

Repeat a restore onto a disposable database once a month. A gzip integrity check is not that restore. No production restore was run as part of the script fix.
