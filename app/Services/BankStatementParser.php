<?php

namespace App\Services;

use App\Models\{
    BankStatementTransaction, Student, ParentInfo, Family, BankAccount, Payment
};
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;
use Illuminate\Support\Str;

class BankStatementParser
{
    /**
     * Parse bank statement PDF and create draft transactions
     */
    public function parseStatement(string $pdfPath, ?int $bankAccountId = null, ?string $bankType = null): array
    {
        $fullPath = Storage::disk('private')->path($pdfPath);
        
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
        
        foreach ($transactions as $txnData) {
            // Extract payer name from description
            $payerName = $this->extractPayerName($txnData['description'] ?? '');
            
            // Check for duplicate using transaction_code (reference_number)
            $isDuplicate = false;
            $duplicatePayment = null;
            if ($txnData['reference_number']) {
                $existingPayment = Payment::where('transaction_code', $txnData['reference_number'])->first();
                if ($existingPayment) {
                    $isDuplicate = true;
                    $duplicatePayment = $existingPayment;
                }
            }
            
            $transaction = BankStatementTransaction::create([
                'bank_account_id' => $bankAccountId,
                'statement_file_path' => $pdfPath,
                'bank_type' => $bankType,
                'transaction_date' => $txnData['transaction_date'],
                'amount' => $txnData['amount'],
                'transaction_type' => $txnData['transaction_type'],
                'reference_number' => $txnData['reference_number'] ?? null,
                'description' => $txnData['description'] ?? null,
                'phone_number' => $txnData['phone_number'] ?? null,
                'payer_name' => $payerName,
                'status' => 'draft',
                'is_duplicate' => $isDuplicate,
                'duplicate_of_payment_id' => $duplicatePayment?->id,
                'raw_data' => $txnData,
                'created_by' => auth()->id(),
            ]);
            
            if ($isDuplicate) {
                $duplicates++;
            } else {
                // Attempt to match transaction only if not duplicate
                $matchResult = $this->matchTransaction($transaction);
                
                if ($matchResult['matched']) {
                    $matched++;
                } else {
                    $unmatched++;
                }
            }
            
            $created[] = $transaction->id;
        }
        
        return [
            'success' => true,
            'message' => sprintf('Parsed %d transactions. %d matched, %d unmatched, %d duplicates.', count($transactions), $matched, $unmatched, $duplicates),
            'transactions' => $created,
            'matched' => $matched,
            'unmatched' => $unmatched,
            'duplicates' => $duplicates,
        ];
    }
    
    /**
     * Match transaction to student by admission number or phone number
     */
    public function matchTransaction(BankStatementTransaction $transaction): array
    {
        $description = $transaction->description ?? '';
        $phoneNumber = $transaction->phone_number;
        
        $matches = [];
        
        // Try to match by admission number in description
        $admissionNumber = $this->extractAdmissionNumber($description);
        if ($admissionNumber) {
            $student = Student::where('admission_number', $admissionNumber)->first();
            if ($student) {
                $matches[] = [
                    'student' => $student,
                    'match_type' => 'admission_number',
                    'confidence' => 0.95,
                    'matched_value' => $admissionNumber,
                ];
            }
        }
        
        // Try to match by student name in description
        $studentName = $this->extractStudentName($description);
        if ($studentName) {
            $nameMatches = Student::where(function($q) use ($studentName) {
                $q->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$studentName}%"])
                  ->orWhereRaw("CONCAT(first_name, ' ', middle_name, ' ', last_name) LIKE ?", ["%{$studentName}%"]);
            })->get();
            
            foreach ($nameMatches as $student) {
                $matches[] = [
                    'student' => $student,
                    'match_type' => 'name',
                    'confidence' => 0.70,
                    'matched_value' => $studentName,
                ];
            }
        }
        
