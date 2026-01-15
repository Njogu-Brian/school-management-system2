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
        
        // Clear MANUALLY_REJECTED marker when manually shared
        $matchNotes = $transaction->match_notes ?? '';
        if (strpos($matchNotes, 'MANUALLY_REJECTED') !== false) {
            $matchNotes = 'Manually shared among siblings';
        } else {
            $matchNotes = $matchNotes ?: 'Manually shared among siblings';
        }
        
        // If transaction is rejected or unmatched, change to draft when sharing
        $newStatus = $transaction->status;
        if (in_array($transaction->status, ['rejected', 'unmatched'])) {
            $newStatus = 'draft';
        }
        
        $transaction->update([
            'is_shared' => true,
            'shared_allocations' => $allocations,
            'status' => $newStatus, // Ensure it's draft if it was rejected/unmatched
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
        
        // Check if payment already exists by transaction code
        if ($transaction->reference_number) {
            $existingPayment = \App\Models\Payment::where('transaction_code', $transaction->reference_number)
                ->where('payment_date', $transaction->transaction_date)
                ->first();
            
            if ($existingPayment) {
                // Link transaction to existing payment
                $transaction->update([
                    'payment_id' => $existingPayment->id,
                    'payment_created' => true,
                ]);
                
                \Log::info('Found existing payment for transaction', [
                    'transaction_id' => $transaction->id,
                    'payment_id' => $existingPayment->id,
                    'transaction_code' => $transaction->reference_number,
                ]);
                
                return $existingPayment;
            }
        }
        
        if ($transaction->is_shared && $transaction->shared_allocations) {
            // Generate same receipt number for all sibling payments
            // Check if receipt number already exists and generate unique one if needed
            $maxAttempts = 10;
            $attempt = 0;
            do {
                $sharedReceiptNumber = \App\Services\DocumentNumberService::generateReceipt();
                $exists = \App\Models\Payment::where('receipt_number', $sharedReceiptNumber)->exists();
                $attempt++;
                
                if ($exists && $attempt < $maxAttempts) {
                    // Wait a tiny bit and try again (handles race conditions)
                    usleep(10000); // 0.01 seconds
                }
            } while ($exists && $attempt < $maxAttempts);
            
            if ($exists) {
                // If still exists after max attempts, append transaction ID to make it unique
                $sharedReceiptNumber = $sharedReceiptNumber . '-' . $transaction->id;
                
                \Log::warning('Shared receipt number collision after max attempts, using modified number', [
                    'modified_receipt' => $sharedReceiptNumber,
                    'transaction_id' => $transaction->id,
                ]);
            }
            
            // Final check: ensure shared receipt number is truly unique before creating payments
            $finalSharedReceiptNumber = $sharedReceiptNumber;
            $maxSharedReceiptAttempts = 10;
            $sharedReceiptAttempt = 0;
            while (\App\Models\Payment::where('receipt_number', $finalSharedReceiptNumber)->exists() && $sharedReceiptAttempt < $maxSharedReceiptAttempts) {
                $uniqueSuffix = $transaction->id . '-' . time() . '-' . rand(1000, 9999);
                $finalSharedReceiptNumber = ($sharedReceiptNumber ?: 'RCPT') . '-' . $uniqueSuffix;
                $sharedReceiptAttempt++;
                usleep(10000); // 0.01 seconds
            }
            
            if ($sharedReceiptAttempt >= $maxSharedReceiptAttempts) {
                // Last resort: use a completely unique receipt number
                $finalSharedReceiptNumber = 'RCPT-' . $transaction->id . '-' . time() . '-' . uniqid();
                \Log::error('Failed to generate unique shared receipt number after max attempts', [
                    'transaction_id' => $transaction->id,
                    'original_receipt' => $sharedReceiptNumber,
                    'final_receipt' => $finalSharedReceiptNumber,
                ]);
            }
            
            // Create payments for each sibling
            $payments = [];
            foreach ($transaction->shared_allocations as $allocation) {
                $student = Student::findOrFail($allocation['student_id']);
                $payment = $this->createSinglePayment($transaction, $student, $allocation['amount'], $finalSharedReceiptNumber, $skipAllocation);
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
     * Create a single payment record
     * @param bool $skipAllocation Skip auto-allocation for bulk operations (faster)
     */
    protected function createSinglePayment(
        BankStatementTransaction $transaction,
        Student $student,
        float $amount,
        ?string $receiptNumber = null,
        bool $skipAllocation = false
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
            // First check for exact match (same student, amount, date)
            $existingPayment = \App\Models\Payment::where('transaction_code', $transaction->reference_number)
                ->where('student_id', $student->id)
                ->where('amount', $amount)
                ->where('payment_date', $transaction->transaction_date)
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
            
            // Check if transaction code (original or modified) is already used
            $originalCode = $transaction->reference_number;
            $modifiedCode = $originalCode . '-' . $transaction->id;
            
            // Check both original and modified codes
            $codeExists = \App\Models\Payment::whereIn('transaction_code', [$originalCode, $modifiedCode])->exists();
            
            if ($codeExists) {
                // Generate a unique transaction code by appending transaction ID and timestamp
                $uniqueSuffix = $transaction->id . '-' . time();
                $transactionCode = $originalCode . '-' . $uniqueSuffix;
                
                // Double-check the new code doesn't exist (very unlikely but possible)
                $maxAttempts = 5;
                $attempt = 0;
                while (\App\Models\Payment::where('transaction_code', $transactionCode)->exists() && $attempt < $maxAttempts) {
                    $uniqueSuffix = $transaction->id . '-' . time() . '-' . rand(1000, 9999);
                    $transactionCode = $originalCode . '-' . $uniqueSuffix;
                    $attempt++;
                    usleep(10000); // 0.01 seconds
                }
                
                Log::warning('Transaction code already exists, using modified code', [
                    'original_code' => $originalCode,
                    'new_code' => $transactionCode,
                    'transaction_id' => $transaction->id,
                ]);
            }
        }
        
        // Generate unique receipt number
        $finalReceiptNumber = $receiptNumber;
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
        
        // Final check: ensure transaction code is truly unique before creating
        $finalTransactionCode = $transactionCode;
        $maxCodeAttempts = 10;
        $codeAttempt = 0;
        while (\App\Models\Payment::where('transaction_code', $finalTransactionCode)->exists() && $codeAttempt < $maxCodeAttempts) {
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
     * Find student by admission number (handles both RKS412 and 412 formats)
     */
    protected function findStudentByAdmissionNumber(string $admissionNumber): ?Student
    {
        // Try matching with full format (RKS412) first
        $student = Student::where('admission_number', $admissionNumber)
            ->where('archive', 0)
            ->where('is_alumni', false)
            ->first();
        
        // If not found, try without RKS prefix (in case stored as just digits)
        if (!$student && preg_match('/RKS(\d+)/i', $admissionNumber, $digitsMatch)) {
            $digitsOnly = $digitsMatch[1];
            $student = Student::where(function($q) use ($digitsOnly) {
                $q->where('admission_number', $digitsOnly)
                  ->orWhere('admission_number', 'LIKE', "%{$digitsOnly}");
            })
            ->where('archive', 0)
            ->where('is_alumni', false)
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
}

