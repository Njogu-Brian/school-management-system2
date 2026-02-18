<?php

namespace App\Services;

use App\Models\BankStatementTransaction;
use App\Models\MpesaC2BTransaction;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Unified Transaction Service
 * Handles both Bank Statement and M-PESA C2B transactions uniformly
 */
class UnifiedTransactionService
{
    protected $bankParser;
    protected $mpesaMatcher;

    public function __construct(BankStatementParser $bankParser, MpesaSmartMatchingService $mpesaMatcher)
    {
        $this->bankParser = $bankParser;
        $this->mpesaMatcher = $mpesaMatcher;
    }

    /**
     * Check for duplicates across both transaction types
     * Uses transaction code/ID, amount, phone, and date
     */
    public function checkDuplicateAcrossTypes($transactionCode, $amount, $phoneNumber, $transactionDate, $excludeId = null, $excludeType = null): ?array
    {
        // Check in bank statement transactions
        $bankDuplicate = BankStatementTransaction::where('reference_number', $transactionCode)
            ->where('amount', $amount)
            ->where('transaction_date', $transactionDate)
            ->when($excludeId && $excludeType === 'bank', fn($q) => $q->where('id', '!=', $excludeId))
            ->first();

        if ($bankDuplicate) {
            return [
                'type' => 'bank',
                'transaction' => $bankDuplicate,
                'id' => $bankDuplicate->id,
            ];
        }

        // Check in C2B transactions
        $c2bDuplicate = MpesaC2BTransaction::where('trans_id', $transactionCode)
            ->where('trans_amount', $amount)
            ->whereDate('trans_time', $transactionDate)
            ->when($excludeId && $excludeType === 'c2b', fn($q) => $q->where('id', '!=', $excludeId))
            ->first();

        if ($c2bDuplicate) {
            return [
                'type' => 'c2b',
                'transaction' => $c2bDuplicate,
                'id' => $c2bDuplicate->id,
            ];
        }

        // Also check by phone + amount + date (within 1 minute) for fuzzy matching
        if ($phoneNumber) {
            $normalizedPhone = $this->normalizePhone($phoneNumber);
            
            // Check bank statements
            $bankFuzzy = BankStatementTransaction::where('phone_number', 'LIKE', '%' . $normalizedPhone . '%')
                ->where('amount', $amount)
                ->whereBetween('transaction_date', [
                    $transactionDate->copy()->subMinute(),
                    $transactionDate->copy()->addMinute(),
                ])
                ->when($excludeId && $excludeType === 'bank', fn($q) => $q->where('id', '!=', $excludeId))
                ->first();

            if ($bankFuzzy) {
                return [
                    'type' => 'bank',
                    'transaction' => $bankFuzzy,
                    'id' => $bankFuzzy->id,
                    'fuzzy' => true,
                ];
            }

            // Do NOT fuzzy-match C2B by amount+time only - different payments can have same amount within a minute.
            // Cross-type C2B duplicate = same trans_id (already checked above).
        }

        return null;
    }

    /**
     * Get unified transactions query (both types combined)
     */
    public function getUnifiedTransactionsQuery(array $filters = [])
    {
        // Get bank statement transactions
        $bankQuery = BankStatementTransaction::query()
            ->select([
                'id',
                DB::raw("'bank' as transaction_type"),
                'transaction_date as trans_date',
                'amount as trans_amount',
                'reference_number as trans_code',
                'description',
                'phone_number',
                'payer_name',
                'student_id',
                'match_status',
                'match_confidence',
                'status',
                'is_duplicate',
                'is_archived',
                'payment_created',
                'is_swimming_transaction',
                'created_at',
            ])
            ->with(['student', 'payment', 'bankAccount']);

        // Get C2B transactions
        $c2bQuery = MpesaC2BTransaction::query()
            ->select([
                'id',
                DB::raw("'c2b' as transaction_type"),
                DB::raw('DATE(trans_time) as trans_date'),
                'trans_amount',
                'trans_id as trans_code',
                DB::raw("CONCAT(COALESCE(first_name, ''), ' ', COALESCE(middle_name, ''), ' ', COALESCE(last_name, '')) as description"),
                'msisdn as phone_number',
                DB::raw("CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) as payer_name"),
                'student_id',
                DB::raw("CASE 
                    WHEN allocation_status = 'auto_matched' THEN 'matched'
                    WHEN allocation_status = 'manually_allocated' THEN 'manual'
                    WHEN allocation_status = 'unallocated' THEN 'unmatched'
                    ELSE 'unmatched'
                END as match_status"),
                'match_confidence',
                DB::raw("CASE 
                    WHEN status = 'processed' THEN 'confirmed'
                    WHEN status = 'failed' THEN 'rejected'
                    ELSE 'draft'
                END as status"),
                'is_duplicate',
                DB::raw('false as is_archived'),
                DB::raw('CASE WHEN payment_id IS NOT NULL THEN true ELSE false END as payment_created'),
                DB::raw('false as is_swimming_transaction'),
                'created_at',
            ])
            ->with(['student', 'payment']);

        // Apply filters to both queries
        $this->applyFilters($bankQuery, $filters, 'bank');
        $this->applyFilters($c2bQuery, $filters, 'c2b');

        // Union and order
        return $bankQuery->union($c2bQuery)
            ->orderBy('trans_date', 'desc')
            ->orderBy('created_at', 'desc');
    }

    /**
     * Apply filters to query
     */
    protected function applyFilters($query, array $filters, string $type)
    {
        $view = $filters['view'] ?? 'all';
        $hasSwimmingColumn = $type === 'bank' && \Illuminate\Support\Facades\Schema::hasColumn('bank_statement_transactions', 'is_swimming_transaction');

        switch ($view) {
            case 'auto-assigned':
                if ($type === 'bank') {
                    $query->where('match_status', 'matched')
                        ->where('match_confidence', '>=', 0.85)
                        ->where('payment_created', false)
                        ->where('is_duplicate', false)
                        ->where('is_archived', false);
                } else {
                    $query->where('match_confidence', '>=', 80)
                        ->whereNull('payment_id')
                        ->where('is_duplicate', false);
                }
                break;
            case 'unassigned':
                if ($type === 'bank') {
                    $query->where('match_status', 'unmatched')
                        ->whereNull('student_id')
                        ->where('is_duplicate', false)
                        ->where('is_archived', false);
                } else {
                    $query->where('allocation_status', 'unallocated')
                        ->whereNull('student_id')
                        ->where('is_duplicate', false);
                }
                break;
            case 'duplicate':
                $query->where('is_duplicate', true);
                if ($type === 'bank') {
                    $query->where('is_archived', false);
                }
                break;
            case 'swimming':
                if ($type === 'bank' && $hasSwimmingColumn) {
                    $query->where('is_swimming_transaction', true)
                        ->where('is_archived', false);
                } else {
                    // C2B doesn't have swimming flag, exclude from swimming view
                    $query->whereRaw('1 = 0');
                }
                break;
            default:
                if ($type === 'bank') {
                    $query->where('is_archived', false);
                }
                $query->where('is_duplicate', false);
        }

        // Additional filters
        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search, $type) {
                if ($type === 'bank') {
                    $q->where('description', 'LIKE', "%{$search}%")
                      ->orWhere('reference_number', 'LIKE', "%{$search}%")
                      ->orWhere('phone_number', 'LIKE', "%{$search}%")
                      ->orWhere('payer_name', 'LIKE', "%{$search}%");
                } else {
                    $q->where('trans_id', 'LIKE', "%{$search}%")
                      ->orWhere('bill_ref_number', 'LIKE', "%{$search}%")
                      ->orWhere('msisdn', 'LIKE', "%{$search}%");
                }
            });
        }

