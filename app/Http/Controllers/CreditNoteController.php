<?php

namespace App\Http\Controllers;

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
}
