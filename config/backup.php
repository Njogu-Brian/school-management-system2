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
    | Retention (local files only)
    |--------------------------------------------------------------------------
    | Files older than this many days are deleted by the scheduled backup:prune
    | command and after each successful on-demand / scheduled backup.
    */
    'retention_days' => (int) env('BACKUP_RETENTION_DAYS', 5),

];