        if (isset($filters['date_from'])) {
            if ($type === 'bank') {
                $query->where('transaction_date', '>=', $filters['date_from']);
            } else {
                $query->whereDate('trans_time', '>=', $filters['date_from']);
            }
        }

        if (isset($filters['date_to'])) {
            if ($type === 'bank') {
                $query->where('transaction_date', '<=', $filters['date_to']);
            } else {
                $query->whereDate('trans_time', '<=', $filters['date_to']);
            }
        }
    }

    /**
     * Normalize phone number
     */
    protected function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        if (strlen($phone) == 12 && substr($phone, 0, 3) == '254') {
            return '0' . substr($phone, 3);
        }
        
        if (strlen($phone) == 10 && substr($phone, 0, 1) == '0') {
            return $phone;
        }
        
        if (strlen($phone) == 9) {
            return '0' . $phone;
        }
        
        return $phone;
    }

    /**
     * Match C2B transaction using bank statement parser logic
     */
    public function matchC2BTransaction(MpesaC2BTransaction $transaction): array
    {
        // First try M-PESA specific matching
        $mpesaMatches = $this->mpesaMatcher->matchTransaction($transaction);
        
        if (!empty($mpesaMatches) && $mpesaMatches[0]['confidence'] >= 80) {
            return $mpesaMatches;
        }

        // If no high-confidence match, try bank statement matching logic
        // Convert C2B transaction to bank statement format for matching
        $description = $transaction->bill_ref_number ?? '';
        if ($transaction->first_name || $transaction->last_name) {
            $description .= ' - ' . $transaction->full_name;
        }

        // Create a temporary bank statement transaction for matching
        $tempBankTxn = new BankStatementTransaction([
            'description' => $description,
            'phone_number' => $transaction->msisdn,
            'amount' => $transaction->trans_amount,
            'reference_number' => $transaction->trans_id,
            'transaction_date' => $transaction->trans_time,
        ]);

        $bankMatches = $this->bankParser->matchTransaction($tempBankTxn);
        
        // Convert bank matches to C2B format
        $convertedMatches = [];
        if (!empty($bankMatches['matches'])) {
            foreach ($bankMatches['matches'] as $match) {
                // Generate reason from match_type if not provided
                $reason = $match['reason'] ?? $this->generateMatchReason($match);
                
                $convertedMatches[] = [
                    'student_id' => $match['student']->id,
                    'student_name' => $match['student']->first_name . ' ' . $match['student']->last_name,
                    'admission_number' => $match['student']->admission_number,
                    'confidence' => $match['confidence'] * 100, // Convert to percentage
                    'reason' => $reason,
                    'match_type' => $match['match_type'] ?? 'bank_parser',
                ];
            }
        }

        return array_merge($mpesaMatches, $convertedMatches);
    }

    /**
     * Generate a match reason from match type and matched value
     */
    protected function generateMatchReason(array $match): string
    {
        $matchType = $match['match_type'] ?? 'unknown';
        $matchedValue = $match['matched_value'] ?? '';
        
        $reasons = [
            'admission_number' => "Matched by admission number: {$matchedValue}",
            'name' => "Matched by student name: {$matchedValue}",
            'phone' => "Matched by phone number: {$matchedValue}",
            'partial_phone' => "Matched by partial phone: {$matchedValue}",
            'parent_name' => "Matched by parent name: {$matchedValue}",
            'historical' => "Matched by historical transaction pattern: {$matchedValue}",
            'historical_phone' => "Matched by historical phone pattern: {$matchedValue}",
            'bank_parser' => "Matched using bank statement parser logic",
        ];
        
        return $reasons[$matchType] ?? "Matched by {$matchType}: {$matchedValue}";
    }
}
