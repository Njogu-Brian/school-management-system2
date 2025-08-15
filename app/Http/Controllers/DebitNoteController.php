<?php

namespace App\Http\Controllers;

use App\Models\DebitNote;
use Illuminate\Http\Request;

class DebitNoteController extends Controller
{
    public function index()
    {
        $debitNotes = DebitNote::with('invoice')->latest()->paginate(20);
        return view('finance.debit_notes.index', compact('debitNotes'));
    }

    public function create()
    {
        return view('finance.debit_notes.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
            'amount' => 'required|numeric|min:1',
            'reason' => 'required|string|max:255',
            'issued_at' => 'required|date',
        ]);

        DebitNote::create($request->all());
        return redirect()->route('debit-notes.index')->with('success', 'Debit note issued.');
    }
}