        // Try to match by phone number (parent/guardian)
        if ($phoneNumber) {
            $normalizedPhone = $this->normalizePhone($phoneNumber);
            $phoneMatches = $this->findStudentsByPhone($normalizedPhone);
            
            foreach ($phoneMatches as $student) {
                $matches[] = [
                    'student' => $student,
                    'match_type' => 'phone',
                    'confidence' => 0.85,
                    'matched_value' => $normalizedPhone,
                ];
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
        
        if (count($uniqueMatches) === 1) {
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
     * Extract admission number from description
     */
    protected function extractAdmissionNumber(string $description): ?string
    {
        // Common patterns: ADM123, ADM-123, 123/2024, etc.
        $patterns = [
            '/ADM[-\s]?(\d+)/i',
            '/(\d{3,})\/(\d{4})/', // Format: 123/2024
            '/ADMISSION[-\s]?(\d+)/i',
            '/ADM[:\s]+(\d+)/i',
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $description, $matches)) {
                return $matches[1] ?? $matches[0];
            }
        }
        
        return null;
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
     * Extract student name from description
     */
    protected function extractStudentName(string $description): ?string
    {
        // Look for common name patterns (2-3 words, capitalized)
        if (preg_match('/\b([A-Z][a-z]+(?:\s+[A-Z][a-z]+){1,2})\b/', $description, $matches)) {
            $name = $matches[1];
            // Filter out common words that aren't names
            $exclude = ['MPESA', 'PAYBILL', 'SENT', 'RECEIVED', 'FROM', 'TO', 'KES', 'SCHOOL', 'FEES'];
            $words = explode(' ', $name);
            $filtered = array_filter($words, fn($w) => !in_array(strtoupper($w), $exclude));
            
            if (count($filtered) >= 2) {
                return implode(' ', $filtered);
            }
        }
        
        return null;
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
     * Call Python parser
     */
    protected function callPythonParser(string $pdfPath, string $bankType): array
    {
        $script = base_path('app/Services/python/bank_statement_parser.py');
        $cmd = ['python', $script, $pdfPath, $bankType];
        
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
        
        if (abs($totalAmount - $transaction->amount) > 0.01) {
            throw new \Exception('Total allocation amount must equal transaction amount');
        }
        
        $transaction->update([
            'is_shared' => true,
            'shared_allocations' => $allocations,
        ]);
        
        return true;
    }
    
    /**
     * Create payment from confirmed transaction
     */
    public function createPaymentFromTransaction(BankStatementTransaction $transaction): \App\Models\Payment
    {
        if (!$transaction->isConfirmed()) {
            throw new \Exception('Transaction must be confirmed before creating payment');
        }
        
        if ($transaction->payment_created) {
            throw new \Exception('Payment already created for this transaction');
        }
        
        if ($transaction->is_shared && $transaction->shared_allocations) {
            // Generate same receipt number for all sibling payments
            $sharedReceiptNumber = \App\Services\DocumentNumberService::generateReceipt();
            
            // Create payments for each sibling
            $payments = [];
            foreach ($transaction->shared_allocations as $allocation) {
                $student = Student::findOrFail($allocation['student_id']);
                $payment = $this->createSinglePayment($transaction, $student, $allocation['amount'], $sharedReceiptNumber);
                $payments[] = $payment;
            }
            
            // Update transaction with first payment ID (for reference)
            $transaction->update([
                'payment_id' => $payments[0]->id,
                'payment_created' => true,
            ]);
            
            return $payments[0];
        } else {
            // Create single payment
            $student = Student::findOrFail($transaction->student_id);
            $payment = $this->createSinglePayment($transaction, $student, $transaction->amount);
            
            $transaction->update([
                'payment_id' => $payment->id,
                'payment_created' => true,
            ]);
            
            return $payment;
        }
    }
    
    /**
     * Create a single payment record
     */
    protected function createSinglePayment(
        BankStatementTransaction $transaction,
        Student $student,
        float $amount,
        ?string $receiptNumber = null
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
        
        $payment = \App\Models\Payment::create([
            'student_id' => $student->id,
            'family_id' => $student->family_id,
            'amount' => $amount,
            'payment_method_id' => $paymentMethod->id,
            'payment_method' => $paymentMethodName,
            'transaction_code' => $transaction->reference_number ?? \App\Models\Payment::generateTransactionCode(),
            'receipt_number' => $receiptNumber ?? \App\Services\DocumentNumberService::generateReceipt(),
            'payer_name' => $transaction->payer_name ?? $transaction->matched_student_name ?? $student->first_name . ' ' . $student->last_name,
            'payer_type' => 'parent',
            'narration' => $transaction->description,
            'payment_date' => $transaction->transaction_date,
            'bank_account_id' => $transaction->bank_account_id,
        ]);
        
        // Auto-allocate payment
        try {
            $allocationService = app(\App\Services\PaymentAllocationService::class);
            $allocationService->autoAllocate($payment);
        } catch (\Exception $e) {
            Log::warning('Auto-allocation failed for bank statement payment', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
        }
        
        return $payment;
    }
}

