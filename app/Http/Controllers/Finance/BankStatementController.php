<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\{
    BankStatementTransaction, BankAccount, Student, Family, MpesaC2BTransaction
};
use App\Services\BankStatementParser;
use App\Services\PaymentAllocationService;
use App\Services\SwimmingTransactionService;
use App\Services\UnifiedTransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BankStatementController extends Controller
{
    protected $parser;
    protected $allocationService;
    protected $swimmingTransactionService;
    protected $unifiedService;

    public function __construct(BankStatementParser $parser, PaymentAllocationService $allocationService)
    {
        $this->parser = $parser;
        $this->allocationService = $allocationService;
        $this->swimmingTransactionService = app(SwimmingTransactionService::class);
        $this->unifiedService = app(UnifiedTransactionService::class);
    }

    /**
     * Display list of imported statement files with summaries
     */
    public function statements(Request $request)
    {
        // Get unique statement files with transaction summaries
        $statements = BankStatementTransaction::select('statement_file_path', 'bank_type', 'bank_account_id')
            ->selectRaw('MIN(created_at) as uploaded_at')
            ->selectRaw('COUNT(*) as total_transactions')
            ->selectRaw('SUM(amount) as total_amount')
            ->selectRaw('SUM(CASE WHEN status = "draft" THEN 1 ELSE 0 END) as draft_count')
            ->selectRaw('SUM(CASE WHEN status = "confirmed" AND payment_created = false THEN 1 ELSE 0 END) as confirmed_count')
            ->selectRaw('SUM(CASE WHEN status = "confirmed" AND payment_created = true THEN 1 ELSE 0 END) as collected_count')
            ->selectRaw('SUM(CASE WHEN is_archived = true THEN 1 ELSE 0 END) as archived_count')
            ->selectRaw('SUM(CASE WHEN is_duplicate = true THEN 1 ELSE 0 END) as duplicate_count')
            ->selectRaw('SUM(CASE WHEN status = "rejected" THEN 1 ELSE 0 END) as rejected_count')
            ->whereNotNull('statement_file_path')
            ->groupBy('statement_file_path', 'bank_type', 'bank_account_id')
            ->orderBy('uploaded_at', 'desc')
            ->paginate(15);

        // Load bank accounts
        $bankAccounts = BankAccount::whereIn('id', $statements->pluck('bank_account_id')->filter())
            ->get()
            ->keyBy('id');

        return view('finance.bank-statements.statements', compact('statements', 'bankAccounts'));
    }

    /**
     * Display list of bank statement transactions
     */
    public function index(Request $request)
    {
        $query = BankStatementTransaction::with(['student', 'family', 'bankAccount', 'payment', 'duplicateOfPayment'])
            ->orderBy('transaction_date', 'desc')
            ->orderBy('created_at', 'desc');

        // View filters (all, auto-assigned, manual-assigned, draft, unassigned, confirmed, collected, archived)
        $view = $request->get('view', 'all');
        $hasSwimmingColumn = Schema::hasColumn('bank_statement_transactions', 'is_swimming_transaction');
        
        // Helper to exclude swimming transactions
        $excludeSwimming = function($q) use ($hasSwimmingColumn) {
            if ($hasSwimmingColumn) {
                $q->where(function($subQ) {
                    $subQ->where('is_swimming_transaction', false)
                         ->orWhereNull('is_swimming_transaction');
                });
            }
        };
        
        switch ($view) {
            case 'auto-assigned':
                $query->where('match_status', 'matched')
                      ->where('match_confidence', '>=', 0.85)
                      ->where('payment_created', false) // Exclude collected transactions
                      ->where('is_duplicate', false)
                      ->where('is_archived', false)
                      ->where('transaction_type', 'credit'); // Only credit transactions
                $excludeSwimming($query);
                break;
            case 'manual-assigned':
                $query->where('match_status', 'manual')
                      ->where('payment_created', false) // Exclude collected transactions
                      ->where('is_duplicate', false)
                      ->where('is_archived', false)
                      ->where('transaction_type', 'credit'); // Only credit transactions
                $excludeSwimming($query);
                break;
            case 'draft':
                // Transactions that system has seen potential but not sure
                // This includes: multiple_matches OR (matched with low confidence)
                $query->where(function($q) {
                    $q->where('match_status', 'multiple_matches')
                      ->orWhere(function($q2) {
                          $q2->where('match_status', 'matched')
                             ->where('match_confidence', '>', 0)
                             ->where('match_confidence', '<', 0.85);
                      });
                })
                ->where('payment_created', false) // Exclude collected transactions
                ->where('is_duplicate', false)
                ->where('is_archived', false)
                ->where('transaction_type', 'credit'); // Only credit transactions
                $excludeSwimming($query);
                break;
            case 'unassigned':
                $query->where('match_status', 'unmatched')
                      ->whereNull('student_id')
                      ->where('is_duplicate', false)
                      ->where('is_archived', false)
                      ->where('transaction_type', 'credit'); // Only credit transactions
                $excludeSwimming($query);
                break;
            case 'confirmed':
                // Confirmed transactions that haven't been collected yet
                $query->where('status', 'confirmed')
                      ->where('payment_created', false) // Exclude collected transactions
                      ->where('is_duplicate', false)
                      ->where('is_archived', false)
                      ->where('transaction_type', 'credit'); // Only credit transactions
                $excludeSwimming($query);
                break;
            case 'collected':
                // Confirmed transactions where payment has been created
                $query->where('status', 'confirmed')
                      ->where('payment_created', true)
                      ->where('is_duplicate', false)
                      ->where('is_archived', false)
                      ->where('transaction_type', 'credit'); // Only credit transactions
                $excludeSwimming($query);
                break;
            case 'duplicate':
                $query->where('is_duplicate', true)
                      ->where('is_archived', false);
                break;
            case 'archived':
                $query->where('is_archived', true);
                break;
            case 'swimming':
                // Swimming transactions (only if column exists)
                if ($hasSwimmingColumn) {
                    $query->where('is_swimming_transaction', true)
                          ->where('is_archived', false);
                } else {
                    // If column doesn't exist, return empty results
                    $query->whereRaw('1 = 0');
                }
                break;
            default:
                // Show all non-archived, non-debit, non-swimming transactions by default
                $query->where('is_archived', false)
                      ->where('transaction_type', 'credit'); // Exclude debit transactions
                $excludeSwimming($query);
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

        if ($request->filled('statement_file')) {
            $query->where('statement_file_path', $request->statement_file);
        }

        if ($request->filled('date_from')) {
            $query->where('transaction_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('transaction_date', '<=', $request->date_to);
        }

        // Swimming transaction filter (only if column exists)
        if ($request->filled('is_swimming') && Schema::hasColumn('bank_statement_transactions', 'is_swimming_transaction')) {
            if ($request->is_swimming == '1') {
                $query->where('is_swimming_transaction', true);
            } elseif ($request->is_swimming == '0') {
                $query->where('is_swimming_transaction', false);
            }
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

        // Get C2B transactions with same filters
        $c2bQuery = $this->getC2BTransactionsQuery($request, $view);
        $c2bTransactions = $c2bQuery->get();
        
        // Check for duplicates across both types
        $this->checkCrossTypeDuplicates($c2bTransactions);
        
        // Combine transactions
        $bankTransactions = $query->get();
        $allTransactions = $bankTransactions->concat($c2bTransactions)
            ->sortByDesc(function($txn) {
                return $txn->trans_time ?? $txn->transaction_date ?? $txn->created_at;
            })
            ->values();
        
        // Paginate manually
        $perPage = 25;
        $currentPage = $request->get('page', 1);
        $items = $allTransactions->slice(($currentPage - 1) * $perPage, $perPage)->values();
        $transactions = new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $allTransactions->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );
        
        $bankAccounts = BankAccount::where('is_active', true)->get();

        // Calculate total amount for current filtered results
        $totalAmount = $bankTransactions->sum('amount') + $c2bTransactions->sum('trans_amount');
        $totalCount = $bankTransactions->count() + $c2bTransactions->count();

        // Get counts for each view (exclude swimming and debit transactions from non-swimming views)
        $hasSwimmingColumn = Schema::hasColumn('bank_statement_transactions', 'is_swimming_transaction');
        
        $counts = [
            'all' => BankStatementTransaction::where('is_archived', false)
                ->where('transaction_type', 'credit') // Exclude debit transactions
                ->when($hasSwimmingColumn, function($q) {
                    $q->where(function($subQ) {
                        $subQ->where('is_swimming_transaction', false)
                             ->orWhereNull('is_swimming_transaction');
                    });
                })
                ->count(),
            'auto-assigned' => BankStatementTransaction::where('match_status', 'matched')
                ->where('match_confidence', '>=', 0.85)
                ->where('payment_created', false) // Exclude collected transactions
                ->where('is_duplicate', false)
                ->where('is_archived', false)
                ->where('transaction_type', 'credit') // Only credit transactions
                ->when($hasSwimmingColumn, function($q) {
                    $q->where(function($subQ) {
                        $subQ->where('is_swimming_transaction', false)
                             ->orWhereNull('is_swimming_transaction');
                    });
                })
                ->count(),
            'manual-assigned' => BankStatementTransaction::where('match_status', 'manual')
                ->where('payment_created', false) // Exclude collected transactions
                ->where('is_duplicate', false)
                ->where('is_archived', false)
                ->where('transaction_type', 'credit') // Only credit transactions
                ->when($hasSwimmingColumn, function($q) {
                    $q->where(function($subQ) {
                        $subQ->where('is_swimming_transaction', false)
                             ->orWhereNull('is_swimming_transaction');
                    });
                })
                ->count(),
            'draft' => BankStatementTransaction::where(function($q) {
                    $q->where('match_status', 'multiple_matches')
                      ->orWhere(function($q2) {
                          $q2->where('match_status', 'matched')
                             ->where('match_confidence', '>', 0)
                             ->where('match_confidence', '<', 0.85);
                      });
                })
                ->where('payment_created', false) // Exclude collected transactions
                ->where('is_duplicate', false)
                ->where('is_archived', false)
                ->where('transaction_type', 'credit') // Only credit transactions
                ->when($hasSwimmingColumn, function($q) {
                    $q->where(function($subQ) {
                        $subQ->where('is_swimming_transaction', false)
                             ->orWhereNull('is_swimming_transaction');
                    });
                })
                ->count(),
            'unassigned' => BankStatementTransaction::where('match_status', 'unmatched')
                ->whereNull('student_id')
                ->where('is_duplicate', false)
                ->where('is_archived', false)
                ->where('transaction_type', 'credit') // Only credit transactions
                ->when($hasSwimmingColumn, function($q) {
                    $q->where(function($subQ) {
                        $subQ->where('is_swimming_transaction', false)
                             ->orWhereNull('is_swimming_transaction');
                    });
                })
                ->count(),
            'confirmed' => BankStatementTransaction::where('status', 'confirmed')
                ->where('payment_created', false) // Exclude collected transactions
                ->where('is_duplicate', false)
                ->where('is_archived', false)
                ->where('transaction_type', 'credit') // Only credit transactions
                ->when($hasSwimmingColumn, function($q) {
                    $q->where(function($subQ) {
                        $subQ->where('is_swimming_transaction', false)
                             ->orWhereNull('is_swimming_transaction');
                    });
                })
                ->count(),
            'collected' => BankStatementTransaction::where('status', 'confirmed')
                ->where('payment_created', true)
                ->where('is_duplicate', false)
                ->where('is_archived', false)
                ->where('transaction_type', 'credit') // Only credit transactions
                ->when($hasSwimmingColumn, function($q) {
                    $q->where(function($subQ) {
                        $subQ->where('is_swimming_transaction', false)
                             ->orWhereNull('is_swimming_transaction');
                    });
                })
                ->count(),
            'duplicate' => BankStatementTransaction::where('is_duplicate', true)
                ->where('is_archived', false)
                ->count(),
            'archived' => BankStatementTransaction::where('is_archived', true)->count(),
            'swimming' => Schema::hasColumn('bank_statement_transactions', 'is_swimming_transaction')
                ? BankStatementTransaction::where('is_swimming_transaction', true)
                    ->where('is_archived', false)
                    ->count()
                : 0,
        ];

        // Add C2B counts to existing counts
        $c2bCounts = $this->getC2BCounts($view);
        foreach ($c2bCounts as $key => $count) {
            $counts[$key] = ($counts[$key] ?? 0) + $count;
        }
        
        return view('finance.bank-statements.index', compact('transactions', 'bankAccounts', 'view', 'counts', 'totalAmount', 'totalCount'));
    }

    /**
     * Get C2B transactions query with filters
     */
    protected function getC2BTransactionsQuery(Request $request, string $view)
    {
        $query = MpesaC2BTransaction::with(['student', 'payment', 'invoice']);

        switch ($view) {
            case 'auto-assigned':
                $query->where('match_confidence', '>=', 80)
                    ->where('allocation_status', 'auto_matched')
                    ->whereNull('payment_id')
                    ->where('is_duplicate', false);
                break;
            case 'manual-assigned':
                $query->where('allocation_status', 'manually_allocated')
                    ->whereNull('payment_id')
                    ->where('is_duplicate', false);
                break;
            case 'unassigned':
                $query->where('allocation_status', 'unallocated')
                    ->whereNull('student_id')
                    ->where('is_duplicate', false);
                break;
            case 'duplicate':
                $query->where('is_duplicate', true);
                break;
            case 'swimming':
                // C2B doesn't have swimming flag, exclude
                $query->whereRaw('1 = 0');
                break;
            default:
                $query->where('is_duplicate', false);
        }

        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('trans_id', 'LIKE', "%{$search}%")
                  ->orWhere('bill_ref_number', 'LIKE', "%{$search}%")
                  ->orWhere('msisdn', 'LIKE', "%{$search}%")
                  ->orWhere('first_name', 'LIKE', "%{$search}%")
                  ->orWhere('last_name', 'LIKE', "%{$search}%")
                  ->orWhereHas('student', function($q) use ($search) {
                      $q->where('admission_number', 'LIKE', "%{$search}%")
                        ->orWhere('first_name', 'LIKE', "%{$search}%")
                        ->orWhere('last_name', 'LIKE', "%{$search}%");
                  });
            });
        }

        // Date filters
        if ($request->filled('date_from')) {
            $query->whereDate('trans_time', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('trans_time', '<=', $request->date_to);
        }

        return $query->orderBy('trans_time', 'desc')->orderBy('created_at', 'desc');
    }

    /**
     * Get C2B transaction counts
     */
    protected function getC2BCounts(string $view): array
    {
        $counts = [
            'all' => MpesaC2BTransaction::where('is_duplicate', false)->count(),
            'auto-assigned' => MpesaC2BTransaction::where('match_confidence', '>=', 80)
                ->where('allocation_status', 'auto_matched')
                ->whereNull('payment_id')
                ->where('is_duplicate', false)
                ->count(),
            'manual-assigned' => MpesaC2BTransaction::where('allocation_status', 'manually_allocated')
                ->whereNull('payment_id')
                ->where('is_duplicate', false)
                ->count(),
            'unassigned' => MpesaC2BTransaction::where('allocation_status', 'unallocated')
                ->whereNull('student_id')
                ->where('is_duplicate', false)
                ->count(),
            'duplicate' => MpesaC2BTransaction::where('is_duplicate', true)->count(),
            'swimming' => 0, // C2B doesn't support swimming
        ];

        return $counts;
    }

    /**
     * Check for duplicates across transaction types
     */
    protected function checkCrossTypeDuplicates($c2bTransactions)
    {
        foreach ($c2bTransactions as $c2bTxn) {
            if ($c2bTxn->is_duplicate) {
                continue;
            }

            // Check against bank statement transactions
            $duplicate = BankStatementTransaction::where('reference_number', $c2bTxn->trans_id)
                ->orWhere(function($q) use ($c2bTxn) {
                    $q->where('amount', $c2bTxn->trans_amount)
                      ->where('transaction_date', $c2bTxn->trans_time->format('Y-m-d'))
                      ->where(function($subQ) use ($c2bTxn) {
                          if ($c2bTxn->msisdn && strlen($c2bTxn->msisdn) > 4) {
                              $subQ->where('phone_number', 'LIKE', '%' . substr($c2bTxn->msisdn, -4) . '%');
                          }
                      });
                })
                ->where('is_duplicate', false)
                ->first();

            if ($duplicate) {
                // Mark C2B as duplicate
                $c2bTxn->update([
                    'is_duplicate' => true,
                    'status' => 'ignored',
                    'allocation_status' => 'duplicate',
                ]);
                
                Log::info('Cross-type duplicate detected', [
                    'c2b_id' => $c2bTxn->id,
                    'bank_id' => $duplicate->id,
                    'trans_code' => $c2bTxn->trans_id,
                ]);
            }
        }
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
        
        // Fix old rejected transactions that still have students assigned
        // This handles transactions rejected before the reject logic was updated
        if ($bankStatement->status === 'rejected' && $bankStatement->student_id) {
            $bankStatement->update([
                'student_id' => null,
                'family_id' => null,
                'match_status' => 'unmatched',
                'match_confidence' => 0,
                'matched_admission_number' => null,
                'matched_student_name' => null,
                'matched_phone_number' => null,
                'match_notes' => 'MANUALLY_REJECTED - Requires manual assignment',
            ]);
            $bankStatement->refresh();
        }
        
        // Get siblings if family exists
        // Only include active, non-archived siblings
        // IMPORTANT: Siblings should ONLY be retrieved via family_id, not through the siblings() relationship
        $siblings = [];
        if ($bankStatement->family_id) {
            $siblings = Student::where('family_id', $bankStatement->family_id)
                ->where('id', '!=', $bankStatement->student_id)
                ->where('archive', 0)
                ->where('is_alumni', false)
                ->get();
        } elseif ($bankStatement->student_id) {
            $student = $bankStatement->student;
            // Get siblings via family_id ONLY (not through siblings() relationship)
            if ($student->family_id) {
                $siblings = Student::where('family_id', $student->family_id)
                    ->where('id', '!=', $student->id)
                    ->where('archive', 0)
                    ->where('is_alumni', false)
                    ->get();
            }
        }

        // Get possible matches if transaction has multiple matches or is unmatched
        $possibleMatches = [];
        if (($bankStatement->match_status === 'multiple_matches' || $bankStatement->match_status === 'unmatched') && !$bankStatement->student_id) {
            try {
                // Re-run matching to get current possible matches
                // This will update the transaction status, but that's okay since we're viewing details
                $matchResult = $this->parser->matchTransaction($bankStatement);
                
                // Refresh to get updated status
                $bankStatement->refresh();
                
                // Get matches from result
                if (isset($matchResult['matches']) && is_array($matchResult['matches'])) {
                    $possibleMatches = $matchResult['matches'];
                }
            } catch (\Exception $e) {
                Log::warning('Failed to get possible matches for transaction', [
                    'transaction_id' => $bankStatement->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return view('finance.bank-statements.show', compact('bankStatement', 'siblings', 'possibleMatches'));
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

            // Clear MANUALLY_REJECTED marker when manually assigned
            $matchNotes = $validated['match_notes'] ?? 'Manually assigned';
            if (strpos($matchNotes, 'MANUALLY_REJECTED') !== false) {
                $matchNotes = 'Manually assigned';
            }
            
            // If assigning a student to a rejected/unmatched transaction, change status to draft
            $newStatus = $bankStatement->status;
            if ($student && in_array($bankStatement->status, ['rejected', 'unmatched'])) {
                $newStatus = 'draft';
            } elseif (!$student && $bankStatement->status === 'draft') {
                // If removing student assignment, keep status as is or set to unmatched
                $newStatus = 'unmatched';
            }
            
            $bankStatement->update([
                'student_id' => $student?->id,
                'family_id' => $student?->family_id,
                'status' => $newStatus,
                'match_status' => $student ? 'manual' : 'unmatched',
                'match_confidence' => $student ? 1.0 : 0,
                'match_notes' => $matchNotes,
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

        // Check if this is a swimming transaction (refresh to get latest value)
        $bankStatement->refresh();
        $isSwimming = Schema::hasColumn('bank_statement_transactions', 'is_swimming_transaction') 
            && $bankStatement->is_swimming_transaction;

        DB::transaction(function () use ($bankStatement, $isSwimming) {
            // Clear MANUALLY_REJECTED marker when confirming (manual assignment)
            $matchNotes = $bankStatement->match_notes ?? '';
            if (strpos($matchNotes, 'MANUALLY_REJECTED') !== false) {
                $matchNotes = $matchNotes ? str_replace('MANUALLY_REJECTED - ', '', $matchNotes) : 'Manually confirmed';
            }
            
            $bankStatement->confirm();
            
            // Update match notes if it had MANUALLY_REJECTED marker
            if (strpos($bankStatement->match_notes ?? '', 'MANUALLY_REJECTED') !== false) {
                $bankStatement->update(['match_notes' => $matchNotes]);
            }

            if ($isSwimming) {
                // Handle swimming transaction - allocate to swimming wallets
                $this->processSwimmingTransaction($bankStatement);
            } else {
                // Create payment for fee allocation if not already created
                if (!$bankStatement->payment_created) {
                try {
                    // Check if payment already exists for this transaction
                    $existingPayment = null;
                    if ($bankStatement->payment_id) {
                        $existingPayment = \App\Models\Payment::find($bankStatement->payment_id);
                    }
                    
                    // Also check by transaction code if reference number exists
                    if (!$existingPayment && $bankStatement->reference_number) {
                        $existingPayment = \App\Models\Payment::where('transaction_code', $bankStatement->reference_number)->first();
                        if ($existingPayment) {
                            // Link the transaction to existing payment
                            $bankStatement->update([
                                'payment_id' => $existingPayment->id,
                                'payment_created' => true,
                            ]);
                        }
                    }
                    
                    if ($existingPayment) {
                        $payment = $existingPayment;
                        Log::info('Using existing payment for transaction', [
                            'transaction_id' => $bankStatement->id,
                            'payment_id' => $payment->id,
                        ]);
                    } else {
                        // Create payment with auto-allocation enabled
                        $payment = $this->parser->createPaymentFromTransaction($bankStatement, false);
                        
                        // Ensure payment is allocated (double-check)
                        if ($payment && $payment->unallocated_amount > 0) {
                            try {
                                $allocationService = app(\App\Services\PaymentAllocationService::class);
                                $allocationService->autoAllocate($payment);
                            } catch (\Exception $e) {
                                Log::warning('Post-creation auto-allocation failed for bank statement payment', [
                                    'payment_id' => $payment->id,
                                    'error' => $e->getMessage(),
                                ]);
                            }
                        }
                    }
                    
                    // Queue receipt generation and notifications for all payments (main + siblings)
                    // This prevents timeout when processing multiple sibling payments
                    // For shared payments, this processes main payment + all siblings in background
                    // For single payments, this processes just the main payment in background
                    \App\Jobs\ProcessSiblingPaymentsJob::dispatch($bankStatement->id, $payment->id)
                        ->onQueue('default');
                } catch (\Exception $e) {
                    Log::error('Failed to create payment from transaction', [
                        'transaction_id' => $bankStatement->id,
                        'error' => $e->getMessage(),
                    ]);
                    throw $e;
                }
            }
            }
        });

        $message = $isSwimming 
            ? 'Transaction confirmed and allocated to swimming wallets'
            : 'Transaction confirmed and payment created';
            
        return redirect()
            ->route('finance.bank-statements.show', $bankStatement)
            ->with('success', $message);
    }

    /**
     * Reject transaction - unmatch it to allow re-matching
     * This does NOT delete or archive, just removes the match
     */
    public function reject(BankStatementTransaction $bankStatement)
    {
        DB::transaction(function () use ($bankStatement) {
            // If payment was created, reverse and delete it
            if ($bankStatement->payment_created && $bankStatement->payment_id) {
                $payment = Payment::find($bankStatement->payment_id);
                if ($payment && !$payment->reversed) {
                    // Reverse payment allocations
                    foreach ($payment->allocations as $allocation) {
                        $allocation->delete();
                    }
                    
                    // Mark payment as reversed
                    $payment->update([
                        'reversed' => true,
                        'reversed_by' => auth()->id(),
                        'reversed_at' => now(),
                    ]);
                    
                    // Delete payment record
                    $payment->delete();
                }
            }
            
            // Unmatch the transaction - set to rejected and unmatched
            // Mark as manually rejected to prevent automatic re-matching
            $bankStatement->update([
                'status' => 'rejected',
                'student_id' => null,
                'family_id' => null,
                'match_status' => 'unmatched',
                'match_confidence' => 0,
                'matched_admission_number' => null,
                'matched_student_name' => null,
                'matched_phone_number' => null,
                'match_notes' => 'MANUALLY_REJECTED - Requires manual assignment',
                'payment_id' => null,
                'payment_created' => false,
                'is_shared' => false,
                'shared_allocations' => null,
                'confirmed_by' => null,
                'confirmed_at' => null,
            ]);
        });

        return redirect()
            ->route('finance.bank-statements.show', $bankStatement)
            ->with('success', 'Transaction rejected and unmatched. It will not be automatically matched again until you manually assign it.');
    }

    /**
     * Update shared allocations (edit amounts)
     */
    public function updateAllocations(Request $request, BankStatementTransaction $bankStatement)
    {
        if (!$bankStatement->is_shared || !$bankStatement->isDraft()) {
            return back()->with('error', 'Can only edit allocations for draft shared transactions');
        }
        
        $validated = $request->validate([
            'allocations' => 'required|array|min:1',
            'allocations.*.student_id' => 'required|exists:students,id',
            'allocations.*.amount' => 'nullable|numeric|min:0', // Allow 0 or empty to exclude siblings
        ]);

        // Filter out allocations with 0 or empty amounts (excluded siblings)
        $activeAllocations = array_filter($validated['allocations'], function($allocation) {
            $amount = $allocation['amount'] ?? 0;
            return !empty($amount) && (float)$amount > 0;
        });

        if (empty($activeAllocations)) {
            return redirect()->back()
                ->withErrors(['allocations' => 'At least one sibling must have an amount greater than 0']);
        }

        // Re-index the array
        $activeAllocations = array_values($activeAllocations);

        $totalAmount = array_sum(array_column($activeAllocations, 'amount'));
        
        if (abs($totalAmount - $bankStatement->amount) > 0.01) {
            return redirect()->back()
                ->withErrors(['allocations' => 'Total allocation amount must equal transaction amount. Current total: Ksh ' . number_format($totalAmount, 2) . ', Required: Ksh ' . number_format($bankStatement->amount, 2)]);
        }

        try {
            $bankStatement->update([
                'shared_allocations' => $activeAllocations,
            ]);
            
            $siblingCount = count($activeAllocations);
            return redirect()
                ->route('finance.bank-statements.show', $bankStatement)
                ->with('success', "Shared allocations updated successfully. Payment shared among {$siblingCount} sibling(s).");
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update allocations: ' . $e->getMessage());
        }
    }
    
    /**
     * Share transaction among siblings
     */
    public function share(Request $request, BankStatementTransaction $bankStatement)
    {
        $validated = $request->validate([
            'allocations' => 'required|array|min:1',
            'allocations.*.student_id' => 'required|exists:students,id',
            'allocations.*.amount' => 'nullable|numeric|min:0', // Allow 0 or empty to exclude siblings
        ]);

        // Filter out allocations with 0 or empty amounts (excluded siblings)
        $activeAllocations = array_filter($validated['allocations'], function($allocation) {
            $amount = $allocation['amount'] ?? 0;
            return !empty($amount) && (float)$amount > 0;
        });

        if (empty($activeAllocations)) {
            return redirect()->back()
                ->withErrors(['allocations' => 'At least one sibling must have an amount greater than 0']);
        }

        // Re-index the array
        $activeAllocations = array_values($activeAllocations);

        $totalAmount = array_sum(array_column($activeAllocations, 'amount'));
        
        if (abs($totalAmount - $bankStatement->amount) > 0.01) {
            return redirect()->back()
                ->withErrors(['allocations' => 'Total allocation amount must equal transaction amount. Current total: Ksh ' . number_format($totalAmount, 2)]);
        }

        try {
            $this->parser->shareTransaction($bankStatement, $activeAllocations);
            
            $siblingCount = count($activeAllocations);
            return redirect()
                ->route('finance.bank-statements.show', $bankStatement)
                ->with('success', "Transaction shared among {$siblingCount} sibling(s).");
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
        // Debug: Log what we're receiving
        \Log::info('Bulk confirm request', [
            'transaction_ids' => $request->input('transaction_ids'),
            'all_input' => $request->all(),
        ]);
        
        // Handle both array and JSON string formats
        $transactionIds = $request->input('transaction_ids', []);
        
        // If it's a JSON string, decode it
        if (is_string($transactionIds)) {
            $transactionIds = json_decode($transactionIds, true) ?? [];
        }
        
        // Ensure it's an array and convert to integers
        if (!is_array($transactionIds)) {
            $transactionIds = [];
        }
        
        $transactionIds = array_filter(array_map('intval', $transactionIds));
        
        if (empty($transactionIds)) {
            return redirect()
                ->route('finance.bank-statements.index')
                ->with('error', 'Please select at least one draft transaction to confirm.');
        }
        
        // Validate that all IDs exist
        $existingIds = BankStatementTransaction::whereIn('id', $transactionIds)->pluck('id')->toArray();
        $invalidIds = array_diff($transactionIds, $existingIds);
        
        if (!empty($invalidIds)) {
            return redirect()
                ->route('finance.bank-statements.index')
                ->with('error', 'Some selected transaction IDs are invalid: ' . implode(', ', $invalidIds));
        }

        $confirmed = 0;
        $errors = [];

        foreach ($transactionIds as $transactionId) {
            try {
                $transaction = BankStatementTransaction::findOrFail($transactionId);
                
                if (!$transaction->student_id && !$transaction->is_shared) {
                    $errors[] = "Transaction #{$transactionId} must be matched before confirming";
                    continue;
                }

                // Allow confirming draft, matched (auto-assigned), and manual-assigned transactions
                // Only skip if already confirmed or rejected
                if ($transaction->status === 'confirmed') {
                    // Already confirmed, skip but don't error
                    continue;
                }
                
                if ($transaction->status === 'rejected') {
                    $errors[] = "Transaction #{$transactionId} is rejected and cannot be confirmed";
                    continue;
                }
                
                // Confirm the transaction
                $transaction->confirm();
                
                // If transaction has student_id or is_shared, ensure match_status is set
                // Don't override if already matched/manual - keep existing status
                if ($transaction->student_id || $transaction->is_shared) {
                    // Only update if match_status is unmatched or null
                    if (!$transaction->match_status || $transaction->match_status === 'unmatched') {
                        $transaction->update(['match_status' => 'manual']);
                    }
                }
                
                // Refresh to get latest swimming status
                $transaction->refresh();
                
                // Check if this is a swimming transaction
                $isSwimming = Schema::hasColumn('bank_statement_transactions', 'is_swimming_transaction') 
                    && $transaction->is_swimming_transaction;
                
                if ($isSwimming) {
                    // Process swimming transaction - allocate to swimming wallets
                    try {
                        $this->processSwimmingTransaction($transaction);
                        Log::info('Bulk confirmed swimming transaction', [
                            'transaction_id' => $transaction->id,
                            'student_id' => $transaction->student_id,
                        ]);
                    } catch (\Exception $e) {
                        $errors[] = "Transaction #{$transactionId} (swimming): " . $e->getMessage();
                        Log::error('Failed to process swimming transaction', [
                            'transaction_id' => $transaction->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                } else {
                    Log::info('Bulk confirmed transaction', [
                        'transaction_id' => $transaction->id,
                        'student_id' => $transaction->student_id,
                    ]);
                }
                
                $confirmed++;
            } catch (\Exception $e) {
                $errors[] = "Transaction #{$transactionId}: " . $e->getMessage();
            }
        }

        $swimmingCount = BankStatementTransaction::whereIn('id', $transactionIds)
            ->where('is_swimming_transaction', true)
            ->where('status', 'confirmed')
            ->count();
        
        $feeCount = $confirmed - $swimmingCount;
        
        $message = "Confirmed {$confirmed} transaction(s).";
        if ($swimmingCount > 0) {
            $message .= " {$swimmingCount} allocated to swimming wallets.";
        }
        if ($feeCount > 0) {
            $message .= " {$feeCount} ready for fee allocation (use Auto-Assign to create payments).";
        }
        if (!empty($errors)) {
            $message .= " Errors: " . implode(', ', array_slice($errors, 0, 5));
        }

        return redirect()
            ->route('finance.bank-statements.index')
            ->with($errors ? 'warning' : 'success', $message);
    }

    /**
     * Bulk archive transactions
     */
    public function bulkArchive(Request $request)
    {
        // Handle both array and JSON string formats
        $transactionIds = $request->input('transaction_ids', []);
        
        // If it's a JSON string, decode it
        if (is_string($transactionIds)) {
            $transactionIds = json_decode($transactionIds, true) ?? [];
        }
        
        // Ensure it's an array and convert to integers
        if (!is_array($transactionIds)) {
            $transactionIds = [];
        }
        
        $transactionIds = array_filter(array_map('intval', $transactionIds));
        
        if (empty($transactionIds)) {
            return redirect()
                ->route('finance.bank-statements.index')
                ->with('error', 'Please select at least one unmatched transaction to archive.');
        }
        
        // Validate that all IDs exist and are unmatched
        $transactions = BankStatementTransaction::whereIn('id', $transactionIds)
            ->where('match_status', 'unmatched')
            ->where('is_archived', false)
            ->whereNull('student_id')
            ->get();
        
        if ($transactions->isEmpty()) {
            return redirect()
                ->route('finance.bank-statements.index')
                ->with('error', 'No unmatched transactions found to archive. Please ensure selected transactions are unmatched and not already archived.');
        }
        
        $archived = 0;
        $errors = [];
        
        foreach ($transactions as $transaction) {
            try {
                $transaction->archive();
                $archived++;
            } catch (\Exception $e) {
                $errors[] = "Transaction #{$transaction->id}: " . $e->getMessage();
                Log::error('Failed to archive transaction', [
                    'transaction_id' => $transaction->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        $message = "Archived {$archived} unmatched transaction(s).";
        if (!empty($errors)) {
            $message .= " Errors: " . implode(', ', array_slice($errors, 0, 5));
        }
        
        return redirect()
            ->route('finance.bank-statements.index', ['view' => 'unassigned'] + request()->except('view'))
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
     * Auto-assign: Creates payments for CONFIRMED transactions only
     * Does NOT confirm transactions - that must be done separately via bulk confirm
     * This prevents timeouts and errors by separating confirmation from payment creation
     */
    public function autoAssign(Request $request)
    {
        // Handle both array and JSON string formats
        $transactionIds = $request->input('transaction_ids', []);
        
        // If it's a JSON string, decode it
        if (is_string($transactionIds)) {
            $transactionIds = json_decode($transactionIds, true) ?? [];
        }
        
        // Ensure it's an array and convert to integers
        if (!is_array($transactionIds)) {
            $transactionIds = [];
        }
        
        $transactionIds = array_filter(array_map('intval', $transactionIds));
        
        // Validate IDs exist if provided
        if (!empty($transactionIds)) {
            $existingIds = BankStatementTransaction::whereIn('id', $transactionIds)->pluck('id')->toArray();
            $invalidIds = array_diff($transactionIds, $existingIds);
            
            if (!empty($invalidIds)) {
                return redirect()
                    ->route('finance.bank-statements.index')
                    ->with('error', 'Some selected transaction IDs are invalid: ' . implode(', ', $invalidIds));
            }
        }
        
        \Log::info('Auto-assign request', [
            'transaction_ids' => $transactionIds,
            'count' => count($transactionIds),
        ]);
        
        // Increase execution time limit for bulk operations (set early)
        set_time_limit(600); // 10 minutes
        ini_set('max_execution_time', 600);
        ini_set('memory_limit', '512M'); // Increase memory limit for bulk operations

        // Only process CONFIRMED transactions that need payment creation
        // Exclude rejected transactions - they should never be auto-assigned
        // Exclude swimming transactions - they are handled during confirmation
        // Get ALL confirmed transactions with student_id or shared allocations, regardless of match_status
        // This includes: matched, manual, multiple_matches, draft (low confidence), and unmatched
        // As long as they have a student_id or are shared, they can be processed
        $hasSwimmingColumn = Schema::hasColumn('bank_statement_transactions', 'is_swimming_transaction');
        
        $matchedQuery = BankStatementTransaction::where('status', 'confirmed')
            ->where('is_duplicate', false)
            ->where('is_archived', false)
            ->where('payment_created', false)
            ->when($hasSwimmingColumn, function($q) {
                $q->where(function($subQ) {
                    $subQ->where('is_swimming_transaction', false)
                         ->orWhereNull('is_swimming_transaction');
                });
            })
            ->where(function($query) {
                $query->whereNotNull('student_id')
                      ->orWhere(function($q) {
                          $q->where('is_shared', true)
                            ->whereNotNull('shared_allocations');
                      });
            });
        
            // Also get confirmed unmatched transactions to try matching first
            // Exclude manually rejected transactions (they require manual assignment)
            // Also exclude transactions that already have payments created
            // Exclude swimming transactions - they are handled during confirmation
            $unmatchedQuery = BankStatementTransaction::where('status', 'confirmed')
                ->where('match_status', 'unmatched')
                ->where('payment_created', false) // Only process unmatched transactions without payments
                ->where('is_duplicate', false)
                ->where('is_archived', false)
                ->when($hasSwimmingColumn, function($q) {
                    $q->where(function($subQ) {
                        $subQ->where('is_swimming_transaction', false)
                             ->orWhereNull('is_swimming_transaction');
                    });
                })
                ->where(function($query) {
                    $query->whereNull('match_notes')
                          ->orWhere('match_notes', 'NOT LIKE', '%MANUALLY_REJECTED%');
                });
        
        // Get all confirmed transactions for re-analysis
        // Exclude rejected transactions and already collected ones
        $allConfirmedQuery = BankStatementTransaction::where('status', 'confirmed')
            ->where('payment_created', false) // Only re-analyze transactions without payments
            ->where('is_duplicate', false)
            ->where('is_archived', false);

        if (!empty($transactionIds)) {
            $unmatchedQuery->whereIn('id', $transactionIds);
            $matchedQuery->whereIn('id', $transactionIds);
            $allConfirmedQuery->whereIn('id', $transactionIds);
        }

        $unmatchedTransactions = $unmatchedQuery->get();
        $matchedTransactions = $matchedQuery->get();
        $allConfirmedTransactions = $allConfirmedQuery->get();
        
        $matched = 0;
        $paymentsCreated = 0;
        $reversed = 0;
        $errors = [];
        
        // For bulk operations (many transactions), skip receipt generation and notifications to avoid timeouts
        // Receipts and notifications can be generated later via a separate process
        $totalTransactions = $matchedTransactions->count();
        $skipReceiptAndNotifications = $totalTransactions > 5; // Skip if processing more than 5 transactions
        
        // Increase execution time limit for bulk operations
        if ($totalTransactions > 5) {
            set_time_limit(600); // 10 minutes for bulk operations
            ini_set('max_execution_time', 600);
        }
        
        // Track payment IDs created during this process for background processing
        $createdPaymentIds = [];
        
        // First, re-analyze ALL confirmed transactions to check for matching issues
        // Skip manually rejected transactions (they require manual assignment)
        foreach ($allConfirmedTransactions as $transaction) {
            try {
                // Skip manually rejected transactions
                if ($transaction->match_notes && strpos($transaction->match_notes, 'MANUALLY_REJECTED') !== false) {
                    continue;
                }
                
                // Store original state BEFORE re-matching
                $originalStudentId = $transaction->student_id;
                $originalMatchStatus = $transaction->match_status;
                $originalPaymentId = $transaction->payment_id;
                $originalPaymentCreated = $transaction->payment_created;
                $originalStatus = $transaction->status;
                
                // Re-run matching
                $result = $this->parser->matchTransaction($transaction);
                $transaction->refresh();
                
                // Check if matching changed for confirmed transactions
                if ($originalStatus === 'confirmed' && $originalPaymentCreated) {
                    $newStudentId = $transaction->student_id;
                    $newMatchStatus = $transaction->match_status;
                    
                    // If student changed or match status changed, we need to reverse the payment
                    if ($originalStudentId !== $newStudentId || 
                        ($originalMatchStatus === 'matched' && $newMatchStatus !== 'matched')) {
                        
                        try {
                            DB::transaction(function () use ($transaction, $originalPaymentId, &$reversed) {
                                // Find and reverse the payment
                                $payment = null;
                                if ($originalPaymentId) {
                                    $payment = \App\Models\Payment::find($originalPaymentId);
                                }
                                
                                if (!$payment && $transaction->reference_number) {
                                    $payment = \App\Models\Payment::where('transaction_code', $transaction->reference_number)->first();
                                }
                                
                                if ($payment && !$payment->reversed) {
                                    // Reverse payment allocations
                                    foreach ($payment->allocations as $allocation) {
                                        $allocation->delete();
                                    }
                                    
                                    // Mark payment as reversed
                                    $payment->update([
                                        'reversed' => true,
                                        'reversed_by' => auth()->id(),
                                        'reversed_at' => now(),
                                    ]);
                                    
                                    // Delete payment record
                                    $payment->delete();
                                    
                                    // Reset transaction state
                                    $transaction->update([
                                        'status' => 'draft',
                                        'payment_created' => false,
                                        'payment_id' => null,
                                    ]);
                                    
                                    $reversed++;
                                    
                                    Log::info('Reversed and deleted payment due to matching change', [
                                        'transaction_id' => $transaction->id,
                                        'original_student_id' => $originalStudentId,
                                        'new_student_id' => $newStudentId,
                                        'payment_id' => $originalPaymentId,
                                    ]);
                                }
                            });
                        } catch (\Exception $e) {
                            $errors[] = "Transaction #{$transaction->id} (reverse payment): " . $e->getMessage();
                            Log::error('Failed to reverse payment for re-matched transaction', [
                                'transaction_id' => $transaction->id,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }
                }
            } catch (\Exception $e) {
                $errors[] = "Transaction #{$transaction->id} (re-analysis): " . $e->getMessage();
                Log::error('Re-analysis failed for transaction', [
                    'transaction_id' => $transaction->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        // Refresh queries after re-analysis (re-run the queries to get updated data)
        if (!empty($transactionIds)) {
            $unmatchedQuery = BankStatementTransaction::where('status', 'confirmed')
                ->where('match_status', 'unmatched')
                ->where('payment_created', false) // Only process unmatched transactions without payments
                ->where('is_duplicate', false)
                ->where('is_archived', false)
                ->whereIn('id', $transactionIds)
                ->when($hasSwimmingColumn, function($q) {
                    $q->where(function($subQ) {
                        $subQ->where('is_swimming_transaction', false)
                             ->orWhereNull('is_swimming_transaction');
                    });
                })
                ->where(function($query) {
                    $query->whereNull('match_notes')
                          ->orWhere('match_notes', 'NOT LIKE', '%MANUALLY_REJECTED%');
                });
            
            // Get ALL confirmed transactions with student_id or shared allocations, regardless of match_status
            // Exclude swimming transactions - they are handled during confirmation
            $matchedQuery = BankStatementTransaction::where('status', 'confirmed')
                ->where('is_duplicate', false)
                ->where('is_archived', false)
                ->where('payment_created', false)
                ->whereIn('id', $transactionIds)
                ->when($hasSwimmingColumn, function($q) {
                    $q->where(function($subQ) {
                        $subQ->where('is_swimming_transaction', false)
                             ->orWhereNull('is_swimming_transaction');
                    });
                })
                ->where(function($query) {
                    $query->whereNotNull('student_id')
                          ->orWhere(function($q) {
                              $q->where('is_shared', true)
                                ->whereNotNull('shared_allocations');
                          });
                });
        }
        
        $unmatchedTransactions = $unmatchedQuery->get();
        $matchedTransactions = $matchedQuery->get();
        
        \Log::info('Auto-assign: Transactions found', [
            'matched_count' => $matchedTransactions->count(),
            'unmatched_count' => $unmatchedTransactions->count(),
            'transaction_ids' => $transactionIds,
        ]);

        // Process unmatched confirmed transactions - try to match them first
        foreach ($unmatchedTransactions as $transaction) {
            try {
                $result = $this->parser->matchTransaction($transaction);
                $transaction->refresh(); // Refresh to get updated match_status and student_id
                
                if ($result['matched']) {
                    $matched++;
                    
                    // If now matched (auto or manual), add to matched transactions list for payment creation
                    if (in_array($transaction->match_status, ['matched', 'manual']) && $transaction->student_id) {
                        // Check if not already in the collection to avoid duplicates
                        if (!$matchedTransactions->contains('id', $transaction->id)) {
                            $matchedTransactions->push($transaction);
                        }
                    }
                }
            } catch (\Exception $e) {
                $errors[] = "Transaction #{$transaction->id}: " . $e->getMessage();
                \Log::error('Failed to match transaction in auto-assign', [
                    'transaction_id' => $transaction->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        // Refresh the matched transactions collection to ensure we have the latest data
        $matchedTransactions = $matchedTransactions->unique('id');

        // Process confirmed matched transactions - CREATE PAYMENTS ONLY (do not confirm)
        // This includes both single payments and shared payments (siblings)
        // Process ALL confirmed transactions with student_id or shared allocations, regardless of match_status
        // For bulk operations, create all payments first without allocations/notifications
        foreach ($matchedTransactions as $transaction) {
            try {
                // Process ALL confirmed transactions that have a student or are shared
                // This includes: matched, manual, multiple_matches, draft, and unmatched (if they have student_id)
                if ($transaction->status === 'confirmed' && 
                    !$transaction->payment_created &&
                    ($transaction->student_id || ($transaction->is_shared && $transaction->shared_allocations))) {
                    
                    // For shared transactions, check if is_shared and has allocations
                    if ($transaction->is_shared && $transaction->shared_allocations) {
                        // Shared payment - will create multiple payments
                        if (count($transaction->shared_allocations) > 0) {
                            // Create payment directly without DB transaction wrapper for speed
                            // DB transactions add overhead - we'll handle errors individually
                            // Skip allocation during creation for bulk operations
                            try {
                                $payment = $this->parser->createPaymentFromTransaction($transaction, true); // Skip allocation
                                if ($payment && $payment->id) {
                                    $createdPaymentIds[] = $payment->id;
                                    // Count all sibling payments created
                                    $siblingPayments = \App\Models\Payment::where('transaction_code', 'LIKE', $payment->transaction_code . '%')
                                        ->where('created_at', '>=', now()->subMinutes(5))
                                        ->get();
                                    $paymentsCreated += $siblingPayments->count();
                                    
                                    // Add all sibling payment IDs for batch allocation
                                    foreach ($siblingPayments as $siblingPayment) {
                                        if (!in_array($siblingPayment->id, $createdPaymentIds)) {
                                            $createdPaymentIds[] = $siblingPayment->id;
                                        }
                                    }
                                }
                            } catch (\Exception $e) {
                                $errors[] = "Transaction #{$transaction->id} (create payment): " . $e->getMessage();
                                Log::error('Payment creation failed for shared transaction', [
                                    'transaction_id' => $transaction->id,
                                    'error' => $e->getMessage(),
                                ]);
                            }
                        }
                    } elseif ($transaction->student_id) {
                        // Single payment - create directly without DB transaction wrapper
                        // Skip allocation during creation for bulk operations
                        try {
                            $payment = $this->parser->createPaymentFromTransaction($transaction, true); // Skip allocation
                            if ($payment && $payment->id) {
                                $createdPaymentIds[] = $payment->id;
                                $paymentsCreated++;
                            }
                        } catch (\Exception $e) {
                            $errors[] = "Transaction #{$transaction->id} (create payment): " . $e->getMessage();
                            Log::error('Payment creation failed for confirmed transaction', [
                                'transaction_id' => $transaction->id,
                                'error' => $e->getMessage(),
                                'trace' => $e->getTraceAsString()
                            ]);
                        }
                    }
                }
            } catch (\Exception $e) {
                $errors[] = "Transaction #{$transaction->id} (create payment): " . $e->getMessage();
                Log::error('Payment creation failed for confirmed transaction', [
                    'transaction_id' => $transaction->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }
        
        // After all payments are created, do allocations in batch (much faster)
        // ALWAYS run batch allocation if we skipped it during payment creation
        // This ensures all payments are allocated regardless of skipReceiptAndNotifications flag
        if (!empty($createdPaymentIds)) {
            try {
                $allocationService = app(\App\Services\PaymentAllocationService::class);
                
                \Log::info('Starting batch allocation', [
                    'payment_count' => count($createdPaymentIds),
                ]);
                
                // Process in smaller batches to avoid memory issues and improve performance
                $allocationBatchSize = 20; // Process 20 payments at a time for allocation
                $paymentChunks = array_chunk($createdPaymentIds, $allocationBatchSize);
                
                foreach ($paymentChunks as $chunk) {
                    // Eager load relationships to reduce database queries
                    $payments = \App\Models\Payment::whereIn('id', $chunk)
                        ->with(['student.invoices.items.votehead']) // Eager load to reduce queries
                        ->get();
                    
                    foreach ($payments as $payment) {
                        try {
                            $allocationService->autoAllocate($payment);
                        } catch (\Exception $e) {
                            Log::warning('Batch auto-allocation failed for payment', [
                                'payment_id' => $payment->id,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }
                    
                    // Small delay between allocation batches to prevent overwhelming the system
                    usleep(100000); // 0.1 seconds
                }
                
                \Log::info('Completed batch allocation', [
                    'payment_count' => count($createdPaymentIds),
                ]);
                
                // Safety check: Allocate any payments that might have been missed
                // This ensures 100% allocation coverage
                $unallocatedPayments = \App\Models\Payment::whereIn('id', $createdPaymentIds)
                    ->where('reversed', false)
                    ->where(function($q) {
                        $q->where('unallocated_amount', '>', 0)
                          ->orWhereRaw('amount > allocated_amount');
                    })
                    ->get();
                
                if ($unallocatedPayments->count() > 0) {
                    \Log::info('Found unallocated payments after batch allocation, allocating now', [
                        'count' => $unallocatedPayments->count(),
                    ]);
                    
                    foreach ($unallocatedPayments as $payment) {
                        try {
                            $allocationService->autoAllocate($payment);
                        } catch (\Exception $e) {
                            Log::warning('Fallback auto-allocation failed for payment', [
                                'payment_id' => $payment->id,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::error('Batch allocation process failed', [
                    'error' => $e->getMessage(),
                    'payment_count' => count($createdPaymentIds),
                ]);
            }
        }
        
        // For bulk operations, process receipts and notifications in batches to avoid timeouts
        // Process after allocations are complete
        if ($skipReceiptAndNotifications && !empty($createdPaymentIds)) {
            // Increase execution time limit for bulk processing
            set_time_limit(600); // 10 minutes for bulk processing
            ini_set('max_execution_time', 600);
            
            // Eager load all payments with relationships to reduce queries
            $payments = \App\Models\Payment::whereIn('id', $createdPaymentIds)
                ->with(['student', 'allocations.invoiceItem'])
                ->get();
            
            $batchSize = 10; // Process 10 payments at a time (increased from 5)
            $receiptService = app(\App\Services\ReceiptService::class);
            $paymentController = app(\App\Http\Controllers\Finance\PaymentController::class);
            
            \Log::info('Starting batch receipt and notification processing', [
                'payment_count' => $payments->count(),
                'batch_size' => $batchSize,
            ]);
            
            foreach ($payments->chunk($batchSize) as $batchIndex => $batch) {
                foreach ($batch as $payment) {
                    try {
                        // Generate receipt (faster with eager loaded relationships)
                        $receiptService->generateReceipt($payment, ['save' => true]);
                    } catch (\Exception $e) {
                        Log::warning('Receipt generation failed for payment', [
                            'payment_id' => $payment->id,
                            'error' => $e->getMessage()
                        ]);
                    }
                    
                    // Send notifications (can be slow, but we continue even if it fails)
                    try {
                        // Refresh payment to ensure we have latest data
                        $payment->refresh();
                        $paymentController->sendPaymentNotifications($payment);
                        \Log::info('Payment notification sent successfully', [
                            'payment_id' => $payment->id,
                            'receipt_number' => $payment->receipt_number,
                        ]);
                    } catch (\Exception $e) {
                        Log::error('Payment notification failed', [
                            'payment_id' => $payment->id,
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        ]);
                        // Continue processing other payments even if notification fails
                    }
                }
                
                // Small delay between batches to prevent overwhelming the system
                if ($batchIndex < $payments->chunk($batchSize)->count() - 1) {
                    usleep(200000); // 0.2 seconds between batches
                }
            }
            
            \Log::info('Completed batch receipt and notification processing', [
                'payment_count' => $payments->count(),
            ]);
        }

        $totalProcessed = $allConfirmedTransactions->count();
        $message = "Processed {$totalProcessed} confirmed transaction(s). ";
        if ($reversed > 0) {
            $message .= "Reversed and deleted {$reversed} incorrect payment(s). ";
        }
        if ($matched > 0) {
            $message .= "Matched {$matched} unmatched transaction(s). ";
        }
        if ($paymentsCreated > 0) {
            if ($skipReceiptAndNotifications) {
                $message .= "{$paymentsCreated} payment(s) created. Receipts and notifications are being processed in the background.";
            } else {
                $message .= "{$paymentsCreated} payment(s) created, receipts generated, and notifications sent.";
            }
        } else {
            $message .= "No payments created. Ensure transactions are confirmed and have a student assigned or are shared.";
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
     * Helper method to create payment, generate receipt, and send notifications
     * @param bool $skipReceiptAndNotifications Skip receipt generation and notifications for bulk operations
     */
    protected function createPaymentAndNotify(BankStatementTransaction $transaction, bool $skipReceiptAndNotifications = false)
    {
        $payment = $this->parser->createPaymentFromTransaction($transaction);
        
        // Payment is automatically allocated to invoices in createPaymentFromTransaction
        // via autoAllocate() call
        
        // Skip receipt generation and notifications for bulk operations to avoid timeouts
        if ($skipReceiptAndNotifications) {
            return $payment;
        }
        
        // Generate receipt for the payment (can be slow for many transactions)
        try {
            $receiptService = app(\App\Services\ReceiptService::class);
            $receiptService->generateReceipt($payment, ['save' => true]);
        } catch (\Exception $e) {
            Log::warning('Receipt generation failed for bank statement payment', [
                'payment_id' => $payment->id ?? null,
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage()
            ]);
        }
        
        // Send payment notifications (SMS, Email, WhatsApp) - can be slow
        try {
            $paymentController = app(\App\Http\Controllers\Finance\PaymentController::class);
            $paymentController->sendPaymentNotifications($payment);
        } catch (\Exception $e) {
            Log::warning('Payment notification failed for bank statement payment', [
                'payment_id' => $payment->id ?? null,
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage()
            ]);
            // Don't fail the process if notifications fail
        }
        
        // If shared payment, handle sibling payments - send individual communications for each
        if ($transaction->is_shared && $transaction->shared_allocations) {
            // Get all sibling payments created for this transaction
            $siblingPayments = \App\Models\Payment::where('transaction_code', 'LIKE', $payment->transaction_code . '%')
                ->where('created_at', '>=', now()->subMinutes(5)) // Payments created in last 5 minutes
                ->get();
            
            foreach ($siblingPayments as $siblingPayment) {
                // Find the allocation for this sibling
                $allocation = collect($transaction->shared_allocations)->firstWhere('student_id', $siblingPayment->student_id);
                
                if ($allocation) {
                    try {
                        // Generate receipt for sibling payment
                        $receiptService->generateReceipt($siblingPayment, ['save' => true]);
                        
                        // Send notifications for sibling payment with their allocated amount
                        try {
                            $paymentController->sendPaymentNotifications($siblingPayment);
                        } catch (\Exception $e) {
                            Log::warning('Payment notification failed for sibling payment', [
                                'payment_id' => $siblingPayment->id,
                                'student_id' => $siblingPayment->student_id,
                                'allocated_amount' => $allocation['amount'],
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
        
        return $payment;
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
     * Retroactively allocate all unallocated payments that have invoices
     * This fixes any payments that were created without allocation
     */
    public function allocateUnallocatedPayments(Request $request)
    {
        $allocated = 0;
        $failed = 0;
        $errors = [];
        
        // Get all unallocated payments (not just from bank statements)
        $unallocatedPayments = \App\Models\Payment::where('reversed', false)
            ->where(function($q) {
                $q->where('unallocated_amount', '>', 0)
                  ->orWhereRaw('amount > allocated_amount');
            })
            ->with(['student.invoices.items'])
            ->get();
        
        foreach ($unallocatedPayments as $payment) {
            try {
                // Only allocate if student exists and has outstanding invoices
                if ($payment->student_id && $payment->student) {
                    // Check if student has any unpaid invoice items
                    $hasUnpaidItems = \App\Models\InvoiceItem::whereHas('invoice', function($q) use ($payment) {
                        $q->where('student_id', $payment->student_id)
                          ->where('status', '!=', 'paid');
                    })
                    ->where('status', 'active')
                    ->get()
                    ->filter(function($item) {
                        return $item->getBalance() > 0;
                    })
                    ->isNotEmpty();
                    
                    if ($hasUnpaidItems) {
                        $this->allocationService->autoAllocate($payment);
                        $allocated++;
                    } else {
                        // No unpaid items - this is an overpayment, which is fine
                        Log::info('Payment has no unpaid items to allocate', [
                            'payment_id' => $payment->id,
                            'student_id' => $payment->student_id,
                        ]);
                    }
                } else {
                    $errors[] = "Payment #{$payment->id}: No student associated";
                    $failed++;
                }
            } catch (\Exception $e) {
                $errors[] = "Payment #{$payment->id}: " . $e->getMessage();
                $failed++;
                Log::warning('Retroactive allocation failed for payment', [
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        $message = "Allocated {$allocated} payment(s)";
        if ($failed > 0) {
            $message .= ". {$failed} payment(s) failed to allocate.";
        }
        
        return redirect()
            ->route('finance.bank-statements.index')
            ->with('success', $message)
            ->with('errors', $errors);
    }

    /**
     * Mark transactions as swimming transactions (bulk)
     */
    public function bulkMarkAsSwimming(Request $request)
    {
        $request->validate([
            'transaction_ids' => 'required|array',
            'transaction_ids.*' => 'exists:bank_statement_transactions,id',
        ]);

        $transactionIds = $request->input('transaction_ids', []);
        
        // Check if column exists before using it
        $hasSwimmingColumn = Schema::hasColumn('bank_statement_transactions', 'is_swimming_transaction');
        
        $query = BankStatementTransaction::whereIn('id', $transactionIds)
            ->where('is_duplicate', false)
            ->where('is_archived', false);
        
        // Only filter by is_swimming_transaction if column exists
        if ($hasSwimmingColumn) {
            $query->where(function($q) {
                $q->where('is_swimming_transaction', false)
                  ->orWhereNull('is_swimming_transaction');
            });
        }
        
        $transactions = $query->get();

        if ($transactions->isEmpty()) {
            return redirect()
                ->route('finance.bank-statements.index')
                ->with('error', 'No valid transactions found to mark as swimming. Transactions may already be marked as swimming or are duplicates/archived.');
        }

        $marked = 0;
        $errors = [];
        
        foreach ($transactions as $transaction) {
            try {
                // Skip if already marked as swimming (double-check)
                if ($hasSwimmingColumn && $transaction->is_swimming_transaction) {
                    continue;
                }
                
                $this->swimmingTransactionService->markAsSwimming($transaction);
                $marked++;
                
                // If transaction is already confirmed, process it immediately
                if ($transaction->status === 'confirmed' && $transaction->student_id) {
                    try {
                        $this->processSwimmingTransaction($transaction);
                    } catch (\Exception $e) {
                        Log::warning('Failed to process already-confirmed swimming transaction', [
                            'transaction_id' => $transaction->id,
                            'error' => $e->getMessage(),
                        ]);
                        // Don't add to errors - marking succeeded, processing can be retried
                    }
                }
            } catch (\Exception $e) {
                $errors[] = "Transaction #{$transaction->id}: " . $e->getMessage();
                Log::error('Failed to mark transaction as swimming', [
                    'transaction_id' => $transaction->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $message = "Marked {$marked} transaction(s) as swimming payments.";
        if ($marked > 0) {
            $confirmedProcessed = BankStatementTransaction::whereIn('id', $transactionIds)
                ->where('status', 'confirmed')
                ->where('is_swimming_transaction', true)
                ->count();
            if ($confirmedProcessed > 0) {
                $message .= " Processing {$confirmedProcessed} confirmed transaction(s) for wallet allocation.";
            }
        }
        if (!empty($errors)) {
            $message .= " Errors: " . implode(', ', array_slice($errors, 0, 3));
        }

        return redirect()
            ->route('finance.bank-statements.index')
            ->with($errors ? 'warning' : 'success', $message);
    }

    /**
     * Unmark individual transaction as swimming
     */
    public function unmarkAsSwimming(Request $request, BankStatementTransaction $bankStatement)
    {
        // Check if column exists
        $hasSwimmingColumn = Schema::hasColumn('bank_statement_transactions', 'is_swimming_transaction');
        
        if (!$hasSwimmingColumn) {
            return redirect()->back()
                ->with('error', 'Swimming transaction column does not exist.');
        }

        if (!$bankStatement->is_swimming_transaction) {
            return redirect()->back()
                ->with('error', 'Transaction is not marked as swimming.');
        }

        try {
            $this->swimmingTransactionService->unmarkAsSwimming($bankStatement);
            
            return redirect()
                ->route('finance.bank-statements.show', $bankStatement)
                ->with('success', 'Transaction unmarked as swimming successfully.');
                
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Failed to unmark transaction: ' . $e->getMessage());
        }
    }

    /**
     * Bulk transfer collected payments to swimming
     */
    public function bulkTransferToSwimming(Request $request)
    {
        $request->validate([
            'transaction_ids' => 'required|array',
            'transaction_ids.*' => 'exists:bank_statement_transactions,id',
        ]);

        $transactionIds = $request->input('transaction_ids', []);
        $transactions = BankStatementTransaction::whereIn('id', $transactionIds)
            ->where('status', 'confirmed')
            ->where('payment_created', true)
            ->where('is_swimming_transaction', false)
            ->where('is_duplicate', false)
            ->where('is_archived', false)
            ->with(['payment', 'student'])
            ->get();

        if ($transactions->isEmpty()) {
            return redirect()
                ->route('finance.bank-statements.index')
                ->with('error', 'No valid collected payments found to transfer to swimming.');
        }

        $transferred = 0;
        $errors = [];

        foreach ($transactions as $transaction) {
            try {
                DB::transaction(function () use ($transaction, &$transferred) {
                    $payment = $transaction->payment;
                    
                    if (!$payment) {
                        throw new \Exception('Payment not found for transaction');
                    }

                    // Reverse the payment (remove allocations from invoices)
                    if ($payment->allocated_amount > 0) {
                        // Get all allocations
                        $allocations = \App\Models\PaymentAllocation::where('payment_id', $payment->id)->get();
                        $invoiceIds = $allocations->pluck('invoice_item_id')
                            ->map(function($itemId) {
                                return \App\Models\InvoiceItem::find($itemId)?->invoice_id;
                            })
                            ->filter()
                            ->unique();

                        // Delete allocations
                        \App\Models\PaymentAllocation::where('payment_id', $payment->id)->delete();

                        // Recalculate affected invoices
                        foreach ($invoiceIds as $invoiceId) {
                            $invoice = \App\Models\Invoice::find($invoiceId);
                            if ($invoice) {
                                \App\Services\InvoiceService::recalc($invoice);
                            }
                        }
                    }

                    // Mark payment as reversed
                    $payment->update([
                        'reversed' => true,
                        'narration' => ($payment->narration ?? '') . ' (Reversed - Transferred to Swimming)',
                    ]);

                    // Mark transaction as swimming
                    $transaction->update([
                        'is_swimming_transaction' => true,
                        'payment_created' => false, // Reset so it can be recreated for swimming
                    ]);

                    // Create swimming allocations
                    if ($transaction->is_shared && $transaction->shared_allocations) {
                        // Shared payment - allocate to multiple students
                        $allocations = [];
                        foreach ($transaction->shared_allocations as $allocation) {
                            $allocations[] = [
                                'student_id' => $allocation['student_id'],
                                'amount' => $allocation['amount'],
                            ];
                        }
                        $this->swimmingTransactionService->allocateToStudents($transaction, $allocations);
                    } elseif ($transaction->student_id) {
                        // Single student payment
                        $this->swimmingTransactionService->allocateToStudents($transaction, [
                            ['student_id' => $transaction->student_id, 'amount' => $transaction->amount]
                        ]);
                    }

                    // Process allocations to credit wallets
                    $this->swimmingTransactionService->processPendingAllocations();

                    $transferred++;
                });
            } catch (\Exception $e) {
                $errors[] = "Transaction #{$transaction->id}: " . $e->getMessage();
                Log::error('Failed to transfer payment to swimming', [
                    'transaction_id' => $transaction->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $message = "Transferred {$transferred} payment(s) to swimming.";
        if (!empty($errors)) {
            $message .= " Errors: " . implode(', ', array_slice($errors, 0, 5));
        }

        return redirect()
            ->route('finance.bank-statements.index')
            ->with($errors ? 'warning' : 'success', $message);
    }

    /**
     * Bulk transfer swimming payments back to ordinary payments
     */
    public function bulkTransferFromSwimming(Request $request)
    {
        $request->validate([
            'transaction_ids' => 'required|array',
            'transaction_ids.*' => 'exists:bank_statement_transactions,id',
        ]);

        $transactionIds = $request->input('transaction_ids', []);
        $transactions = BankStatementTransaction::whereIn('id', $transactionIds)
            ->where('status', 'confirmed')
            ->where('is_swimming_transaction', true)
            ->where('is_duplicate', false)
            ->where('is_archived', false)
            ->with(['swimmingAllocations', 'student'])
            ->get();

        if ($transactions->isEmpty()) {
            return redirect()
                ->route('finance.bank-statements.index')
                ->with('error', 'No valid swimming transactions found to transfer back to ordinary payments.');
        }

        $transferred = 0;
        $errors = [];

        foreach ($transactions as $transaction) {
            try {
                DB::transaction(function () use ($transaction, &$transferred) {
                    // Get swimming allocations
                    $swimmingAllocations = $transaction->swimmingAllocations()
                        ->where('status', '!=', \App\Models\SwimmingTransactionAllocation::STATUS_REVERSED)
                        ->get();

                    if ($swimmingAllocations->isEmpty()) {
                        throw new \Exception('No swimming allocations found for transaction');
                    }

                    // Reverse swimming wallet allocations
                    foreach ($swimmingAllocations as $allocation) {
                        if ($allocation->status === \App\Models\SwimmingTransactionAllocation::STATUS_PROCESSED) {
                            // Debit the wallet if it was credited
                            $wallet = \App\Models\SwimmingWallet::where('student_id', $allocation->student_id)->first();
                            if ($wallet) {
                                $oldBalance = $wallet->balance;
                                $newBalance = $oldBalance - $allocation->amount;
                                
                                // Update wallet balance and totals
                                $wallet->update([
                                    'balance' => $newBalance,
                                    'total_debited' => $wallet->total_debited + $allocation->amount,
                                    'last_transaction_at' => now(),
                                ]);
                                
                                // Create a ledger entry to record the debit
                                if (Schema::hasTable('swimming_ledgers')) {
                                    $student = \App\Models\Student::find($allocation->student_id);
                                    if ($student) {
                                        \App\Models\SwimmingLedger::create([
                                            'student_id' => $allocation->student_id,
                                            'type' => \App\Models\SwimmingLedger::TYPE_DEBIT,
                                            'amount' => $allocation->amount,
                                            'balance_after' => $newBalance,
                                            'source' => \App\Models\SwimmingLedger::SOURCE_ADJUSTMENT,
                                            'description' => 'Payment transferred from swimming to ordinary payments - ' . ($transaction->reference_number ?? 'N/A'),
                                            'created_by' => auth()->id(),
                                        ]);
                                    }
                                }
                            }
                        }
                        
                        // Mark allocation as reversed
                        $allocation->update([
                            'status' => \App\Models\SwimmingTransactionAllocation::STATUS_REVERSED,
                            'reversed_at' => now(),
                            'reversed_by' => auth()->id(),
                        ]);
                    }

                    // Mark transaction as NOT swimming
                    $transaction->update([
                        'is_swimming_transaction' => false,
                        'payment_created' => false, // Reset so payment can be recreated
                    ]);

                    // If transaction has a student assigned, create payment for ordinary allocation
                    if ($transaction->student_id || ($transaction->is_shared && $transaction->shared_allocations)) {
                        // Create payment using the parser service
                        $payment = $this->parser->createPaymentFromTransaction($transaction, false);
                        
                        if ($payment) {
                            // Auto-allocate to invoices
                            $allocationService = app(\App\Services\PaymentAllocationService::class);
                            $allocationService->autoAllocate($payment);
                            
                            // Queue receipt generation and notifications
                            \App\Jobs\ProcessSiblingPaymentsJob::dispatch($transaction->id, $payment->id)
                                ->onQueue('default');
                        }
                    }

                    $transferred++;
                });
            } catch (\Exception $e) {
                $errors[] = "Transaction #{$transaction->id}: " . $e->getMessage();
                Log::error('Failed to transfer payment from swimming', [
                    'transaction_id' => $transaction->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $message = "Transferred {$transferred} payment(s) from swimming back to ordinary payments.";
        if (!empty($errors)) {
            $message .= " Errors: " . implode(', ', array_slice($errors, 0, 5));
        }

        return redirect()
            ->route('finance.bank-statements.index')
            ->with($errors ? 'warning' : 'success', $message);
    }

    /**
     * Process swimming transaction - allocate to swimming wallets
     */
    protected function processSwimmingTransaction(BankStatementTransaction $transaction): void
    {
        // Check if already allocated
        $hasSwimmingColumn = Schema::hasColumn('bank_statement_transactions', 'is_swimming_transaction');
        if (!$hasSwimmingColumn) {
            Log::warning('is_swimming_transaction column does not exist on bank_statement_transactions table.');
            return;
        }
        
        // Ensure transaction is marked as swimming
        if (!$transaction->is_swimming_transaction) {
            $transaction->update(['is_swimming_transaction' => true]);
        }
        
        // Check if allocations table exists
        if (!Schema::hasTable('swimming_transaction_allocations')) {
            Log::warning('swimming_transaction_allocations table does not exist. Please run migrations.');
            return;
        }
        
        // Check if already processed
        $existingAllocations = \App\Models\SwimmingTransactionAllocation::where('bank_statement_transaction_id', $transaction->id)
            ->where('status', '!=', \App\Models\SwimmingTransactionAllocation::STATUS_REVERSED)
            ->get();
        
        if ($existingAllocations->isNotEmpty()) {
            // Check if there are pending allocations to process
            $pendingAllocations = $existingAllocations->where('status', \App\Models\SwimmingTransactionAllocation::STATUS_PENDING);
            if ($pendingAllocations->isNotEmpty()) {
                // Process pending allocations
                $this->swimmingTransactionService->processPendingAllocations();
            }
            return; // Already allocated
        }
        
        // Create allocations based on student assignment
        $allocations = [];
        
        if ($transaction->is_shared && $transaction->shared_allocations) {
            // Shared payment - allocate to multiple students
            foreach ($transaction->shared_allocations as $allocation) {
                $allocations[] = [
                    'student_id' => $allocation['student_id'],
                    'amount' => $allocation['amount'],
                ];
            }
        } elseif ($transaction->student_id) {
            // Single student payment
            $allocations[] = [
                'student_id' => $transaction->student_id,
                'amount' => $transaction->amount,
            ];
        }
        
        if (empty($allocations)) {
            throw new \Exception('No students assigned to transaction');
        }
        
        // Create swimming allocations
        $this->swimmingTransactionService->allocateToStudents($transaction, $allocations);
        
        // Process allocations to credit wallets
        $results = $this->swimmingTransactionService->processPendingAllocations();
        
        Log::info('Swimming transaction processed', [
            'transaction_id' => $transaction->id,
            'allocations_count' => count($allocations),
            'processed' => $results['processed'] ?? 0,
            'failed' => $results['failed'] ?? 0,
            'errors' => $results['errors'] ?? [],
        ]);
        
        // Log any errors
        if (!empty($results['errors'])) {
            foreach ($results['errors'] as $error) {
                Log::error('Swimming allocation processing error', [
                    'transaction_id' => $transaction->id,
                    'error' => $error,
                ]);
            }
        }
    }

    /**
     * Allocate swimming transaction to students
     */
    public function allocateSwimmingTransaction(Request $request, BankStatementTransaction $transaction)
    {
        $request->validate([
            'allocations' => 'required|array|min:1',
            'allocations.*.student_id' => 'required|exists:students,id',
            'allocations.*.amount' => 'required|numeric|min:0.01',
        ]);

        try {
            $allocations = $this->swimmingTransactionService->allocateToStudents(
                $transaction,
                $request->allocations
            );

            // Process pending allocations to credit wallets
            $this->swimmingTransactionService->processPendingAllocations();

            return redirect()
                ->route('finance.bank-statements.show', $transaction)
                ->with('success', 'Transaction allocated to swimming wallets successfully.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Failed to allocate transaction: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Reprocess confirmed swimming transactions
     * This helps fix transactions that were confirmed before being marked as swimming
     */
    public function reprocessSwimmingTransactions(Request $request)
    {
        $request->validate([
            'transaction_ids' => 'nullable|array',
            'transaction_ids.*' => 'exists:bank_statement_transactions,id',
        ]);
        
        $transactionIds = $request->input('transaction_ids', []);
        
        // If no IDs provided, find all confirmed swimming transactions that might need processing
        if (empty($transactionIds)) {
            $query = BankStatementTransaction::where('status', 'confirmed')
                ->where('is_swimming_transaction', true)
                ->where(function($q) {
                    $q->whereNotNull('student_id')
                      ->orWhere('is_shared', true);
                });
            
            // Only get transactions that might not have been processed
            if (Schema::hasTable('swimming_transaction_allocations')) {
                $processedIds = \App\Models\SwimmingTransactionAllocation::where('status', '!=', \App\Models\SwimmingTransactionAllocation::STATUS_REVERSED)
                    ->pluck('bank_statement_transaction_id')
                    ->unique();
                $query->whereNotIn('id', $processedIds);
            }
            
            $transactions = $query->get();
        } else {
            $transactions = BankStatementTransaction::whereIn('id', $transactionIds)
                ->where('status', 'confirmed')
                ->where('is_swimming_transaction', true)
                ->get();
        }
        
        if ($transactions->isEmpty()) {
            return redirect()
                ->route('finance.bank-statements.index')
                ->with('info', 'No confirmed swimming transactions found that need processing.');
        }
        
        $processed = 0;
        $errors = [];
        
        foreach ($transactions as $transaction) {
            try {
                $this->processSwimmingTransaction($transaction);
                $processed++;
            } catch (\Exception $e) {
                $errors[] = "Transaction #{$transaction->id}: " . $e->getMessage();
                Log::error('Failed to reprocess swimming transaction', [
                    'transaction_id' => $transaction->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        $message = "Reprocessed {$processed} swimming transaction(s).";
        if (!empty($errors)) {
            $message .= " Errors: " . implode(', ', array_slice($errors, 0, 5));
        }
        
        return redirect()
            ->route('finance.bank-statements.index')
            ->with($errors ? 'warning' : 'success', $message);
    }

    /**
     * Get student balance (fee or swimming)
     */
    public function getStudentBalance(Student $student, Request $request)
    {
        $isSwimming = $request->get('swimming', false);
        
        if ($isSwimming) {
            $wallet = \App\Models\SwimmingWallet::getOrCreateForStudent($student->id);
            return response()->json([
                'balance' => $wallet->balance ?? 0,
                'label' => 'Swimming Balance'
            ]);
        } else {
            $balance = \App\Services\StudentBalanceService::getTotalOutstandingBalance($student);
            return response()->json([
                'balance' => $balance,
                'label' => 'Balance'
            ]);
        }
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
