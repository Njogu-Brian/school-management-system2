<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\{
    BankStatementTransaction, BankAccount, Student, Family
};
use App\Services\BankStatementParser;
use App\Services\PaymentAllocationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class BankStatementController extends Controller
{
    protected $parser;
    protected $allocationService;

    public function __construct(BankStatementParser $parser, PaymentAllocationService $allocationService)
    {
        $this->parser = $parser;
        $this->allocationService = $allocationService;
    }

    /**
     * Display list of bank statement transactions
     */
    public function index(Request $request)
    {
        $query = BankStatementTransaction::with(['student', 'family', 'bankAccount', 'payment', 'duplicateOfPayment'])
            ->orderBy('transaction_date', 'desc')
            ->orderBy('created_at', 'desc');

        // View filters (auto-assigned, draft, duplicate, archived)
        $view = $request->get('view', 'all');
        
        switch ($view) {
            case 'auto-assigned':
                $query->where('match_status', 'matched')
                      ->where('match_confidence', '>=', 0.85)
                      ->where('is_duplicate', false)
                      ->where('is_archived', false);
                break;
            case 'draft':
                $query->where('status', 'draft')
                      ->where('is_duplicate', false)
                      ->where('is_archived', false);
                break;
            case 'duplicate':
                $query->where('is_duplicate', true)
                      ->where('is_archived', false);
                break;
            case 'archived':
                $query->where('is_archived', true);
                break;
            default:
                // Show all non-archived by default
                $query->where('is_archived', false);
        }

        // Additional filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('match_status')) {
            $query->where('match_status', $request->match_status);
        }

        if ($request->filled('bank_account_id')) {
            $query->where('bank_account_id', $request->bank_account_id);
        }

        if ($request->filled('date_from')) {
            $query->where('transaction_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('transaction_date', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('description', 'LIKE', "%{$search}%")
                  ->orWhere('reference_number', 'LIKE', "%{$search}%")
                  ->orWhere('phone_number', 'LIKE', "%{$search}%")
                  ->orWhere('payer_name', 'LIKE', "%{$search}%")
                  ->orWhereHas('student', function($q) use ($search) {
                      $q->where('admission_number', 'LIKE', "%{$search}%")
                        ->orWhere('first_name', 'LIKE', "%{$search}%")
                        ->orWhere('last_name', 'LIKE', "%{$search}%");
                  });
            });
        }

        $transactions = $query->paginate(25)->withQueryString();
        $bankAccounts = BankAccount::where('is_active', true)->get();

        // Calculate total amount for current filtered results
        $totalAmount = (clone $query)->sum('amount');
        $totalCount = (clone $query)->count();

        // Get counts for each view
        $counts = [
            'all' => BankStatementTransaction::where('is_archived', false)->count(),
            'auto-assigned' => BankStatementTransaction::where('match_status', 'matched')
                ->where('match_confidence', '>=', 0.85)
                ->where('is_duplicate', false)
                ->where('is_archived', false)
                ->count(),
            'draft' => BankStatementTransaction::where('status', 'draft')
                ->where('is_duplicate', false)
                ->where('is_archived', false)
                ->count(),
            'duplicate' => BankStatementTransaction::where('is_duplicate', true)
                ->where('is_archived', false)
                ->count(),
            'archived' => BankStatementTransaction::where('is_archived', true)->count(),
        ];

        return view('finance.bank-statements.index', compact('transactions', 'bankAccounts', 'view', 'counts', 'totalAmount', 'totalCount'));
    }

    /**
     * Show upload form
     */
    public function create()
    {
        $bankAccounts = BankAccount::where('is_active', true)->get();
        return view('finance.bank-statements.create', compact('bankAccounts'));
    }

    /**
     * Upload and parse bank statement
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'statement_file' => 'required|file|mimes:pdf|max:10240', // 10MB max
            'bank_account_id' => 'nullable|exists:bank_accounts,id',
            'bank_type' => 'nullable|in:mpesa,equity',
        ]);

        try {
            // Store uploaded file
            $file = $request->file('statement_file');
            $path = $file->store('bank-statements', 'private');

            // Parse statement
            $result = $this->parser->parseStatement(
                $path,
                $validated['bank_account_id'] ?? null,
                $validated['bank_type'] ?? null
            );

            if (!$result['success']) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['statement_file' => $result['message']]);
            }

            return redirect()
                ->route('finance.bank-statements.index')
                ->with('success', $result['message']);

        } catch (\Exception $e) {
            Log::error('Bank statement upload failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()->back()
                ->withInput()
                ->withErrors(['statement_file' => 'Failed to parse statement: ' . $e->getMessage()]);
        }
    }

    /**
     * Show transaction details
     */
    public function show(BankStatementTransaction $bankStatement)
    {
        $bankStatement->load(['student', 'family', 'bankAccount', 'payment', 'confirmedBy', 'createdBy']);
        
        // Get siblings if family exists
        $siblings = [];
        if ($bankStatement->family_id) {
            $siblings = Student::where('family_id', $bankStatement->family_id)
                ->where('id', '!=', $bankStatement->student_id)
                ->get();
        } elseif ($bankStatement->student_id) {
            $student = $bankStatement->student;
            $siblings = $student->siblings;
        }

        return view('finance.bank-statements.show', compact('bankStatement', 'siblings'));
    }

    /**
     * Edit transaction (manual matching)
     */
    public function edit(BankStatementTransaction $bankStatement)
    {
        $bankStatement->load(['student', 'family']);
        
        // Get potential matches
        $potentialMatches = [];
        if ($bankStatement->phone_number) {
            $normalizedPhone = $this->parser->normalizePhone($bankStatement->phone_number);
            $potentialMatches = $this->parser->findStudentsByPhone($normalizedPhone);
        }

        $students = Student::where('archive', false)
            ->orderBy('first_name')
            ->get();

        return view('finance.bank-statements.edit', compact('bankStatement', 'potentialMatches', 'students'));
    }

    /**
     * Update transaction
     */
    public function update(Request $request, BankStatementTransaction $bankStatement)
    {
        $validated = $request->validate([
            'student_id' => 'nullable|exists:students,id',
            'match_notes' => 'nullable|string|max:1000',
        ]);

        DB::transaction(function () use ($bankStatement, $validated) {
            $student = null;
            if ($validated['student_id']) {
                $student = Student::findOrFail($validated['student_id']);
            }

            $bankStatement->update([
                'student_id' => $student?->id,
                'family_id' => $student?->family_id,
                'match_status' => $student ? 'manual' : 'unmatched',
                'match_confidence' => $student ? 1.0 : 0,
                'match_notes' => $validated['match_notes'] ?? 'Manually assigned',
            ]);
        });

        return redirect()
            ->route('finance.bank-statements.show', $bankStatement)
            ->with('success', 'Transaction updated successfully');
    }

    /**
     * Confirm transaction
     */
    public function confirm(Request $request, BankStatementTransaction $bankStatement)
    {
        if (!$bankStatement->student_id && !$bankStatement->is_shared) {
            return redirect()->back()
                ->withErrors(['error' => 'Transaction must be matched to a student or shared before confirming']);
        }

        DB::transaction(function () use ($bankStatement) {
            $bankStatement->confirm();

            // Create payment if not already created
            if (!$bankStatement->payment_created) {
                try {
                    $payment = $this->parser->createPaymentFromTransaction($bankStatement);
                    
                    // Generate receipt for the payment
                    try {
                        $receiptService = app(\App\Services\ReceiptService::class);
                        $receiptService->generateReceipt($payment, ['save' => true]);
                    } catch (\Exception $e) {
                        Log::warning('Receipt generation failed for bank statement payment', [
                            'payment_id' => $payment->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                    
                    // If shared payment, generate receipts for all sibling payments
                    if ($bankStatement->is_shared && $bankStatement->shared_allocations) {
                        foreach ($bankStatement->shared_allocations as $allocation) {
                            $siblingPayment = \App\Models\Payment::where('student_id', $allocation['student_id'])
                                ->where('transaction_code', 'LIKE', $payment->transaction_code . '%')
                                ->where('id', '!=', $payment->id)
                                ->first();
                            
                            if ($siblingPayment) {
                                try {
                                    $receiptService->generateReceipt($siblingPayment, ['save' => true]);
                                } catch (\Exception $e) {
                                    Log::warning('Receipt generation failed for sibling payment', [
                                        'payment_id' => $siblingPayment->id,
                                        'error' => $e->getMessage()
                                    ]);
                                }
                            }
                        }
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to create payment from transaction', [
                        'transaction_id' => $bankStatement->id,
                        'error' => $e->getMessage(),
                    ]);
                    throw $e;
                }
            }
        });

        return redirect()
            ->route('finance.bank-statements.show', $bankStatement)
            ->with('success', 'Transaction confirmed and payment created');
    }

    /**
     * Reject transaction
     */
    public function reject(BankStatementTransaction $bankStatement)
    {
        $bankStatement->reject();

        return redirect()
            ->route('finance.bank-statements.show', $bankStatement)
            ->with('success', 'Transaction rejected');
    }

    /**
     * Share transaction among siblings
     */
    public function share(Request $request, BankStatementTransaction $bankStatement)
    {
        $validated = $request->validate([
            'allocations' => 'required|array|min:1',
            'allocations.*.student_id' => 'required|exists:students,id',
            'allocations.*.amount' => 'required|numeric|min:0.01',
        ]);

        $totalAmount = array_sum(array_column($validated['allocations'], 'amount'));
        
        if (abs($totalAmount - $bankStatement->amount) > 0.01) {
            return redirect()->back()
                ->withErrors(['allocations' => 'Total allocation amount must equal transaction amount']);
        }

        try {
            $this->parser->shareTransaction($bankStatement, $validated['allocations']);
            
            return redirect()
                ->route('finance.bank-statements.show', $bankStatement)
                ->with('success', 'Transaction shared among siblings');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * View statement PDF (embedded view page)
     */
    public function viewPdf(BankStatementTransaction $bankStatement)
    {
        if (!$bankStatement->statement_file_path) {
            abort(404, 'Statement file not found');
        }

        if (!Storage::disk('private')->exists($bankStatement->statement_file_path)) {
            abort(404, 'Statement file not found');
        }

        return view('finance.bank-statements.view-pdf', compact('bankStatement'));
    }
    
    /**
     * Serve PDF file directly
     */
    public function servePdf(BankStatementTransaction $bankStatement)
    {
        if (!$bankStatement->statement_file_path) {
            abort(404, 'Statement file not found');
        }

        if (!Storage::disk('private')->exists($bankStatement->statement_file_path)) {
            abort(404, 'Statement file not found');
        }

        $path = Storage::disk('private')->path($bankStatement->statement_file_path);
        
        if (!file_exists($path)) {
            abort(404, 'Statement file not found');
        }

        return response()->file($path, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    /**
     * Download statement PDF
     */
    public function downloadPdf(BankStatementTransaction $bankStatement)
    {
        if (!$bankStatement->statement_file_path) {
            abort(404, 'Statement file not found');
        }

        if (!Storage::disk('private')->exists($bankStatement->statement_file_path)) {
            abort(404, 'Statement file not found');
        }

        return Storage::disk('private')->download($bankStatement->statement_file_path, 'bank-statement-' . $bankStatement->id . '.pdf');
    }

    /**
     * Bulk confirm transactions
     */
    public function bulkConfirm(Request $request)
    {
        $validated = $request->validate([
            'transaction_ids' => 'required|array',
            'transaction_ids.*' => 'exists:bank_statement_transactions,id',
        ]);

        $confirmed = 0;
        $errors = [];

        foreach ($validated['transaction_ids'] as $transactionId) {
            try {
                $transaction = BankStatementTransaction::findOrFail($transactionId);
                
                if (!$transaction->student_id && !$transaction->is_shared) {
                    $errors[] = "Transaction #{$transactionId} must be matched before confirming";
                    continue;
                }

                DB::transaction(function () use ($transaction) {
                    $transaction->confirm();
                    if (!$transaction->payment_created) {
                        $payment = $this->parser->createPaymentFromTransaction($transaction);
                        
                        // Generate receipt for the payment
                        try {
                            $receiptService = app(\App\Services\ReceiptService::class);
                            $receiptService->generateReceipt($payment, ['save' => true]);
                        } catch (\Exception $e) {
                            Log::warning('Receipt generation failed for bank statement payment', [
                                'payment_id' => $payment->id,
                                'error' => $e->getMessage()
                            ]);
                        }
                        
                        // If shared payment, generate receipts for all sibling payments
                        if ($transaction->is_shared && $transaction->shared_allocations) {
                            foreach ($transaction->shared_allocations as $allocation) {
                                $siblingPayment = \App\Models\Payment::where('student_id', $allocation['student_id'])
                                    ->where('transaction_code', 'LIKE', $payment->transaction_code . '%')
                                    ->where('id', '!=', $payment->id)
                                    ->first();
                                
                                if ($siblingPayment) {
                                    try {
                                        $receiptService->generateReceipt($siblingPayment, ['save' => true]);
                                    } catch (\Exception $e) {
                                        Log::warning('Receipt generation failed for sibling payment', [
                                            'payment_id' => $siblingPayment->id,
                                            'error' => $e->getMessage()
                                        ]);
                                    }
                                }
                            }
                        }
                    }
                });

                $confirmed++;
            } catch (\Exception $e) {
                $errors[] = "Transaction #{$transactionId}: " . $e->getMessage();
            }
        }

        $message = "Confirmed {$confirmed} transaction(s)";
        if (!empty($errors)) {
            $message .= ". Errors: " . implode(', ', $errors);
        }

        return redirect()
            ->route('finance.bank-statements.index')
            ->with($errors ? 'warning' : 'success', $message);
    }

    /**
     * Archive transaction
     */
    public function archive(BankStatementTransaction $bankStatement)
    {
        $bankStatement->archive();

        return redirect()
            ->route('finance.bank-statements.index', ['view' => 'archived'] + request()->except('view'))
            ->with('success', 'Transaction archived');
    }

    /**
     * Unarchive transaction
     */
    public function unarchive(BankStatementTransaction $bankStatement)
    {
        $bankStatement->unarchive();

        return redirect()
            ->route('finance.bank-statements.index')
            ->with('success', 'Transaction unarchived');
    }

    /**
     * Auto-assign unmatched transactions
     */
    public function autoAssign(Request $request)
    {
        $validated = $request->validate([
            'transaction_ids' => 'nullable|array',
            'transaction_ids.*' => 'exists:bank_statement_transactions,id',
            'auto_confirm' => 'nullable|boolean', // Option to auto-confirm high-confidence matches
        ]);

        $query = BankStatementTransaction::where('match_status', 'unmatched')
            ->where('is_duplicate', false)
            ->where('is_archived', false);

        if (!empty($validated['transaction_ids'])) {
            $query->whereIn('id', $validated['transaction_ids']);
        }

        $transactions = $query->get();
        $matched = 0;
        $confirmed = 0;
        $errors = [];
        $autoConfirm = $validated['auto_confirm'] ?? true; // Default to true for auto-confirmation

        foreach ($transactions as $transaction) {
            try {
                $result = $this->parser->matchTransaction($transaction);
                if ($result['matched']) {
                    $matched++;
                    
                    // Refresh transaction to get updated match_confidence
                    $transaction->refresh();
                    
                    // Auto-confirm high-confidence matches (>= 0.85) if enabled
                    if ($autoConfirm && $transaction->match_confidence >= 0.85 && $transaction->student_id) {
                        try {
                            DB::transaction(function () use ($transaction) {
                                // Confirm the transaction
                                $transaction->confirm();
                                
                                // Create payment if not already created
                                if (!$transaction->payment_created) {
                                    $payment = $this->parser->createPaymentFromTransaction($transaction);
                                    
                                    // Generate receipt for the payment
                                    try {
                                        $receiptService = app(\App\Services\ReceiptService::class);
                                        $receiptService->generateReceipt($payment, ['save' => true]);
                                    } catch (\Exception $e) {
                                        Log::warning('Receipt generation failed for auto-assigned payment', [
                                            'payment_id' => $payment->id ?? null,
                                            'transaction_id' => $transaction->id,
                                            'error' => $e->getMessage()
                                        ]);
                                    }
                                    
                                    // Send payment notifications (SMS, Email, WhatsApp)
                                    try {
                                        $paymentController = app(\App\Http\Controllers\Finance\PaymentController::class);
                                        $paymentController->sendPaymentNotifications($payment);
                                    } catch (\Exception $e) {
                                        Log::warning('Payment notification failed for auto-assigned payment', [
                                            'payment_id' => $payment->id ?? null,
                                            'transaction_id' => $transaction->id,
                                            'error' => $e->getMessage()
                                        ]);
                                        // Don't fail the process if notifications fail
                                    }
                                    
                                    // If shared payment, handle sibling payments
                                    if ($transaction->is_shared && $transaction->shared_allocations) {
                                        foreach ($transaction->shared_allocations as $allocation) {
                                            $siblingPayment = \App\Models\Payment::where('student_id', $allocation['student_id'])
                                                ->where('transaction_code', 'LIKE', $payment->transaction_code . '%')
                                                ->where('id', '!=', $payment->id)
                                                ->first();
                                            
                                            if ($siblingPayment) {
                                                try {
                                                    $receiptService->generateReceipt($siblingPayment, ['save' => true]);
                                                    
                                                    // Send notifications for sibling payment
                                                    try {
                                                        $paymentController->sendPaymentNotifications($siblingPayment);
                                                    } catch (\Exception $e) {
                                                        Log::warning('Payment notification failed for sibling payment', [
                                                            'payment_id' => $siblingPayment->id,
                                                            'error' => $e->getMessage()
                                                        ]);
                                                    }
                                                } catch (\Exception $e) {
                                                    Log::warning('Receipt generation failed for sibling payment', [
                                                        'payment_id' => $siblingPayment->id,
                                                        'error' => $e->getMessage()
                                                    ]);
                                                }
                                            }
                                        }
                                    }
                                    
                                    $confirmed++;
                                }
                            });
                        } catch (\Exception $e) {
                            $errors[] = "Transaction #{$transaction->id} (auto-confirm): " . $e->getMessage();
                            Log::error('Auto-confirm failed for transaction', [
                                'transaction_id' => $transaction->id,
                                'error' => $e->getMessage(),
                                'trace' => $e->getTraceAsString()
                            ]);
                        }
                    }
                }
            } catch (\Exception $e) {
                $errors[] = "Transaction #{$transaction->id}: " . $e->getMessage();
            }
        }

        $message = "Auto-assigned {$matched} out of {$transactions->count()} transactions.";
        if ($confirmed > 0) {
            $message .= " {$confirmed} payment(s) created, receipts generated, and notifications sent.";
        }
        if (!empty($errors)) {
            $message .= " Errors: " . implode(', ', array_slice($errors, 0, 5)); // Limit error display
            if (count($errors) > 5) {
                $message .= " (and " . (count($errors) - 5) . " more)";
            }
        }

        return redirect()
            ->route('finance.bank-statements.index')
            ->with('success', $message);
    }

    /**
     * Reparse/re-analyze statement
     */
    public function reparse(BankStatementTransaction $bankStatement)
    {
        // Store values before deletion
        $pdfPath = $bankStatement->statement_file_path;
        $bankAccountId = $bankStatement->bank_account_id;
        $bankType = $bankStatement->bank_type;

        DB::transaction(function () use ($bankStatement, $pdfPath, $bankAccountId, $bankType) {
            // Get all transactions from the same statement file
            $statementTransactions = BankStatementTransaction::where('statement_file_path', $pdfPath)
                ->get();

            // Delete all related payments
            foreach ($statementTransactions as $transaction) {
                if ($transaction->payment_id) {
                    $payment = \App\Models\Payment::find($transaction->payment_id);
                    if ($payment) {
                        // Delete payment allocations first
                        \App\Models\PaymentAllocation::where('payment_id', $payment->id)->delete();
                        // Delete the payment
                        $payment->delete();
                    }
                }
            }

            // Delete all transactions from this statement
            BankStatementTransaction::where('statement_file_path', $pdfPath)
                ->delete();

            // Re-parse the statement
            $this->parser->parseStatement($pdfPath, $bankAccountId, $bankType);
        });

        return redirect()
            ->route('finance.bank-statements.index')
            ->with('success', 'Statement re-analyzed successfully. All previous transactions and payments have been deleted.');
    }

    /**
     * Delete statement and all related records
     */
    public function destroy(BankStatementTransaction $bankStatement)
    {
        DB::transaction(function () use ($bankStatement) {
            // Get all transactions from the same statement file
            $statementTransactions = BankStatementTransaction::where('statement_file_path', $bankStatement->statement_file_path)
                ->get();

            // Delete all related payments
            foreach ($statementTransactions as $transaction) {
                if ($transaction->payment_id) {
                    $payment = \App\Models\Payment::find($transaction->payment_id);
                    if ($payment) {
                        // Delete payment allocations first
                        \App\Models\PaymentAllocation::where('payment_id', $payment->id)->delete();
                        // Delete the payment
                        $payment->delete();
                    }
                }
            }

            // Delete the PDF file
            if ($bankStatement->statement_file_path && Storage::disk('private')->exists($bankStatement->statement_file_path)) {
                Storage::disk('private')->delete($bankStatement->statement_file_path);
            }

            // Delete all transactions from this statement
            BankStatementTransaction::where('statement_file_path', $bankStatement->statement_file_path)
                ->delete();
        });

        return redirect()
            ->route('finance.bank-statements.index')
            ->with('success', 'Statement and all related records deleted successfully.');
    }
}
