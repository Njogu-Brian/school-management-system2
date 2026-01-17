<?php

namespace App\Http\Controllers\Swimming;

use App\Http\Controllers\Controller;
use App\Models\{SwimmingWallet, Student, Payment};
use App\Services\SwimmingWalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SwimmingWalletController extends Controller
{
    protected $walletService;

    public function __construct(SwimmingWalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    /**
     * List all wallets
     */
    public function index(Request $request)
    {
        $query = SwimmingWallet::with(['student.classroom']);
        
        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('student', function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('admission_number', 'like', "%{$search}%");
            });
        }
        
        // Filter by classroom
        if ($request->filled('classroom_id')) {
            $query->whereHas('student', function($q) use ($request) {
                $q->where('classroom_id', $request->classroom_id);
            });
        }
        
        // Filter by balance
        if ($request->filled('balance_filter')) {
            if ($request->balance_filter === 'positive') {
                $query->where('balance', '>', 0);
            } elseif ($request->balance_filter === 'zero') {
                $query->where('balance', '=', 0);
            } elseif ($request->balance_filter === 'negative') {
                $query->where('balance', '<', 0);
            }
        }
        
        $wallets = $query->orderBy('balance', 'desc')
            ->paginate(50);
        
        $classrooms = \App\Models\Academics\Classroom::orderBy('name')->get();
        
        return view('swimming.wallets.index', [
            'wallets' => $wallets,
            'classrooms' => $classrooms,
            'filters' => $request->only(['search', 'classroom_id', 'balance_filter']),
        ]);
    }

    /**
     * Show wallet details for a student
     */
    public function show(Student $student)
    {
        $wallet = SwimmingWallet::getOrCreateForStudent($student->id);
        $wallet->load(['student', 'ledgerEntries' => function($q) {
            $q->orderBy('created_at', 'desc')->limit(100);
        }]);
        
        return view('swimming.wallets.show', [
            'wallet' => $wallet,
            'student' => $student,
        ]);
    }

    /**
     * Adjust wallet balance (admin only)
     */
    public function adjust(Request $request, Student $student)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'type' => 'required|in:credit,debit',
            'description' => 'required|string|max:500',
        ]);
        
        if (!Auth::user()->hasAnyRole(['Super Admin', 'Admin'])) {
            abort(403, 'Only administrators can adjust wallet balances.');
        }
        
        try {
            if ($request->type === 'credit') {
                $this->walletService->creditFromAdjustment(
                    $student,
                    $request->amount,
                    $request->description,
                    Auth::user()
                );
                $message = "Credited {$request->amount} to wallet.";
            } else {
                // For debit, we need to check balance first
                $wallet = SwimmingWallet::getOrCreateForStudent($student->id);
                if ($wallet->balance < $request->amount) {
                    return redirect()->back()
                        ->with('error', "Insufficient balance. Current balance: {$wallet->balance}");
                }
                
                // Create a negative adjustment (debit)
                $this->walletService->creditFromAdjustment(
                    $student,
                    -$request->amount,
                    $request->description,
                    Auth::user()
                );
                $message = "Debited {$request->amount} from wallet.";
            }
            
            return redirect()->route('swimming.wallets.show', $student)
                ->with('success', $message);
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to adjust wallet: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Credit wallets for students who have paid swimming optional fees
     * This fixes wallets that were empty due to payments made before wallet crediting was implemented
     */
    public function creditFromOptionalFees(Request $request)
    {
        if (!Auth::user()->hasAnyRole(['Super Admin', 'Admin'])) {
            abort(403, 'Only administrators can credit wallets from optional fees.');
        }

        try {
            // Find swimming votehead
            $swimmingVotehead = \App\Models\Votehead::where(function($q) {
                $q->where('name', 'like', '%swimming%')
                  ->orWhere('code', 'like', '%SWIM%');
            })->where('is_mandatory', false)->first();

            if (!$swimmingVotehead) {
                return redirect()->back()
                    ->with('error', 'Swimming votehead not found. Please ensure a swimming optional fee votehead exists.');
            }

            // Get all swimming optional fees that are fully paid but wallets not credited
            $optionalFees = \App\Models\OptionalFee::where('votehead_id', $swimmingVotehead->id)
                ->where('status', 'billed')
                ->with(['student'])
                ->get();

            $credited = 0;
            $skipped = 0;
            $failed = 0;
            $errors = [];

            foreach ($optionalFees as $optionalFee) {
                try {
                    $student = $optionalFee->student;
                    if (!$student) {
                        $skipped++;
                        continue;
                    }

                    // Check if invoice item for this optional fee is fully paid
                    $invoiceItem = \App\Models\InvoiceItem::whereHas('invoice', function($q) use ($student, $optionalFee) {
                        $q->where('student_id', $student->id)
                          ->where('year', $optionalFee->year)
                          ->where('term', $optionalFee->term);
                    })
                    ->where('votehead_id', $optionalFee->votehead_id)
                    ->where('status', 'active')
                    ->first();

                    if (!$invoiceItem) {
                        $skipped++;
                        continue;
                    }

                    // Check if invoice item is fully paid
                    $balance = $invoiceItem->getBalance();
                    if ($balance > 0.01) {
                        $skipped++;
                        continue;
                    }

                    // Check if wallet was already credited for this optional fee
                    $ledgerExists = \App\Models\SwimmingLedger::where('student_id', $student->id)
                        ->where('source', \App\Models\SwimmingLedger::SOURCE_OPTIONAL_FEE)
                        ->where('source_id', $optionalFee->id)
                        ->exists();

                    if ($ledgerExists) {
                        $skipped++;
                        continue;
                    }

                    // Credit wallet
                    $this->walletService->creditFromOptionalFee(
                        $student,
                        $optionalFee,
                        (float) $optionalFee->amount,
                        "Swimming termly fee payment for Term {$optionalFee->term} (backfilled)"
                    );
                    
                    $credited++;
                } catch (\Exception $e) {
                    $failed++;
                    $studentAdmission = $student->admission_number ?? 'Unknown';
                    $errors[] = "Student {$studentAdmission}: {$e->getMessage()}";
                    \Illuminate\Support\Facades\Log::error('Failed to credit swimming wallet from optional fee', [
                        'optional_fee_id' => $optionalFee->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $message = "Credited wallets for {$credited} student(s).";
            if ($skipped > 0) {
                $message .= " Skipped {$skipped} (wallet already credited from optional fee payment, or optional fee invoice item not fully paid yet, or no invoice item found).";
            }
            if ($failed > 0) {
                $message .= " Failed {$failed}.";
                if (count($errors) > 0) {
                    $message .= " Errors: " . implode('; ', array_slice($errors, 0, 3));
                }
            }
            if ($credited == 0 && $skipped > 0) {
                $message .= " Note: All wallets were already credited. No action needed.";
            }

            return redirect()->route('swimming.wallets.index')
                ->with($failed > 0 ? 'warning' : 'success', $message);
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to credit wallets: ' . $e->getMessage());
        }
    }

    /**
     * Process unpaid attendance and debit wallets for students with optional fees
     * This debits wallets for attendance that was marked but not yet paid
     */
    public function processUnpaidAttendance(Request $request)
    {
        if (!Auth::user()->hasAnyRole(['Super Admin', 'Admin'])) {
            abort(403, 'Only administrators can process unpaid attendance.');
        }

        try {
            $attendanceService = app(\App\Services\SwimmingAttendanceService::class);
            $results = $attendanceService->bulkRetryPayments();
            
            $message = "Processed {$results['processed']} attendance record(s).";
            if ($results['insufficient'] > 0) {
                $message .= " {$results['insufficient']} had insufficient wallet balance (wallets need to be credited first).";
            }
            if ($results['failed'] > 0) {
                $message .= " {$results['failed']} failed.";
                if (!empty($results['errors'])) {
                    $message .= " Errors: " . implode('; ', array_slice($results['errors'], 0, 2));
                }
            }
            if ($results['processed'] == 0 && $results['insufficient'] == 0 && $results['failed'] == 0) {
                $message = "No unpaid attendance records found for students with optional fees. This means either:\n";
                $message .= "- All attendance is already paid, OR\n";
                $message .= "- Attendance records don't have termly_fee_covered=true, OR\n";
                $message .= "- Session costs are not set (session_cost = 0)";
            }
            
            return redirect()->route('swimming.wallets.index')
                ->with($results['failed'] > 0 || ($results['processed'] == 0 && $results['insufficient'] == 0) ? 'warning' : 'success', $message);
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to process unpaid attendance: ' . $e->getMessage());
        }
    }

    /**
     * Unallocate all swimming payments from invoices
     */
    public function unallocateSwimmingPayments(Request $request)
    {
        if (!Auth::user()->hasAnyRole(['Super Admin', 'Admin'])) {
            abort(403, 'Only administrators can unallocate swimming payments.');
        }

        try {
            // Find all swimming payments
            $swimmingPayments = Payment::where('receipt_number', 'like', 'SWIM-%')
                ->where('reversed', false)
                ->with(['allocations.invoiceItem.invoice'])
                ->get();

            if ($swimmingPayments->isEmpty()) {
                return redirect()->route('swimming.wallets.index')
                    ->with('info', 'No swimming payments found that need to be unallocated.');
            }

            $totalAllocations = 0;
            $affectedInvoices = collect();
            $reversedPayments = 0;

            DB::transaction(function () use ($swimmingPayments, &$totalAllocations, &$affectedInvoices, &$reversedPayments) {
                foreach ($swimmingPayments as $payment) {
                    $allocations = $payment->allocations;

                    if ($allocations->isEmpty()) {
                        continue;
                    }

                    // Collect invoice IDs
                    foreach ($allocations as $allocation) {
                        if ($allocation->invoiceItem && $allocation->invoiceItem->invoice) {
                            $affectedInvoices->push($allocation->invoiceItem->invoice_id);
                        }
                        $totalAllocations++;
                    }

                    // Delete all allocations
                    \App\Models\PaymentAllocation::where('payment_id', $payment->id)->delete();

                    // Update payment allocation totals
                    $payment->updateAllocationTotals();

                    // Mark payment as reversed since it should not be allocated to invoices
                    $payment->update([
                        'reversed' => true,
                        'reversed_by' => auth()->id(),
                        'reversed_at' => now(),
                        'narration' => ($payment->narration ?? '') . ' (Reversed - Swimming payment should not allocate to invoices)',
                    ]);

                    $reversedPayments++;
                }

                // Recalculate affected invoices
                $uniqueInvoiceIds = $affectedInvoices->unique();
                foreach ($uniqueInvoiceIds as $invoiceId) {
                    $invoice = \App\Models\Invoice::find($invoiceId);
                    if ($invoice) {
                        \App\Services\InvoiceService::recalc($invoice);
                    }
                }
            });

            $message = "Unallocated {$totalAllocations} allocation(s) from {$reversedPayments} swimming payment(s). ";
            $message .= "Recalculated {$affectedInvoices->unique()->count()} affected invoice(s).";

            \Illuminate\Support\Facades\Log::info('Swimming payments unallocated from invoices', [
                'allocations_removed' => $totalAllocations,
                'payments_reversed' => $reversedPayments,
                'invoices_recalculated' => $affectedInvoices->unique()->count(),
                'user_id' => auth()->id(),
            ]);

            return redirect()->route('swimming.wallets.index')
                ->with('success', $message);

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to unallocate swimming payments: ' . $e->getMessage());
        }
    }
}
