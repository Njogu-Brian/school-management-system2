<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\CreditNote;
use Illuminate\Http\Request;

class CreditNoteController extends Controller
{
    public function index()
    {
        $creditNotes = CreditNote::with('invoice')->latest()->paginate(20);
        return view('finance.credit_notes.index', compact('creditNotes'));
    }

    public function create()
    {
        return view('finance.credit_notes.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
            'amount' => 'required|numeric|min:1',
            'reason' => 'required|string|max:255',
            'issued_at' => 'required|date',
        ]);

        CreditNote::create($request->all());
        return redirect()->route('credit-notes.index')->with('success', 'Credit note issued.');
    }

    public function reverse(CreditNote $creditNote)
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($creditNote) {
            $invoice = $creditNote->invoice;
            
            // If there's an associated invoice item, adjust it back
            if ($creditNote->invoice_item_id) {
                $item = $creditNote->invoiceItem;
                if ($item) {
                    // Reverse the credit by adding the amount back
                    $item->increment('amount', $creditNote->amount);
                    $item->save();
                }
            }
            
            // Delete the credit note
            $creditNote->delete();
            
            // Recalculate invoice
            if ($invoice) {
                \App\Services\InvoiceService::recalc($invoice);
            }
            
            return back()->with('success', 'Credit note reversed successfully.');
        });
    }
}
