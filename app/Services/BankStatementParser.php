<?php

namespace App\Services;

use App\Models\{
    BankStatementTransaction, Student, ParentInfo, Family, BankAccount, Payment
};
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\UniqueConstraintViolationException;
use Symfony\Component\Process\Process;
use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

class BankStatementParser
{
    /**
     * Extract correct transaction reference from Equity statement description.
     * MPS format: "MPS <phone> <code> <payer>" -> use code not phone.
     * Returns null if no reliable code found (e.g. APP narrations have ref in statement column, not here).
     */
    public static function extractReferenceFromEquityDescription(?string $description): ?string
    {
        if ($description === null || trim($description) === '') {
            return null;
        }
        $desc = trim($description);
        // Equity MPS: MPS <10-12 digits> <alphanumeric code 8-12 chars>
        if (preg_match('/^\s*MPS\s+\d{10,12}\s+([A-Z0-9]{8,12})\b/i', $desc, $m)) {
            return strtoupper($m[1]);
        }
        return null;
    }

    /**
     * Parse a statement PDF and return raw transaction rows (no DB writes).
     * Used to re-extract reference numbers (e.g. APP Transaction Reference column) for existing transactions.
     *
     * @param string $pdfPath Storage-relative path (e.g. statement_file_path from DB) or absolute path to a PDF file
     * @param string|null $bankType Defaults to detection if null
     * @return array List of [tran_date, particulars, credit, debit, transaction_code, ...]
     */
    public function parseStatementToArray(string $pdfPath, ?string $bankType = null): array
    {
        $fullPath = $this->resolvePdfPath($pdfPath);
        if (!$fullPath || !file_exists($fullPath)) {
            return [];
        }
        if (!$bankType) {
            $bankType = $this->detectBankType($fullPath);
        }
        return $this->callPythonParser($fullPath, $bankType);
    }

    /**
     * Resolve PDF path: if absolute (or has drive letter on Windows), use as-is; else resolve via private disk.
     */
    protected function resolvePdfPath(string $pdfPath): string
    {
        $pdfPath = trim($pdfPath);
        if ($pdfPath === '') {
            return '';
        }
        if (preg_match('#^[A-Za-z]:[/\\\\]#', $pdfPath) || str_starts_with($pdfPath, '/')) {
            return $pdfPath;
        }
        return storage_local_path(config('filesystems.private_disk', 'private'), $pdfPath);
    }

    /**
     * Parse bank statement PDF and create draft transactions
     */
    public function parseStatement(string $pdfPath, ?int $bankAccountId = null, ?string $bankType = null): array
    {
        $fullPath = storage_local_path(config('filesystems.private_disk', 'private'), $pdfPath);
        
        // Detect bank type if not provided
        if (!$bankType) {
            $bankType = $this->detectBankType($fullPath);
        }
        
        // Call Python parser
        $transactions = $this->callPythonParser($fullPath, $bankType);
        
        if (empty($transactions)) {
            return [
                'success' => false,
                'message' => 'No transactions found in statement',
                'transactions' => []
            ];
        }
        
        // Create draft transactions and attempt matching
        $created = [];
        $matched = 0;
        $unmatched = 0;
        $duplicates = 0;
        $linkedToExistingPayment = 0;
        
        foreach ($transactions as $txnData) {
            // Map parser output to our format
            // Parser returns: tran_date, particulars, credit, debit, transaction_code
            $transactionDate = $txnData['tran_date'] ?? null;
            $particulars = $txnData['particulars'] ?? '';
            $credit = $txnData['credit'] ?? 0;
            $debit = $txnData['debit'] ?? 0;
            $transactionCode = $txnData['transaction_code'] ?? null;
            
            // Determine amount and type (use credit if available, otherwise debit)
            $amount = $credit > 0 ? $credit : $debit;
            $transactionType = $credit > 0 ? 'credit' : 'debit';
            
            // Extract payer name and phone from particulars
            $payerName = $this->extractPayerName($particulars);
            $phoneNumber = $this->extractPhoneNumber($particulars);
            
            // STEP 1: Check for duplicates in BOTH transaction tables using reference_number
            // If found in either table, skip creating a new transaction
            $isDuplicate = false;
            if ($transactionCode) {
                // Check bank_statement_transactions table
                $existingBankTxn = \App\Models\BankStatementTransaction::where('reference_number', $transactionCode)
                    ->where('amount', $amount)
                    ->whereDate('transaction_date', $transactionDate)
                    ->first();
                
                if ($existingBankTxn) {
                    $isDuplicate = true;
                    \Log::info('Skipping duplicate bank statement transaction', [
                        'reference_number' => $transactionCode,
                        'amount' => $amount,
                        'transaction_date' => $transactionDate,
                        'existing_transaction_id' => $existingBankTxn->id,
                    ]);
                } else {
                    // Check mpesa_c2b_transactions table
                    $existingC2BTxn = \App\Models\MpesaC2BTransaction::where('trans_id', $transactionCode)
                        ->where('trans_amount', $amount)
                        ->whereDate('trans_time', $transactionDate)
                        ->first();
                    
                    if ($existingC2BTxn) {
                        $isDuplicate = true;
                        \Log::info('Skipping duplicate C2B transaction', [
                            'reference_number' => $transactionCode,
                            'amount' => $amount,
                            'transaction_date' => $transactionDate,
                            'existing_c2b_id' => $existingC2BTxn->id,
                        ]);
                    }
                }
            }
            
            // Skip if duplicate found in either transaction table
            if ($isDuplicate) {
                $duplicates++;
                continue;
            }
            
            // STEP 2: Create new bank statement transaction (no duplicate found)
            // Auto-archive debit transactions
            $isArchived = ($transactionType === 'debit');
            
            $transaction = BankStatementTransaction::create([
                'bank_account_id' => $bankAccountId,
                'statement_file_path' => $pdfPath,
                'bank_type' => $bankType,
                'transaction_date' => $transactionDate,
                'amount' => $amount,
                'transaction_type' => $transactionType,
                'reference_number' => $transactionCode,
                'description' => $particulars,
                'phone_number' => $phoneNumber,
                'payer_name' => $payerName,
                'status' => 'draft',
                'is_duplicate' => false,
                'is_archived' => $isArchived,
                'archived_at' => $isArchived ? now() : null,
                'archived_by' => $isArchived ? auth()->id() : null,
                'raw_data' => $txnData,
                'created_by' => auth()->id(),
            ]);

            // If another transaction already exists with same reference+amount+date (duplicate slipped through), mark this one as duplicate
            if ($transactionCode) {
                $existingOther = BankStatementTransaction::where('reference_number', $transactionCode)
                    ->where('amount', $amount)
                    ->where('id', '!=', $transaction->id)
                    ->whereDate('transaction_date', $transactionDate)
                    ->where('is_duplicate', false)
                    ->first();
                if ($existingOther) {
                    $update = [
                        'is_duplicate' => true,
                        'duplicate_of_payment_id' => $existingOther->payment_id,
                    ];
                    if (\Schema::hasColumn('bank_statement_transactions', 'duplicate_of_transaction_id')) {
                        $update['duplicate_of_transaction_id'] = $existingOther->id;
                    }
                    $transaction->update($update);
                    \Log::info('Marked bank statement transaction as duplicate (post-create check)', [
                        'transaction_id' => $transaction->id,
                        'original_id' => $existingOther->id,
                        'reference_number' => $transactionCode,
                    ]);
                    // Skip payment linking and matching for duplicates
                    $created[] = $transaction->id;
                    continue;
                }
            }
            
            // STEP 3: Check if a payment exists with this transaction reference number
            $existingPayment = null;
            if ($transactionCode) {
                $existingPayment = Payment::where('transaction_code', $transactionCode)
                    ->where('reversed', false)
                    ->first();
            }
            
            // Only auto-link if this payment is not already linked to another bank statement transaction
            $paymentAlreadyLinked = false;
            if ($existingPayment) {
                $otherWithPaymentId = BankStatementTransaction::where('payment_id', $existingPayment->id)->where('id', '!=', $transaction->id)->exists();
                $otherWithLinkedIds = false;
                if (\Schema::hasColumn('bank_statement_transactions', 'linked_payment_ids')) {
                    $others = BankStatementTransaction::where('id', '!=', $transaction->id)
                        ->whereNotNull('linked_payment_ids')
                        ->get();
                    foreach ($others as $o) {
                        $ids = is_array($o->linked_payment_ids) ? $o->linked_payment_ids : json_decode($o->linked_payment_ids, true);
                        if (is_array($ids) && in_array((int) $existingPayment->id, array_map('intval', $ids))) {
                            $otherWithLinkedIds = true;
                            break;
                        }
                    }
                }
                $paymentAlreadyLinked = $otherWithPaymentId || $otherWithLinkedIds;
            }
            
            if ($existingPayment && !$paymentAlreadyLinked) {
                // Payment exists and is not already linked to another transaction - link this one
                $transaction->update([
                    'payment_id' => $existingPayment->id,
                    'payment_created' => true,
                    'status' => 'confirmed',
                    'confirmed_at' => now(),
                    'confirmed_by' => auth()->id(),
                    'student_id' => $existingPayment->student_id,
                    'family_id' => optional(\App\Models\Student::find($existingPayment->student_id))->family_id,
                    'match_status' => 'manual',
                    'match_confidence' => 1.0,
                    'match_notes' => 'Automatically linked to existing payment from statement upload',
                ]);
                // Set payment narration from statement description if empty (same as manual payment)
                $narration = $transaction->description ?? '';
                if ($narration !== '' && (trim((string) $existingPayment->narration) === '')) {
                    $existingPayment->update(['narration' => $narration]);
                }
                $linkedToExistingPayment++;
            } else {
                // No payment exists - proceed to normal matching service and await manual confirmation
                $matchResult = $this->matchTransaction($transaction);
                if ($matchResult['matched']) {
                    $matched++;
                } else {
                    $unmatched++;
                }
            }
            
            $created[] = $transaction->id;
        }
        
        // Build success message
        $msgParts = [];
        $msgParts[] = sprintf('Parsed %d transactions', count($transactions));
        if ($duplicates > 0) {
            $msgParts[] = sprintf('%d duplicates (skipped)', $duplicates);
        }
        if ($linkedToExistingPayment > 0) {
            $msgParts[] = sprintf('%d linked to existing payments', $linkedToExistingPayment);
        }
        if ($matched > 0) {
            $msgParts[] = sprintf('%d auto-matched', $matched);
        }
        if ($unmatched > 0) {
            $msgParts[] = sprintf('%d awaiting manual confirmation', $unmatched);
        }
        
        $msg = implode('. ', $msgParts) . '.';
        
        return [
            'success' => true,
            'message' => $msg,
            'transactions' => $created,
            'matched' => $matched,
            'unmatched' => $unmatched,
            'duplicates' => $duplicates,
            'linked_to_existing_payment' => $linkedToExistingPayment,
        ];
    }
    
