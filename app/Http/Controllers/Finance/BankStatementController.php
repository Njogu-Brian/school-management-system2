<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\{
    BankStatementTransaction, BankAccount, Student, Family, MpesaC2BTransaction, Payment
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
        
        switch ($view) {
            case 'auto-assigned':
                $query->where('match_status', 'matched')
                      ->where('match_confidence', '>=', 0.85)
                      ->where('payment_created', false) // Exclude collected transactions
                      ->where('is_duplicate', false)
                      ->where('is_archived', false)
                      ->where('transaction_type', 'credit'); // Only credit transactions
                break;
            case 'manual-assigned':
                $query->where('match_status', 'manual')
                      ->where('payment_created', false) // Exclude collected transactions
                      ->where('is_duplicate', false)
                      ->where('is_archived', false)
                      ->where('transaction_type', 'credit'); // Only credit transactions
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
                break;
            case 'unassigned':
                $query->where('match_status', 'unmatched')
                      ->whereNull('student_id')
                      ->where('is_duplicate', false)
                      ->where('is_archived', false)
                      ->where('transaction_type', 'credit'); // Only credit transactions
                break;
            case 'confirmed':
                // Confirmed transactions that haven't been collected yet
                $query->where('status', 'confirmed')
                      ->where('payment_created', false) // Exclude collected transactions
                      ->where('is_duplicate', false)
                      ->where('is_archived', false)
                      ->where('transaction_type', 'credit'); // Only credit transactions
                break;
            case 'collected':
                // Confirmed transactions where payment has been created and NOT reversed
                $query->where('status', 'confirmed')
                      ->where('payment_created', true)
                      ->where('is_duplicate', false)
                      ->where('is_archived', false)
                      ->where('transaction_type', 'credit') // Only credit transactions
                      ->where(function($q) {
                          // Primary check: payment_id exists and payment is not reversed
                          $q->where(function($subQ) {
                              $subQ->whereNotNull('payment_id')
                                   ->whereHas('payment', function($paymentQ) {
                                       $paymentQ->where('reversed', false)
                                                ->whereNull('deleted_at');
                                   });
                          })
                          ->orWhere(function($subQ) {
                              // Fallback: check by reference number for shared payments
                              $subQ->whereNotNull('reference_number')
                                   ->whereExists(function($existsQ) {
                                       $existsQ->select(\DB::raw(1))
                                               ->from('payments')
                                               ->whereColumn('payments.transaction_code', 'bank_statement_transactions.reference_number')
                                               ->orWhere('payments.transaction_code', 'LIKE', \DB::raw("CONCAT(bank_statement_transactions.reference_number, '-%')"))
                                               ->where('payments.reversed', false)
                                               ->whereNull('payments.deleted_at');
                                   });
                          });
                      });
                break;
            case 'duplicate':
                $query->where('is_duplicate', true)
                      ->where('is_archived', false);
                break;
            case 'archived':
                // Archived transactions - only credit (money IN) transactions
                $query->where('is_archived', true)
                      ->where('transaction_type', 'credit'); // Only money IN transactions
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
            case 'all':
                // All transactions - exclude swimming, archived, and duplicates
                $query->where('is_archived', false)
                      ->where('is_duplicate', false)
                      ->where('transaction_type', 'credit'); // Exclude debit transactions
                // Swimming exclusion is handled below
                break;
            default:
                // Default to 'all' behavior
                $query->where('is_archived', false)
                      ->where('is_duplicate', false)
                      ->where('transaction_type', 'credit'); // Exclude debit transactions
                // Swimming exclusion is handled below
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
        
        // CRITICAL: Apply swimming exclusion as the FINAL constraint after all other filters
        // Swimming transactions MUST ONLY appear in 'swimming' view - this cannot be overridden
        if ($view !== 'swimming' && $hasSwimmingColumn) {
            $query->where(function($q) {
                $q->where('is_swimming_transaction', false)
                  ->orWhereNull('is_swimming_transaction');
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
        
        // Paginate manually with per-page option
        $perPageOptions = [20, 50, 100, 200];
        $perPage = $request->get('per_page', 25);
        // Validate per_page value
        if (!in_array($perPage, $perPageOptions)) {
            $perPage = 25; // Default fallback
        }
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
        // Only show total for 'all' and 'swimming' views
        $totalAmount = null;
        $totalCount = null;
        if ($view === 'all' || $view === 'swimming') {
            $totalAmount = $bankTransactions->sum('amount') + $c2bTransactions->sum('trans_amount');
            $totalCount = $bankTransactions->count() + $c2bTransactions->count();
        } elseif ($view === 'archived') {
            // For archived, only calculate total for credit (money IN) transactions
            $totalAmount = $bankTransactions->where('transaction_type', 'credit')->sum('amount') 
                         + $c2bTransactions->sum('trans_amount'); // C2B are always credit
            $totalCount = $bankTransactions->where('transaction_type', 'credit')->count() 
                        + $c2bTransactions->count();
        }

        // Get counts for each view (exclude swimming and debit transactions from non-swimming views)
        $hasSwimmingColumn = Schema::hasColumn('bank_statement_transactions', 'is_swimming_transaction');
        
        $counts = [
            'all' => BankStatementTransaction::where('is_archived', false)
                ->where('is_duplicate', false)
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
                ->where(function($q) {
                    // Primary check: payment_id exists and payment is not reversed
                    $q->where(function($subQ) {
                        $subQ->whereNotNull('payment_id')
                             ->whereHas('payment', function($paymentQ) {
                                 $paymentQ->where('reversed', false)
                                          ->whereNull('deleted_at');
                             });
                    })
                    ->orWhere(function($subQ) {
                        // Fallback: check by reference number for shared payments
                        $subQ->whereNotNull('reference_number')
                             ->whereExists(function($existsQ) {
                                 $existsQ->select(\DB::raw(1))
                                         ->from('payments')
                                         ->whereColumn('payments.transaction_code', 'bank_statement_transactions.reference_number')
                                         ->orWhere('payments.transaction_code', 'LIKE', \DB::raw("CONCAT(bank_statement_transactions.reference_number, '-%')"))
                                         ->where('payments.reversed', false)
                                         ->whereNull('payments.deleted_at');
                             });
                    });
                })
                ->when($hasSwimmingColumn, function($q) {
                    $q->where(function($subQ) {
                        $subQ->where('is_swimming_transaction', false)
                             ->orWhereNull('is_swimming_transaction');
                    });
                })
                ->count(),
            'duplicate' => BankStatementTransaction::where('is_duplicate', true)
                ->where('is_archived', false)
                ->when($hasSwimmingColumn, function($q) {
                    $q->where(function($subQ) {
                        $subQ->where('is_swimming_transaction', false)
                             ->orWhereNull('is_swimming_transaction');
                    });
                })
                ->count(),
            'archived' => BankStatementTransaction::where('is_archived', true)
                ->where('transaction_type', 'credit') // Only money IN transactions
                ->count(),
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
        
        $perPageOptions = [20, 50, 100, 200];
        $currentPerPage = $request->get('per_page', 25);
        if (!in_array($currentPerPage, $perPageOptions)) {
            $currentPerPage = 25;
        }
        
        return view('finance.bank-statements.index', compact('transactions', 'bankAccounts', 'view', 'counts', 'totalAmount', 'totalCount', 'perPageOptions', 'currentPerPage'));
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
            case 'draft':
                // C2B transactions with low confidence matches (similar to bank statement draft logic)
                $query->where(function($q) {
                    $q->where(function($q2) {
                        $q2->where('match_confidence', '>', 0)
                           ->where('match_confidence', '<', 80);
                    })
                    ->orWhere(function($q2) {
                        // Multiple suggestions but not auto-matched
                        $q2->whereNotNull('matching_suggestions')
                           ->where('allocation_status', 'unallocated')
                           ->where('match_confidence', '>', 0)
                           ->where('match_confidence', '<', 80);
                    });
                })
                ->whereNull('payment_id')
                ->where('is_duplicate', false);
                break;
            case 'unassigned':
                $query->where('allocation_status', 'unallocated')
                    ->whereNull('student_id')
                    ->where('is_duplicate', false);
                break;
            case 'confirmed':
                // C2B transactions that are processed but don't have payment yet (uncollected)
                $query->where('status', 'processed')
                    ->whereNull('payment_id')
                    ->where('is_duplicate', false);
                break;
            case 'collected':
                // C2B transactions with payment that is NOT reversed
                $query->whereNotNull('payment_id')
                    ->where('is_duplicate', false)
                    ->whereHas('payment', function($q) {
                        $q->where('reversed', false)
                          ->whereNull('deleted_at');
                    });
                break;
            case 'archived':
                // C2B doesn't have archived flag, so return empty
                $query->whereRaw('1 = 0');
                break;
            case 'duplicate':
                $query->where('is_duplicate', true);
                break;
            case 'swimming':
                // Show C2B swimming transactions if column exists
                if (Schema::hasColumn('mpesa_c2b_transactions', 'is_swimming_transaction')) {
                    $query->where('is_swimming_transaction', true);
                } else {
                    // If column doesn't exist, exclude C2B from swimming view
                    $query->whereRaw('1 = 0');
                }
                break;
            case 'all':
                // All transactions - exclude swimming, archived, and duplicates
                $query->where('is_duplicate', false);
                break;
            default:
                // Default to all - exclude swimming, archived, and duplicates
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
        
        // CRITICAL: Apply swimming exclusion as the FINAL constraint after all other filters
        // Swimming C2B transactions MUST ONLY appear in 'swimming' view - this cannot be overridden
        if ($view !== 'swimming' && Schema::hasColumn('mpesa_c2b_transactions', 'is_swimming_transaction')) {
            $query->where(function($q) {
                $q->where('is_swimming_transaction', false)
                  ->orWhereNull('is_swimming_transaction');
            });
        }

        return $query->orderBy('trans_time', 'desc')->orderBy('created_at', 'desc');
    }

    /**
     * Get C2B transaction counts
     */
    protected function getC2BCounts(string $view): array
    {
        $hasSwimmingColumn = Schema::hasColumn('mpesa_c2b_transactions', 'is_swimming_transaction');
        
        // Base query to exclude swimming transactions for non-swimming views
        $excludeSwimming = function($query) use ($view, $hasSwimmingColumn) {
            if ($view !== 'swimming' && $hasSwimmingColumn) {
                $query->where(function($q) {
                    $q->where('is_swimming_transaction', false)
                      ->orWhereNull('is_swimming_transaction');
                });
            }
        };
        
        $counts = [
            'all' => MpesaC2BTransaction::where('is_duplicate', false)
                ->when($view !== 'swimming' && $hasSwimmingColumn, $excludeSwimming)
                ->count(),
            'auto-assigned' => MpesaC2BTransaction::where('match_confidence', '>=', 80)
                ->where('allocation_status', 'auto_matched')
                ->whereNull('payment_id')
                ->where('is_duplicate', false)
                ->when($view !== 'swimming' && $hasSwimmingColumn, $excludeSwimming)
                ->count(),
            'manual-assigned' => MpesaC2BTransaction::where('allocation_status', 'manually_allocated')
                ->whereNull('payment_id')
                ->where('is_duplicate', false)
                ->when($view !== 'swimming' && $hasSwimmingColumn, $excludeSwimming)
                ->count(),
            'draft' => MpesaC2BTransaction::where(function($q) {
                    // C2B transactions with low confidence matches (similar to bank statement draft logic)
                    $q->where(function($q2) {
                        $q2->where('match_confidence', '>', 0)
                           ->where('match_confidence', '<', 80);
                    })
                    ->orWhere(function($q2) {
                        // Multiple suggestions but not auto-matched
                        $q2->whereNotNull('matching_suggestions')
                           ->where('allocation_status', 'unallocated')
                           ->where('match_confidence', '>', 0)
                           ->where('match_confidence', '<', 80);
                    });
                })
                ->whereNull('payment_id')
                ->where('is_duplicate', false)
                ->when($view !== 'swimming' && $hasSwimmingColumn, $excludeSwimming)
                ->count(),
            'unassigned' => MpesaC2BTransaction::where('allocation_status', 'unallocated')
                ->whereNull('student_id')
                ->where('is_duplicate', false)
                ->when($view !== 'swimming' && $hasSwimmingColumn, $excludeSwimming)
                ->count(),
            'confirmed' => MpesaC2BTransaction::where('status', 'processed')
                ->whereNull('payment_id')
                ->where('is_duplicate', false)
                ->when($view !== 'swimming' && $hasSwimmingColumn, $excludeSwimming)
                ->count(),
            'collected' => MpesaC2BTransaction::whereNotNull('payment_id')
                ->where('is_duplicate', false)
                ->whereHas('payment', function($q) {
                    $q->where('reversed', false)
                      ->whereNull('deleted_at');
                })
                ->when($view !== 'swimming' && $hasSwimmingColumn, $excludeSwimming)
                ->count(),
            'archived' => 0, // C2B doesn't have archived flag
            'duplicate' => MpesaC2BTransaction::where('is_duplicate', true)
                ->when($view !== 'swimming' && $hasSwimmingColumn, $excludeSwimming)
                ->count(),
            'swimming' => $hasSwimmingColumn
                ? MpesaC2BTransaction::where('is_swimming_transaction', true)
                    ->where('is_duplicate', false)
                    ->count()
                : 0,
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
                    $q->where('amount', $c2bTxn->trans_amount);
                    
                    // Only add date filter if trans_time exists
                    if ($c2bTxn->trans_time) {
                        $q->where('transaction_date', $c2bTxn->trans_time->format('Y-m-d'));
                    }
                    
                    $q->where(function($subQ) use ($c2bTxn) {
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
     * Resolve transaction from ID (supports both BankStatementTransaction and MpesaC2BTransaction)
     * Checks both tables and returns the correct one, using type hint if provided
     */
    protected function resolveTransaction($id, $typeHint = null)
    {
        // If type hint is provided, ALWAYS prioritize it to avoid conflicts
        if ($typeHint === 'c2b') {
            $c2bTransaction = MpesaC2BTransaction::find($id);
            if ($c2bTransaction) {
                // Check if there's also a bank transaction with same ID (conflict)
                $bankTransaction = BankStatementTransaction::find($id);
                if ($bankTransaction) {
                    \Log::warning('Transaction ID conflict detected - both C2B and Bank Statement transactions exist with same ID, using C2B per type hint', [
                        'id' => $id,
                        'c2b_trans_id' => $c2bTransaction->trans_id,
                        'bank_ref_number' => $bankTransaction->reference_number,
                        'referer' => request()->header('referer'),
                        'url' => request()->fullUrl(),
                    ]);
                }
                return $c2bTransaction;
            }
            // If not found in C2B, try bank statement (fallback)
            $bankTransaction = BankStatementTransaction::find($id);
            if ($bankTransaction) {
                return $bankTransaction;
            }
        } elseif ($typeHint === 'bank') {
            $bankTransaction = BankStatementTransaction::find($id);
            if ($bankTransaction) {
                // Check if there's also a C2B transaction with same ID (conflict)
                $c2bTransaction = MpesaC2BTransaction::find($id);
                if ($c2bTransaction) {
                    \Log::warning('Transaction ID conflict detected - both C2B and Bank Statement transactions exist with same ID, using Bank per type hint', [
                        'id' => $id,
                        'c2b_trans_id' => $c2bTransaction->trans_id,
                        'bank_ref_number' => $bankTransaction->reference_number,
                        'referer' => request()->header('referer'),
                        'url' => request()->fullUrl(),
                    ]);
                }
                return $bankTransaction;
            }
            // If not found in bank, try C2B (fallback)
            $c2bTransaction = MpesaC2BTransaction::find($id);
            if ($c2bTransaction) {
                return $c2bTransaction;
            }
        } else {
            // No type hint - check both tables simultaneously
            $bankTransaction = BankStatementTransaction::find($id);
            $c2bTransaction = MpesaC2BTransaction::find($id);
            
            // If both exist (ID conflict - this should not happen but can occur if IDs were manually changed)
            if ($bankTransaction && $c2bTransaction) {
                \Log::warning('Transaction ID conflict detected - both C2B and Bank Statement transactions exist with same ID', [
                    'id' => $id,
                    'c2b_trans_id' => $c2bTransaction->trans_id,
                    'bank_ref_number' => $bankTransaction->reference_number,
                    'referer' => request()->header('referer'),
                    'url' => request()->fullUrl(),
                ]);
                
                // Check referer or query parameter to determine which one to use
                $referer = request()->header('referer');
                $typeParam = request()->get('type');
                
                if ($typeParam === 'c2b' || ($referer && strpos($referer, '/mpesa/c2b/') !== false)) {
                    return $c2bTransaction;
                }
                if ($typeParam === 'bank' || ($referer && strpos($referer, '/bank-statements/') !== false)) {
                    return $bankTransaction;
                }
                
                // Default: return bank statement (original behavior)
                return $bankTransaction;
            }
            
            // Return whichever exists
            if ($bankTransaction) {
                return $bankTransaction;
            }
            
            if ($c2bTransaction) {
                return $c2bTransaction;
            }
        }
        
        abort(404, 'Transaction not found');
    }
    
    /**
     * Normalize transaction data for unified display
     */
    protected function normalizeTransaction($transaction)
    {
        $isC2B = $transaction instanceof MpesaC2BTransaction;
        
        return [
            'id' => $transaction->id,
            'type' => $isC2B ? 'c2b' : 'bank',
            'transaction_date' => $isC2B ? $transaction->trans_time : $transaction->transaction_date,
            'amount' => $isC2B ? $transaction->trans_amount : $transaction->amount,
            'transaction_type' => $isC2B ? 'credit' : ($transaction->transaction_type ?? 'credit'),
            'reference_number' => $isC2B ? $transaction->trans_id : $transaction->reference_number,
            'description' => $isC2B ? ($transaction->bill_ref_number ?? 'M-PESA Payment') : $transaction->description,
            'phone_number' => $isC2B ? ($transaction->formatted_phone ?? $transaction->msisdn) : $transaction->phone_number,
            'payer_name' => $isC2B ? $transaction->full_name : $transaction->payer_name,
            'student_id' => $transaction->student_id,
            'family_id' => $isC2B ? null : ($transaction->family_id ?? null),
            'match_status' => $isC2B ? ($transaction->allocation_status === 'auto_matched' ? 'matched' : ($transaction->allocation_status === 'manually_allocated' ? 'manual' : 'unmatched')) : $transaction->match_status,
            'match_confidence' => $transaction->match_confidence ?? 0,
            'match_notes' => $isC2B ? ($transaction->match_reason ?? '') : ($transaction->match_notes ?? ''),
            'matched_admission_number' => $isC2B ? null : ($transaction->matched_admission_number ?? null),
            'matched_student_name' => $isC2B ? null : ($transaction->matched_student_name ?? null),
            'matched_phone_number' => $isC2B ? null : ($transaction->matched_phone_number ?? null),
            'status' => $isC2B ? (
                $transaction->status === 'processed' || $transaction->payment_id ? 'confirmed' : 
                ($transaction->status === 'failed' ? 'rejected' : 'draft')
            ) : (
                // For bank statements: if payment exists and is created, always show as confirmed
                // This ensures UI consistency even if DB status is temporarily out of sync
                ($transaction->payment_id && ($transaction->payment_created ?? false) && $transaction->status !== 'rejected')
                    ? 'confirmed'
                    : $transaction->status
            ),
            'payment_id' => $transaction->payment_id,
            'payment_created' => $isC2B ? ($transaction->payment_id !== null) : ($transaction->payment_created ?? false),
            'is_duplicate' => $transaction->is_duplicate ?? false,
            'is_shared' => $isC2B ? false : ($transaction->is_shared ?? false),
            'shared_allocations' => $isC2B ? null : (is_string($transaction->shared_allocations ?? null) ? json_decode($transaction->shared_allocations, true) : ($transaction->shared_allocations ?? null)),
            'is_swimming_transaction' => $transaction->is_swimming_transaction ?? false,
            'statement_file_path' => $isC2B ? null : $transaction->statement_file_path,
            'bank_type' => $isC2B ? 'MPESA' : ($transaction->bank_type ?? 'N/A'),
            'version' => $isC2B ? 0 : ($transaction->version ?? 0), // For optimistic locking
            'raw_transaction' => $transaction, // Keep original for relationships
        ];
    }

    /**
     * Show transaction details (unified for both BankStatementTransaction and MpesaC2BTransaction)
     */
    public function show(Request $request, $bankStatement)
    {
        // Get the ID (route parameter might be string, int, or model instance)
        $id = is_object($bankStatement) ? $bankStatement->id : (int) $bankStatement;
        
        // Get type hint from query parameter if provided
        $typeHint = $request->get('type');
        
        // Resolve the transaction using the type hint to ensure we get the correct one
        $transaction = $this->resolveTransaction($id, $typeHint);
        $isC2B = $transaction instanceof MpesaC2BTransaction;
        
        // Load relationships based on type
        if ($isC2B) {
            $transaction->load(['student', 'payment', 'processedBy']);
        } else {
            $transaction->load(['student', 'family', 'bankAccount', 'payment', 'confirmedBy', 'createdBy']);
        }
        
        // Use normalized data for view
        $normalized = $this->normalizeTransaction($transaction);
        $bankStatement = (object) $normalized; // Create object for view compatibility
        $rawTransaction = $transaction; // Keep original for relationships
        
        // Fix old rejected transactions that still have students assigned (only for bank statements)
        if (!$isC2B && $bankStatement->status === 'rejected' && $bankStatement->student_id) {
            $transaction->update([
                'student_id' => null,
                'family_id' => null,
                'match_status' => 'unmatched',
                'match_confidence' => 0,
                'matched_admission_number' => null,
                'matched_student_name' => null,
                'matched_phone_number' => null,
                'match_notes' => 'MANUALLY_REJECTED - Requires manual assignment',
            ]);
            $transaction->refresh();
            $normalized = $this->normalizeTransaction($transaction);
            $bankStatement = (object) $normalized;
        }
        
        // Get siblings if family exists (for bank statements) or via student family (for C2B)
        $siblings = [];
        if (!$isC2B && $bankStatement->family_id) {
            $siblings = Student::where('family_id', $bankStatement->family_id)
                ->where('id', '!=', $bankStatement->student_id)
                ->where('archive', 0)
                ->where('is_alumni', false)
                ->get();
        } elseif ($bankStatement->student_id && $transaction->student) {
            $student = $transaction->student;
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
        if (!$isC2B && ($bankStatement->match_status === 'multiple_matches' || $bankStatement->match_status === 'unmatched') && !$bankStatement->student_id) {
            try {
                // Re-run matching to get current possible matches (only for bank statements)
                $matchResult = $this->parser->matchTransaction($transaction);
                $transaction->refresh();
                $normalized = $this->normalizeTransaction($transaction);
                $bankStatement = (object) $normalized;
                
                if (isset($matchResult['matches']) && is_array($matchResult['matches'])) {
                    $possibleMatches = $matchResult['matches'];
                }
            } catch (\Exception $e) {
                Log::warning('Failed to get possible matches for transaction', [
                    'transaction_id' => $bankStatement->id,
                    'error' => $e->getMessage()
                ]);
            }
        } elseif ($isC2B && $transaction->matching_suggestions) {
            // For C2B, use matching suggestions
            $possibleMatches = collect($transaction->matching_suggestions)->map(function($suggestion) {
                return [
                    'student_id' => $suggestion['student_id'] ?? null,
                    'student_name' => $suggestion['student_name'] ?? '',
                    'admission_number' => $suggestion['admission_number'] ?? '',
                    'confidence' => $suggestion['confidence'] ?? 0,
                    'reason' => $suggestion['reason'] ?? '',
                ];
            })->toArray();
        }

        // Get all payments related to this transaction (including sibling payments for shared transactions)
        $allPayments = collect();
        $activePayments = collect();
        $reversedPayments = collect();
        
        if ($bankStatement->reference_number) {
            $ref = $bankStatement->reference_number;
            // New payments (especially after reversal) use modified transaction_code (ref + suffix);
            // fetch exact match and ref-* so both original and newly created payments appear.
            $allPayments = \App\Models\Payment::withTrashed()
                ->where(function ($q) use ($ref) {
                    $q->where('transaction_code', $ref)
                      ->orWhere('transaction_code', 'LIKE', $ref . '-%');
                })
                ->with('student')
                ->orderBy('created_at', 'desc')
                ->get();
            
            // For C2B transactions, also check if payment exists but wasn't found by reference
            // This handles cases where payment was created but transaction_code doesn't match exactly
            if ($isC2B && $allPayments->isEmpty() && $rawTransaction->trans_id) {
                $c2bPayment = \App\Models\Payment::withTrashed()
                    ->where('transaction_code', $rawTransaction->trans_id)
                    ->where('reversed', false)
                    ->whereNull('deleted_at')
                    ->with('student')
                    ->orderBy('created_at', 'desc')
                    ->first();
                if ($c2bPayment) {
                    $allPayments = collect([$c2bPayment]);
                    // Also update the C2B transaction to link the payment_id if not set
                    if (!$rawTransaction->payment_id) {
                        $rawTransaction->update([
                            'payment_id' => $c2bPayment->id,
                            'status' => 'processed', // Update status when payment is linked
                        ]);
                        // Refresh normalized data
                        $normalized = $this->normalizeTransaction($rawTransaction);
                        $bankStatement = (object) $normalized;
                    } elseif ($rawTransaction->status !== 'processed' && $rawTransaction->status !== 'failed') {
                        // Update status if payment_id was already set but status wasn't updated
                        $rawTransaction->update(['status' => 'processed']);
                        $normalized = $this->normalizeTransaction($rawTransaction);
                        $bankStatement = (object) $normalized;
                    }
                }
            }
            
            // For bank statements, check if payment exists by reference but transaction not updated
            if (!$isC2B && $allPayments->isEmpty() && $bankStatement->reference_number && !$bankStatement->payment_created) {
                $bankPayment = \App\Models\Payment::where('transaction_code', $bankStatement->reference_number)
                    ->orWhere('transaction_code', 'LIKE', $bankStatement->reference_number . '-%')
                    ->where('reversed', false)
                    ->whereNull('deleted_at')
                    ->with('student')
                    ->orderBy('created_at', 'desc')
                    ->first();
                if ($bankPayment) {
                    $allPayments = collect([$bankPayment]);
                    // Update the bank statement transaction to link the payment
                    if (!$rawTransaction->payment_id) {
                        $rawTransaction->update([
                            'payment_id' => $bankPayment->id,
                            'payment_created' => true,
                            'status' => 'confirmed', // Update status when payment is linked
                        ]);
                        // Refresh normalized data
                        $normalized = $this->normalizeTransaction($rawTransaction);
                        $bankStatement = (object) $normalized;
                    } elseif (!$rawTransaction->payment_created) {
                        // Update payment_created flag if payment_id was already set
                        $rawTransaction->update([
                            'payment_created' => true,
                            'status' => $rawTransaction->status === 'draft' ? 'confirmed' : $rawTransaction->status,
                        ]);
                        $normalized = $this->normalizeTransaction($rawTransaction);
                        $bankStatement = (object) $normalized;
                    }
                }
            }
            
            // Auto-update status if transaction has payment but status is still draft/pending
            // Also refresh the transaction to ensure we have the latest data
            $rawTransaction->refresh();
            
            if ($bankStatement->payment_id || $bankStatement->payment_created || $allPayments->isNotEmpty()) {
                $hasValidPayment = false;
                if ($bankStatement->payment_id) {
                    $checkPayment = \App\Models\Payment::where('id', $bankStatement->payment_id)
                        ->where('reversed', false)
                        ->whereNull('deleted_at')
                        ->first();
                    if ($checkPayment) {
                        $hasValidPayment = true;
                    }
                }
                
                // If we found payments in allPayments, we have a valid payment
                if ($allPayments->isNotEmpty() && $allPayments->where('reversed', false)->whereNull('deleted_at')->isNotEmpty()) {
                    $hasValidPayment = true;
                }
                
                if ($hasValidPayment) {
                    if ($isC2B) {
                        if ($rawTransaction->status === 'pending' || ($rawTransaction->payment_id && $rawTransaction->status !== 'processed' && $rawTransaction->status !== 'failed')) {
                            $rawTransaction->update(['status' => 'processed']);
                            $rawTransaction->refresh();
                            $normalized = $this->normalizeTransaction($rawTransaction);
                            $bankStatement = (object) $normalized;
                        }
                    } else {
                        // For bank statements, if payment exists, status should be confirmed
                        if ($rawTransaction->status === 'draft' || ($rawTransaction->payment_id && $rawTransaction->status !== 'confirmed' && $rawTransaction->status !== 'rejected')) {
                            $rawTransaction->update([
                                'status' => 'confirmed',
                                'payment_created' => true,
                            ]);
                            $rawTransaction->refresh();
                            $normalized = $this->normalizeTransaction($rawTransaction);
                            $bankStatement = (object) $normalized;
                        } elseif ($rawTransaction->status === 'confirmed' && !$rawTransaction->payment_created && $rawTransaction->payment_id) {
                            // Ensure payment_created flag is set if payment_id exists
                            $rawTransaction->update(['payment_created' => true]);
                            $rawTransaction->refresh();
                            $normalized = $this->normalizeTransaction($rawTransaction);
                            $bankStatement = (object) $normalized;
                        }
                    }
                }
            }
            
            // Final refresh to ensure normalized data matches database
            $rawTransaction->refresh();
            
            // Force update status if payment exists but status is wrong (CRITICAL FIX)
            if (!$isC2B && $rawTransaction->payment_id && $rawTransaction->payment_created) {
                // Check if payment is valid (not reversed)
                $validPayment = \App\Models\Payment::where('id', $rawTransaction->payment_id)
                    ->where('reversed', false)
                    ->whereNull('deleted_at')
                    ->exists();
                
                if ($validPayment && $rawTransaction->status !== 'confirmed' && $rawTransaction->status !== 'rejected') {
                    $rawTransaction->update(['status' => 'confirmed']);
                    $rawTransaction->refresh();
                }
            } elseif ($isC2B && $rawTransaction->payment_id && $rawTransaction->status !== 'processed' && $rawTransaction->status !== 'failed') {
                // For C2B, ensure status is processed if payment exists
                $validPayment = \App\Models\Payment::where('id', $rawTransaction->payment_id)
                    ->where('reversed', false)
                    ->whereNull('deleted_at')
                    ->exists();
                
                if ($validPayment) {
                    $rawTransaction->update(['status' => 'processed']);
                    $rawTransaction->refresh();
                }
            }
            
            // Final normalization with fresh data
            $normalized = $this->normalizeTransaction($rawTransaction);
            $bankStatement = (object) $normalized;
            
            // Double-check: if payment exists, force status to confirmed in normalized data
            if (!$isC2B && $bankStatement->payment_id && ($bankStatement->payment_created ?? false) && $bankStatement->status !== 'rejected') {
                $bankStatement->status = 'confirmed';
            } elseif ($isC2B && $bankStatement->payment_id && $bankStatement->status !== 'rejected') {
                $bankStatement->status = 'confirmed';
            }
            // Ensure payment_id-linked payment is included (e.g. shared first payment with different code)
            if ($bankStatement->payment_id && !$allPayments->contains('id', $bankStatement->payment_id)) {
                $linked = \App\Models\Payment::withTrashed()->with('student')->find($bankStatement->payment_id);
                if ($linked) {
                    $allPayments = $allPayments->push($linked)->sortByDesc('created_at')->values();
                }
            }
            // Shared transactions: include all sibling payments by receipt base (only for bank statements)
            if (!$isC2B && $bankStatement->is_shared && $allPayments->isNotEmpty()) {
                $receipts = $allPayments->pluck('receipt_number')->unique()->filter()->values();
                if ($receipts->isNotEmpty()) {
                    $base = $receipts->sortBy(fn ($r) => strlen($r))->first();
                    $byReceipt = \App\Models\Payment::withTrashed()
                        ->with('student')
                        ->where(function ($q) use ($base) {
                            $q->where('receipt_number', $base)
                              ->orWhere('receipt_number', 'LIKE', $base . '-%');
                        })
                        ->get();
                    $allPayments = $allPayments->merge($byReceipt)->unique('id')->sortByDesc('created_at')->values();
                }
            }
            $activePayments = $allPayments->where('reversed', false)->values();
            $reversedPayments = $allPayments->where('reversed', true)->values();
        }
        
        // Always check by payment_id directly (for C2B transactions or when reference lookup fails)
        // This ensures payments are always found if payment_id is set
        if ($bankStatement->payment_id) {
            $directPayment = \App\Models\Payment::withTrashed()->with('student')->find($bankStatement->payment_id);
            if ($directPayment) {
                // Add to allPayments if not already included
                if (!$allPayments->contains('id', $directPayment->id)) {
                    $allPayments->push($directPayment);
                }
                // Update active/reversed collections - ensure it's in the right collection
                if ($directPayment->reversed) {
                    if (!$reversedPayments->contains('id', $directPayment->id)) {
                        $reversedPayments->push($directPayment);
                    }
                    // Remove from activePayments if it's there (shouldn't be, but just in case)
                    $activePayments = $activePayments->reject(fn($p) => $p->id === $directPayment->id);
                } else {
                    if (!$activePayments->contains('id', $directPayment->id)) {
                        $activePayments->push($directPayment);
                    }
                    // Remove from reversedPayments if it's there (shouldn't be, but just in case)
                    $reversedPayments = $reversedPayments->reject(fn($p) => $p->id === $directPayment->id);
                }
            }
        }
        
        // Also check for payments by student_id and transaction_code if payment_id is set but payment not found
        // This handles cases where payment was created but payment_id wasn't updated
        if ($bankStatement->payment_id && $allPayments->isEmpty()) {
            // Try to find by student_id and reference_number
            if ($bankStatement->student_id && $bankStatement->reference_number) {
                $studentPayments = \App\Models\Payment::withTrashed()
                    ->where('student_id', $bankStatement->student_id)
                    ->where(function ($q) use ($bankStatement) {
                        $q->where('transaction_code', $bankStatement->reference_number)
                          ->orWhere('transaction_code', 'LIKE', $bankStatement->reference_number . '-%');
                    })
                    ->with('student')
                    ->orderBy('created_at', 'desc')
                    ->get();
                
                if ($studentPayments->isNotEmpty()) {
                    $allPayments = $studentPayments;
                    $activePayments = $allPayments->where('reversed', false);
                    $reversedPayments = $allPayments->where('reversed', true);
                    
                    // Update the transaction's payment_id if it's not set
                    if (!$rawTransaction->payment_id && $activePayments->isNotEmpty()) {
                        $rawTransaction->update(['payment_id' => $activePayments->first()->id]);
                    }
                }
            }
        }
        
        // Final fallback: If no payments found by reference but payment_id exists, use payment_id
        if ($allPayments->isEmpty() && $bankStatement->payment_id) {
            $payment = \App\Models\Payment::withTrashed()->with('student')->find($bankStatement->payment_id);
            if ($payment) {
                $allPayments = collect([$payment]);
                if ($payment->reversed) {
                    $reversedPayments = collect([$payment]);
                } else {
                    $activePayments = collect([$payment]);
                }
            }
        }

        return view('finance.bank-statements.show', compact('bankStatement', 'siblings', 'possibleMatches', 'allPayments', 'activePayments', 'reversedPayments', 'rawTransaction', 'isC2B'));
    }

    /**
     * Edit transaction (manual matching) - unified for both types
     */
    public function edit($id)
    {
        $transaction = $this->resolveTransaction($id);
        $isC2B = $transaction instanceof MpesaC2BTransaction;
        
        // Load relationships
        if ($isC2B) {
            $transaction->load(['student']);
        } else {
            $transaction->load(['student', 'family']);
        }
        
        // Normalize for view
        $normalized = $this->normalizeTransaction($transaction);
        $bankStatement = (object) $normalized;
        $rawTransaction = $transaction;
        
        // Get potential matches
        $potentialMatches = [];
        if ($bankStatement->phone_number) {
            if ($isC2B) {
                // For C2B, use matching suggestions if available
                if ($transaction->matching_suggestions) {
                    $potentialMatches = collect($transaction->matching_suggestions)->map(function($suggestion) {
                        return [
                            'student_id' => $suggestion['student_id'] ?? null,
                            'student_name' => $suggestion['student_name'] ?? '',
                            'admission_number' => $suggestion['admission_number'] ?? '',
                            'confidence' => $suggestion['confidence'] ?? 0,
                            'reason' => $suggestion['reason'] ?? '',
                        ];
                    })->toArray();
                }
            } else {
                // For bank statements, use parser
                $normalizedPhone = $this->parser->normalizePhone($bankStatement->phone_number);
                $potentialMatches = $this->parser->findStudentsByPhone($normalizedPhone);
            }
        }

        $students = Student::where('archive', false)
            ->orderBy('first_name')
            ->get();

        return view('finance.bank-statements.edit', compact('bankStatement', 'potentialMatches', 'students', 'rawTransaction', 'isC2B'));
    }

    /**
     * Update transaction - unified for both types
     */
    public function update(Request $request, $bankStatement)
    {
        try {
            // $bankStatement is the route parameter - it could be an ID or a model instance
            // Convert to ID if it's a model, matching the show() method pattern
            $id = is_object($bankStatement) ? $bankStatement->id : (int) $bankStatement;
            
            $transaction = $this->resolveTransaction($id);
            $isC2B = $transaction instanceof MpesaC2BTransaction;
            
            $validated = $request->validate([
                'student_id' => 'nullable|exists:students,id',
                'match_notes' => 'nullable|string|max:1000',
            ]);

            DB::transaction(function () use ($transaction, $validated, $isC2B, $id) {
            $student = null;
            if ($validated['student_id']) {
                $student = Student::findOrFail($validated['student_id']);
            }

            // Check if student is being changed and payment exists
            $oldStudentId = $transaction->student_id;
            $newStudentId = $student?->id;
            $studentChanged = $oldStudentId !== $newStudentId;
            
            // Check for existing payments that are ACTUALLY linked to THIS transaction
            // Only check payments that are directly linked via payment_id or were created from this transaction
            $existingPayments = collect();
            
            // First, check if this transaction has a payment_id directly linked
            if ($transaction->payment_id) {
                $payment = \App\Models\Payment::find($transaction->payment_id);
                if ($payment && !$payment->reversed) {
                    $existingPayments->push($payment);
                }
            }
            
            // Also check by reference number, but ONLY if the payment is actually linked to this transaction
            // This prevents false positives from other transactions with the same reference number
            $normalized = $this->normalizeTransaction($transaction);
            $normalizedTransaction = (object) $normalized;
            if ($normalizedTransaction->reference_number) {
                $ref = $normalizedTransaction->reference_number;
                
                // For C2B transactions, check if payment is linked via C2B transaction's payment_id
                // For bank statement transactions, check if payment is linked via bank statement transaction's payment_id
                if ($isC2B) {
                    // For C2B: check payments linked to C2B transactions with this reference number
                    $c2bTransactionsWithRef = MpesaC2BTransaction::where('trans_id', $ref)
                        ->where('id', $transaction->id) // Only this specific transaction
                        ->whereNotNull('payment_id')
                        ->pluck('payment_id');
                    
                    if ($c2bTransactionsWithRef->isNotEmpty()) {
                        $refPayments = \App\Models\Payment::whereIn('id', $c2bTransactionsWithRef)
                            ->where('reversed', false)
                            ->get();
                        
                        foreach ($refPayments as $refPayment) {
                            if (!$existingPayments->contains('id', $refPayment->id)) {
                                $existingPayments->push($refPayment);
                            }
                        }
                    }
                } else {
                    // For bank statements: check payments linked to bank statement transactions with this reference number
                    $bankTransactionsWithRef = BankStatementTransaction::where('reference_number', $ref)
                        ->where('id', $transaction->id) // Only this specific transaction
                        ->whereNotNull('payment_id')
                        ->pluck('payment_id');
                    
                    if ($bankTransactionsWithRef->isNotEmpty()) {
                        $refPayments = \App\Models\Payment::whereIn('id', $bankTransactionsWithRef)
                            ->where('reversed', false)
                            ->get();
                        
                        foreach ($refPayments as $refPayment) {
                            if (!$existingPayments->contains('id', $refPayment->id)) {
                                $existingPayments->push($refPayment);
                            }
                        }
                    }
                }
            }
            
            // If student is being changed and payments exist that are linked to THIS transaction, prevent the change
            if ($studentChanged && $existingPayments->isNotEmpty()) {
                $oldStudent = $oldStudentId ? Student::find($oldStudentId) : null;
                $oldStudentName = $oldStudent ? $oldStudent->full_name : 'Unknown';
                
                $paymentStudents = $existingPayments->map(function($p) {
                    $s = $p->student;
                    return $s ? $s->full_name . ' (' . $s->admission_number . ')' : 'Unknown';
                })->unique()->implode(', ');
                
                // Log for debugging ID conflicts
                \Log::warning('Transaction reassignment blocked due to existing payment', [
                    'transaction_id' => $transaction->id,
                    'transaction_type' => $isC2B ? 'c2b' : 'bank',
                    'old_student_id' => $oldStudentId,
                    'new_student_id' => $newStudentId,
                    'payment_ids' => $existingPayments->pluck('id')->toArray(),
                    'reference_number' => $normalizedTransaction->reference_number ?? null,
                ]);
                
                throw new \Exception(
                    "Cannot reassign this transaction. A payment already exists for student(s): {$paymentStudents}. " .
                    "The transaction is currently assigned to: {$oldStudentName}. " .
                    "To reassign, you must first reverse the existing payment(s) or reject this transaction."
                );
            }

            // Clear MANUALLY_REJECTED marker when manually assigned
            $matchNotes = $validated['match_notes'] ?? 'Manually assigned';
            if (strpos($matchNotes, 'MANUALLY_REJECTED') !== false) {
                $matchNotes = 'Manually assigned';
            }
            
            // Get current status
            $currentStatus = $isC2B 
                ? ($transaction->status === 'processed' ? 'confirmed' : ($transaction->status === 'failed' ? 'rejected' : 'draft'))
                : $transaction->status;
            
            // If assigning a student to a rejected/unmatched transaction, change status to draft
            $newStatus = $currentStatus;
            if ($student && in_array($currentStatus, ['rejected', 'unmatched'])) {
                $newStatus = 'draft';
            } elseif (!$student && $currentStatus === 'draft') {
                $newStatus = 'unmatched';
            }
            
            if ($isC2B) {
                // Update C2B transaction
                $transaction->update([
                    'student_id' => $student?->id,
                    'allocation_status' => $student ? 'manually_allocated' : 'unallocated',
                    'match_confidence' => $student ? 100 : 0,
                    'match_reason' => $matchNotes,
                    'status' => $newStatus === 'confirmed' ? 'processed' : ($newStatus === 'rejected' ? 'failed' : 'pending'),
                ]);
            } else {
                // Update bank statement transaction
                $transaction->update([
                    'student_id' => $student?->id,
                    'family_id' => $student?->family_id,
                    'status' => $newStatus,
                    'match_status' => $student ? 'manual' : 'unmatched',
                    'match_confidence' => $student ? 1.0 : 0,
                    'match_notes' => $matchNotes,
                ]);
            }
        });

            // Use the original ID from route parameter, not from closure
            $redirectId = is_object($bankStatement) ? $bankStatement->id : (int) $bankStatement;
            
            return redirect()
                ->route('finance.bank-statements.show', $redirectId)
                ->with('success', 'Transaction updated successfully');
                
        } catch (\Exception $e) {
            // Use the original ID from route parameter, not from closure
            $redirectId = is_object($bankStatement) ? $bankStatement->id : (int) $bankStatement;
            
            return redirect()
                ->route('finance.bank-statements.show', $redirectId)
                ->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Confirm transaction - unified for both types
     */
    public function confirm(Request $request, $id)
    {
        $transaction = $this->resolveTransaction($id);
        $isC2B = $transaction instanceof MpesaC2BTransaction;
        
        // Normalize for checks
        $normalized = $this->normalizeTransaction($transaction);
        $bankStatement = (object) $normalized;
        
        if (!$bankStatement->student_id && !($bankStatement->is_shared ?? false)) {
            return redirect()->back()
                ->withErrors(['error' => 'Transaction must be matched to a student or shared before confirming']);
        }

        // Check if there are non-reversed payments for this transaction (exact ref + ref-*)
        // ALWAYS check for existing payments to prevent duplicate confirmations
        // This check should run regardless of status or payment_created flag
        $nonReversedPayments = collect();
        
        // Check by payment_id first (most direct)
        if ($bankStatement->payment_id) {
            $payment = \App\Models\Payment::find($bankStatement->payment_id);
            if ($payment && !$payment->reversed) {
                $nonReversedPayments->push($payment);
            }
        }
        
        // Also check by reference number (for shared payments or multiple payments with same ref)
        // This catches cases where payment exists but payment_id wasn't linked
        if ($bankStatement->reference_number) {
            $ref = $bankStatement->reference_number;
            $refPayments = \App\Models\Payment::where('reversed', false)
                ->where(function ($q) use ($ref) {
                    $q->where('transaction_code', $ref)
                      ->orWhere('transaction_code', 'LIKE', $ref . '-%');
                })
                ->get();
            
            // Merge with existing payments, avoiding duplicates
            foreach ($refPayments as $refPayment) {
                if (!$nonReversedPayments->contains('id', $refPayment->id)) {
                    $nonReversedPayments->push($refPayment);
                }
            }
        }
        
        // If payments found, prevent confirmation and optionally link payment_id
        if ($nonReversedPayments->isNotEmpty()) {
            $firstPayment = $nonReversedPayments->first();
            
            // Auto-link payment_id if not set (for both C2B and bank statements)
            if (!$transaction->payment_id) {
                if ($isC2B) {
                    $transaction->update(['payment_id' => $firstPayment->id]);
                    
                    // Update status to processed if payment exists
                    if ($transaction->status !== 'processed' && $transaction->status !== 'failed') {
                        $transaction->update(['status' => 'processed']);
                    }
                } else {
                    // For bank statements, update payment_id and payment_created
                    $transaction->update([
                        'payment_id' => $firstPayment->id,
                        'payment_created' => true,
                    ]);
                    
                    // Update status to confirmed if it's still draft
                    if ($transaction->status === 'draft') {
                        $transaction->update(['status' => 'confirmed']);
                    }
                }
            } else {
                // Payment_id already set, but ensure status is correct
                if ($isC2B) {
                    if ($transaction->status !== 'processed' && $transaction->status !== 'failed') {
                        $transaction->update(['status' => 'processed']);
                    }
                } else {
                    // For bank statements, ensure payment_created is true and status is confirmed
                    if (!$transaction->payment_created) {
                        $transaction->update(['payment_created' => true]);
                    }
                    if ($transaction->status === 'draft') {
                        $transaction->update(['status' => 'confirmed']);
                    }
                }
            }
            
            return redirect()->back()
                ->withErrors(['error' => 'This transaction already has active (non-reversed) payments. Cannot confirm again.'])
                ->with('info', 'Payment has been automatically linked to this transaction.');
        }

        // Check if this is a swimming transaction
        $transaction->refresh();
        $isSwimming = $bankStatement->is_swimming_transaction ?? false;

        DB::transaction(function () use ($transaction, $isSwimming, $isC2B, $bankStatement, $id) {
            // Clear MANUALLY_REJECTED marker when confirming (manual assignment)
            $matchNotes = $bankStatement->match_notes ?? '';
            if (strpos($matchNotes, 'MANUALLY_REJECTED') !== false) {
                $matchNotes = $matchNotes ? str_replace('MANUALLY_REJECTED - ', '', $matchNotes) : 'Manually confirmed';
            }
            
            // Update transaction status
            if ($isC2B) {
                $transaction->update([
                    'status' => 'processed',
                    'match_reason' => $matchNotes,
                ]);
            } else {
                $transaction->confirm();
                if (strpos($transaction->match_notes ?? '', 'MANUALLY_REJECTED') !== false) {
                    $transaction->update(['match_notes' => $matchNotes]);
                }
            }

            if ($isSwimming) {
                // Handle swimming transaction - allocate to swimming wallets
                if ($isC2B) {
                    // For C2B, use the existing C2B allocation logic
                    // This should already be handled when the transaction was allocated
                    // But we can ensure payment is created if not already
                    if (!$transaction->payment_id) {
                        // Create payment for swimming C2B transaction
                        $this->createSwimmingPaymentForC2B($transaction);
                    }
                } else {
                    $this->processSwimmingTransaction($transaction);
                }
            } else {
                // Create payment for fee allocation if not already created
                if (!$bankStatement->payment_created) {
                    try {
                        if ($isC2B) {
                            // For C2B, create payment using C2B logic
                            $payment = $this->createPaymentForC2B($transaction);
                        } else {
                            // For bank statements, use existing logic
                            $payment = $this->createPaymentForBankStatement($transaction, $bankStatement);
                        }
                        
                        // Queue receipt generation and notifications
                        if (isset($payment)) {
                            \App\Jobs\ProcessSiblingPaymentsJob::dispatch($transaction->id, $payment->id)
                                ->onQueue('default');
                        }
                    } catch (\App\Exceptions\PaymentConflictException $e) {
                        return redirect()
                            ->route('finance.bank-statements.show', $id)
                            ->with('payment_conflict', [
                                'conflicting_payments' => $e->conflictingPayments,
                                'student_id' => $e->studentId,
                                'transaction_code' => $e->transactionCode,
                                'message' => $e->getMessage(),
                            ])
                            ->with('error', 'Payment conflict detected. Please review the conflicting payment(s) and choose an action.');
                    } catch (\Exception $e) {
                        Log::error('Failed to create payment from transaction', [
                            'transaction_id' => $transaction->id,
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
            ->route('finance.bank-statements.show', $id)
            ->with('success', $message);
    }
    
    /**
     * Helper: Create payment for C2B transaction
     */
    protected function createPaymentForC2B($c2bTransaction)
    {
        // Check for existing payments
        $ref = $c2bTransaction->trans_id;
        $existingPayment = \App\Models\Payment::where('reversed', false)
            ->where(function ($q) use ($ref) {
                $q->where('transaction_code', $ref)
                  ->orWhere('transaction_code', 'LIKE', $ref . '-%');
            })
            ->first();
        
        if ($existingPayment) {
            // Update status to processed if payment already exists
            $c2bTransaction->update([
                'payment_id' => $existingPayment->id,
                'status' => 'processed', // Mark as processed when payment exists
            ]);
            return $existingPayment;
        }
        
        // Create new payment
        $student = $c2bTransaction->student;
        if (!$student) {
            throw new \Exception('Student not found for C2B transaction');
        }
        
        $payment = \App\Models\Payment::create([
            'student_id' => $student->id,
            'amount' => $c2bTransaction->trans_amount,
            'payment_method' => 'mpesa',
            'payment_date' => $c2bTransaction->trans_time,
            'receipt_number' => 'REC-' . strtoupper(\Illuminate\Support\Str::random(10)),
            'transaction_code' => $c2bTransaction->trans_id,
            'status' => 'approved',
            'notes' => 'M-PESA Paybill payment - ' . $c2bTransaction->full_name,
            'created_by' => \Illuminate\Support\Facades\Auth::id(),
        ]);
        
        // Update C2B transaction with payment_id and status
        $c2bTransaction->update([
            'payment_id' => $payment->id,
            'status' => 'processed', // Mark as processed when payment is created
        ]);
        
        // Auto-allocate if not swimming
        if (!$c2bTransaction->is_swimming_transaction) {
            $allocationService = app(\App\Services\PaymentAllocationService::class);
            $allocationService->autoAllocate($payment);
        }
        
        return $payment;
    }
    
    /**
     * Helper: Create payment for bank statement transaction
     */
    protected function createPaymentForBankStatement($transaction, $normalized)
    {
        // Check for existing payments
        $ref = $normalized->reference_number;
        $nonReversedPayments = collect();
        if ($ref) {
            $nonReversedPayments = \App\Models\Payment::where('reversed', false)
                ->where(function ($q) use ($ref) {
                    $q->where('transaction_code', $ref)
                      ->orWhere('transaction_code', 'LIKE', $ref . '-%');
                })
                ->get();
        }
        
        if ($nonReversedPayments->isNotEmpty()) {
            $payment = $nonReversedPayments->first();
            $transaction->update([
                'payment_id' => $payment->id,
                'payment_created' => true,
            ]);
            return $payment;
        }
        
        // Check existing payment
        $existingPayment = null;
        if ($transaction->payment_id) {
            $existingPayment = \App\Models\Payment::find($transaction->payment_id);
        }
        
        if ($existingPayment && !$existingPayment->reversed) {
            return $existingPayment;
        }
        
        // Create payment with auto-allocation
        $payment = $this->parser->createPaymentFromTransaction($transaction, false);
        
        if ($payment && $payment->unallocated_amount > 0) {
            try {
                $allocationService = app(\App\Services\PaymentAllocationService::class);
                $allocationService->autoAllocate($payment);
            } catch (\Exception $e) {
                Log::warning('Post-creation auto-allocation failed', [
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        return $payment;
    }
    
    /**
     * Helper: Create swimming payment for C2B
     */
    protected function createSwimmingPaymentForC2B($c2bTransaction)
    {
        $student = $c2bTransaction->student;
        if (!$student) {
            throw new \Exception('Student not found for C2B swimming transaction');
        }
        
        $payment = \App\Models\Payment::create([
            'student_id' => $student->id,
            'amount' => $c2bTransaction->trans_amount,
            'payment_method' => 'mpesa',
            'payment_date' => $c2bTransaction->trans_time,
            'receipt_number' => 'REC-' . strtoupper(\Illuminate\Support\Str::random(10)),
            'transaction_code' => $c2bTransaction->trans_id,
            'status' => 'approved',
            'notes' => 'M-PESA Paybill swimming payment - ' . $c2bTransaction->full_name,
            'created_by' => \Illuminate\Support\Facades\Auth::id(),
        ]);
        
        // Update C2B transaction with payment_id and status
        $c2bTransaction->update([
            'payment_id' => $payment->id,
            'status' => 'processed', // Mark as processed when payment is created
        ]);
        
        // Credit swimming wallet
        $swimmingWalletService = app(\App\Services\SwimmingWalletService::class);
        $swimmingWalletService->creditFromTransaction(
            $student,
            $payment,
            $c2bTransaction->trans_amount,
            "Swimming payment from M-PESA transaction #{$c2bTransaction->trans_id}"
        );
        
        $payment->update([
            'allocated_amount' => $c2bTransaction->trans_amount,
            'unallocated_amount' => 0,
        ]);
        
        // Update C2B transaction with payment_id and status
        $c2bTransaction->update([
            'payment_id' => $payment->id,
            'status' => 'processed', // Mark as processed when payment is created
        ]);
        
        return $payment;
    }

    /**
     * Create payment for confirmed transaction (when payment_created is false)
     * This is used for confirmed transactions that don't have payments yet (e.g., after reversal)
     */
    public function createPayment(Request $request, $id)
    {
        $transaction = $this->resolveTransaction($id);
        $isC2B = $transaction instanceof MpesaC2BTransaction;
        
        // Normalize for checks
        $normalized = $this->normalizeTransaction($transaction);
        $bankStatement = (object) $normalized;
        
        if ($bankStatement->status !== 'confirmed') {
            return redirect()->back()
                ->withErrors(['error' => 'Only confirmed transactions can have payments created directly.']);
        }
        
        if ($bankStatement->payment_created) {
            return redirect()->back()
                ->withErrors(['error' => 'Payment already exists for this transaction.']);
        }
        
        if (!$bankStatement->student_id && !($bankStatement->is_shared ?? false)) {
            return redirect()->back()
                ->withErrors(['error' => 'Transaction must be matched to a student or shared before creating payment.']);
        }

        // Check if this is a swimming transaction
        $transaction->refresh();
        $isSwimming = $bankStatement->is_swimming_transaction ?? false;

        if ($isSwimming) {
            return redirect()->back()
                ->withErrors(['error' => 'Swimming transactions are handled separately.']);
        }

        try {
            DB::transaction(function () use ($transaction, $bankStatement, $isC2B) {
                // Check if there are any non-reversed payments for this transaction
                $nonReversedPayments = collect();
                if ($bankStatement->reference_number) {
                    $nonReversedPayments = \App\Models\Payment::where('transaction_code', $bankStatement->reference_number)
                        ->where('reversed', false)
                        ->get();
                }
                
                // If there are non-reversed payments, link to them instead of creating new ones
                if ($nonReversedPayments->isNotEmpty()) {
                    $existingPayment = $bankStatement->payment_id 
                        ? \App\Models\Payment::find($bankStatement->payment_id)
                        : $nonReversedPayments->first();
                    
                    if ($existingPayment && !$existingPayment->reversed) {
                        $transaction->update([
                            'payment_id' => $existingPayment->id,
                            'payment_created' => true,
                        ]);
                        Log::info('Linked to existing non-reversed payment', [
                            'transaction_id' => $transaction->id,
                            'payment_id' => $existingPayment->id,
                        ]);
                    } else {
                        $payment = $nonReversedPayments->first();
                        $transaction->update([
                            'payment_id' => $payment->id,
                            'payment_created' => true,
                        ]);
                        Log::info('Linked to existing non-reversed payment', [
                            'transaction_id' => $transaction->id,
                            'payment_id' => $payment->id,
                        ]);
                    }
                } else {
                    // Before creating new payment, check if there are reversed payments to unlink
                    // This ensures the transaction points to the new payment, not the old reversed one
                    if ($bankStatement->payment_id) {
                        $oldPayment = \App\Models\Payment::find($bankStatement->payment_id);
                        if ($oldPayment && $oldPayment->reversed) {
                            // Unlink the old reversed payment
                            $transaction->update([
                                'payment_id' => null,
                                'payment_created' => false,
                            ]);
                            Log::info('Unlinked old reversed payment before creating new one', [
                                'transaction_id' => $transaction->id,
                                'old_payment_id' => $oldPayment->id,
                            ]);
                        }
                    }
                    
                    // Create payment with auto-allocation enabled
                    if ($isC2B) {
                        $payment = $this->createPaymentForC2B($transaction);
                    } else {
                        $payment = $this->parser->createPaymentFromTransaction($transaction, false);
                    }
                    
                    // Log all payments created (for shared transactions, log all siblings)
                    if (!$isC2B && ($bankStatement->is_shared ?? false) && $bankStatement->reference_number) {
                        // Wait a moment for all payments to be created
                        usleep(500000); // 0.5 seconds
                        
                        $allCreatedPayments = \App\Models\Payment::where('transaction_code', $bankStatement->reference_number)
                            ->where('created_at', '>=', now()->subMinutes(2)) // Payments created in last 2 minutes
                            ->with('student')
                            ->get();
                        
                        Log::info('Payments created from shared bank statement transaction', [
                            'transaction_id' => $bankStatement->id,
                            'reference_number' => $bankStatement->reference_number,
                            'payments_count' => $allCreatedPayments->count(),
                            'expected_count' => count($bankStatement->shared_allocations ?? []),
                            'payment_ids' => $allCreatedPayments->pluck('id')->toArray(),
                            'receipt_numbers' => $allCreatedPayments->pluck('receipt_number')->toArray(),
                            'student_ids' => $allCreatedPayments->pluck('student_id')->toArray(),
                            'student_names' => $allCreatedPayments->map(function($p) {
                                return $p->student ? $p->student->full_name . ' (' . $p->student->admission_number . ')' : 'N/A';
                            })->toArray(),
                            'reversed_status' => $allCreatedPayments->pluck('reversed')->toArray(),
                        ]);
                        
                        // Verify all expected payments were created
                        $expectedStudentIds = collect($bankStatement->shared_allocations)->pluck('student_id')->toArray();
                        $createdStudentIds = $allCreatedPayments->pluck('student_id')->toArray();
                        $missingStudentIds = array_diff($expectedStudentIds, $createdStudentIds);
                        
                        if (!empty($missingStudentIds)) {
                            Log::warning('Some payments were not created for shared transaction', [
                                'transaction_id' => $bankStatement->id,
                                'missing_student_ids' => $missingStudentIds,
                                'expected_student_ids' => $expectedStudentIds,
                                'created_student_ids' => $createdStudentIds,
                            ]);
                        }
                    } else {
                        Log::info('Payment created from bank statement transaction', [
                            'transaction_id' => $bankStatement->id,
                            'payment_id' => $payment->id ?? null,
                            'receipt_number' => $payment->receipt_number ?? null,
                            'student_id' => $payment->student_id ?? null,
                            'reversed' => $payment->reversed ?? false,
                        ]);
                    }
                    
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
                    
                    // Queue receipt generation and notifications
                    if (isset($payment)) {
                        \App\Jobs\ProcessSiblingPaymentsJob::dispatch($transaction->id, $payment->id)
                            ->onQueue('default');
                    }
                }
            });
            
            return redirect()
                ->route('finance.bank-statements.show', $id)
                ->with('success', 'Payment created and allocated successfully.');
        } catch (\Exception $e) {
            Log::error('Failed to create payment from confirmed transaction', [
                'transaction_id' => $transaction->id,
                'error' => $e->getMessage(),
            ]);
            
            return redirect()->back()
                ->withErrors(['error' => 'Failed to create payment: ' . $e->getMessage()]);
        }
    }

    /**
     * Reject transaction - unified for both types
     * Reverses any associated payments, clears matching/confirmation/allocations (including
     * sibling sharing and swimming). Transaction moves to unassigned; user must manually
     * match, allocate, confirm, then create payment.
     */
    public function reject($id)
    {
        $transaction = $this->resolveTransaction($id);
        $isC2B = $transaction instanceof MpesaC2BTransaction;
        
        // Normalize for checks
        $normalized = $this->normalizeTransaction($transaction);
        $bankStatement = (object) $normalized;

        DB::transaction(function () use ($transaction, $bankStatement, $isC2B) {
            // 1. Find and reverse ALL related payments (exact ref + ref-*; all sibling receipts share same source)
            $relatedPayments = collect();
            $ref = $bankStatement->reference_number;
            if ($ref) {
                $relatedPayments = Payment::where('reversed', false)
                    ->where(function ($q) use ($ref) {
                        $q->where('transaction_code', $ref)
                          ->orWhere('transaction_code', 'LIKE', $ref . '-%');
                    })
                    ->get();
            }
            if ($relatedPayments->isEmpty() && $bankStatement->payment_id) {
                $p = Payment::find($bankStatement->payment_id);
                if ($p && !$p->reversed) {
                    $relatedPayments = collect([$p]);
                }
            }
            // Shared transactions: all sibling receipts share a base (only for bank statements)
            // Find by receipt base + prefix so we never miss any sibling (e.g. Dawn)
            if (!$isC2B && ($bankStatement->is_shared ?? false) && $relatedPayments->isNotEmpty()) {
                $receipts = $relatedPayments->pluck('receipt_number')->unique()->filter()->values();
                if ($receipts->isNotEmpty()) {
                    $base = $receipts->sortBy(fn ($r) => strlen($r))->first();
                    $byReceipt = Payment::where('reversed', false)
                        ->where(function ($q) use ($base) {
                            $q->where('receipt_number', $base)
                              ->orWhere('receipt_number', 'LIKE', $base . '-%');
                        })
                        ->get();
                    $relatedPayments = $relatedPayments->merge($byReceipt)->unique('id');
                }
            }

            foreach ($relatedPayments as $payment) {
                if (!$payment || $payment->reversed) {
                    continue;
                }
                $invoiceIds = collect();
                foreach ($payment->allocations as $allocation) {
                    if ($allocation->invoiceItem && $allocation->invoiceItem->invoice) {
                        $invoiceIds->push($allocation->invoiceItem->invoice_id);
                    }
                }
                foreach ($payment->allocations as $allocation) {
                    $allocation->delete();
                }
                $payment->update([
                    'reversed' => true,
                    'reversed_by' => auth()->id(),
                    'reversed_at' => now(),
                    'reversal_reason' => 'Transaction rejected – reset to unassigned',
                ]);
                foreach ($invoiceIds->unique() as $invoiceId) {
                    $invoice = \App\Models\Invoice::find($invoiceId);
                    if ($invoice) {
                        \App\Services\InvoiceService::recalc($invoice);
                    }
                }
                $paymentId = $payment->id;
                $payment->delete();
                Log::info('Payment reversed and deleted due to transaction rejection', [
                    'transaction_id' => $bankStatement->id,
                    'payment_id' => $paymentId,
                ]);
            }

            // 2. Reverse swimming allocations if this is a swimming transaction
            if ($bankStatement->is_swimming_transaction ?? false) {
                // For C2B, check swimming wallet directly; for bank statements, use allocations table
                if ($isC2B) {
                    // Reverse C2B swimming wallet credits
                    if ($transaction->payment_id) {
                        $payment = Payment::find($transaction->payment_id);
                        if ($payment && $transaction->student_id) {
                            $wallet = \App\Models\SwimmingWallet::where('student_id', $transaction->student_id)->first();
                            if ($wallet) {
                                $oldBalance = $wallet->balance;
                                $newBalance = $oldBalance - $transaction->trans_amount;
                                $wallet->update([
                                    'balance' => max(0, $newBalance),
                                    'total_debited' => ($wallet->total_debited ?? 0) + $transaction->trans_amount,
                                    'last_transaction_at' => now(),
                                ]);
                            }
                        }
                    }
                } else {
                    // For bank statements, use allocations table
                    if (Schema::hasTable('swimming_transaction_allocations')) {
                        $swimmingAllocations = \App\Models\SwimmingTransactionAllocation::where('bank_statement_transaction_id', $transaction->id)
                            ->where('status', '!=', \App\Models\SwimmingTransactionAllocation::STATUS_REVERSED)
                            ->get();

                        foreach ($swimmingAllocations as $allocation) {
                    if ($allocation->status === \App\Models\SwimmingTransactionAllocation::STATUS_ALLOCATED) {
                        $wallet = \App\Models\SwimmingWallet::where('student_id', $allocation->student_id)->first();
                        if ($wallet) {
                            $oldBalance = $wallet->balance;
                            $newBalance = $oldBalance - $allocation->amount;
                            $wallet->update([
                                'balance' => $newBalance,
                                'total_debited' => ($wallet->total_debited ?? 0) + $allocation->amount,
                                'last_transaction_at' => now(),
                            ]);
                            if (Schema::hasTable('swimming_ledgers')) {
                                $student = \App\Models\Student::find($allocation->student_id);
                                if ($student) {
                                    \App\Models\SwimmingLedger::create([
                                        'student_id' => $allocation->student_id,
                                        'type' => \App\Models\SwimmingLedger::TYPE_DEBIT,
                                        'amount' => $allocation->amount,
                                        'balance_after' => $newBalance,
                                        'source' => \App\Models\SwimmingLedger::SOURCE_ADJUSTMENT,
                                        'description' => 'Transaction rejected – allocation reversed: ' . ($bankStatement->reference_number ?? 'N/A'),
                                        'created_by' => auth()->id(),
                                    ]);
                                }
                            }
                        }
                    }
                            $updateData = ['status' => \App\Models\SwimmingTransactionAllocation::STATUS_REVERSED];
                            if (Schema::hasColumn('swimming_transaction_allocations', 'reversed_at')) {
                                $updateData['reversed_at'] = now();
                            }
                            if (Schema::hasColumn('swimming_transaction_allocations', 'reversed_by')) {
                                $updateData['reversed_by'] = auth()->id();
                            }
                            $allocation->update($updateData);
                        }
                    }
                }
            }

            // 3. Log audit while transaction still has previous state
            try {
                if (!$isC2B) {
                    \App\Services\FinancialAuditService::logTransactionRejection($transaction);
                }
            } catch (\Exception $e) {
                Log::warning('Failed to log transaction rejection audit', [
                    'transaction_id' => $transaction->id,
                    'error' => $e->getMessage(),
                ]);
            }

            // 4. Reset transaction to unassigned (draft + unmatched) – no MANUALLY_REJECTED so it can be re-matched
            if ($isC2B) {
                $transaction->update([
                    'student_id' => null,
                    'allocation_status' => 'unallocated',
                    'match_confidence' => 0,
                    'match_reason' => null,
                    'status' => 'pending',
                    'payment_id' => null,
                    'is_swimming_transaction' => false,
                ]);
            } else {
                $transaction->update([
                    'status' => 'draft',
                    'student_id' => null,
                    'family_id' => null,
                    'match_status' => 'unmatched',
                    'match_confidence' => 0,
                    'matched_admission_number' => null,
                    'matched_student_name' => null,
                    'matched_phone_number' => null,
                    'match_notes' => null,
                    'payment_id' => null,
                    'payment_created' => false,
                    'is_shared' => false,
                    'shared_allocations' => null,
                    'confirmed_by' => null,
                    'confirmed_at' => null,
                ]);
            }
        });

        return redirect()
            ->route('finance.bank-statements.show', $id)
            ->with('success', 'Transaction rejected and reset to unassigned. You can now manually match, allocate, confirm, and create payment.');
    }

    /**
     * Handle payment conflict: reverse existing payment and create new one
     */
    public function resolveConflictReverse(Request $request, $id)
    {
        $transaction = $this->resolveTransaction($id);
        $isC2B = $transaction instanceof MpesaC2BTransaction;
        
        $request->validate([
            'payment_id' => 'required|exists:payments,id',
            'student_id' => 'required|exists:students,id',
        ]);

        DB::transaction(function () use ($transaction, $request, $isC2B) {
            $payment = Payment::findOrFail($request->payment_id);
            $student = Student::findOrFail($request->student_id);

            // Reverse the conflicting payment
            $invoiceIds = collect();
            foreach ($payment->allocations as $allocation) {
                if ($allocation->invoiceItem && $allocation->invoiceItem->invoice) {
                    $invoiceIds->push($allocation->invoiceItem->invoice_id);
                }
            }
            foreach ($payment->allocations as $allocation) {
                $allocation->delete();
            }
            $payment->update([
                'reversed' => true,
                'reversed_by' => auth()->id(),
                'reversed_at' => now(),
                'reversal_reason' => 'Reversed to resolve payment conflict with transaction #' . $transaction->id,
            ]);
            foreach ($invoiceIds->unique() as $invoiceId) {
                $invoice = \App\Models\Invoice::find($invoiceId);
                if ($invoice) {
                    \App\Services\InvoiceService::recalc($invoice);
                }
            }

            // Now create new payment
            if ($isC2B) {
                $payment = $this->createPaymentForC2B($transaction);
            } else {
                $payment = $this->parser->createPaymentFromTransaction($transaction, false);
            }
            
            if ($payment && $payment->unallocated_amount > 0) {
                try {
                    $allocationService = app(\App\Services\PaymentAllocationService::class);
                    $allocationService->autoAllocate($payment);
                } catch (\Exception $e) {
                    Log::warning('Auto-allocation failed after conflict resolution', [
                        'payment_id' => $payment->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            if (isset($payment)) {
                \App\Jobs\ProcessSiblingPaymentsJob::dispatch($transaction->id, $payment->id)
                    ->onQueue('default');
            }
        });

        return redirect()
            ->route('finance.bank-statements.show', $id)
            ->with('success', 'Conflicting payment reversed and new payment created successfully.');
    }

    /**
     * Handle payment conflict: keep existing payment and link to transaction
     */
    public function resolveConflictKeep(Request $request, $id)
    {
        $request->validate([
            'payment_id' => 'required|exists:payments,id',
        ]);

        $transaction = $this->resolveTransaction($id);
        $isC2B = $transaction instanceof MpesaC2BTransaction;
        
        $payment = Payment::findOrFail($request->payment_id);

        if ($payment->reversed) {
            return redirect()->back()
                ->withErrors(['error' => 'Cannot link a reversed payment to this transaction.']);
        }

        // Link transaction to existing payment
        if ($isC2B) {
            $transaction->update(['payment_id' => $payment->id]);
        } else {
            $transaction->update([
                'payment_id' => $payment->id,
                'payment_created' => true,
            ]);
        }

        return redirect()
            ->route('finance.bank-statements.show', $id)
            ->with('success', 'Transaction linked to existing payment successfully.');
    }

    /**
     * Handle payment conflict: create new payment with different transaction code
     */
    public function resolveConflictCreateNew(Request $request, $id)
    {
        $transaction = $this->resolveTransaction($id);
        $isC2B = $transaction instanceof MpesaC2BTransaction;
        
        // Conflict resolution is primarily for bank statements
        if ($isC2B) {
            return redirect()->back()
                ->withErrors(['error' => 'C2B transactions use different conflict resolution.']);
        }

        DB::transaction(function () use ($transaction) {
            // For shared transactions, we need to create payments for all siblings
            // Temporarily modify reference_number to force unique transaction codes
            $originalRef = $transaction->reference_number;
            $tempRef = $originalRef . '-NEW-' . $transaction->id . '-' . time();
            
            // Temporarily update reference_number
            $transaction->update(['reference_number' => $tempRef]);
            $transaction->refresh();
            
            try {
                $payment = $this->parser->createPaymentFromTransaction($transaction, false);
                
                if ($payment && $payment->unallocated_amount > 0) {
                    try {
                        $allocationService = app(\App\Services\PaymentAllocationService::class);
                        $allocationService->autoAllocate($payment);
                    } catch (\Exception $e) {
                        Log::warning('Auto-allocation failed after conflict resolution', [
                            'payment_id' => $payment->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                if (isset($payment)) {
                    \App\Jobs\ProcessSiblingPaymentsJob::dispatch($transaction->id, $payment->id)
                        ->onQueue('default');
                }
            } finally {
                // Restore original reference_number
                $transaction->update(['reference_number' => $originalRef]);
            }
        });

        return redirect()
            ->route('finance.bank-statements.show', $id)
            ->with('success', 'New payment(s) created with different transaction code successfully.');
    }

    /**
     * Update shared allocations (edit amounts)
     */
    public function updateAllocations(Request $request, $id)
    {
        // Optimistic locking check
        if ($request->has('version') && $transaction->version != $request->version) {
            return back()->with('error', 
                'This transaction was modified by another user. Please refresh the page and try again.'
            );
        }
        
        if (!($bankStatement->is_shared ?? false)) {
            return back()->with('error', 'Can only edit allocations for shared transactions');
        }
        
        // Prevent editing if transaction is rejected
        if ($normalized['status'] === 'rejected') {
            return back()->with('error', 'Cannot edit allocations for a rejected transaction.');
        }
        
        // Allow editing for both draft and confirmed transactions
        // For confirmed transactions with payments, we need to update the related payments too
        if ($normalized['status'] === 'confirmed' && $normalized['payment_created']) {
            // Check if any related payments are reversed
            $relatedPayments = \App\Models\Payment::where('transaction_code', $normalized['reference_number'])
                ->where('reversed', true)
                ->exists();
            
            if ($relatedPayments) {
                return back()->with('error', 
                    'Cannot edit allocations: One or more related payments have been reversed.'
                );
            }
            
            // Check if any payment is fully allocated (prevent editing if it would cause issues)
            $fullyAllocatedPayments = \App\Models\Payment::where('transaction_code', $normalized['reference_number'])
                ->where('reversed', false)
                ->whereRaw('allocated_amount >= amount')
                ->exists();
            
            if ($fullyAllocatedPayments) {
                return back()->with('warning', 
                    'One or more payments are fully allocated. Editing may require payment reversal first.'
                );
            }
        } elseif ($normalized['status'] !== 'draft' && $normalized['status'] !== 'confirmed') {
            return back()->with('error', 'Can only edit allocations for draft or confirmed shared transactions');
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
        
        if (abs($totalAmount - $normalized['amount']) > 0.01) {
            return redirect()->back()
                ->withErrors(['allocations' => 'Total allocation amount must equal transaction amount. Current total: Ksh ' . number_format($totalAmount, 2) . ', Required: Ksh ' . number_format($normalized['amount'], 2)]);
        }

        try {
            // Store old allocations for audit log
            $oldAllocations = $transaction->shared_allocations ?? [];
            
            return DB::transaction(function () use ($transaction, $activeAllocations, $oldAllocations, $normalized) {
                // Update transaction shared allocations
                $transaction->update([
                    'shared_allocations' => $activeAllocations,
                ]);
                
                // If transaction is confirmed and has payments, update the payments too
                if ($normalized['status'] === 'confirmed' && $normalized['payment_created']) {
                    // Find all payments related to this transaction
                    $relatedPayments = \App\Models\Payment::where('transaction_code', $normalized['reference_number'])
                        ->where('reversed', false)
                        ->get();
                    
                    foreach ($activeAllocations as $allocation) {
                        $payment = $relatedPayments->firstWhere('student_id', $allocation['student_id']);
                        
                        if ($payment) {
                            $oldAmount = $payment->amount;
                            $newAmount = (float) $allocation['amount'];
                            
                            if (abs($oldAmount - $newAmount) > 0.01) {
                                // Update payment amount
                                $payment->update(['amount' => $newAmount]);
                                
                                // If amount decreased, deallocate excess
                                if ($newAmount < $oldAmount) {
                                    $excess = $oldAmount - $newAmount;
                                    $remaining = $excess;
                                    $affectedInvoices = collect();
                                    
                                    // Get allocations ordered by date (oldest first) - FIFO
                                    $allocations = \App\Models\PaymentAllocation::where('payment_id', $payment->id)
                                        ->with('invoiceItem.invoice')
                                        ->orderBy('allocated_at', 'asc')
                                        ->get();
                                    
                                    foreach ($allocations as $allocation) {
                                        if ($remaining <= 0) {
                                            break;
                                        }
                                        
                                        $allocationAmount = (float)$allocation->amount;
                                        $invoice = $allocation->invoiceItem->invoice;
                                        
                                        if ($allocationAmount <= $remaining) {
                                            // Delete entire allocation
                                            $remaining -= $allocationAmount;
                                            $allocation->delete();
                                            
                                            if ($invoice && !$affectedInvoices->contains('id', $invoice->id)) {
                                                $affectedInvoices->push($invoice);
                                            }
                                        } else {
                                            // Partially deallocate
                                            $newAllocationAmount = $allocationAmount - $remaining;
                                            $allocation->update(['amount' => $newAllocationAmount]);
                                            $remaining = 0;
                                            
                                            if ($invoice && !$affectedInvoices->contains('id', $invoice->id)) {
                                                $affectedInvoices->push($invoice);
                                            }
                                        }
                                    }
                                    
                                    // Recalculate affected invoices
                                    foreach ($affectedInvoices as $invoice) {
                                        \App\Services\InvoiceService::recalc($invoice);
                                    }
                                }
                                
                                // Update allocation totals
                                $payment->updateAllocationTotals();
                                
                                // Regenerate receipt if exists
                                if ($payment->receipt) {
                                    try {
                                        $receiptService = app(\App\Services\ReceiptService::class);
                                        $receiptService->generateReceipt($payment->fresh(), ['save' => true, 'regenerate' => true]);
                                    } catch (\Exception $e) {
                                        \Log::warning('Failed to regenerate receipt after allocation update', [
                                            'payment_id' => $payment->id,
                                            'error' => $e->getMessage(),
                                        ]);
                                    }
                                }
                            }
                        }
                    }
                }
                
                // Increment version for optimistic locking
                $transaction->increment('version');
                
                // Log audit trail
                try {
                    \App\Services\FinancialAuditService::logTransactionSharedAllocationEdit(
                        $transaction,
                        $oldAllocations,
                        $activeAllocations
                    );
                } catch (\Exception $e) {
                    \Log::warning('Failed to log transaction allocation edit audit', [
                        'transaction_id' => $transaction->id,
                        'error' => $e->getMessage(),
                    ]);
                }
                
                $siblingCount = count($activeAllocations);
                $message = "Shared allocations updated successfully. Payment shared among {$siblingCount} sibling(s).";
                if ($normalized['status'] === 'confirmed' && $normalized['payment_created']) {
                    $message .= " Related payments have been updated.";
                }
                
                return redirect()
                    ->route('finance.bank-statements.show', $id)
                    ->with('success', $message);
            });
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to update allocations: ' . $e->getMessage());
        }
    }
    
    /**
     * Share transaction among siblings
     */
    public function share(Request $request, $id)
    {
        $transaction = $this->resolveTransaction($id);
        $isC2B = $transaction instanceof MpesaC2BTransaction;
        
        // Sharing is only for bank statements (C2B doesn't support sharing in the same way)
        if ($isC2B) {
            return redirect()->back()
                ->withErrors(['error' => 'C2B transactions cannot be shared. Use the allocation feature instead.']);
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

        $normalized = $this->normalizeTransaction($transaction);
        $totalAmount = array_sum(array_column($activeAllocations, 'amount'));
        
        if (abs($totalAmount - $normalized['amount']) > 0.01) {
            return redirect()->back()
                ->withErrors(['allocations' => 'Total allocation amount must equal transaction amount. Current total: Ksh ' . number_format($totalAmount, 2)]);
        }

        try {
            $this->parser->shareTransaction($transaction, $activeAllocations);
            
            $siblingCount = count($activeAllocations);
            return redirect()
                ->route('finance.bank-statements.show', $id)
                ->with('success', "Transaction shared among {$siblingCount} sibling(s).");
        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * View statement PDF (embedded view page) - only for bank statements
     */
    public function viewPdf($id)
    {
        $transaction = $this->resolveTransaction($id);
        $isC2B = $transaction instanceof MpesaC2BTransaction;
        
        if ($isC2B || !$transaction->statement_file_path) {
            abort(404, 'Statement file not found');
        }

        if (!Storage::disk('private')->exists($transaction->statement_file_path)) {
            abort(404, 'Statement file not found');
        }

        // Normalize for view
        $normalized = $this->normalizeTransaction($transaction);
        $bankStatement = (object) $normalized;
        $rawTransaction = $transaction;
        $bankStatementId = $transaction->id;

        return view('finance.bank-statements.view-pdf', compact('bankStatement', 'bankStatementId', 'rawTransaction', 'isC2B'));
    }
    
    /**
     * Serve PDF file directly
     */
    public function servePdf($id)
    {
        $transaction = $this->resolveTransaction($id);
        $isC2B = $transaction instanceof MpesaC2BTransaction;
        
        if ($isC2B || !$transaction->statement_file_path) {
            abort(404, 'Statement file not found');
        }

        if (!Storage::disk('private')->exists($transaction->statement_file_path)) {
            abort(404, 'Statement file not found');
        }

        $path = Storage::disk('private')->path($transaction->statement_file_path);
        
        if (!file_exists($path)) {
            abort(404, 'Statement file not found');
        }

        return response()->file($path, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    /**
     * Download statement PDF - only for bank statements
     */
    public function downloadPdf($id)
    {
        $transaction = $this->resolveTransaction($id);
        $isC2B = $transaction instanceof MpesaC2BTransaction;
        
        if ($isC2B || !$transaction->statement_file_path) {
            abort(404, 'Statement file not found');
        }

        if (!Storage::disk('private')->exists($transaction->statement_file_path)) {
            abort(404, 'Statement file not found');
        }

        return Storage::disk('private')->download($transaction->statement_file_path, 'bank-statement-' . $transaction->id . '.pdf');
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
    public function archive($id)
    {
        $transaction = $this->resolveTransaction($id);
        $isC2B = $transaction instanceof MpesaC2BTransaction;
        
        // Archiving is only for bank statements
        if ($isC2B) {
            return redirect()->back()
                ->with('error', 'C2B transactions cannot be archived.');
        }
        
        return DB::transaction(function () use ($transaction) {
            $paymentsReversed = 0;
            
            // If payment was created, reverse it automatically
            // For shared transactions, reverse ALL related payments
            $normalized = $this->normalizeTransaction($transaction);
            if ($normalized['payment_created']) {
                // Find all payments related to this transaction
                $relatedPayments = [];
                
                if ($transaction->payment_id) {
                    $payment = Payment::find($transaction->payment_id);
                    if ($payment) {
                        $relatedPayments[] = $payment;
                    }
                }
                
                // Also find all sibling payments if this is a shared transaction
                if ($transaction->is_shared && $transaction->reference_number) {
                    $siblingPayments = Payment::where('transaction_code', $transaction->reference_number)
                        ->where('reversed', false)
                        ->get();
                    $relatedPayments = array_merge($relatedPayments, $siblingPayments->all());
                }
                
                // Reverse all related payments
                foreach ($relatedPayments as $payment) {
                    if ($payment && !$payment->reversed) {
                        // Collect invoice IDs before deleting allocations
                        $invoiceIds = collect();
                        foreach ($payment->allocations as $allocation) {
                            if ($allocation->invoiceItem && $allocation->invoiceItem->invoice) {
                                $invoiceIds->push($allocation->invoiceItem->invoice_id);
                            }
                        }
                        
                        // Reverse payment allocations
                        foreach ($payment->allocations as $allocation) {
                            $allocation->delete();
                        }
                        
                        // Mark payment as reversed
                        $payment->update([
                            'reversed' => true,
                            'reversed_by' => auth()->id(),
                            'reversed_at' => now(),
                            'narration' => ($payment->narration ?? '') . ' (Reversed - Transaction archived)',
                        ]);
                        
                        // Recalculate affected invoices
                        foreach ($invoiceIds->unique() as $invoiceId) {
                            $invoice = \App\Models\Invoice::find($invoiceId);
                            if ($invoice) {
                                \App\Services\InvoiceService::recalc($invoice);
                            }
                        }
                        
                        $paymentsReversed++;
                        
                        \Log::info('Payment automatically reversed due to transaction archive', [
                            'transaction_id' => $transaction->id,
                            'payment_id' => $payment->id,
                        ]);
                    }
                }
            }
            
            // Archive the transaction
            $transaction->archive();
            
            // Update transaction to remove payment link and increment version
            $transaction->update([
                'payment_created' => false,
                'payment_id' => null,
            ]);
            $transaction->increment('version');
            
            // Log audit trail
            try {
                \App\Services\FinancialAuditService::logTransactionArchive($transaction, $paymentsReversed);
            } catch (\Exception $e) {
                \Log::warning('Failed to log transaction archive audit', [
                    'transaction_id' => $transaction->id,
                    'error' => $e->getMessage(),
                ]);
            }

            $message = 'Transaction archived';
            if ($paymentsReversed > 0) {
                $message .= " and {$paymentsReversed} related payment(s) reversed";
            }

            return redirect()
                ->route('finance.bank-statements.index', ['view' => 'archived'] + request()->except('view'))
                ->with('success', $message);
        });
    }

    /**
     * Unarchive transaction
     */
    public function unarchive($id)
    {
        $transaction = $this->resolveTransaction($id);
        $isC2B = $transaction instanceof MpesaC2BTransaction;
        
        // Unarchiving is only for bank statements
        if ($isC2B) {
            return redirect()->back()
                ->with('error', 'C2B transactions cannot be unarchived.');
        }
        
        $transaction->unarchive();
        $transaction->increment('version');

        return redirect()
            ->route('finance.bank-statements.index')
            ->with('success', 'Transaction unarchived');
    }
    
    /**
     * Show transaction history/audit trail
     */
    public function history($id)
    {
        $transaction = $this->resolveTransaction($id);
        $isC2B = $transaction instanceof MpesaC2BTransaction;
        
        // History is primarily for bank statements, but we can show C2B too
        $auditLogs = collect();
        if (!$isC2B) {
            $auditLogs = \App\Models\AuditLog::where('auditable_type', BankStatementTransaction::class)
                ->where('auditable_id', $transaction->id)
                ->with('user')
                ->orderBy('created_at', 'desc')
                ->get();
        }
        
        // Normalize for view
        $normalized = $this->normalizeTransaction($transaction);
        $bankStatement = (object) $normalized;
        $rawTransaction = $transaction;
        
        return view('finance.bank-statements.history', compact('bankStatement', 'auditLogs', 'rawTransaction', 'isC2B'));
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
            'transaction_ids.*' => 'required|integer',
        ]);

        $transactionIds = $request->input('transaction_ids', []);
        
        // Separate bank and C2B transaction IDs
        $bankTransactionIds = BankStatementTransaction::whereIn('id', $transactionIds)->pluck('id')->toArray();
        $c2bTransactionIds = MpesaC2BTransaction::whereIn('id', $transactionIds)->pluck('id')->toArray();
        
        // Check if columns exist
        $hasBankSwimmingColumn = Schema::hasColumn('bank_statement_transactions', 'is_swimming_transaction');
        $hasC2BSwimmingColumn = Schema::hasColumn('mpesa_c2b_transactions', 'is_swimming_transaction');
        
        // Get bank transactions
        $bankQuery = BankStatementTransaction::whereIn('id', $bankTransactionIds)
            ->where('is_duplicate', false)
            ->where('is_archived', false);
        $bankTransactions = $bankQuery->get();
        
        // Get C2B transactions
        $c2bQuery = MpesaC2BTransaction::whereIn('id', $c2bTransactionIds)
            ->where('is_duplicate', false);
        $c2bTransactions = $c2bQuery->get();
        
        // Combine for processing
        $transactions = $bankTransactions->concat($c2bTransactions);

        if ($transactions->isEmpty()) {
            return redirect()
                ->route('finance.bank-statements.index', ['view' => 'swimming'])
                ->with('error', 'No valid transactions found. Transactions may be duplicates or archived.');
        }

        $marked = 0;
        $processed = 0;
        $skipped = 0;
        $errors = [];
        
        foreach ($transactions as $transaction) {
            try {
                $isC2B = $transaction instanceof MpesaC2BTransaction;
                $isBank = $transaction instanceof BankStatementTransaction;
                
                // Check if already marked
                $wasAlreadyMarked = false;
                if ($isBank && $hasBankSwimmingColumn) {
                    $wasAlreadyMarked = $transaction->is_swimming_transaction ?? false;
                } elseif ($isC2B && $hasC2BSwimmingColumn) {
                    $wasAlreadyMarked = $transaction->is_swimming_transaction ?? false;
                }
                
                // If not already marked, mark it as swimming
                if (!$wasAlreadyMarked) {
                    if ($isBank) {
                        $this->swimmingTransactionService->markAsSwimming($transaction);
                    } elseif ($isC2B) {
                        // Mark C2B transaction as swimming
                        if (!$hasC2BSwimmingColumn) {
                            throw new \Exception('C2B swimming column not found. Please run migration to add is_swimming_transaction to mpesa_c2b_transactions table.');
                        }
                        $transaction->update(['is_swimming_transaction' => true]);
                        Log::info('C2B transaction marked as swimming', [
                            'transaction_id' => $transaction->id,
                            'amount' => $transaction->trans_amount,
                        ]);
                    }
                    $marked++;
                } else {
                    $skipped++;
                }
                
                // Check if transaction has been allocated to wallets (only for bank transactions)
                $hasAllocations = false;
                if ($isBank && Schema::hasTable('swimming_transaction_allocations')) {
                    $existingAllocations = \App\Models\SwimmingTransactionAllocation::where('bank_statement_transaction_id', $transaction->id)
                        ->where('status', \App\Models\SwimmingTransactionAllocation::STATUS_ALLOCATED)
                        ->exists();
                    $hasAllocations = $existingAllocations;
                }
                
                // If transaction is already assigned, process it immediately to credit swimming wallets
                $isAssigned = false;
                if ($isBank) {
                    $isAssigned = ($transaction->student_id || ($transaction->is_shared && $transaction->shared_allocations));
                } elseif ($isC2B) {
                    $isAssigned = (bool)$transaction->student_id;
                }
                
                if ($isAssigned && !$hasAllocations && $isBank) {
                    // Only process bank transactions (C2B processing would need separate logic)
                    // First validate that students exist
                    $hasValidStudents = false;
                    if ($transaction->student_id) {
                        $hasValidStudents = \App\Models\Student::where('id', $transaction->student_id)->exists();
                    } elseif ($transaction->is_shared && $transaction->shared_allocations) {
                        $studentIds = array_column($transaction->shared_allocations, 'student_id');
                        $validCount = \App\Models\Student::whereIn('id', $studentIds)->count();
                        $hasValidStudents = $validCount > 0;
                    }
                    
                    if (!$hasValidStudents) {
                        $errors[] = "Transaction #{$transaction->id}: Cannot process - assigned student(s) not found. Please reassign to valid student(s) before processing.";
                        Log::warning('Skipping swimming transaction processing - invalid students', [
                            'transaction_id' => $transaction->id,
                            'student_id' => $transaction->student_id,
                            'is_shared' => $transaction->is_shared ?? false,
                        ]);
                        continue; // Skip processing but don't fail the entire operation
                    }
                    
                    try {
                        $this->processSwimmingTransaction($transaction);
                        $processed++;
                        Log::info('Swimming transaction processed', [
                            'transaction_id' => $transaction->id,
                            'type' => 'bank',
                            'status' => $transaction->status ?? 'N/A',
                            'has_student' => (bool)$transaction->student_id,
                            'is_shared' => $transaction->is_shared ?? false,
                            'was_already_marked' => $wasAlreadyMarked,
                        ]);
                    } catch (\Exception $e) {
                        Log::warning('Failed to process swimming transaction', [
                            'transaction_id' => $transaction->id,
                            'error' => $e->getMessage(),
                        ]);
                        // Provide more user-friendly error message
                        $errorMsg = $e->getMessage();
                        if (strpos($errorMsg, 'No query results for model') !== false) {
                            $errorMsg = "Student not found. Please reassign this transaction to a valid student.";
                        }
                        $errors[] = "Transaction #{$transaction->id}: " . $errorMsg;
                    }
                }
            } catch (\Exception $e) {
                $errors[] = "Transaction #{$transaction->id}: " . $e->getMessage();
                Log::error('Failed to mark transaction as swimming', [
                    'transaction_id' => $transaction->id,
                    'type' => $transaction instanceof MpesaC2BTransaction ? 'c2b' : 'bank',
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Build message based on what happened
        $messageParts = [];
        
        if ($marked > 0) {
            $messageParts[] = "Marked {$marked} new transaction(s) as swimming.";
        }
        
        if ($skipped > 0 && $processed > 0) {
            $messageParts[] = "Processed {$processed} already-marked swimming transaction(s) and credited wallets.";
        } elseif ($processed > 0) {
            $messageParts[] = "Processed {$processed} assigned transaction(s) and credited to swimming wallets.";
        }
        
        if ($skipped > 0 && $processed == 0) {
            $messageParts[] = "{$skipped} transaction(s) already marked as swimming.";
        }
        
        // Count unassigned transactions
        $unassignedCount = 0;
        foreach ($transactions as $transaction) {
            $isAssigned = ($transaction->student_id || ($transaction->is_shared && $transaction->shared_allocations));
            if (!$isAssigned) {
                $unassignedCount++;
            }
        }
        
        if ($unassignedCount > 0) {
            $messageParts[] = "{$unassignedCount} unassigned transaction(s) moved to swimming tab - allocate to students to credit wallets.";
        }
        
        $message = !empty($messageParts) ? implode(' ', $messageParts) : "No changes made.";
        
        if (!empty($errors)) {
            // Format errors more user-friendly
            $formattedErrors = array_map(function($error) {
                // Clean up technical error messages
                if (strpos($error, 'No query results for model') !== false) {
                    preg_match('/\[App\\\\Models\\\\Student\] (\d+)/', $error, $matches);
                    if (isset($matches[1])) {
                        return "Transaction has invalid student ID {$matches[1]} (student may have been deleted). Please reassign.";
                    }
                }
                return $error;
            }, array_slice($errors, 0, 5));
            
            $message .= " " . count($errors) . " error(s): " . implode('; ', $formattedErrors);
            if (count($errors) > 5) {
                $message .= " (and " . (count($errors) - 5) . " more)";
            }
        }

        return redirect()
            ->route('finance.bank-statements.index', ['view' => 'swimming'])
            ->with($errors ? 'warning' : 'success', $message);
    }

    /**
     * Unmark individual transaction as swimming
     */
    public function unmarkAsSwimming(Request $request, $id)
    {
        // Check if column exists (only for bank statements)
        if (!$isC2B) {
            $hasSwimmingColumn = Schema::hasColumn('bank_statement_transactions', 'is_swimming_transaction');
            
            if (!$hasSwimmingColumn) {
                return redirect()->back()
                    ->with('error', 'Swimming transaction column does not exist.');
            }
        }

        if (!($transaction->is_swimming_transaction ?? false)) {
            return redirect()->back()
                ->with('error', 'Transaction is not marked as swimming.');
        }

        try {
            $this->swimmingTransactionService->unmarkAsSwimming($transaction);
            
            return redirect()
                ->route('finance.bank-statements.show', $id)
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
        $invalidStudents = [];
        
        if ($transaction->is_shared && $transaction->shared_allocations) {
            // Shared payment - allocate to multiple students
            foreach ($transaction->shared_allocations as $allocation) {
                $studentId = $allocation['student_id'];
                // Validate student exists
                if (!\App\Models\Student::where('id', $studentId)->exists()) {
                    $invalidStudents[] = $studentId;
                    Log::warning('Invalid student ID in shared allocation', [
                        'transaction_id' => $transaction->id,
                        'student_id' => $studentId,
                    ]);
                    continue; // Skip this allocation
                }
                $allocations[] = [
                    'student_id' => $studentId,
                    'amount' => $allocation['amount'],
                ];
            }
        } elseif ($transaction->student_id) {
            // Single student payment - validate student exists
            if (!\App\Models\Student::where('id', $transaction->student_id)->exists()) {
                throw new \Exception("Student ID {$transaction->student_id} not found. The student may have been deleted. Please reassign this transaction to a valid student.");
            }
            $allocations[] = [
                'student_id' => $transaction->student_id,
                'amount' => $transaction->amount,
            ];
        }
        
        if (empty($allocations)) {
            if (!empty($invalidStudents)) {
                throw new \Exception('No valid students found. Student IDs ' . implode(', ', $invalidStudents) . ' do not exist. Please reassign this transaction to valid students.');
            }
            throw new \Exception('No students assigned to transaction');
        }
        
        // Log warning if some students were invalid
        if (!empty($invalidStudents)) {
            Log::warning('Some students in shared allocation were invalid', [
                'transaction_id' => $transaction->id,
                'invalid_student_ids' => $invalidStudents,
                'valid_allocations' => count($allocations),
            ]);
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
    public function destroy($id)
    {
        $transaction = $this->resolveTransaction($id);
        $isC2B = $transaction instanceof MpesaC2BTransaction;
        
        // Destroy is only for bank statements (C2B transactions are deleted differently)
        if ($isC2B) {
            // For C2B, just delete the transaction
            DB::transaction(function () use ($transaction) {
                if ($transaction->payment_id) {
                    $payment = \App\Models\Payment::find($transaction->payment_id);
                    if ($payment) {
                        \App\Models\PaymentAllocation::where('payment_id', $payment->id)->delete();
                        $payment->delete();
                    }
                }
                $transaction->delete();
            });
            
            return redirect()
                ->route('finance.bank-statements.index')
                ->with('success', 'C2B transaction deleted successfully.');
        }
        
        DB::transaction(function () use ($transaction) {
            // Get all transactions from the same statement file
            $statementTransactions = BankStatementTransaction::where('statement_file_path', $transaction->statement_file_path)
                ->get();

            // Delete all related payments
            foreach ($statementTransactions as $txn) {
                if ($txn->payment_id) {
                    $payment = \App\Models\Payment::find($txn->payment_id);
                    if ($payment) {
                        // Delete payment allocations first
                        \App\Models\PaymentAllocation::where('payment_id', $payment->id)->delete();
                        // Delete the payment
                        $payment->delete();
                    }
                }
            }

            // Delete the PDF file
            if ($transaction->statement_file_path && Storage::disk('private')->exists($transaction->statement_file_path)) {
                Storage::disk('private')->delete($transaction->statement_file_path);
            }

            // Delete all transactions from this statement
            BankStatementTransaction::where('statement_file_path', $transaction->statement_file_path)
                ->delete();
        });

        return redirect()
            ->route('finance.bank-statements.index')
            ->with('success', 'Statement and all related records deleted successfully.');
    }
}
