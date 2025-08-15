<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index()
    {
        $payments = Payment::with('student', 'invoice')->latest()->paginate(20);
        return view('finance.payments.index', compact('payments'));
    }

    public function create()
    {
        return view('finance.payments.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
            'invoice_id' => 'required|exists:invoices,id',
            'amount_paid' => 'required|numeric|min:1',
            'payment_date' => 'required|date',
        ]);

        $payment = Payment::create($request->all());
        return redirect()->route('payments.index')->with('success', 'Payment recorded successfully.');
    }

    public function show(Payment $payment)
    {
        return view('finance.payments.show', compact('payment'));
    }
}