    /**
     * Match transaction to student by admission number, phone number, parent name, and student name
     */
    public function matchTransaction(BankStatementTransaction $transaction): array
    {
        // Skip matching if transaction was manually rejected or has rejected status
        // Only allow manual assignment after rejection
        if ($transaction->status === 'rejected' || 
            ($transaction->match_notes && strpos($transaction->match_notes, 'MANUALLY_REJECTED') !== false)) {
            return [
                'matched' => false,
                'confidence' => 0,
                'reason' => 'Transaction was manually rejected and requires manual assignment',
                'matches' => [],
            ];
        }
        
        $description = $transaction->description ?? '';
        $phoneNumber = $transaction->phone_number;
        
        $matches = [];

        // Use learned suggestions from past manual assignments (system improves over time)
        $learned = \App\Models\ManualMatchLearning::findSuggestions(
            'bank',
            $transaction->reference_number ?? null,
            $description
        );
        foreach ($learned as $s) {
            $student = Student::with('classroom')->find($s['student_id']);
            if ($student && $student->archive == 0 && !$student->is_alumni) {
                $matches[] = [
                    'student' => $student,
                    'match_type' => 'learned',
                    'confidence' => $s['confidence'] / 100.0,
                    'matched_value' => $s['reason'],
                ];
            }
        }
        
        // Parse MPESA paybill format: "Pay Bill from 25471****156 - FRANCISCAH WAMBUGU Acc. Trevor Osairi"
        $parsedData = $this->parseMpesaPaybillDescription($description);
        
        // Try to match by admission number(s) in description
        // Admission number matches are 100% confident - if admission number is found, it's a definite match
        $admissionNumbers = $this->extractAdmissionNumbers($description);
        
        // Also check for admission numbers in the account field (child_admission_numbers)
        if (!empty($parsedData['child_admission_numbers'])) {
            $admissionNumbers = array_merge($admissionNumbers, $parsedData['child_admission_numbers']);
            $admissionNumbers = array_unique($admissionNumbers);
        }
        
        // If multiple admission numbers found, this is likely a sibling payment
        if (count($admissionNumbers) > 1) {
            $siblingStudents = [];
            foreach ($admissionNumbers as $admissionNumber) {
                $student = $this->findStudentByAdmissionNumber($admissionNumber);
                if ($student) {
                    $siblingStudents[] = $student;
                }
            }
            
            // If we found multiple siblings, mark this as a shared transaction
            if (count($siblingStudents) > 1) {
                // Store all sibling matches for potential sharing
                foreach ($siblingStudents as $student) {
                    $matches[] = [
                        'student' => $student,
                        'match_type' => 'admission_number',
                        'confidence' => 1.0,
                        'matched_value' => $student->admission_number,
                    ];
                }
            } elseif (count($siblingStudents) === 1) {
                // Only one sibling found, match to that one
                $matches[] = [
                    'student' => $siblingStudents[0],
                    'match_type' => 'admission_number',
                    'confidence' => 1.0,
                    'matched_value' => $siblingStudents[0]->admission_number,
                ];
            }
        } else {
            // Single admission number matching
            foreach ($admissionNumbers as $admissionNumber) {
                $student = $this->findStudentByAdmissionNumber($admissionNumber);
                if ($student) {
                    $matches[] = [
                        'student' => $student,
                        'match_type' => 'admission_number',
                        'confidence' => 1.0,
                        'matched_value' => $admissionNumber,
                    ];
                }
            }
        }
        
        // Check if we have any admission number matches (100% confidence)
        $hasAdmissionNumberMatch = !empty(array_filter($matches, fn($m) => ($m['match_type'] ?? '') === 'admission_number'));
        
        // Try to match by student name(s) - handle siblings
        // Only match by name if no admission number match was found (admission number takes priority)
        $studentNames = $this->extractStudentNames($description);
        // Also use child names from account field
        if (!empty($parsedData['child_names'])) {
            $studentNames = array_merge($studentNames, $parsedData['child_names']);
            $studentNames = array_unique($studentNames);
        }
        
        if (!$hasAdmissionNumberMatch && !empty($studentNames)) {
            foreach ($studentNames as $studentName) {
                // Handle full names like "Christiannjenga" (no space) or "Trevor Osairi" (with space)
                $nameParts = preg_split('/\s+/', trim($studentName));
                $firstName = $nameParts[0] ?? '';
                $lastName = count($nameParts) > 1 ? $nameParts[1] : '';
                $isFullName = !empty($firstName) && !empty($lastName);
                
                // First, try exact full name match (case-insensitive)
                if ($isFullName) {
                    $exactMatches = Student::where('archive', 0)
                        ->where('is_alumni', false)
                        ->where(function($q) use ($firstName, $lastName) {
                            // Exact match: first_name = firstName AND last_name = lastName
                            $q->whereRaw('LOWER(TRIM(first_name)) = ?', [strtolower(trim($firstName))])
                              ->whereRaw('LOWER(TRIM(last_name)) = ?', [strtolower(trim($lastName))]);
                        })
                        ->get();
                    
                    // If only one exact match, use it with high confidence
                    if ($exactMatches->count() === 1) {
                        $student = $exactMatches->first();
                        $confidence = 0.95; // Very high confidence for exact full name match
                        
                        // Increase confidence if parent name also matches
                        if ($parsedData['parent_name'] && $this->matchesParentName($student, $parsedData['parent_name'])) {
                            $confidence = 1.0; // 100% if parent name also matches
                        }
                        
                        // Increase confidence if partial phone matches
                        if ($parsedData['partial_phone']) {
                            $phoneMatches = $this->findStudentsByPartialPhone($parsedData['partial_phone']);
                            if (in_array($student->id, array_column($phoneMatches, 'id'))) {
                                $confidence = 1.0; // 100% if phone also matches
                            }
                        }
                        
                        $matches[] = [
                            'student' => $student,
                            'match_type' => 'name',
                            'confidence' => $confidence,
                            'matched_value' => $studentName,
                        ];
                        
                        // Skip fuzzy matching if we have exact match
                        continue;
                    }
                }
                
                // Fall back to fuzzy matching
                $nameMatches = Student::where('archive', 0)
                    ->where('is_alumni', false)
                    ->where(function($q) use ($studentName, $firstName, $lastName) {
                        // Match full name (with or without space)
                        $q->whereRaw("CONCAT(first_name, last_name) LIKE ?", ["%" . str_replace(' ', '', $studentName) . "%"])
                          ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$studentName}%"])
                          ->orWhereRaw("CONCAT(first_name, ' ', middle_name, ' ', last_name) LIKE ?", ["%{$studentName}%"]);
                        
                        // If we have both first and last name, match more precisely
                        if ($firstName && $lastName) {
                            $q->orWhere(function($q2) use ($firstName, $lastName) {
                                $q2->where('first_name', 'LIKE', "%{$firstName}%")
                                   ->where('last_name', 'LIKE', "%{$lastName}%");
                            });
                        } else {
                            // Single name - match first or last
                            $q->orWhere('first_name', 'LIKE', "%{$studentName}%")
                              ->orWhere('last_name', 'LIKE', "%{$studentName}%");
                        }
                    })
                    ->orderBy('created_at', 'desc') // Most recent first
                    ->get();
            
            foreach ($nameMatches as $student) {
                $confidence = 0.70;
                // Increase confidence if parent name also matches
                if ($parsedData['parent_name'] && $this->matchesParentName($student, $parsedData['parent_name'])) {
                    $confidence = 0.90;
                }
                // Increase confidence if partial phone matches
                if ($parsedData['partial_phone'] && $this->matchesPartialPhone($student, $parsedData['partial_phone'])) {
                    $confidence = min(0.95, $confidence + 0.10);
                }
                // Increase confidence if full phone matches
                if ($phoneNumber && $this->matchesPhone($student, $phoneNumber)) {
                    $confidence = min(0.98, $confidence + 0.15);
                }
                
                $matches[] = [
                    'student' => $student,
                    'match_type' => 'name',
                    'confidence' => $confidence,
                    'matched_value' => $studentName,
                ];
            }
            }
        }
        
        // Try to match by partial phone number (first 3 and last 3 digits)
        // Only if no admission number match was found (admission number takes priority)
        if (!$hasAdmissionNumberMatch && $parsedData['partial_phone']) {
            $phoneMatches = $this->findStudentsByPartialPhone($parsedData['partial_phone']);
            // Filter to only active students
            $phoneMatches = array_filter($phoneMatches, fn($s) => $s->archive == 0 && $s->is_alumni == false);
            
            // Cross-reference with child names from account field if available
            $childNames = $parsedData['child_names'] ?? [];
            if (!empty($childNames) && count($phoneMatches) > 1) {
                // Filter phone matches by child names
                $filteredMatches = [];
                foreach ($phoneMatches as $student) {
                    $studentFullName = strtolower($student->first_name . ' ' . $student->last_name);
                    $studentFirstName = strtolower($student->first_name);
                    $studentLastName = strtolower($student->last_name);
                    
                    foreach ($childNames as $childName) {
                        $childNameLower = strtolower(trim($childName));
                        // Check if child name matches student's full name or parts
                        if (stripos($studentFullName, $childNameLower) !== false || 
                            stripos($childNameLower, $studentFirstName) !== false ||
                            stripos($childNameLower, $studentLastName) !== false ||
                            stripos($studentFullName, str_replace(' ', '', $childNameLower)) !== false) {
                            $filteredMatches[] = $student;
                            break;
                        }
                    }
                }
                
                // If we found matches by name, use those; otherwise use all phone matches
                if (!empty($filteredMatches)) {
                    $phoneMatches = $filteredMatches;
                }
            }
            
            // Sort by created_at descending (most recent first)
            usort($phoneMatches, fn($a, $b) => $b->created_at <=> $a->created_at);
            
            // Handle sibling matching: if multiple child names found, check for siblings
            if (count($childNames) > 1 && count($phoneMatches) > 1) {
                $siblingMatches = $this->findSiblingMatches($phoneMatches, $childNames);
                if (!empty($siblingMatches)) {
                    // If siblings found, this should be a shared transaction
                    // For now, take the first sibling match
                    $phoneMatches = [$siblingMatches[0]];
                }
            }
            
            // Only take the first match if multiple found (most recent active student)
            if (count($phoneMatches) > 0) {
                $student = $phoneMatches[0];
                $confidence = 0.80;
                // Increase confidence if parent name also matches
                if ($parsedData['parent_name'] && $this->matchesParentName($student, $parsedData['parent_name'])) {
                    $confidence = 0.92;
                }
                // Increase confidence if student name also matches
                if (!empty($childNames)) {
                    $studentFullName = strtolower($student->first_name . ' ' . $student->last_name);
                    foreach ($childNames as $name) {
                        $nameLower = strtolower(trim($name));
                        if (stripos($studentFullName, $nameLower) !== false || 
                            stripos($studentFullName, str_replace(' ', '', $nameLower)) !== false) {
                            $confidence = 0.95;
                            break;
                        }
                    }
                }
                
                $matches[] = [
                    'student' => $student,
                    'match_type' => 'partial_phone',
                    'confidence' => $confidence,
                    'matched_value' => $parsedData['partial_phone'],
                ];
            }
        }
        
        // Try to match by full phone number (parent/guardian)
        // Only if no admission number match was found (admission number takes priority)
        if (!$hasAdmissionNumberMatch && $phoneNumber) {
            $normalizedPhone = $this->normalizePhone($phoneNumber);
            $phoneMatches = $this->findStudentsByPhone($normalizedPhone);
            
            // Filter to only active students
            $phoneMatches = array_filter($phoneMatches, fn($s) => $s->archive == 0 && $s->is_alumni == false);
            
            // Cross-reference with child names from account field if available
            $childNames = $parsedData['child_names'] ?? [];
            if (!empty($childNames) && count($phoneMatches) > 1) {
                // Filter phone matches by child names
                $filteredMatches = [];
                foreach ($phoneMatches as $student) {
                    $studentFullName = strtolower($student->first_name . ' ' . $student->last_name);
                    $studentFirstName = strtolower($student->first_name);
                    $studentLastName = strtolower($student->last_name);
                    
                    foreach ($childNames as $childName) {
                        $childNameLower = strtolower(trim($childName));
                        // Check if child name matches student's full name or parts
                        if (stripos($studentFullName, $childNameLower) !== false || 
                            stripos($childNameLower, $studentFirstName) !== false ||
                            stripos($childNameLower, $studentLastName) !== false ||
                            stripos($studentFullName, str_replace(' ', '', $childNameLower)) !== false) {
                            $filteredMatches[] = $student;
                            break;
                        }
                    }
                }
                
                // If we found matches by name, use those; otherwise use all phone matches
                if (!empty($filteredMatches)) {
                    $phoneMatches = $filteredMatches;
                }
            }
            
            // Sort by created_at descending (most recent first)
            usort($phoneMatches, fn($a, $b) => $b->created_at <=> $a->created_at);
            
            // Handle sibling matching: if multiple child names found, check for siblings
            if (count($childNames) > 1 && count($phoneMatches) > 1) {
                $siblingMatches = $this->findSiblingMatches($phoneMatches, $childNames);
                if (!empty($siblingMatches)) {
                    // If siblings found, this should be a shared transaction
                    // For now, take the first sibling match
                    $phoneMatches = [$siblingMatches[0]];
                }
            }
            
            // Only take the first match if multiple found (most recent active student)
            if (count($phoneMatches) > 0) {
                $student = $phoneMatches[0];
                $confidence = 0.85;
                // Increase confidence if parent name also matches
                if ($parsedData['parent_name'] && $this->matchesParentName($student, $parsedData['parent_name'])) {
                    $confidence = 0.95;
                }
                // Increase confidence if child name matches
                if (!empty($childNames)) {
                    $studentFullName = strtolower($student->first_name . ' ' . $student->last_name);
                    foreach ($childNames as $name) {
                        $nameLower = strtolower(trim($name));
                        if (stripos($studentFullName, $nameLower) !== false || 
                            stripos($studentFullName, str_replace(' ', '', $nameLower)) !== false) {
                            $confidence = 0.98;
                            break;
                        }
                    }
                }
                
                $matches[] = [
                    'student' => $student,
                    'match_type' => 'phone',
                    'confidence' => $confidence,
                    'matched_value' => $normalizedPhone,
                ];
            }
        }
        
