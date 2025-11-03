<?php

namespace App\Services;

use App\Models\Invoice;

class InvoiceService
{
    public static function ensure(int $studentId, int $year, int $term): Invoice
    {
        return Invoice::firstOrCreate(
            ['student_id'=>$studentId,'year'=>$year,'term'=>$term],
            ['invoice_number'=>NumberSeries::invoice(), 'total'=>0]
        );
    }

    public static function recalc(Invoice $invoice): void
    {
        $invoice->refresh();
        $total = $invoice->items()->where('status','active')->sum('amount'); // only active contribute
        $invoice->update(['total'=>$total]);
    }
}
