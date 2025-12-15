<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Journal;

class NumberSeries
{
    public static function invoice(): string
    {
        try {
            if (class_exists(DocumentNumberService::class)) {
                return DocumentNumberService::generateInvoice();
            }
        } catch (\Throwable $e) {}
        $next = (int) (Invoice::max('id') ?? 0) + 1;
        return 'INV-'.date('Y').'-'.str_pad($next, 5, '0', STR_PAD_LEFT);
    }
    
    public static function receipt(): string
    {
        try {
            if (class_exists(DocumentNumberService::class)) {
                return DocumentNumberService::generateReceipt();
            }
        } catch (\Throwable $e) {}
        $next = (int) (\App\Models\Payment::max('id') ?? 0) + 1;
        return 'RCPT-'.date('Y').'-'.str_pad($next, 6, '0', STR_PAD_LEFT);
    }

    public static function journal(): string
    {
        $next = (int) (Journal::max('id') ?? 0) + 1;
        return 'JRN-'.date('Y').'-'.str_pad($next, 5, '0', STR_PAD_LEFT);
    }
}