        // Try to match using historical transaction data (previous successful matches)
        // This uses patterns from past transactions to improve matching accuracy
        if (empty($matches) || (count($matches) > 1 && max(array_column($matches, 'confidence')) < 0.90)) {
            $historicalMatches = $this->matchByHistoricalData($transaction, $parsedData, $phoneNumber);
            if (!empty($historicalMatches)) {
                // Merge historical matches, boosting confidence for students with transaction history
                foreach ($historicalMatches as $histMatch) {
                    $existingMatchIndex = null;
                    foreach ($matches as $idx => $match) {
                        if ($match['student']->id === $histMatch['student']->id) {
                            $existingMatchIndex = $idx;
                            break;
                        }
                    }
                    
                    if ($existingMatchIndex !== null) {
                        // Boost confidence for existing match
                        $matches[$existingMatchIndex]['confidence'] = min(1.0, $matches[$existingMatchIndex]['confidence'] + 0.15);
                        $matches[$existingMatchIndex]['match_type'] = $matches[$existingMatchIndex]['match_type'] . '+historical';
                    } else {
                        // Add new match from history
                        $matches[] = $histMatch;
                    }
                }
            }
        }
        
        // Try to match by parent name only (if no other matches found)
        // BUT: Only match by parent name if there's also a student name in the description
        // If only parent name exists (like "DOUGLAS NJOROGE KAMAU" without student name), don't match
        // This prevents false matches where only the payer's name is mentioned
        if (empty($matches) && $parsedData['parent_name'] && !empty($studentNames)) {
            $parentMatches = $this->findStudentsByParentName($parsedData['parent_name']);
            // Filter to only active students
            $parentMatches = array_filter($parentMatches, fn($s) => $s->archive == 0 && $s->is_alumni == false);
            
            foreach ($parentMatches as $student) {
                // Only add if student name also matches (double verification)
                $studentFullName = strtolower($student->first_name . ' ' . $student->last_name);
                $matchesStudentName = false;
                foreach ($studentNames as $name) {
                    if (stripos($studentFullName, strtolower($name)) !== false) {
                        $matchesStudentName = true;
                        break;
                    }
                }
                
                if ($matchesStudentName) {
                    $matches[] = [
                        'student' => $student,
                        'match_type' => 'parent_name',
                        'confidence' => 0.60,
                        'matched_value' => $parsedData['parent_name'],
                    ];
                }
            }
        }
        
        // Process matches
        if (empty($matches)) {
            $transaction->update([
                'match_status' => 'unmatched',
                'match_confidence' => 0,
            ]);
            
            return ['matched' => false, 'matches' => []];
        }
        
        // Remove duplicates (same student matched multiple ways)
        $uniqueMatches = [];
        $seenStudentIds = [];
        foreach ($matches as $match) {
            $studentId = $match['student']->id;
            if (!in_array($studentId, $seenStudentIds)) {
                $uniqueMatches[] = $match;
                $seenStudentIds[] = $studentId;
            }
        }
        
        // Check if this is a sibling payment (multiple admission numbers in account field)
        $isSiblingPayment = !empty($parsedData['child_admission_numbers']) && count($parsedData['child_admission_numbers']) > 1;
        
