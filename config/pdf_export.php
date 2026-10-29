<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Chunked DomPDF exports
    |--------------------------------------------------------------------------
    |
    | DomPDF builds a full frame tree in memory. Never render hundreds of
    | documents or huge tables as one loadView/loadHTML call. Chunk, render,
    | free memory, then merge (qpdf preferred) or ZIP as last resort.
    |
    */

    /** Items (invoices, report cards, receipts, table rows) per DomPDF render. */
    'chunk_size' => (int) env('PDF_EXPORT_CHUNK_SIZE', 15),

    /** Soft cap for synchronous HTTP bulk exports (above this → queue or reject). */
    'sync_max_items' => (int) env('PDF_EXPORT_SYNC_MAX', 150),

    /** Absolute hard cap even for queued exports. */
    'hard_max_items' => (int) env('PDF_EXPORT_HARD_MAX', 2000),

    /** Memory limit applied while a chunked export runs. */
    'memory_limit' => env('PDF_EXPORT_MEMORY_LIMIT', '512M'),

    /** Prefer external merge tools in this order. */
    'merge_binaries' => ['qpdf', 'gs', 'gswin64c', 'pdftk'],

    /** Temp subdirectory under storage/app */
    'temp_directory' => 'pdf-export-tmp',

];
