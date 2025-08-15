<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class InvoiceAdjustmentController extends Controller
{
    public function importForm()
    {
        return view('finance.invoices.adjustments.import');
    }

    public function import(Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:xlsx,xls']);

        $data = Excel::toCollection(null, $request->file('file'))->first();

        foreach ($data as $row) {
            $invoice = Invoice::where('invoice_number', $row['invoice_number'])->first();
            if (!$invoice) continue;

            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'votehead_id' => $row['votehead_id'],
                'amount' => $row['amount'],
            ]);

            $invoice->total += $row['amount'];
            $invoice->save();
        }

        return back()->with('success', 'Adjustments imported.');
    }
}