        if ($isSiblingPayment && count($uniqueMatches) > 1) {
            // Multiple siblings found - mark as shared transaction
            $siblingIds = array_map(fn($m) => $m['student']->id, $uniqueMatches);
            $siblingAllocations = [];
            $amountPerSibling = $transaction->amount / count($uniqueMatches);
            
            foreach ($uniqueMatches as $match) {
                $siblingAllocations[] = [
                    'student_id' => $match['student']->id,
                    'amount' => $amountPerSibling,
                ];
            }
            
            $transaction->update([
                'match_status' => 'matched',
                'match_confidence' => 1.0, // 100% for admission numbers
                'is_shared' => true,
                'shared_allocations' => $siblingAllocations,
                'match_notes' => sprintf('Matched to %d siblings by admission numbers', count($uniqueMatches)),
            ]);
            
            return ['matched' => true, 'matches' => $uniqueMatches, 'multiple' => true, 'shared' => true];
        } elseif (count($uniqueMatches) === 1) {
            // Single match - auto-assign
            $match = $uniqueMatches[0];
            $student = $match['student'];
            
            $transaction->update([
                'student_id' => $student->id,
                'family_id' => $student->family_id,
                'match_status' => 'matched',
                'match_confidence' => $match['confidence'],
                'matched_admission_number' => $match['match_type'] === 'admission_number' ? $match['matched_value'] : null,
                'matched_student_name' => $match['match_type'] === 'name' ? $match['matched_value'] : null,
                'matched_phone_number' => $match['match_type'] === 'phone' ? $match['matched_value'] : null,
                'match_notes' => "Auto-matched by {$match['match_type']}",
            ]);
            
            return ['matched' => true, 'matches' => [$match], 'student_id' => $student->id];
        } else {
            // Multiple matches - flag for manual review
            $transaction->update([
                'match_status' => 'multiple_matches',
                'match_confidence' => max(array_column($uniqueMatches, 'confidence')),
                'match_notes' => sprintf('Found %d possible matches', count($uniqueMatches)),
            ]);
            
            return ['matched' => false, 'matches' => $uniqueMatches, 'multiple' => true];
        }
    }
    
    /**
     * Parse MPESA paybill description format
     * Format: "Pay Bill from 25471****156 - FRANCISCAH WAMBUGU Acc. Trevor Osairi"
     * Returns: ['partial_phone' => '25471...156', 'parent_name' => 'FRANCISCAH WAMBUGU', 'child_name' => 'Trevor Osairi']
     */
    protected function parseMpesaPaybillDescription(string $description): array
    {
        $result = [
            'partial_phone' => null,
            'parent_name' => null,
            'child_name' => null,
        ];
        
        // Extract partial phone: 25471****156 (first 3 and last 3 digits)
        if (preg_match('/(\d{3,5})\*+(\d{3})/', $description, $phoneMatches)) {
            $result['partial_phone'] = $phoneMatches[1] . '...' . $phoneMatches[2];
        }
        
        // Extract parent name: Between "from" and "Acc." or "-"
        // Pattern: "Pay Bill from 25471****156 - FRANCISCAH WAMBUGU Acc. Trevor Osairi"
        if (preg_match('/from\s+\d+\*+[^\s]+\s*-\s*([A-Z][A-Z\s]+?)(?:\s+Acc\.|$)/i', $description, $parentMatches)) {
            $result['parent_name'] = trim($parentMatches[1]);
        } elseif (preg_match('/-\s*([A-Z][A-Z\s]{3,30}?)(?:\s+Acc\.|$)/i', $description, $parentMatches)) {
            $result['parent_name'] = trim($parentMatches[1]);
        }
        
        // Extract child name(s) and admission numbers: After "Acc."
        // Handles: "Acc. Trevor Osairi", "Acc. Imani wakina", "Acc. susan & david", "Acc. RKS066&RKS233&RKS702", "Acc. Christiannjenga"
        if (preg_match('/Acc\.\s*([A-Z0-9][A-Za-z0-9\s&]+?)(?:\s|$)/i', $description, $childMatches)) {
            $childNameString = trim($childMatches[1]);
            $result['child_name'] = $childNameString;
            
            // Check if it contains admission numbers (RKS format)
            if (preg_match_all('/RKS\s*\d{3,}/i', $childNameString, $admMatches)) {
                $result['child_admission_numbers'] = array_map(function($m) {
                    return preg_replace('/\s+/', '', strtoupper($m));
                }, $admMatches[0]);
            }
            
            // Extract individual names - preserve full names (2 words) before splitting
            $childNames = [];
            
            // First, extract full names (2 words) like "Imani wakina" or "Trevor Osairi"
            // Split by "and" or "&" first to separate multiple students
            $studentParts = preg_split('/\s*(?:&|and)\s*/i', $childNameString);
            
            foreach ($studentParts as $part) {
                $part = trim($part);
                
                // Skip if it's an admission number
                if (preg_match('/^RKS\d+$/i', $part)) {
                    continue;
                }
                
                // Check if it's a full name (2 words) - preserve it as full name
                if (preg_match('/^([A-Z][a-z]+\s+[a-z]+)$/i', $part, $fullNameMatch)) {
                    // Full name like "Imani wakina" - capitalize properly
                    $fullName = ucwords(strtolower($part));
                    $childNames[] = $fullName;
                } elseif (preg_match('/^([A-Z][a-z]+\s+[A-Z][a-z]+)$/', $part, $fullNameMatch)) {
                    // Full name like "Imani Wakina" - already proper case
                    $childNames[] = $part;
                } elseif (strlen($part) > 2) {
                    // Single name or other format
                    $childNames[] = $part;
                }
            }
            
            $result['child_names'] = array_unique(array_filter($childNames, function($name) {
                return strlen(trim($name)) > 1 && !preg_match('/^RKS\d+$/i', trim($name));
            }));
        }
        
        return $result;
    }
    
    /**
     * Extract admission numbers from description (handles multiple: RKS000 RKS001, RKS000 & RKS001)
     */
    protected function extractAdmissionNumbers(string $description): array
    {
        $admissionNumbers = [];
        
        // Patterns: RKS000, RKS 000, RKS412, RKS000 RKS001, RKS000 & RKS001
        // Also handle "Acc. RKS412" format
        // Match the full "RKS" + digits pattern
        $patterns = [
            '/\bRKS\s*(\d{3,})\b/i',        // RKS000, RKS 000, RKS412 (standalone word)
            '/Acc\.\s*RKS\s*(\d{3,})/i',   // Acc. RKS412, Acc. RKS 412
            '/ADM[-\s]?(\d+)/i',            // ADM123, ADM-123
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $description, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    // Get the captured group (admission number digits)
                    $digits = $match[1] ?? null;
                    if ($digits) {
                        // Always return in RKS format (e.g., RKS412)
                        $admissionNumber = 'RKS' . str_pad($digits, 3, '0', STR_PAD_LEFT);
                        $admissionNumbers[] = $admissionNumber;
                    }
                }
            }
        }
        
        return array_unique($admissionNumbers);
    }
    
    /**
     * Extract student names from description (handles siblings: Trevor Susan, trevor and susan, Trevor & Susan)
     */
    protected function extractStudentNames(string $description): array
    {
        $names = [];
        
        // Extract from "Acc." pattern: "Acc. Trevor Osairi" or "Acc. Imani wakina" or "Acc. Trevor Susan"
        if (preg_match('/Acc\.\s*([A-Z][A-Za-z\s&]+?)(?:\s|$)/i', $description, $matches)) {
            $nameString = trim($matches[1]);
            
            // First, try to extract full names (2 words) before splitting
            // Pattern: "Imani wakina" or "Trevor Osairi" (case-insensitive first word, case-insensitive second word)
            if (preg_match_all('/\b([A-Z][a-z]+\s+[a-z]+)\b/i', $nameString, $fullNameMatches)) {
                foreach ($fullNameMatches[1] as $fullName) {
                    $fullName = trim($fullName);
                    // Capitalize first letter of each word for consistency
                    $fullName = ucwords(strtolower($fullName));
                    if (strlen($fullName) > 5) { // At least 6 characters for a full name
                        $names[] = $fullName;
                    }
                }
            }
            
            // Also split by "and", "&" to handle multiple names
            $nameParts = preg_split('/\s+(?:and|&)\s+/i', $nameString);
            foreach ($nameParts as $part) {
                $part = trim($part);
                // If it's a 2-word name, add it as full name
                if (preg_match('/^([A-Z][a-z]+\s+[a-z]+)$/i', $part)) {
                    $part = ucwords(strtolower($part));
                    if (strlen($part) > 5) {
                        $names[] = $part;
                    }
                } elseif (strlen($part) > 2 && preg_match('/^[A-Z][a-z]+/', $part)) {
                    // Single name
                    $names[] = $part;
                }
            }
        }
        
        // Also try to extract capitalized names (2-3 words) that look like student names from anywhere in description
        // Pattern: "Imani wakina" (case-insensitive) or "Imani Wakina" (proper case)
        if (preg_match_all('/\b([A-Z][a-z]+\s+[a-z]+)\b/i', $description, $nameMatches)) {
            foreach ($nameMatches[1] as $name) {
                // Exclude common words and patterns
                $exclude = ['Pay', 'Bill', 'From', 'Acc', 'Online', 'MPESA', 'Bank', 'Transfer', 'Erastus Ongeso'];
                $name = ucwords(strtolower(trim($name)));
                if (!in_array($name, $exclude) && strlen($name) > 5) {
                    $names[] = $name;
                }
            }
        }
        
        // Also extract proper case names (2 words with both capitalized)
        if (preg_match_all('/\b([A-Z][a-z]+\s+[A-Z][a-z]+)\b/', $description, $properCaseMatches)) {
            foreach ($properCaseMatches[1] as $name) {
                $exclude = ['Pay Bill', 'From 254', 'Acc Erastus'];
                if (!in_array($name, $exclude) && strlen($name) > 5) {
                    $names[] = $name;
                }
            }
        }

        // Child name in small or capital or combined: "GABRIELA MUTHONI", "gabriela muthoni", "Gabriela MUTHONI"
        // After "Acc." we may have 2+ words (name) possibly followed by RKS123 - match 2+ letter words
        if (preg_match_all('/Acc\.\s*([A-Za-z\s]+?)(?:\s+RKS\d+|$)/i', $description, $accMatches)) {
            foreach ($accMatches[1] as $segment) {
                $segment = trim($segment);
                if (preg_match('/^([A-Za-z]+\s+[A-Za-z]+)(?:\s+[A-Za-z]+)*$/i', $segment, $m)) {
                    $name = ucwords(strtolower(trim($m[1])));
                    if (strlen($name) > 5 && !preg_match('/^RKS\d+$/i', $name)) {
                        $names[] = $name;
                    }
                }
            }
        }
        
        return array_unique($names);
    }
    
    /**
     * Find students by partial phone (first 3 and last 3 digits)
     */
    protected function findStudentsByPartialPhone(string $partialPhone): array
    {
        // Format: "25471...156" or "25471****156"
        $parts = preg_split('/\.\.\.|\*+/', $partialPhone);
        if (count($parts) !== 2) {
            return [];
        }
        
        $prefix = $parts[0]; // First 3-5 digits
        $suffix = $parts[1]; // Last 3 digits
        
        $students = [];
        
        // Search in parent_info table
        $parents = ParentInfo::where(function($q) use ($prefix, $suffix) {
            $q->where('father_phone', 'LIKE', "{$prefix}%{$suffix}")
              ->orWhere('mother_phone', 'LIKE', "{$prefix}%{$suffix}")
              ->orWhere('guardian_phone', 'LIKE', "{$prefix}%{$suffix}")
              ->orWhere('father_whatsapp', 'LIKE', "{$prefix}%{$suffix}")
              ->orWhere('mother_whatsapp', 'LIKE', "{$prefix}%{$suffix}")
              ->orWhere('guardian_whatsapp', 'LIKE', "{$prefix}%{$suffix}");
        })->get();
        
        foreach ($parents as $parent) {
            $students = array_merge($students, $parent->students->all());
        }
        
        return $students;
    }
    
    /**
     * Find students by parent name
     */
    protected function findStudentsByParentName(string $parentName): array
    {
        $students = [];
        
        // Normalize parent name for searching
        $parentName = trim($parentName);
        if (empty($parentName)) {
            return [];
        }
        
        // Search in parent_info table using the actual column names
        // parent_info has: father_name, mother_name, guardian_name (full names, not split)
        $parents = ParentInfo::where(function($q) use ($parentName) {
            $q->where('father_name', 'LIKE', "%{$parentName}%")
              ->orWhere('mother_name', 'LIKE', "%{$parentName}%")
              ->orWhere('guardian_name', 'LIKE', "%{$parentName}%");
        })->get();
        
        foreach ($parents as $parent) {
            $students = array_merge($students, $parent->students->all());
        }
        
        return $students;
    }
    
    /**
     * Check if student's parent name matches
     */
    protected function matchesParentName(Student $student, string $parentName): bool
    {
        if (!$student->parentInfo) {
            return false;
        }
        
        $parent = $student->parentInfo;
        $parentName = trim($parentName);
        
        // Check father name (full name stored in single column)
        if ($parent->father_name) {
            if (stripos($parent->father_name, $parentName) !== false) {
                return true;
            }
        }
        
        // Check mother name (full name stored in single column)
        if ($parent->mother_name) {
            if (stripos($parent->mother_name, $parentName) !== false) {
                return true;
            }
        }
        
        // Check guardian name (full name stored in single column)
        if ($parent->guardian_name) {
            if (stripos($parent->guardian_name, $parentName) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if student's parent phone matches partial phone
     */
    protected function matchesPartialPhone(Student $student, string $partialPhone): bool
    {
        if (!$student->parentInfo) {
            return false;
        }
        
        $parts = preg_split('/\.\.\.|\*+/', $partialPhone);
        if (count($parts) !== 2) {
            return false;
        }
        
        $prefix = $parts[0];
        $suffix = $parts[1];
        
        $parent = $student->parentInfo;
        $phones = [
            $parent->father_phone,
            $parent->mother_phone,
            $parent->guardian_phone,
            $parent->father_whatsapp,
            $parent->mother_whatsapp,
            $parent->guardian_whatsapp,
        ];
        
        foreach ($phones as $phone) {
            if ($phone && preg_match("/^{$prefix}.*{$suffix}$/", $phone)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Extract admission number from description (legacy method for single admission)
     */
    protected function extractAdmissionNumber(string $description): ?string
    {
        $admissionNumbers = $this->extractAdmissionNumbers($description);
        return $admissionNumbers[0] ?? null;
    }
    
    /**
     * Extract payer name from description
     */
    protected function extractPayerName(string $description): ?string
    {
        if (empty($description)) {
            return null;
        }
        
        // Common patterns for payer names in MPESA/Equity statements
        // Look for patterns like "FROM JOHN DOE", "PAID BY JANE SMITH", etc.
        $patterns = [
            '/FROM\s+([A-Z][A-Z\s]{2,30})/i',
            '/PAID\s+BY\s+([A-Z][A-Z\s]{2,30})/i',
            '/SENT\s+FROM\s+([A-Z][A-Z\s]{2,30})/i',
            '/RECEIVED\s+FROM\s+([A-Z][A-Z\s]{2,30})/i',
            '/BY\s+([A-Z][A-Z\s]{2,30})/i',
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $description, $matches)) {
                $name = trim($matches[1]);
                // Filter out common words that aren't names
                $exclude = ['MPESA', 'PAYBILL', 'SENT', 'RECEIVED', 'FROM', 'TO', 'KES', 'SCHOOL', 'FEES', 'ADMISSION'];
                $words = explode(' ', $name);
                $filtered = array_filter($words, fn($w) => !in_array(strtoupper($w), $exclude) && strlen($w) > 2);
                
                if (count($filtered) >= 1) {
                    return implode(' ', $filtered);
                }
            }
        }
        
        // Fallback: Look for capitalized words at the start (likely names)
        if (preg_match('/^([A-Z][a-z]+(?:\s+[A-Z][a-z]+){0,2})/', $description, $matches)) {
            $name = trim($matches[1]);
            $exclude = ['MPESA', 'PAYBILL', 'SENT', 'RECEIVED', 'FROM', 'TO', 'KES', 'SCHOOL', 'FEES'];
            $words = explode(' ', $name);
            $filtered = array_filter($words, fn($w) => !in_array(strtoupper($w), $exclude));
            
            if (count($filtered) >= 1) {
                return implode(' ', $filtered);
            }
        }
        
        return null;
    }
    
    /**
     * Extract phone number from description
     */
    protected function extractPhoneNumber(string $description): ?string
    {
        if (empty($description)) {
            return null;
        }
        
        // Kenyan phone number patterns: 254XXXXXXXXX, 07XXXXXXXX, +254XXXXXXXXX
        $patterns = [
            '/254\d{9}/',           // 254XXXXXXXXX
            '/\+254\d{9}/',         // +254XXXXXXXXX
            '/0[17]\d{8}/',          // 07XXXXXXXX or 01XXXXXXXX
            '/\b(\d{10,12})\b/',     // 10-12 digit numbers (fallback)
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $description, $matches)) {
                $phone = $matches[0];
                // Normalize to 254 format
                if (strpos($phone, '0') === 0 && strlen($phone) === 10) {
                    $phone = '254' . substr($phone, 1);
                } elseif (strpos($phone, '+') === 0) {
                    $phone = substr($phone, 1);
                }
                
                // Validate it's a reasonable phone number (9-12 digits after country code)
                if (preg_match('/^254\d{9}$/', $phone)) {
                    return $phone;
                }
            }
        }
        
        return null;
    }
    
    /**
     * Extract student name from description (legacy method - uses extractStudentNames)
     */
    protected function extractStudentName(string $description): ?string
    {
        $names = $this->extractStudentNames($description);
        return $names[0] ?? null;
    }
    
    /**
     * Find students by parent/guardian phone number
     */
    public function findStudentsByPhone(string $phoneNumber): array
    {
        $students = [];
        
        // Search in parent_info table
        $parents = ParentInfo::where(function($q) use ($phoneNumber) {
            $q->where('father_phone', 'LIKE', "%{$phoneNumber}%")
              ->orWhere('mother_phone', 'LIKE', "%{$phoneNumber}%")
              ->orWhere('guardian_phone', 'LIKE', "%{$phoneNumber}%")
              ->orWhere('father_whatsapp', 'LIKE', "%{$phoneNumber}%")
              ->orWhere('mother_whatsapp', 'LIKE', "%{$phoneNumber}%")
              ->orWhere('guardian_whatsapp', 'LIKE', "%{$phoneNumber}%");
        })->get();
        
        foreach ($parents as $parent) {
            $students = array_merge($students, $parent->students->all());
        }
        
        return $students;
    }
    
    /**
     * Normalize phone number to standard format
     */
    public function normalizePhone(?string $phone): ?string
    {
        if (!$phone) {
            return null;
        }
        
        // Remove spaces, dashes, plus signs
        $phone = preg_replace('/[\s\-+]/', '', $phone);
        
        // Convert 07XXXXXXXX to 2547XXXXXXXX
        if (preg_match('/^0(\d{9})$/', $phone, $matches)) {
            return '254' . $matches[1];
        }
        
        // Ensure starts with 254
        if (!str_starts_with($phone, '254') && strlen($phone) === 9) {
            return '254' . $phone;
        }
        
        return $phone;
    }
    
    /**
     * Get Python command (python3 or python)
     */
    protected function getPythonCommand(): string
    {
        // Check if python3 is available
        $process = new Process(['python3', '--version'], base_path());
        $process->run();
        if ($process->isSuccessful()) {
            return 'python3';
        }
        
        // Fallback to python
        $process = new Process(['python', '--version'], base_path());
        $process->run();
        if ($process->isSuccessful()) {
            return 'python';
        }
        
        // Default to python3 (most common on Linux)
        return 'python3';
    }
    
    /**
     * Call Python parser. Uses Equity parser (Evimeria) for equity statements;
     * uses existing bank_statement_parser for M-Pesa. C2B transactions are not affected (they come from API).
     */
    protected function callPythonParser(string $pdfPath, string $bankType): array
    {
        $script = $bankType === 'equity'
            ? base_path('app/Services/python/equity_statement_parser.py')
            : base_path('app/Services/python/bank_statement_parser.py');
        // Equity: Evimeria parser (Equity bank statement format). M-Pesa: existing parser (paybill format).
        $pythonCmd = $this->getPythonCommand();
        $cmd = [$pythonCmd, $script, $pdfPath];
        
        $process = new Process($cmd, base_path());
        $process->setTimeout(300); // 5 minutes timeout
        $process->run();
        
        if (!$process->isSuccessful()) {
            Log::error('Python bank statement parser failed', [
                'cmd' => $cmd,
                'exit' => $process->getExitCode(),
                'stderr' => $process->getErrorOutput(),
                'stdout' => $process->getOutput(),
            ]);
            return [];
        }
        
        $output = $process->getOutput();
        $decoded = json_decode($output, true);
        
        if (!is_array($decoded)) {
            Log::error('Python parser returned invalid JSON', [
                'cmd' => $cmd,
                'stdout' => $output,
                'stderr' => $process->getErrorOutput(),
            ]);
            return [];
        }
        
        return $decoded;
    }
    
    /**
     * Detect bank type from PDF content
     */
    protected function detectBankType(string $fullPath): string
    {
        try {
            $script = base_path('app/Services/python/parse.py');
            $cmd = ['python', $script, $fullPath];
            
            $process = new Process($cmd, base_path());
            $process->run();
            
            if ($process->isSuccessful()) {
                $output = $process->getOutput();
                $lines = json_decode($output, true) ?? [];
                $text = implode(' ', $lines);
                
                if (stripos($text, 'mpesa') !== false || stripos($text, 'safaricom') !== false) {
                    return 'mpesa';
                }
                if (stripos($text, 'equity') !== false || stripos($text, 'equity bank') !== false) {
                    return 'equity';
                }
            }
        } catch (\Exception $e) {
            Log::warning('Bank type detection failed', ['error' => $e->getMessage()]);
        }
        
        return 'mpesa'; // Default to MPESA
    }
    
    /**
     * Share transaction amount among siblings
     */
    public function shareTransaction(BankStatementTransaction $transaction, array $allocations): bool
    {
        // allocations: [['student_id' => X, 'amount' => Y], ...]
        $totalAmount = array_sum(array_column($allocations, 'amount'));
        
        if ($totalAmount - $transaction->amount > 0.01) {
            throw new \Exception('Total allocation amount cannot exceed transaction amount');
        }
        
        // Clear MANUALLY_REJECTED marker when manually shared
        $matchNotes = $transaction->match_notes ?? '';
        if (strpos($matchNotes, 'MANUALLY_REJECTED') !== false) {
            $matchNotes = 'Manually shared among siblings';
        } else {
            $matchNotes = $matchNotes ?: 'Manually shared among siblings';
        }
        
        // If transaction is rejected or unmatched, change to draft when sharing
        // Also change to draft if confirmed but payment_created is false (unallocated uncollected - payment was reversed)
        $newStatus = $transaction->status;
        if (in_array($transaction->status, ['rejected', 'unmatched'])) {
            $newStatus = 'draft';
        } elseif ($transaction->status === 'confirmed' && !$transaction->payment_created) {
            // Transaction is confirmed but payment was reversed - allow sharing by changing to draft
            $newStatus = 'draft';
        }

        if ($totalAmount + 0.01 < $transaction->amount) {
            $newStatus = 'draft';
            $matchNotes = $matchNotes . ' (Partially allocated; remaining balance unallocated)';
        }
        
        $transaction->update([
            'is_shared' => true,
            'shared_allocations' => $allocations,
            'status' => $newStatus, // Ensure it's draft if it was rejected/unmatched or confirmed with reversed payment
            'match_status' => 'manual', // Set to manual since it was manually shared
            'match_notes' => $matchNotes,
        ]);
        
        return true;
    }
    
    /**
     * Create payment from confirmed transaction
     */
    public function createPaymentFromTransaction(BankStatementTransaction $transaction, bool $skipAllocation = false): \App\Models\Payment
    {
        if (!$transaction->isConfirmed()) {
            throw new \Exception('Transaction must be confirmed before creating payment');
        }
        
        if ($transaction->transaction_date) {
            $transactionDate = Carbon::parse($transaction->transaction_date);
            if ($transactionDate->gt(now()->endOfDay())) {
                throw new \Exception('Cannot create payment with future payment date (' . $transactionDate->format('d M Y') . ').');
            }
        }
        
        if ($transaction->payment_created) {
            // Check if payment still exists
            if ($transaction->payment_id) {
                $existingPayment = \App\Models\Payment::find($transaction->payment_id);
                if ($existingPayment) {
                    return $existingPayment;
                }
            }
            // Payment was marked as created but doesn't exist, reset the flag
            $transaction->update(['payment_created' => false, 'payment_id' => null]);
        }
        
        // Check if payment already exists by transaction code AND student_id (exclude reversed – create new instead)
        // Skip early return for shared transactions – we need to create ALL sibling payments
        if ($transaction->reference_number && !($transaction->is_shared && $transaction->shared_allocations)) {
            // First check by exact transaction_code and student_id (respects unique constraint)
            if ($transaction->student_id) {
                $existingPayment = \App\Models\Payment::where('transaction_code', $transaction->reference_number)
                    ->where('student_id', $transaction->student_id)
                    ->where('reversed', false)
                    ->first();
                
                if ($existingPayment) {
                    // Link transaction to existing payment
                    $transaction->update([
                        'payment_id' => $existingPayment->id,
                        'payment_created' => true,
                    ]);
                    
                    \Log::info('Found existing payment for transaction (by student_id)', [
                        'transaction_id' => $transaction->id,
                        'payment_id' => $existingPayment->id,
                        'transaction_code' => $transaction->reference_number,
                        'student_id' => $transaction->student_id,
                    ]);
                    
                    return $existingPayment;
                }
            }
            
            // Fallback: Check by transaction_code and payment_date (for cases where student might have changed)
            $existingPayment = \App\Models\Payment::where('transaction_code', $transaction->reference_number)
                ->where('payment_date', $transaction->transaction_date)
                ->where('reversed', false)
                ->first();
            
            if ($existingPayment) {
                // If payment exists but for different student, this is a conflict
                if ($transaction->student_id && $existingPayment->student_id != $transaction->student_id) {
                    \Log::warning('Payment exists for different student', [
                        'transaction_id' => $transaction->id,
                        'existing_payment_id' => $existingPayment->id,
                        'existing_student_id' => $existingPayment->student_id,
                        'transaction_student_id' => $transaction->student_id,
                        'transaction_code' => $transaction->reference_number,
                    ]);
                    
                    throw new \Exception(
                        "A payment already exists for transaction code '{$transaction->reference_number}' " .
                        "but for a different student. Payment ID: {$existingPayment->id}, " .
                        "Existing Student ID: {$existingPayment->student_id}, " .
                        "Transaction Student ID: {$transaction->student_id}. " .
                        "Please resolve this conflict before confirming."
                    );
                }
                
                // Link transaction to existing payment
                $transaction->update([
                    'payment_id' => $existingPayment->id,
                    'payment_created' => true,
                ]);
                
                \Log::info('Found existing payment for transaction (by payment_date)', [
                    'transaction_id' => $transaction->id,
                    'payment_id' => $existingPayment->id,
                    'transaction_code' => $transaction->reference_number,
                ]);
                
                return $existingPayment;
            }
        }
        
        if ($transaction->is_shared && $transaction->shared_allocations) {
            $finalSharedReceiptNumber = \App\Services\ReceiptNumberService::generateForPayment();

            // Create payments for each sibling: first gets base, next base-01, base-02, etc.
            $payments = [];
            foreach ($transaction->shared_allocations as $index => $allocation) {
                $student = Student::findOrFail($allocation['student_id']);
                $payment = $this->createSinglePayment($transaction, $student, $allocation['amount'], $finalSharedReceiptNumber, $skipAllocation, $index);
                $payments[] = $payment;
            }
            
            // Update transaction with first payment ID (for reference) and mark as collected
            // Also update match_status to 'manual' since it's been confirmed and collected
            $transaction->update([
                'payment_id' => $payments[0]->id,
                'payment_created' => true,
                'status' => 'confirmed', // Ensure it's confirmed when payment is created
                'match_status' => 'manual', // Update match_status to manual when collected
            ]);
            
            return $payments[0];
        } else {
            // Create single payment
            $student = Student::findOrFail($transaction->student_id);
            $payment = $this->createSinglePayment($transaction, $student, $transaction->amount, null, $skipAllocation);
            
            // Mark transaction as collected (payment created)
            // Also update match_status to 'manual' since it's been confirmed and collected
            $transaction->update([
                'payment_id' => $payment->id,
                'payment_created' => true,
                'status' => 'confirmed', // Ensure it's confirmed when payment is created
                'match_status' => 'manual', // Update match_status to manual when collected
            ]);
            
            return $payment;
        }
    }

    /**
     * Create missing payments for a transaction that already has partial payments.
     */
    public function createMissingPaymentsForTransaction(BankStatementTransaction $transaction, bool $skipAllocation = false): array
    {
        if (!$transaction->isConfirmed()) {
            throw new \Exception('Transaction must be confirmed before creating payment');
        }

        $ref = $transaction->reference_number;
        if (!$ref) {
            throw new \Exception('Transaction reference number is required to create payments.');
        }

        $activePayments = \App\Models\Payment::where('reversed', false)
            ->whereNull('deleted_at')
            ->where(function ($q) use ($ref) {
                $q->where('transaction_code', $ref)
                  ->orWhere('transaction_code', 'LIKE', $ref . '-%');
            })
            ->get();

        $created = [];
        $totalActive = (float) $activePayments->sum('amount');

        if ($transaction->is_shared && $transaction->shared_allocations) {
            $paidByStudent = $activePayments->groupBy('student_id')->map(fn($rows) => (float) $rows->sum('amount'));

            $sharedReceiptNumber = $activePayments->pluck('shared_receipt_number')->filter()->first()
                ?? $activePayments->pluck('receipt_number')->filter()->first();

            foreach ($transaction->shared_allocations as $allocation) {
                $studentId = (int) ($allocation['student_id'] ?? 0);
                $expectedAmount = (float) ($allocation['amount'] ?? 0);
                if ($studentId === 0 || $expectedAmount <= 0) {
                    continue;
                }

                $alreadyPaid = (float) ($paidByStudent[$studentId] ?? 0);
                $remaining = $expectedAmount - $alreadyPaid;
                if ($remaining <= 0.01) {
                    continue;
                }

                $student = Student::findOrFail($studentId);
                $payment = $this->createSinglePayment(
                    $transaction,
                    $student,
                    $remaining,
                    $sharedReceiptNumber,
                    $skipAllocation
                );
                $created[] = $payment;
            }

            // If no per-allocation shortfall but transaction total > sum of existing payments (unassigned remainder),
            // create one payment for the first sibling so "Create Payment" can collect the remainder.
            if (empty($created)) {
                $transactionTotal = (float) $transaction->amount;
                $unassignedRemaining = $transactionTotal - $totalActive;
                if ($unassignedRemaining > 0.01) {
                    $firstAllocation = collect($transaction->shared_allocations)->first();
                    $firstStudentId = $firstAllocation ? (int) ($firstAllocation['student_id'] ?? 0) : 0;
                    if ($firstStudentId > 0) {
                        $student = Student::findOrFail($firstStudentId);
                        $payment = $this->createSinglePayment(
                            $transaction,
                            $student,
                            $unassignedRemaining,
                            $sharedReceiptNumber ?? null,
                            $skipAllocation
                        );
                        $created[] = $payment;
                    }
                }
            }
        } else {
            $remaining = (float) $transaction->amount - $totalActive;
            if ($remaining > 0.01) {
                $student = Student::findOrFail($transaction->student_id);
                $payment = $this->createSinglePayment($transaction, $student, $remaining, null, $skipAllocation);
                $created[] = $payment;
            }
        }

        if (!empty($created)) {
            $firstPayment = $activePayments->first() ?? $created[0];
            $transaction->update([
                'payment_id' => $firstPayment->id,
                'payment_created' => true,
                'status' => 'confirmed',
                'match_status' => 'manual',
            ]);
        }

        return $created;
    }
    
    /**
     * Create a single payment record
     * @param bool $skipAllocation Skip auto-allocation for bulk operations (faster)
     */
    protected function createSinglePayment(
        BankStatementTransaction $transaction,
        Student $student,
        float $amount,
        ?string $receiptNumber = null,
        bool $skipAllocation = false,
        ?int $siblingIndex = null
    ): \App\Models\Payment {
        // Determine payment method based on bank type
        $paymentMethodName = $transaction->bank_type === 'mpesa' ? 'MPESA Paybill' : 'Equity Bank Transfer';
        
        // Try to find existing payment method or create one
        $paymentMethod = \App\Models\PaymentMethod::where('name', $paymentMethodName)->first();
        
        if (!$paymentMethod) {
            // Create payment method if it doesn't exist
            $paymentMethod = \App\Models\PaymentMethod::create([
                'name' => $paymentMethodName,
                'code' => $transaction->bank_type === 'mpesa' ? 'MPESA_PAYBILL' : 'EQUITY_BANK_TRANSFER',
                'requires_reference' => true,
                'is_online' => $transaction->bank_type === 'mpesa',
                'is_active' => true,
                'display_order' => 10,
                'description' => $paymentMethodName,
            ]);
        }
        
        // Check if payment already exists for this transaction
        $transactionCode = $transaction->reference_number ?? \App\Models\Payment::generateTransactionCode();
        
        // If using reference number, check if payment with this code already exists
        if ($transaction->reference_number) {
            // First check for exact match (same student, amount, date); exclude reversed
            $existingPayment = \App\Models\Payment::where('transaction_code', $transaction->reference_number)
                ->where('student_id', $student->id)
                ->where('amount', $amount)
                ->where('payment_date', $transaction->transaction_date)
                ->where('reversed', false)
                ->first();
            
            if ($existingPayment) {
                // Payment already exists, update transaction to link to it
                $transaction->update([
                    'payment_id' => $existingPayment->id,
                    'payment_created' => true,
                ]);
                
                Log::info('Payment already exists for transaction', [
                    'transaction_id' => $transaction->id,
                    'payment_id' => $existingPayment->id,
                    'transaction_code' => $transactionCode,
                ]);
                
                return $existingPayment;
            }
            
            // Check if ANY payment exists with same transaction_code + student_id (unique constraint)
            // This is the actual unique constraint, so we MUST check this before creating
            // CRITICAL CHECK: This must happen before any payment creation logic
            $conflictingPayment = \App\Models\Payment::where('transaction_code', $transaction->reference_number)
                ->where('student_id', $student->id)
                ->where('reversed', false)
                ->first();
            
            Log::info('Checking for conflicting payment', [
                'transaction_id' => $transaction->id,
                'reference_number' => $transaction->reference_number,
                'student_id' => $student->id,
                'found_payment' => $conflictingPayment ? $conflictingPayment->id : 'none',
            ]);
            
            if ($conflictingPayment) {
                // Payment already exists with this transaction_code + student_id combination
                // Link transaction to existing payment instead of creating duplicate
                $transaction->update([
                    'payment_id' => $conflictingPayment->id,
                    'payment_created' => true,
                ]);
                
                Log::warning('Payment already exists (unique constraint match) - RETURNING EARLY to prevent duplicate', [
                    'transaction_id' => $transaction->id,
                    'payment_id' => $conflictingPayment->id,
                    'transaction_code' => $transactionCode,
                    'reference_number' => $transaction->reference_number,
                    'student_id' => $student->id,
                    'conflicting_payment_id' => $conflictingPayment->id,
                    'conflicting_payment_code' => $conflictingPayment->transaction_code,
                ]);
                
                // CRITICAL: Return immediately to prevent duplicate creation
                return $conflictingPayment;
            }

            // If only reversed payments exist for this student/code, generate a new unique code
            $reversedSameStudent = \App\Models\Payment::where('transaction_code', $transaction->reference_number)
                ->where('student_id', $student->id)
                ->where('reversed', true)
                ->exists();
            if ($reversedSameStudent) {
                $baseCode = $transaction->reference_number . '-' . $transaction->id . '-R';
                $transactionCode = $baseCode;
                $attempt = 0;
                while (\App\Models\Payment::where('transaction_code', $transactionCode)
                    ->where('student_id', $student->id)
                    ->exists() && $attempt < 5) {
                    $transactionCode = $baseCode . '-' . time() . '-' . rand(100, 999);
                    $attempt++;
                }
            }
            
            // Legacy check for conflict handling (for different students with same code)
            // Skip this for shared transactions (siblings can share the same code)
            if (!$transaction->is_shared) {
                $conflictingPayments = \App\Models\Payment::where('transaction_code', $transaction->reference_number)
                    ->where('student_id', '!=', $student->id)
                    ->with('student')
                    ->get();
                
                if ($conflictingPayments->isNotEmpty()) {
                    $existingPayment = $conflictingPayments->firstWhere('reversed', false) ?? null;
                    if ($existingPayment) {
                        // Auto-link to existing non-reversed payment
                        $transaction->update([
                            'payment_id' => $existingPayment->id,
                            'payment_created' => true,
                            'student_id' => $existingPayment->student_id,
                            'status' => 'confirmed',
                            'match_status' => 'manual',
                        ]);

                        Log::info('Auto-linked existing payment for conflicting transaction code', [
                            'transaction_id' => $transaction->id,
                            'payment_id' => $existingPayment->id,
                            'transaction_code' => $transaction->reference_number,
                            'student_id' => $existingPayment->student_id,
                        ]);

                        return $existingPayment;
                    }

                    // Only reversed payments exist; allow creating a new payment
                    $conflictingPayments = collect(); // treat as no conflict
                } else {
                    // Check if transaction code (original or modified) is already used by other students
                    $originalCode = $transaction->reference_number;
                    $modifiedCode = $originalCode . '-' . $transaction->id;
                    
                    // Check both original and modified codes (for other students)
                    $codeExists = \App\Models\Payment::whereIn('transaction_code', [$originalCode, $modifiedCode])
                        ->where('student_id', '!=', $student->id)
                        ->where('reversed', false)
                        ->exists();
                    
                    if ($codeExists) {
                        // Generate a unique transaction code by appending transaction ID and timestamp
                        $uniqueSuffix = $transaction->id . '-' . time();
                        $transactionCode = $originalCode . '-' . $uniqueSuffix;
                        
                        // Double-check the new code doesn't exist for this student (very unlikely but possible)
                        $maxAttempts = 5;
                        $attempt = 0;
                        while (\App\Models\Payment::where('transaction_code', $transactionCode)->where('student_id', $student->id)->exists() && $attempt < $maxAttempts) {
                            $uniqueSuffix = $transaction->id . '-' . time() . '-' . rand(1000, 9999);
                            $transactionCode = $originalCode . '-' . $uniqueSuffix;
                            $attempt++;
                            usleep(10000); // 0.01 seconds
                        }
                        
                        Log::warning('Transaction code already exists for other students, using modified code', [
                            'original_code' => $originalCode,
                            'new_code' => $transactionCode,
                            'transaction_id' => $transaction->id,
                        ]);
                    }
                }
            }
        }
        
        // Generate unique receipt number
        $sharedReceiptNumber = null;
        $finalReceiptNumber = $receiptNumber;
        if ($transaction->is_shared && $receiptNumber !== null) {
            $sharedReceiptNumber = $receiptNumber;
            $finalReceiptNumber = $siblingIndex !== null
                ? \App\Services\ReceiptNumberService::receiptNumberForSibling($receiptNumber, $siblingIndex)
                : \App\Services\ReceiptNumberService::nextReceiptNumberForSibling($receiptNumber);
        }
        if (!$finalReceiptNumber) {
            $maxAttempts = 10;
            $attempt = 0;
            do {
                $finalReceiptNumber = \App\Services\DocumentNumberService::generateReceipt();
                $exists = \App\Models\Payment::where('receipt_number', $finalReceiptNumber)->exists();
                $attempt++;
                
                if ($exists && $attempt < $maxAttempts) {
                    // Wait a tiny bit and try again (handles race conditions)
                    usleep(10000); // 0.01 seconds
                }
            } while ($exists && $attempt < $maxAttempts);
            
            if ($exists) {
                // If still exists after max attempts, append transaction ID to make it unique
                $finalReceiptNumber = $finalReceiptNumber . '-' . $transaction->id;
                
                \Log::warning('Receipt number collision after max attempts, using modified number', [
                    'original_receipt' => \App\Services\DocumentNumberService::generateReceipt(),
                    'modified_receipt' => $finalReceiptNumber,
                    'transaction_id' => $transaction->id,
                ]);
            }
        } else {
            // If receipt number is provided, check if it exists
            $exists = \App\Models\Payment::where('receipt_number', $finalReceiptNumber)->exists();
            if ($exists) {
                // Append transaction ID to make it unique
                $finalReceiptNumber = $finalReceiptNumber . '-' . $transaction->id;
                
                \Log::warning('Provided receipt number already exists, using modified number', [
                    'original_receipt' => $receiptNumber,
                    'modified_receipt' => $finalReceiptNumber,
                    'transaction_id' => $transaction->id,
                ]);
            }
        }
        
        // Final check: ensure transaction code + student_id combination is truly unique before creating
        // This respects the unique constraint: payments_transaction_code_student_id_unique
        // CRITICAL: Check one more time right before creating to prevent race conditions
        $finalTransactionCode = $transactionCode;
        
        // If using the original reference_number, do a final check for existing payment
        if ($transaction->reference_number && $finalTransactionCode === $transaction->reference_number) {
            $finalCheckPayment = \App\Models\Payment::where('transaction_code', $finalTransactionCode)
                ->where('student_id', $student->id)
                ->where('reversed', false)
                ->first();
            
            if ($finalCheckPayment) {
                // Payment exists - link and return instead of creating
                $transaction->update([
                    'payment_id' => $finalCheckPayment->id,
                    'payment_created' => true,
                ]);
                
                Log::info('Payment found in final check before create - returning existing', [
                    'transaction_id' => $transaction->id,
                    'payment_id' => $finalCheckPayment->id,
                    'transaction_code' => $finalTransactionCode,
                    'student_id' => $student->id,
                ]);
                
                return $finalCheckPayment;
            }
        }
        
        $maxCodeAttempts = 10;
        $codeAttempt = 0;
        while (\App\Models\Payment::where('transaction_code', $finalTransactionCode)
                ->where('student_id', $student->id)
                ->exists() && $codeAttempt < $maxCodeAttempts) {
            $uniqueSuffix = $transaction->id . '-' . time() . '-' . rand(1000, 9999);
            $finalTransactionCode = ($transaction->reference_number ?? 'TXN') . '-' . $uniqueSuffix;
            $codeAttempt++;
            usleep(10000); // 0.01 seconds
        }
        
        if ($codeAttempt >= $maxCodeAttempts) {
            // Last resort: use a completely unique code
            $finalTransactionCode = 'TXN-' . $transaction->id . '-' . time() . '-' . uniqid();
            \Log::error('Failed to generate unique transaction code after max attempts', [
                'transaction_id' => $transaction->id,
                'original_code' => $transactionCode,
                'final_code' => $finalTransactionCode,
            ]);
        }
        
        // ONE MORE FINAL CHECK right before the database insert
        // Check both the final code AND the original reference_number to catch any edge cases
        $absoluteFinalCheck = \App\Models\Payment::where(function($q) use ($finalTransactionCode, $transaction) {
                $q->where('transaction_code', $finalTransactionCode);
                if ($transaction->reference_number && $finalTransactionCode !== $transaction->reference_number) {
                    $q->orWhere('transaction_code', $transaction->reference_number);
                }
            })
            ->where('student_id', $student->id)
            ->where('reversed', false)
            ->first();
        
        if ($absoluteFinalCheck) {
            // Payment exists - link and return instead of creating
            $transaction->update([
                'payment_id' => $absoluteFinalCheck->id,
                'payment_created' => true,
            ]);
            
            Log::warning('Payment found in absolute final check right before create - preventing duplicate', [
                'transaction_id' => $transaction->id,
                'payment_id' => $absoluteFinalCheck->id,
                'transaction_code' => $finalTransactionCode,
                'original_reference' => $transaction->reference_number,
                'student_id' => $student->id,
            ]);
            
            return $absoluteFinalCheck;
        }
        
        // Final check: ensure receipt number is truly unique before creating
        $finalReceiptNumberCheck = $finalReceiptNumber;
        $maxReceiptAttempts = 10;
        $receiptAttempt = 0;
        while (\App\Models\Payment::where('receipt_number', $finalReceiptNumberCheck)->exists() && $receiptAttempt < $maxReceiptAttempts) {
            $uniqueSuffix = $transaction->id . '-' . time() . '-' . rand(1000, 9999);
            $finalReceiptNumberCheck = ($finalReceiptNumber ?: 'RCPT') . '-' . $uniqueSuffix;
            $receiptAttempt++;
            usleep(10000); // 0.01 seconds
        }
        
        if ($receiptAttempt >= $maxReceiptAttempts) {
            // Last resort: use a completely unique receipt number
            $finalReceiptNumberCheck = 'RCPT-' . $transaction->id . '-' . time() . '-' . uniqid();
            \Log::error('Failed to generate unique receipt number after max attempts', [
                'transaction_id' => $transaction->id,
                'original_receipt' => $finalReceiptNumber,
                'final_receipt' => $finalReceiptNumberCheck,
            ]);
        }
        
        // ABSOLUTE FINAL CHECK: One last check right before database insert to prevent unique constraint violation
        // This is a safety net in case any previous checks were bypassed
        // Check both final code AND original reference_number
        $lastChanceCheck = \App\Models\Payment::where(function($q) use ($finalTransactionCode, $transaction) {
                $q->where('transaction_code', $finalTransactionCode);
                if ($transaction->reference_number && $finalTransactionCode !== $transaction->reference_number) {
                    $q->orWhere('transaction_code', $transaction->reference_number);
                }
            })
            ->where('student_id', $student->id)
            ->where('reversed', false)
            ->first();
        
        if ($lastChanceCheck) {
            // Payment exists - link and return instead of creating
            $transaction->update([
                'payment_id' => $lastChanceCheck->id,
                'payment_created' => true,
            ]);
            
            Log::warning('Payment found in last-chance check right before Payment::create() - preventing duplicate insert', [
                'transaction_id' => $transaction->id,
                'payment_id' => $lastChanceCheck->id,
                'transaction_code' => $finalTransactionCode,
                'original_reference' => $transaction->reference_number,
                'student_id' => $student->id,
            ]);
            
            return $lastChanceCheck;
        }
        
        // Log before creating to help debug if this still fails
        Log::info('Creating new payment - all checks passed', [
            'transaction_id' => $transaction->id,
            'transaction_code' => $finalTransactionCode,
            'original_reference' => $transaction->reference_number,
            'student_id' => $student->id,
            'amount' => $amount,
        ]);
        
        try {
            $payment = \App\Models\Payment::create([
                'student_id' => $student->id,
                'family_id' => $student->family_id,
                'amount' => $amount,
                'payment_method_id' => $paymentMethod->id,
                'payment_method' => $paymentMethodName,
                'transaction_code' => $finalTransactionCode,
                'receipt_number' => $finalReceiptNumberCheck,
                'shared_receipt_number' => $sharedReceiptNumber,
                'payer_name' => $transaction->payer_name ?? $transaction->matched_student_name ?? $student->first_name . ' ' . $student->last_name,
                'payer_type' => 'parent',
                'narration' => $transaction->description,
                'payment_date' => $transaction->transaction_date,
                'bank_account_id' => $transaction->bank_account_id,
            ]);
        } catch (\Exception $e) {
            // Catch all exceptions first, then check the type
            // UniqueConstraintViolationException extends QueryException, so we check for both
            // CRITICAL: Log that we caught an exception
            Log::error('Exception caught in createSinglePayment', [
                'exception_class' => get_class($e),
                'error_message' => substr($e->getMessage(), 0, 200),
            ]);
            
            $errorMessage = $e->getMessage();
            $errorCode = $e->getCode();
            $sqlState = null;
            
            // Get SQL state if available (for QueryException)
            if ($e instanceof \Illuminate\Database\QueryException) {
                $sqlState = isset($e->errorInfo[0]) ? $e->errorInfo[0] : null;
            }
            
            // Check for unique constraint violation in multiple ways
            $isUniqueViolation = 
                ($e instanceof \Illuminate\Database\UniqueConstraintViolationException) ||
                ($e instanceof \Illuminate\Database\QueryException && (
                    ($errorCode == 23000 || $errorCode == '23000' || $sqlState == '23000') ||
                    strpos($errorMessage, 'payments_transaction_code_student_id_unique') !== false ||
                    strpos($errorMessage, 'Duplicate entry') !== false ||
                    strpos($errorMessage, '1062') !== false
                ));
            
            if ($isUniqueViolation) {
                Log::warning('Caught unique constraint violation exception - attempting to find existing payment', [
                    'transaction_id' => $transaction->id,
                    'transaction_code' => $finalTransactionCode,
                    'original_reference' => $transaction->reference_number,
                    'student_id' => $student->id,
                    'error_message' => substr($errorMessage, 0, 200),
                    'error_code' => $errorCode,
                    'sql_state' => $sqlState,
                    'exception_class' => get_class($e),
                ]);
                
                // Try to find the payment - ONLY look for active, non-reversed, non-deleted payments
                // We should NOT return reversed or deleted payments as they are invalid
                $existingPayment = \App\Models\Payment::where(function($q) use ($finalTransactionCode, $transaction) {
                        $q->where('transaction_code', $finalTransactionCode);
                        if ($transaction->reference_number && $finalTransactionCode !== $transaction->reference_number) {
                            $q->orWhere('transaction_code', $transaction->reference_number);
                        }
                    })
                    ->where('student_id', $student->id)
                    ->where('reversed', false)
                    ->whereNull('deleted_at') // Exclude soft-deleted payments
                    ->first();
                
                // If not found, check if there's a reversed/deleted payment causing the constraint violation
                if (!$existingPayment) {
                    $reversedOrDeleted = \App\Models\Payment::withTrashed()
                        ->where(function($q) use ($finalTransactionCode, $transaction) {
                            $q->where('transaction_code', $finalTransactionCode);
                            if ($transaction->reference_number && $finalTransactionCode !== $transaction->reference_number) {
                                $q->orWhere('transaction_code', $transaction->reference_number);
                            }
                        })
                        ->where('student_id', $student->id)
                        ->first();
                    
                    if ($reversedOrDeleted && ($reversedOrDeleted->reversed || $reversedOrDeleted->deleted_at)) {
                        // A reversed/deleted payment exists - modify transaction code to create a new one
                        Log::warning('Unique constraint violation due to reversed/deleted payment - modifying transaction code', [
                            'transaction_id' => $transaction->id,
                            'original_code' => $finalTransactionCode,
                            'existing_payment_id' => $reversedOrDeleted->id,
                            'existing_reversed' => $reversedOrDeleted->reversed,
                            'existing_deleted' => $reversedOrDeleted->deleted_at ? 'YES' : 'NO',
                        ]);
                        
                        // Clear the transaction's payment_id if it points to the reversed/deleted payment
                        if ($transaction->payment_id == $reversedOrDeleted->id) {
                            $transaction->update([
                                'payment_id' => null,
                                'payment_created' => false,
                            ]);
                        }
                        
                        // Modify transaction code to make it unique
                        $finalTransactionCode = $transaction->reference_number . '-' . $transaction->id . '-' . time();
                        
                        // Try creating again with modified code
                        try {
                            $payment = \App\Models\Payment::create([
                                'student_id' => $student->id,
                                'family_id' => $student->family_id,
                                'amount' => $amount,
                                'payment_method_id' => $paymentMethod->id,
                                'payment_method' => $paymentMethodName,
                                'transaction_code' => $finalTransactionCode,
                                'receipt_number' => $finalReceiptNumberCheck,
                                'payer_name' => $transaction->payer_name ?? $transaction->matched_student_name ?? $student->first_name . ' ' . $student->last_name,
                                'payer_type' => 'parent',
                                'narration' => $transaction->description,
                                'payment_date' => $transaction->transaction_date,
                                'bank_account_id' => $transaction->bank_account_id,
                            ]);
                            
                            // Success! Link and return
                            $transaction->update([
                                'payment_id' => $payment->id,
                                'payment_created' => true,
                            ]);
                            
                            Log::info('Created new payment with modified transaction code after reversed/deleted payment conflict', [
                                'transaction_id' => $transaction->id,
                                'payment_id' => $payment->id,
                                'new_transaction_code' => $finalTransactionCode,
                            ]);
                            
                            return $payment;
                        } catch (\Exception $e2) {
                            // If this also fails, re-throw the original exception
                            Log::error('Failed to create payment with modified transaction code', [
                                'transaction_id' => $transaction->id,
                                'new_code' => $finalTransactionCode,
                                'error' => $e2->getMessage(),
                            ]);
                            throw $e;
                        }
                    } else {
                        // No reversed/deleted payment found - this shouldn't happen
                        Log::error('Unique constraint violation but no valid or reversed/deleted payment found', [
                            'transaction_id' => $transaction->id,
                            'transaction_code' => $finalTransactionCode,
                            'student_id' => $student->id,
                        ]);
                        throw $e;
                    }
                }
                
                if ($existingPayment) {
                    $transaction->update([
                        'payment_id' => $existingPayment->id,
                        'payment_created' => true,
                    ]);
                    
                    Log::warning('Found existing payment after unique constraint violation - returning it', [
                        'transaction_id' => $transaction->id,
                        'payment_id' => $existingPayment->id,
                    ]);
                    
                    return $existingPayment;
                } else {
                    Log::error('Unique constraint violation but could not find existing payment - trying without reversed check', [
                        'transaction_id' => $transaction->id,
                        'transaction_code' => $finalTransactionCode,
                        'original_reference' => $transaction->reference_number,
                        'student_id' => $student->id,
                    ]);
                    
                    // Try one more time without the reversed check - maybe it was reversed?
                    $existingPayment = \App\Models\Payment::where(function($q) use ($finalTransactionCode, $transaction) {
                            $q->where('transaction_code', $finalTransactionCode);
                            if ($transaction->reference_number && $finalTransactionCode !== $transaction->reference_number) {
                                $q->orWhere('transaction_code', $transaction->reference_number);
                            }
                        })
                        ->where('student_id', $student->id)
                        ->first(); // No reversed check
                    
                    if ($existingPayment) {
                        $transaction->update([
                            'payment_id' => $existingPayment->id,
                            'payment_created' => true,
                        ]);
                        
                        Log::warning('Found existing payment (possibly reversed) after unique constraint violation', [
                            'transaction_id' => $transaction->id,
                            'payment_id' => $existingPayment->id,
                            'reversed' => $existingPayment->reversed,
                        ]);
                        
                        return $existingPayment;
                    }
                    
                    // If we still can't find it, throw the original exception
                    Log::error('CRITICAL: Unique constraint violation but payment not found even after exhaustive search', [
                        'transaction_id' => $transaction->id,
                        'transaction_code' => $finalTransactionCode,
                        'original_reference' => $transaction->reference_number,
                        'student_id' => $student->id,
                    ]);
                    throw $e; // Re-throw the original exception
                }
            }
            
            // Re-throw if it's not a unique constraint violation
            Log::error('Exception caught but not a unique constraint violation', [
                'exception_class' => get_class($e),
                'error_message' => substr($errorMessage, 0, 200),
            ]);
            throw $e;
        } catch (\Exception $e) {
            // Catch any other exceptions and check if it's a unique constraint violation
            $errorMessage = $e->getMessage();
            if (strpos($errorMessage, 'Duplicate entry') !== false || 
                strpos($errorMessage, 'payments_transaction_code_student_id_unique') !== false ||
                strpos($errorMessage, '1062') !== false ||
                strpos($errorMessage, 'UniqueConstraintViolationException') !== false) {
                
                $existingPayment = \App\Models\Payment::where(function($q) use ($finalTransactionCode, $transaction) {
                        $q->where('transaction_code', $finalTransactionCode);
                        if ($transaction->reference_number && $finalTransactionCode !== $transaction->reference_number) {
                            $q->orWhere('transaction_code', $transaction->reference_number);
                        }
                    })
                    ->where('student_id', $student->id)
                    ->where('reversed', false)
                    ->first();
                
                if ($existingPayment) {
                    $transaction->update([
                        'payment_id' => $existingPayment->id,
                        'payment_created' => true,
                    ]);
                    
                    Log::warning('Caught exception with duplicate entry message - returning existing payment', [
                        'transaction_id' => $transaction->id,
                        'payment_id' => $existingPayment->id,
                    ]);
                    
                    return $existingPayment;
                }
            }
            
            throw $e;
        }
        
        // Skip auto-allocation during bulk creation - it will be done later in batch
        // Auto-allocation is time-consuming and can be done asynchronously
        // This significantly speeds up bulk payment creation
        // Also skip if transaction is marked as swimming (will be allocated to swimming wallets instead)
        if (!$skipAllocation && !$transaction->is_swimming_transaction) {
            try {
                $allocationService = app(\App\Services\PaymentAllocationService::class);
                $allocationService->autoAllocate($payment);
            } catch (\Exception $e) {
                Log::warning('Auto-allocation failed for bank statement payment', [
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        return $payment;
    }

    /**
     * Create fee payments for a split transaction (fees + swimming).
     * Unlike shared allocations, fee allocations do NOT need to sum to full transaction amount.
     */
    public function createSplitFeePayments(BankStatementTransaction $transaction, array $allocations, bool $skipAllocation = false): array
    {
        if (!$transaction->isConfirmed()) {
            throw new \Exception('Transaction must be confirmed before creating payments');
        }

        $payments = [];
        foreach ($allocations as $allocation) {
            $studentId = (int) ($allocation['student_id'] ?? 0);
            $amount = (float) ($allocation['amount'] ?? 0);
            if ($studentId <= 0 || $amount <= 0) {
                continue;
            }

            $student = Student::findOrFail($studentId);
            $payment = $this->createSinglePayment($transaction, $student, $amount, null, $skipAllocation);
            $payments[] = $payment;

            if (!$skipAllocation) {
                try {
                    $allocationService = app(\App\Services\PaymentAllocationService::class);
                    $allocationService->autoAllocate($payment);
                } catch (\Exception $e) {
                    \Log::warning('Split fee auto-allocation failed', [
                        'transaction_id' => $transaction->id,
                        'payment_id' => $payment->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return $payments;
    }
    
    /**
     * Find student by admission number (handles RKS412, 412, case-insensitive and name+admission combined)
     */
    protected function findStudentByAdmissionNumber(string $admissionNumber): ?Student
    {
        $admissionNumber = trim($admissionNumber);
        if ($admissionNumber === '') {
            return null;
        }
        // Case-insensitive match (child name may be in small/capital letters with admission)
        $student = Student::whereRaw('UPPER(TRIM(admission_number)) = ?', [strtoupper($admissionNumber)])
            ->where('archive', 0)
            ->where('is_alumni', false)
            ->first();
        if ($student) {
            return $student;
        }
        // Try without RKS prefix (in case stored as just digits)
        if (preg_match('/RKS(\d+)/i', $admissionNumber, $digitsMatch)) {
            $digitsOnly = $digitsMatch[1];
            $student = Student::where('archive', 0)
                ->where('is_alumni', false)
                ->where(function($q) use ($digitsOnly) {
                    $q->whereRaw('UPPER(TRIM(admission_number)) = ?', [strtoupper($digitsOnly)])
                      ->orWhereRaw('UPPER(admission_number) LIKE ?', ['%' . $digitsOnly . '%']);
                })
                ->first();
        }
        return $student;
    }
    
    /**
     * Find sibling matches when multiple child names are provided
     * Returns students that are siblings and match the provided names
     */
    protected function findSiblingMatches(array $candidates, array $childNames): array
    {
        $siblingMatches = [];
        
        foreach ($candidates as $student) {
            // Check if this student matches any of the child names
            $studentFullName = strtolower($student->first_name . ' ' . $student->last_name);
            $studentFirstName = strtolower($student->first_name);
            $studentLastName = strtolower($student->last_name);
            
            $matchesName = false;
            foreach ($childNames as $childName) {
                $childNameLower = strtolower(trim($childName));
                if (stripos($studentFullName, $childNameLower) !== false || 
                    stripos($childNameLower, $studentFirstName) !== false ||
                    stripos($childNameLower, $studentLastName) !== false ||
                    stripos($studentFullName, str_replace(' ', '', $childNameLower)) !== false) {
                    $matchesName = true;
                    break;
                }
            }
            
            if ($matchesName) {
                // Check if this student has siblings that match other names
                $siblings = Student::where('family_id', $student->family_id)
                    ->where('id', '!=', $student->id)
                    ->where('archive', 0)
                    ->where('is_alumni', false)
                    ->get();
                
                $siblingMatchesThisFamily = [$student];
                foreach ($siblings as $sibling) {
                    $siblingFullName = strtolower($sibling->first_name . ' ' . $sibling->last_name);
                    $siblingFirstName = strtolower($sibling->first_name);
                    $siblingLastName = strtolower($sibling->last_name);
                    
                    foreach ($childNames as $childName) {
                        $childNameLower = strtolower(trim($childName));
                        if ((stripos($siblingFullName, $childNameLower) !== false || 
                             stripos($childNameLower, $siblingFirstName) !== false ||
                             stripos($childNameLower, $siblingLastName) !== false ||
                             stripos($siblingFullName, str_replace(' ', '', $childNameLower)) !== false) &&
                            !in_array($sibling->id, array_map(fn($s) => $s->id, $siblingMatchesThisFamily))) {
                            $siblingMatchesThisFamily[] = $sibling;
                        }
                    }
                }
                
                // If we found multiple siblings matching the names, this is likely the right family
                if (count($siblingMatchesThisFamily) >= min(2, count($childNames))) {
                    $siblingMatches = array_merge($siblingMatches, $siblingMatchesThisFamily);
                    break; // Found the right family
                }
            }
        }
        
        return array_unique($siblingMatches, SORT_REGULAR);
    }
    
    /**
     * Check if student's parent phone matches
     */
    protected function matchesPhone(Student $student, string $phoneNumber): bool
    {
        if (!$student->parentInfo) {
            return false;
        }
        
        $normalizedPhone = $this->normalizePhone($phoneNumber);
        $parent = $student->parentInfo;
        $phones = [
            $parent->father_phone,
            $parent->mother_phone,
            $parent->guardian_phone,
            $parent->father_whatsapp,
            $parent->mother_whatsapp,
            $parent->guardian_whatsapp,
        ];
        
        foreach ($phones as $phone) {
            if ($phone) {
                $normalizedParentPhone = $this->normalizePhone($phone);
                if ($normalizedParentPhone === $normalizedPhone) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Match transaction using historical transaction data
     * Uses patterns from previous successful matches to improve accuracy
     */
    protected function matchByHistoricalData(BankStatementTransaction $transaction, array $parsedData, ?string $phoneNumber): array
    {
        $matches = [];
        $description = $transaction->description ?? '';
        $parentName = $parsedData['parent_name'] ?? null;
        $childNames = $parsedData['child_names'] ?? [];
        $partialPhone = $parsedData['partial_phone'] ?? null;
        
        // Get historical transactions that were successfully matched (confirmed and have student_id)
        // Look for patterns: same parent name + child name, same partial phone, similar amounts
        $historicalQuery = BankStatementTransaction::where('status', 'confirmed')
            ->whereNotNull('student_id')
            ->where('is_archived', false)
            ->where('is_duplicate', false)
            ->where('transaction_type', 'credit')
            ->orderBy('transaction_date', 'desc')
            ->limit(1000); // Look at last 1000 successful matches
        
        $historicalMatches = [];
        
        // Match by parent name + child name pattern
        if ($parentName && !empty($childNames)) {
            $parentNameLower = strtolower(trim($parentName));
            
            foreach ($childNames as $childName) {
                $childNameLower = strtolower(trim($childName));
                
                // Find historical transactions with similar parent and child names
                $similarTransactions = (clone $historicalQuery)
                    ->where(function($q) use ($parentNameLower, $childNameLower) {
                        $q->whereRaw('LOWER(payer_name) LIKE ?', ["%{$parentNameLower}%"])
                          ->orWhereRaw('LOWER(description) LIKE ?', ["%{$parentNameLower}%"])
                          ->orWhereRaw('LOWER(matched_student_name) LIKE ?', ["%{$childNameLower}%"])
                          ->orWhereRaw('LOWER(description) LIKE ?', ["%{$childNameLower}%"]);
                    })
                    ->with('student')
                    ->get();
                
                foreach ($similarTransactions as $histTxn) {
                    if (!$histTxn->student || $histTxn->student->archive || $histTxn->student->is_alumni) {
                        continue;
                    }
                    
                    $student = $histTxn->student;
                    $studentFullName = strtolower($student->first_name . ' ' . $student->last_name);
                    
                    // Check if child name matches student
                    if (stripos($studentFullName, $childNameLower) !== false || 
                        stripos($studentFullName, str_replace(' ', '', $childNameLower)) !== false ||
                        stripos($childNameLower, strtolower($student->first_name)) !== false ||
                        stripos($childNameLower, strtolower($student->last_name)) !== false) {
                        
                        // Check if parent name also matches
                        $parentMatches = false;
                        if ($student->parentInfo) {
                            $parent = $student->parentInfo;
                            $parentNames = [
                                strtolower($parent->father_name ?? ''),
                                strtolower($parent->mother_name ?? ''),
                                strtolower($parent->guardian_name ?? ''),
                            ];
                            
                            foreach ($parentNames as $pName) {
                                if (!empty($pName) && (stripos($pName, $parentNameLower) !== false || 
                                    stripos($parentNameLower, $pName) !== false)) {
                                    $parentMatches = true;
                                    break;
                                }
                            }
                        }
                        
                        $confidence = 0.75; // Base confidence for historical match
                        if ($parentMatches) {
                            $confidence = 0.88; // Higher if parent name also matches
                        }
                        
                        // Boost confidence if partial phone matches
                        if ($partialPhone && $this->matchesPartialPhone($student, $partialPhone)) {
                            $confidence = 0.92;
                        }
                        
                        // Boost confidence if full phone matches
                        if ($phoneNumber && $this->matchesPhone($student, $phoneNumber)) {
                            $confidence = 0.95;
                        }
                        
                        // Boost confidence based on number of historical matches (more history = higher confidence)
                        $matchCount = (clone $historicalQuery)
                            ->where('student_id', $student->id)
                            ->where(function($q) use ($parentNameLower) {
                                $q->whereRaw('LOWER(payer_name) LIKE ?', ["%{$parentNameLower}%"])
                                  ->orWhereRaw('LOWER(description) LIKE ?', ["%{$parentNameLower}%"]);
                            })
                            ->count();
                        
                        if ($matchCount >= 3) {
                            $confidence = min(0.98, $confidence + 0.10);
                        } elseif ($matchCount >= 2) {
                            $confidence = min(0.95, $confidence + 0.05);
                        }
                        
                        $historicalMatches[] = [
                            'student' => $student,
                            'match_type' => 'historical',
                            'confidence' => $confidence,
                            'matched_value' => "Historical: {$parentName} + {$childName}",
                            'match_count' => $matchCount,
                        ];
                    }
                }
            }
        }
        
        // Match by partial phone + child name pattern
        if ($partialPhone && !empty($childNames)) {
            $phoneMatches = $this->findStudentsByPartialPhone($partialPhone);
            $phoneMatches = array_filter($phoneMatches, fn($s) => $s->archive == 0 && $s->is_alumni == false);
            
            foreach ($phoneMatches as $student) {
                $studentFullName = strtolower($student->first_name . ' ' . $student->last_name);
                
                foreach ($childNames as $childName) {
                    $childNameLower = strtolower(trim($childName));
                    
                    if (stripos($studentFullName, $childNameLower) !== false || 
                        stripos($studentFullName, str_replace(' ', '', $childNameLower)) !== false) {
                        
                        // Check historical transactions for this student with similar phone pattern
                        $histCount = (clone $historicalQuery)
                            ->where('student_id', $student->id)
                            ->where(function($q) use ($partialPhone) {
                                $parts = preg_split('/\.\.\.|\*+/', $partialPhone);
                                if (count($parts) === 2) {
                                    $q->where('phone_number', 'LIKE', "{$parts[0]}%{$parts[1]}")
                                      ->orWhere('description', 'LIKE', "%{$parts[0]}%{$parts[1]}%");
                                }
                            })
                            ->count();
                        
                        if ($histCount > 0) {
                            $confidence = 0.80 + min(0.15, $histCount * 0.03);
                            
                            $historicalMatches[] = [
                                'student' => $student,
                                'match_type' => 'historical_phone',
                                'confidence' => $confidence,
                                'matched_value' => "Historical: {$partialPhone} + {$childName}",
                                'match_count' => $histCount,
                            ];
                        }
                    }
                }
            }
        }
        
        // Remove duplicates, keeping highest confidence match for each student
        $uniqueHistoricalMatches = [];
        $seenStudentIds = [];
        foreach ($historicalMatches as $match) {
            $studentId = $match['student']->id;
            if (!in_array($studentId, $seenStudentIds)) {
                $uniqueHistoricalMatches[] = $match;
                $seenStudentIds[] = $studentId;
            } else {
                // If duplicate, keep the one with higher confidence
                $existingIndex = null;
                foreach ($uniqueHistoricalMatches as $idx => $existing) {
                    if ($existing['student']->id === $studentId) {
                        $existingIndex = $idx;
                        break;
                    }
                }
                if ($existingIndex !== null && $match['confidence'] > $uniqueHistoricalMatches[$existingIndex]['confidence']) {
                    $uniqueHistoricalMatches[$existingIndex] = $match;
                }
            }
        }
        
        return $uniqueHistoricalMatches;
    }
}

