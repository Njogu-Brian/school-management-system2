<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\PaymentTransaction;
use App\Models\Invoice;
use App\Models\Student;
use App\Models\BankAccount;
use App\Models\PaymentMethod;
use App\Services\PaymentService;
use App\Services\PaymentAllocationService;
use App\Services\ReceiptService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    protected PaymentService $paymentService;
    protected PaymentAllocationService $allocationService;
    protected ReceiptService $receiptService;

    public function __construct(
        PaymentService $paymentService,
        PaymentAllocationService $allocationService,
        ReceiptService $receiptService
    ) {
        $this->paymentService = $paymentService;
        $this->allocationService = $allocationService;
        $this->receiptService = $receiptService;
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
        
        $bankAccounts = BankAccount::active()->get();
        $paymentMethods = PaymentMethod::active()->get();

        return view('finance.payments.create', compact(
            'students', 'invoices', 'studentId', 'invoiceId',
            'bankAccounts', 'paymentMethods'
        ));
    }

    public function getStudentInfo($studentId)
    {
        $student = Student::with('family.students')->findOrFail($studentId);
        
        // Calculate balance
        $invoices = Invoice::where('student_id', $studentId)->get();
        $totalBalance = $invoices->sum('balance');
        $unpaidInvoices = $invoices->where('status', '!=', 'paid')->count();
        $partialInvoices = $invoices->where('status', 'partial')->count();
        
        // Get siblings (excluding current student)
        $siblings = $student->family 
            ? $student->family->students()->where('id', '!=', $studentId)->get()->map(function($sibling) {
                $siblingInvoices = Invoice::where('student_id', $sibling->id)->get();
                return [
                    'id' => $sibling->id,
                    'name' => $sibling->first_name . ' ' . $sibling->last_name,
                    'admission_number' => $sibling->admission_number,
                    'balance' => $siblingInvoices->sum('balance'),
                ];
            })
            : collect();
        
        return response()->json([
            'student' => [
                'id' => $student->id,
                'name' => $student->first_name . ' ' . $student->last_name,
                'admission_number' => $student->admission_number,
            ],
            'balance' => [
                'total_balance' => $totalBalance,
                'unpaid_invoices' => $unpaidInvoices,
                'partial_invoices' => $partialInvoices,
            ],
            'siblings' => $siblings,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'invoice_id' => 'nullable|exists:invoices,id',
            'amount' => 'required|numeric|min:1',
            'payment_date' => 'required|date',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'payer_name' => 'nullable|string|max:255',
            'payer_type' => 'nullable|in:parent,sponsor,student,other',
            'narration' => 'nullable|string',
            'transaction_code' => 'required|string|unique:payments,transaction_code', // Transaction code must be unique
            'auto_allocate' => 'nullable|boolean',
            'allocations' => 'nullable|array', // Manual allocations
            'allocations.*.invoice_item_id' => 'required|exists:invoice_items,id',
            'allocations.*.amount' => 'required|numeric|min:0.01',
            'shared_payment' => 'nullable|boolean', // For payment sharing among siblings
            'shared_students' => 'nullable|array', // Array of student IDs for shared payment
            'shared_amounts' => 'nullable|array', // Array of amounts for each student
        ]);

        $student = Student::findOrFail($validated['student_id']);

        // Check for overpayment warning
        $invoice = $validated['invoice_id'] ? \App\Models\Invoice::find($validated['invoice_id']) : null;
        $balance = $invoice ? $invoice->balance : $student->outstanding_balance ?? 0;
        $isOverpayment = $validated['amount'] > $balance;
        
        if ($isOverpayment && !($request->has('confirm_overpayment') && $request->confirm_overpayment)) {
            return back()
                ->withInput()
                ->with('warning', "Warning: Payment amount (Ksh " . number_format($validated['amount'], 2) . ") exceeds balance (Ksh " . number_format($balance, 2) . "). Overpayment of Ksh " . number_format($validated['amount'] - $balance, 2) . " will be carried forward.")
                ->with('show_overpayment_confirm', true);
        }

        DB::transaction(function () use ($validated, $student, $isOverpayment) {
            // Handle payment sharing among siblings
            if ($validated['shared_payment'] ?? false && !empty($validated['shared_students'])) {
                $sharedStudents = $validated['shared_students'];
                $sharedAmounts = $validated['shared_amounts'] ?? [];
                $totalShared = array_sum($sharedAmounts);
                
                // Validate total shared equals payment amount
                if (abs($totalShared - $validated['amount']) > 0.01) {
                    throw new \Exception('Total shared amounts must equal payment amount.');
                }
                
                // Create payments for each sibling
                foreach ($sharedStudents as $index => $siblingId) {
                    $sibling = Student::findOrFail($siblingId);
                    $siblingAmount = $sharedAmounts[$index] ?? 0;
                    
                    if ($siblingAmount > 0) {
                        // Generate unique transaction code for each sibling payment
                        $transactionCode = $validated['transaction_code'] . '-' . ($index + 1);
                        
                        $payment = Payment::create([
                            'student_id' => $siblingId,
                            'family_id' => $sibling->family_id,
                            'invoice_id' => null, // Will be auto-allocated
                            'amount' => $siblingAmount,
                            'payment_method_id' => $validated['payment_method_id'],
                            'payer_name' => $validated['payer_name'],
                            'payer_type' => $validated['payer_type'],
                            'narration' => $validated['narration'],
                            'transaction_code' => $transactionCode,
                            'payment_date' => $validated['payment_date'],
                            // receipt_date is set automatically in Payment model
                        ]);
                        
                        // Auto-allocate for sibling
                        $this->allocationService->autoAllocate($payment);
                    }
                }
            } else {
                // Create single payment
                $payment = Payment::create([
                    'student_id' => $validated['student_id'],
                    'family_id' => $student->family_id,
                    'invoice_id' => $validated['invoice_id'] ?? null,
                    'amount' => $validated['amount'],
                    'payment_method_id' => $validated['payment_method_id'],
                    'payer_name' => $validated['payer_name'],
                    'payer_type' => $validated['payer_type'],
                    'narration' => $validated['narration'],
                    'transaction_code' => $validated['transaction_code'],
                    'payment_date' => $validated['payment_date'],
                    // receipt_date is set automatically in Payment model
                ]);

                // Allocate payment
                if ($validated['auto_allocate'] ?? false) {
                    $this->allocationService->autoAllocate($payment);
                } elseif (!empty($validated['allocations'])) {
                    $this->allocationService->allocatePayment($payment, $validated['allocations']);
                }
                
                // Handle overpayment
                if ($payment->hasOverpayment()) {
                    $this->allocationService->handleOverpayment($payment);
                }
                
                // Log audit
                if (class_exists(\App\Models\AuditLog::class)) {
                    \App\Models\AuditLog::log(
                        'created',
                        $payment,
                        null,
                        [
                            'amount' => $payment->amount,
                            'student_id' => $payment->student_id,
                            'payment_method_id' => $payment->payment_method_id,
                        ],
                        ['payment_recorded']
                    );
                }
            }
        });

        return redirect()
            ->route('finance.payments.index')
            ->with('success', 'Payment recorded successfully.');
    }
    
    public function allocate(Request $request, Payment $payment)
    {
        $validated = $request->validate([
            'allocations' => 'required|array',
            'allocations.*.invoice_item_id' => 'required|exists:invoice_items,id',
            'allocations.*.amount' => 'required|numeric|min:0.01',
        ]);

        try {
            $this->allocationService->allocatePayment($payment, $validated['allocations']);
            return redirect()
                ->route('finance.payments.show', $payment)
                ->with('success', 'Payment allocated successfully.');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
    
    public function printReceipt(Payment $payment)
    {
        try {
            $payment->load(['student', 'invoice', 'paymentMethod', 'bankAccount', 'allocations.invoiceItem.votehead']);
            $pdf = $this->receiptService->generateReceipt($payment, ['save' => false]);
            
            // Return PDF in new window
            return response($pdf, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="Receipt_' . $payment->receipt_number . '.pdf"',
            ]);
        } catch (\Exception $e) {
            return back()->with('error', 'Receipt generation failed: ' . $e->getMessage());
        }
    }

    public function viewReceipt(Payment $payment)
    {
        $payment->load(['student', 'invoice', 'paymentMethod', 'bankAccount', 'allocations.invoiceItem.votehead']);
        return view('finance.receipts.view', compact('payment'));
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
        $payment->load([
            'student', 
            'invoice', 
            'paymentMethod', 
            'bankAccount',
            'allocations.invoiceItem.votehead',
            'allocations.invoiceItem.invoice'
        ]);
        return view('finance.payments.show', compact('payment'));
    }
}
