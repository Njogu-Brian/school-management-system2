<?php

namespace App\Http\Controllers\Swimming;

use App\Http\Controllers\Controller;
use App\Models\{Student, Payment, PaymentMethod, BankAccount};
use App\Services\SwimmingWalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SwimmingPaymentController extends Controller
{
    protected $walletService;

    public function __construct(SwimmingWalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    /**
     * Show payment creation form
     */
    public function create(Request $request)
    {
        $paymentMethods = PaymentMethod::active()->orderBy('display_order')->get();
        $bankAccounts = BankAccount::active()->get();
        
        $studentId = $request->query('student_id');
        $student = null;
        
        if ($studentId) {
            $student = Student::withAlumni()->find($studentId);
        }
        
        return view('swimming.payments.create', [
            'paymentMethods' => $paymentMethods,
            'bankAccounts' => $bankAccounts,
            'student' => $student,
        ]);
    }

    /**
     * Store swimming payment
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_method_id' => 'required|exists:payment_methods,id',
            'payment_date' => 'required|date',
            'payer_name' => 'nullable|string|max:255',
            'payer_type' => 'nullable|in:parent,student,other',
            'transaction_code' => 'nullable|string|max:255',
            'narration' => 'nullable|string|max:500',
            'bank_account_id' => 'nullable|exists:bank_accounts,id',
            'share_with_siblings' => 'boolean',
            'sibling_allocations' => 'nullable|array',
            'sibling_allocations.*.student_id' => 'exists:students,id',
            'sibling_allocations.*.amount' => 'numeric|min:0.01',
        ]);

        try {
            return DB::transaction(function () use ($validated) {
                $student = Student::withAlumni()->findOrFail($validated['student_id']);
                $paymentMethod = PaymentMethod::findOrFail($validated['payment_method_id']);
                
                // Handle sibling sharing
                if (!empty($validated['share_with_siblings']) && !empty($validated['sibling_allocations'])) {
                    // Flatten the sibling allocations array (it comes as nested array)
                    $allocations = [];
                    foreach ($validated['sibling_allocations'] as $key => $allocation) {
                        if (is_array($allocation) && isset($allocation['student_id']) && isset($allocation['amount'])) {
                            $allocations[] = $allocation;
                        }
                    }
                    
                    if (empty($allocations)) {
                        return redirect()->back()
                            ->with('error', 'Please allocate amounts to siblings.')
                            ->withInput();
                    }
                    
                    $totalAmount = array_sum(array_column($allocations, 'amount'));
                    
                    if (abs($totalAmount - $validated['amount']) > 0.01) {
                        return redirect()->back()
                            ->with('error', 'Total sibling allocation amount (' . number_format($totalAmount, 2) . ') must equal payment amount (' . number_format($validated['amount'], 2) . ').')
                            ->withInput();
                    }
                    
                    // Create payments for each sibling
                    $createdPayments = [];
                    $transactionCode = $validated['transaction_code'] ?? 'SWIM-' . time();
                    
                    foreach ($allocations as $allocation) {
                        if (empty($allocation['amount']) || $allocation['amount'] <= 0) {
                            continue; // Skip zero allocations
                        }
                        
                        $sibling = Student::findOrFail($allocation['student_id']);
                        
                        $payment = Payment::create([
                            'student_id' => $sibling->id,
                            'family_id' => $sibling->family_id,
                            'amount' => $allocation['amount'],
                            'payment_method_id' => $paymentMethod->id,
                            'payment_method' => $paymentMethod->name,
                            'payment_date' => $validated['payment_date'],
                            'transaction_code' => $transactionCode,
                            'payer_name' => $validated['payer_name'] ?? $sibling->first_name . ' ' . $sibling->last_name,
                            'payer_type' => $validated['payer_type'] ?? 'parent',
                            'narration' => ($validated['narration'] ?? 'Swimming payment') . ' (Shared)',
                            'bank_account_id' => $validated['bank_account_id'] ?? null,
                        ]);
                        
                        // Credit swimming wallet
                        $this->walletService->creditFromTransaction(
                            $sibling,
                            $payment,
                            $allocation['amount'],
                            $validated['narration'] ?? "Swimming payment - {$paymentMethod->name}"
                        );
                        
                        $createdPayments[] = $payment;
                    }
                    
                    return redirect()->route('swimming.wallets.index')
                        ->with('success', 'Swimming payment created and allocated to ' . count($createdPayments) . ' student(s).');
                } else {
                    // Single student payment
                    $payment = Payment::create([
                        'student_id' => $student->id,
                        'family_id' => $student->family_id,
                        'amount' => $validated['amount'],
                        'payment_method_id' => $paymentMethod->id,
                        'payment_method' => $paymentMethod->name,
                        'payment_date' => $validated['payment_date'],
                        'transaction_code' => $validated['transaction_code'] ?? 'SWIM-' . time(),
                        'payer_name' => $validated['payer_name'] ?? $student->first_name . ' ' . $student->last_name,
                        'payer_type' => $validated['payer_type'] ?? 'parent',
                        'narration' => $validated['narration'] ?? 'Swimming payment',
                        'bank_account_id' => $validated['bank_account_id'] ?? null,
                    ]);
                    
                    // Credit swimming wallet
                    $this->walletService->creditFromTransaction(
                        $student,
                        $payment,
                        $validated['amount'],
                        $validated['narration'] ?? "Swimming payment - {$paymentMethod->name}"
                    );
                    
                    return redirect()->route('swimming.wallets.show', $student)
                        ->with('success', 'Swimming payment created and wallet credited successfully.');
                }
            });
        } catch (\Exception $e) {
            Log::error('Failed to create swimming payment', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return redirect()->back()
                ->with('error', 'Failed to create payment: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Get siblings for a student
     */
    public function getSiblings(Student $student)
    {
        $siblings = Student::where('family_id', $student->family_id)
            ->where('id', '!=', $student->id)
            ->where('archive', 0)
            ->where('is_alumni', false)
            ->select('id', 'first_name', 'last_name', 'admission_number')
            ->get();

        return response()->json([
            'siblings' => $siblings->map(function($sibling) {
                return [
                    'id' => $sibling->id,
                    'first_name' => $sibling->first_name,
                    'last_name' => $sibling->last_name,
                    'admission_number' => $sibling->admission_number,
                ];
            })
        ]);
    }
}
