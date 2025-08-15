<?php

namespace App\Http\Controllers;

use App\Models\Receipt;
use Illuminate\Http\Request;

class ReceiptController extends Controller
{
    public function index()
    {
        $receipts = Receipt::with('payment')->latest()->paginate(20);
        return view('finance.receipts.index', compact('receipts'));
    }

    public function show(Receipt $receipt)
    {
        return view('finance.receipts.show', compact('receipt'));
    }
}
