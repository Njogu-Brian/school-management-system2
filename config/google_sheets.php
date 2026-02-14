<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Google Sheets API – Fee / Income sync
    |--------------------------------------------------------------------------
    | Used by the fee sync agent to read your income sheet and push updates
    | when invoices or payments change. Requires a Google Cloud project with
    | Sheets API enabled and a service account (or OAuth) credentials.
    */

    'enabled' => env('GOOGLE_SHEETS_SYNC_ENABLED', false),

    /** Path to service account JSON (storage/app or absolute). Never commit this file. */
    'credentials_path' => env('GOOGLE_SHEETS_CREDENTIALS_PATH', storage_path('app/google-sheets-credentials.json')),

    /** Spreadsheet ID from the sheet URL: https://docs.google.com/spreadsheets/d/SPREADSHEET_ID/edit */
    'spreadsheet_id' => env('GOOGLE_SHEETS_SPREADSHEET_ID', ''),

    /** Sheet tab name and range to read/write (e.g. "Income!A2:H500"). */
    'income_sheet_range' => env('GOOGLE_SHEETS_INCOME_RANGE', 'Income!A2:H500'),

    /**
     * Column mapping (0-based index or header name).
     * Adjust to match your sheet: admission_number, student_name, votehead, invoiced, paid, balance, term, year.
     */
    'columns' => [
        'admission_number' => env('GOOGLE_SHEETS_COL_ADMISSION', 0),
        'student_name'     => env('GOOGLE_SHEETS_COL_NAME', 1),
        'votehead'         => env('GOOGLE_SHEETS_COL_VOTEHEAD', 2),
        'invoiced'         => env('GOOGLE_SHEETS_COL_INVOICED', 3),
        'paid'             => env('GOOGLE_SHEETS_COL_PAID', 4),
        'balance'          => env('GOOGLE_SHEETS_COL_BALANCE', 5),
        'term'             => env('GOOGLE_SHEETS_COL_TERM', 6),
        'year'             => env('GOOGLE_SHEETS_COL_YEAR', 7),
    ],

];
