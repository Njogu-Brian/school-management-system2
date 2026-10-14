<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Local database dump directory
    |--------------------------------------------------------------------------
    | SQL/zip backups created by BackupRestoreController are stored here.
    | For off-server copies, sync or upload to S3 separately (see docs).
    */
    'storage_path' => env('BACKUP_STORAGE_PATH', storage_path('app/backups')),

    /*
    |--------------------------------------------------------------------------
    | Upload backups to S3
    |--------------------------------------------------------------------------
    | When enabled, every new backup will be uploaded to this disk/prefix.
    | Recommended disk: s3_private (Option B, Block Public Access).
    */
    'upload_to_s3' => (bool) env('BACKUP_UPLOAD_TO_S3', true),
    's3_disk' => env('BACKUP_S3_DISK', 's3_private'),
    's3_prefix' => trim((string) env('BACKUP_S3_PREFIX', 'backups/mysql'), '/'),

    /*
    |--------------------------------------------------------------------------
    | Retention (local files only)
    |--------------------------------------------------------------------------
    | Files older than this many days are deleted by the scheduled backup:prune
    | command and after each successful on-demand / scheduled backup.
    */
    'retention_days' => (int) env('BACKUP_RETENTION_DAYS', 5),

    /*
    |--------------------------------------------------------------------------
    | Keep latest N local backups
    |--------------------------------------------------------------------------
    | Helps prevent EC2 storage growth when running backups hourly.
    | Set to 0 to disable count-based pruning.
    */
    'keep_local_latest' => (int) env('BACKUP_KEEP_LOCAL_LATEST', 2),

    /*
    |--------------------------------------------------------------------------
    | Disaster-recovery health check
    |--------------------------------------------------------------------------
    | The shell jobs in scripts/dr publish status to storage/app/backups/dr-status.json.
    | Leave health_required off until a dump and a verified binary log have been
    | restored on a separate database. backup:health is not on the scheduler.
    | These ages are monitoring thresholds. They are not the recovery point.
    | The recovery point is the last closed log whose size was verified in S3.
    */
    'health_required' => (bool) env('BACKUP_HEALTH_REQUIRED', false),
    'frequent_max_age_minutes' => (int) env('BACKUP_FREQUENT_MAX_AGE_MINUTES', 45),
    'binlog_max_age_minutes' => (int) env('BACKUP_BINLOG_MAX_AGE_MINUTES', 10),

    /*
    |--------------------------------------------------------------------------
    | Optional second copy
    |--------------------------------------------------------------------------
    | When set, daily, weekly, monthly, and binary-log objects are also uploaded
    | to this disk. Leave empty until that bucket and its credentials exist.
    | Frequent 30-minute dumps stay on the primary backup disk only.
    */
    'recovery_disk' => env('BACKUP_RECOVERY_DISK'),
    'recovery_prefix' => trim((string) env('BACKUP_RECOVERY_PREFIX', 'recovery/mysql'), '/'),

];
