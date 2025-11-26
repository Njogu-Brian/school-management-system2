<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\PaymentTransaction;
use App\Models\Invoice;
use App\Models\Student;
use App\Services\PaymentService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    protected PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function index(Request $request)
    {
        $query = Payment::with('student', 'invoice');

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $payments = $query->latest()->paginate(20)->withQueryString();
        return view('finance.payments.index', compact('payments'));
    }

    public function create(Request $request)
    {
        $studentId = $request->get('student_id');
        $invoiceId = $request->get('invoice_id');

        $students = Student::orderBy('first_name')->get();
        $invoices = $invoiceId 
            ? Invoice::where('id', $invoiceId)->get()
            : ($studentId 
                ? Invoice::where('student_id', $studentId)->where('status', '!=', 'paid')->get()
                : Invoice::where('status', '!=', 'paid')->get());

        return view('finance.payments.create', compact('students', 'invoices', 'studentId', 'invoiceId'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'invoice_id' => 'required|exists:invoices,id',
            'amount_paid' => 'required|numeric|min:1',
            'payment_date' => 'required|date',
            'payment_method' => 'required|in:cash,mpesa,stripe,paypal,bank,cheque',
            'reference' => 'nullable|string|max:255',
        ]);

        $invoice = Invoice::findOrFail($validated['invoice_id']);
        
        // Create payment record
        $payment = Payment::create([
            'student_id' => $validated['student_id'],
            'invoice_id' => $validated['invoice_id'],
            'amount' => $validated['amount_paid'],
            'payment_method' => $validated['payment_method'],
            'reference' => $validated['reference'],
            'payment_date' => $validated['payment_date'],
        ]);

        // Update invoice
        $invoice->increment('paid_amount', $validated['amount_paid']);
        $invoice->update(['balance' => $invoice->total_amount - $invoice->paid_amount]);
        
        if ($invoice->balance <= 0) {
            $invoice->update(['status' => 'paid']);
        }

        return redirect()
            ->route('finance.payments.index')
            ->with('success', 'Payment recorded successfully.');
    }

    /**
     * Initiate online payment
     */
    public function initiateOnline(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'invoice_id' => 'required|exists:invoices,id',
            'gateway' => 'required|in:mpesa,stripe,paypal',
            'phone_number' => 'required_if:gateway,mpesa|string',
        ]);

        $student = Student::findOrFail($validated['student_id']);
        $invoice = Invoice::findOrFail($validated['invoice_id']);

        try {
            $options = [];
            if ($validated['gateway'] === 'mpesa' && isset($validated['phone_number'])) {
                $options['phone_number'] = $validated['phone_number'];
            }

            $transaction = $this->paymentService->initiatePayment(
                $student,
                $invoice,
                $validated['gateway'],
                $options
            );

            return redirect()
                ->route('finance.payment-transactions.show', $transaction)
                ->with('success', 'Payment initiated successfully. Please complete the payment on your phone.');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Payment initiation failed: ' . $e->getMessage());
        }
    }

    /**
     * Show payment transaction
     */
    public function showTransaction(PaymentTransaction $transaction)
    {
        $transaction->load(['student', 'invoice']);
        return view('finance.payments.transaction', compact('transaction'));
    }

    /**
     * Verify payment status
     */
    public function verifyTransaction(PaymentTransaction $transaction)
    {
        try {
            $result = $this->paymentService->verifyPayment($transaction);
            
            return back()->with('success', 'Payment status updated.');
        } catch (\Exception $e) {
            return back()->with('error', 'Verification failed: ' . $e->getMessage());
        }
    }

    public function show(Payment $payment)
    {
        $payment->load(['student', 'invoice']);
        return view('finance.payments.show', compact('payment'));
    }
}
