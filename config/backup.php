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

];
