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
            
            // Check for duplicate using transaction_code
            $isDuplicate = false;
            $duplicatePayment = null;
            if ($transactionCode) {
                $existingPayment = Payment::where('transaction_code', $transactionCode)->first();
                if ($existingPayment) {
                    $isDuplicate = true;
                    $duplicatePayment = $existingPayment;
                }
            }
            
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
     * Match transaction to student by admission number, phone number, parent name, and student name
     */
    public function matchTransaction(BankStatementTransaction $transaction): array
    {
        $description = $transaction->description ?? '';
        $phoneNumber = $transaction->phone_number;
        
        $matches = [];
        
        // Parse MPESA paybill format: "Pay Bill from 25471****156 - FRANCISCAH WAMBUGU Acc. Trevor Osairi"
        $parsedData = $this->parseMpesaPaybillDescription($description);
        
        // Try to match by admission number(s) in description
        $admissionNumbers = $this->extractAdmissionNumbers($description);
        foreach ($admissionNumbers as $admissionNumber) {
            $student = Student::where('admission_number', $admissionNumber)->first();
            if ($student) {
                $confidence = 0.95;
                // Increase confidence if parent name also matches
                if ($parsedData['parent_name'] && $this->matchesParentName($student, $parsedData['parent_name'])) {
                    $confidence = 0.98;
                }
                // Increase confidence if partial phone matches
                if ($parsedData['partial_phone'] && $this->matchesPartialPhone($student, $parsedData['partial_phone'])) {
                    $confidence = min(0.99, $confidence + 0.02);
                }
                
                $matches[] = [
                    'student' => $student,
                    'match_type' => 'admission_number',
                    'confidence' => $confidence,
                    'matched_value' => $admissionNumber,
                ];
            }
        }
        
        // Try to match by student name(s) - handle siblings
        $studentNames = $this->extractStudentNames($description);
        foreach ($studentNames as $studentName) {
            $nameMatches = Student::where(function($q) use ($studentName) {
                $q->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$studentName}%"])
                  ->orWhereRaw("CONCAT(first_name, ' ', middle_name, ' ', last_name) LIKE ?", ["%{$studentName}%"])
                  ->orWhere('first_name', 'LIKE', "%{$studentName}%")
                  ->orWhere('last_name', 'LIKE', "%{$studentName}%");
            })->get();
            
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
                
                $matches[] = [
                    'student' => $student,
                    'match_type' => 'name',
                    'confidence' => $confidence,
                    'matched_value' => $studentName,
                ];
            }
        }
        
        // Try to match by partial phone number (first 3 and last 3 digits)
        if ($parsedData['partial_phone']) {
            $phoneMatches = $this->findStudentsByPartialPhone($parsedData['partial_phone']);
            foreach ($phoneMatches as $student) {
                $confidence = 0.80;
                // Increase confidence if parent name also matches
                if ($parsedData['parent_name'] && $this->matchesParentName($student, $parsedData['parent_name'])) {
                    $confidence = 0.92;
                }
                // Increase confidence if student name also matches
                if (count($studentNames) > 0) {
                    $studentFullName = $student->first_name . ' ' . $student->last_name;
                    foreach ($studentNames as $name) {
                        if (stripos($studentFullName, $name) !== false) {
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
        if ($phoneNumber) {
            $normalizedPhone = $this->normalizePhone($phoneNumber);
            $phoneMatches = $this->findStudentsByPhone($normalizedPhone);
            
            foreach ($phoneMatches as $student) {
                $confidence = 0.85;
                // Increase confidence if parent name also matches
                if ($parsedData['parent_name'] && $this->matchesParentName($student, $parsedData['parent_name'])) {
                    $confidence = 0.95;
                }
                
                $matches[] = [
                    'student' => $student,
                    'match_type' => 'phone',
                    'confidence' => $confidence,
                    'matched_value' => $normalizedPhone,
                ];
            }
        }
        
        // Try to match by parent name only (if no other matches found)
        if (empty($matches) && $parsedData['parent_name']) {
            $parentMatches = $this->findStudentsByParentName($parsedData['parent_name']);
            foreach ($parentMatches as $student) {
                $matches[] = [
                    'student' => $student,
                    'match_type' => 'parent_name',
                    'confidence' => 0.60,
                    'matched_value' => $parsedData['parent_name'],
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
        
        // Extract child name: After "Acc."
        if (preg_match('/Acc\.\s*([A-Z][A-Za-z\s&]+?)(?:\s|$)/i', $description, $childMatches)) {
            $result['child_name'] = trim($childMatches[1]);
        }
        
        return $result;
    }
    
    /**
     * Extract admission numbers from description (handles multiple: RKS000 RKS001, RKS000 & RKS001)
     */
    protected function extractAdmissionNumbers(string $description): array
    {
        $admissionNumbers = [];
        
        // Patterns: RKS000, RKS 000, RKS000 RKS001, RKS000 & RKS001
        $patterns = [
            '/RKS\s*(\d{3,})/i',  // RKS000 or RKS 000
            '/ADM[-\s]?(\d+)/i',   // ADM123, ADM-123
            '/(\d{3,})\/(\d{4})/', // Format: 123/2024
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $description, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $admissionNumbers[] = $match[1];
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
        
        // Extract from "Acc." pattern: "Acc. Trevor Osairi" or "Acc. Trevor Susan"
        if (preg_match('/Acc\.\s*([A-Z][A-Za-z\s&]+?)(?:\s|$)/i', $description, $matches)) {
            $nameString = trim($matches[1]);
            // Split by "and", "&", or space
            $nameParts = preg_split('/\s+(?:and|&)\s+|\s+/i', $nameString);
            foreach ($nameParts as $part) {
                $part = trim($part);
                if (strlen($part) > 2 && preg_match('/^[A-Z][a-z]+/', $part)) {
                    $names[] = $part;
                }
            }
        }
        
        // Also try to extract capitalized names (2-3 words) that look like student names
        if (preg_match_all('/\b([A-Z][a-z]+(?:\s+[A-Z][a-z]+){1,2})\b/', $description, $nameMatches)) {
            foreach ($nameMatches[1] as $name) {
                // Exclude common words
                $exclude = ['Pay', 'Bill', 'From', 'Acc', 'Online', 'MPESA', 'Bank', 'Transfer'];
                if (!in_array($name, $exclude) && strlen($name) > 3) {
                    $names[] = $name;
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
        
        // Split parent name into parts
        $nameParts = explode(' ', trim($parentName));
        if (count($nameParts) < 2) {
            return [];
        }
        
        $firstName = $nameParts[0];
        $lastName = end($nameParts);
        
        // Search in parent_info table
        $parents = ParentInfo::where(function($q) use ($firstName, $lastName, $parentName) {
            $q->where(function($q2) use ($firstName, $lastName) {
                $q2->where('father_first_name', 'LIKE', "%{$firstName}%")
                   ->where('father_last_name', 'LIKE', "%{$lastName}%");
            })->orWhere(function($q2) use ($firstName, $lastName) {
                $q2->where('mother_first_name', 'LIKE', "%{$firstName}%")
                   ->where('mother_last_name', 'LIKE', "%{$lastName}%");
            })->orWhere(function($q2) use ($firstName, $lastName) {
                $q2->where('guardian_first_name', 'LIKE', "%{$firstName}%")
                   ->where('guardian_last_name', 'LIKE', "%{$lastName}%");
            })->orWhere('father_full_name', 'LIKE', "%{$parentName}%")
              ->orWhere('mother_full_name', 'LIKE', "%{$parentName}%")
              ->orWhere('guardian_full_name', 'LIKE', "%{$parentName}%");
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
        $nameParts = explode(' ', trim($parentName));
        $firstName = $nameParts[0] ?? '';
        $lastName = end($nameParts);
        
        // Check father
        if ($parent->father_first_name && $parent->father_last_name) {
            if (stripos($parent->father_first_name, $firstName) !== false && 
                stripos($parent->father_last_name, $lastName) !== false) {
                return true;
            }
        }
        
        // Check mother
        if ($parent->mother_first_name && $parent->mother_last_name) {
            if (stripos($parent->mother_first_name, $firstName) !== false && 
                stripos($parent->mother_last_name, $lastName) !== false) {
                return true;
            }
        }
        
        // Check guardian
        if ($parent->guardian_first_name && $parent->guardian_last_name) {
            if (stripos($parent->guardian_first_name, $firstName) !== false && 
                stripos($parent->guardian_last_name, $lastName) !== false) {
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
     * Call Python parser
     */
    protected function callPythonParser(string $pdfPath, string $bankType): array
    {
        $script = base_path('app/Services/python/bank_statement_parser.py');
        // The parser from reference project only needs PDF path, it auto-detects MPESA vs Bank
        // Try python3 first, fallback to python (for compatibility)
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

