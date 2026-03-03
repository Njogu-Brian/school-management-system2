<?php

namespace App\Services;

use App\Models\Student;
use App\Models\Invoice;
use App\Models\MpesaC2BTransaction;
use App\Models\ParentInfo;
use App\Services\StudentBalanceService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MpesaSmartMatchingService
{
    /**
     * Attempt to match a C2B transaction to a student
     */
    public function matchTransaction(MpesaC2BTransaction $transaction): array
    {
        // Start with learned suggestions from past manual assignments (system improves over time)
        $suggestions = \App\Models\ManualMatchLearning::findSuggestions(
            'c2b',
            $transaction->trans_id ?? null,
            $transaction->bill_ref_number ?? $transaction->full_name ?? null
        );

        // Method 1: Exact match by admission number in bill_ref_number
        $admissionMatch = $this->matchByAdmissionNumber($transaction);
        if ($admissionMatch) {
            $suggestions[] = $admissionMatch;
        }

        // Method 2: Exact match by invoice number
        $invoiceMatch = $this->matchByInvoiceNumber($transaction);
        if ($invoiceMatch) {
            $suggestions[] = $invoiceMatch;
        }

        // Method 3: Match by phone number
        $phoneMatches = $this->matchByPhoneNumber($transaction);
        if (!empty($phoneMatches)) {
            $suggestions = array_merge($suggestions, $phoneMatches);
        }

        // Method 4: Match by parent name and reference (for siblings)
        $parentSiblingMatches = $this->matchByParentAndReference($transaction);
        if (!empty($parentSiblingMatches)) {
            $suggestions = array_merge($suggestions, $parentSiblingMatches);
        }

        // Method 4b: Match by reference as multiple sibling names (e.g. "Christie and Chrissy" -> siblings)
        $refSiblingMatches = $this->matchByReferenceAsSiblingNames($transaction);
        if (!empty($refSiblingMatches)) {
            $suggestions = array_merge($suggestions, $refSiblingMatches);
        }

        // Method 4c: Match by reference/particulars as student name (e.g. "job" -> student Job)
        $refNameMatches = $this->matchByReferenceAsStudentName($transaction);
        if (!empty($refNameMatches)) {
            $suggestions = array_merge($suggestions, $refNameMatches);
        }

        // Payer name (full_name) is only used to match PARENT in Method 4 (matchByParentAndReference).
        // It must never be used for direct student name similarity — so we do not call matchByName().

        // Remove duplicates and sort by confidence
        $suggestions = $this->deduplicateAndSort($suggestions);

        // Store suggestions in transaction
        $transaction->storeSuggestions(array_slice($suggestions, 0, 5)); // Store top 5

        // Auto-match if confidence is high enough
        if (!empty($suggestions) && $suggestions[0]['confidence'] >= 80) {
            $top = $suggestions[0];
            $siblingIds = $top['siblings'] ?? [];
            if (count($siblingIds) >= 2 && in_array($top['match_type'] ?? '', ['parent_sibling', 'reference_sibling'])) {
                // Sibling payment: smart share based on fee balances
                $smartAllocations = $this->computeSmartSiblingAllocations((float) $transaction->trans_amount, $siblingIds);
                if (!empty($smartAllocations)) {
                    $transaction->autoMatchSiblings($siblingIds, $smartAllocations, $top['confidence'], $top['reason']);
                    Log::info('Auto-matched C2B transaction to siblings', [
                        'transaction_id' => $transaction->id,
                        'trans_id' => $transaction->trans_id,
                        'sibling_ids' => $siblingIds,
                        'allocations' => $smartAllocations,
                        'confidence' => $top['confidence'],
                        'reason' => $top['reason'],
                    ]);
                }
            } else {
                $student = Student::find($top['student_id']);
                if ($student) {
                    $transaction->autoMatch($student, $top['confidence'], $top['reason']);
                    Log::info('Auto-matched C2B transaction', [
                        'transaction_id' => $transaction->id,
                        'trans_id' => $transaction->trans_id,
                        'student_id' => $student->id,
                        'confidence' => $top['confidence'],
                        'reason' => $top['reason'],
                    ]);
                }
            }
        }

        return $suggestions;
    }

    /**
     * Match by admission number in bill reference (case-insensitive; handles "Name RKS354", "RKS354", "gabriela muthoni RKS354")
     */
    protected function matchByAdmissionNumber(MpesaC2BTransaction $transaction): ?array
    {
        if (empty($transaction->bill_ref_number)) {
            return null;
        }

        $ref = trim($transaction->bill_ref_number);
        $refUpper = strtoupper($ref);

        // Try exact match first (case-insensitive)
        $student = Student::with('classroom')->whereRaw('UPPER(TRIM(admission_number)) = ?', [$refUpper])
            ->where('archive', 0)
            ->where('is_alumni', false)
            ->first();
        if ($student) {
            return [
                'student_id' => $student->id,
                'student_name' => $student->first_name . ' ' . $student->last_name,
                'admission_number' => $student->admission_number,
                'classroom_name' => $student->classroom ? $student->classroom->name : null,
                'confidence' => 100,
                'reason' => 'Exact admission number match in reference',
                'match_type' => 'admission_exact',
            ];
        }

        // Extract RKS123 or RKS 123 from reference (child name + admission in any case)
        if (preg_match('/RKS\s*(\d{3,})/i', $ref, $rksMatch)) {
            $digits = $rksMatch[1];
            $adm = 'RKS' . $digits;
            $admPadded = 'RKS' . str_pad($digits, 3, '0', STR_PAD_LEFT);
            $student = Student::with('classroom')
                ->where('archive', 0)
                ->where('is_alumni', false)
                ->where(function ($q) use ($adm, $admPadded, $digits) {
                    $q->whereRaw('UPPER(TRIM(admission_number)) = ?', [strtoupper($adm)])
                      ->orWhereRaw('UPPER(TRIM(admission_number)) = ?', [strtoupper($admPadded)])
                      ->orWhereRaw('UPPER(admission_number) LIKE ?', ['%' . $digits . '%']);
                })
                ->first();
            if ($student) {
                return [
                    'student_id' => $student->id,
                    'student_name' => $student->first_name . ' ' . $student->last_name,
                    'admission_number' => $student->admission_number,
                    'classroom_name' => $student->classroom ? $student->classroom->name : null,
                    'confidence' => 95,
                    'reason' => 'Admission number (RKS) extracted from reference',
                    'match_type' => 'admission_extracted',
                ];
            }
        }

        // Fallback: extract any alphanumeric token that might be admission (ADM123, 354, etc.)
        preg_match('/([A-Z]*\d{3,})/i', $ref, $matches);
        if (!empty($matches[1])) {
            $extracted = strtoupper(trim($matches[1]));
            $student = Student::with('classroom')
                ->where('archive', 0)
                ->where('is_alumni', false)
                ->where(function ($q) use ($extracted) {
                    $q->whereRaw('UPPER(TRIM(admission_number)) = ?', [$extracted])
                      ->orWhereRaw('UPPER(admission_number) LIKE ?', ['%' . $extracted . '%']);
                })
                ->first();
            if ($student) {
                return [
                    'student_id' => $student->id,
                    'student_name' => $student->first_name . ' ' . $student->last_name,
                    'admission_number' => $student->admission_number,
                    'classroom_name' => $student->classroom ? $student->classroom->name : null,
                    'confidence' => 90,
                    'reason' => 'Admission number extracted from reference',
                    'match_type' => 'admission_extracted',
                ];
            }
        }

        return null;
    }

    /**
     * Match by invoice number
     */
    protected function matchByInvoiceNumber(MpesaC2BTransaction $transaction): ?array
    {
        $ref = $transaction->bill_ref_number ?? $transaction->invoice_number;
        
        if (empty($ref)) {
            return null;
        }

        $ref = strtoupper(trim($ref));
        
        // Look for invoice number patterns
        $invoice = Invoice::whereRaw('UPPER(invoice_number) = ?', [$ref])
            ->orWhereRaw('UPPER(invoice_number) LIKE ?', ['%' . $ref . '%'])
            ->with('student')
            ->first();

        if ($invoice && $invoice->student) {
            $invoice->student->load('classroom');
            return [
                'student_id' => $invoice->student->id,
                'student_name' => $invoice->student->first_name . ' ' . $invoice->student->last_name,
                'admission_number' => $invoice->student->admission_number,
                'classroom_name' => $invoice->student->classroom ? $invoice->student->classroom->name : null,
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'confidence' => 95,
                'reason' => 'Exact invoice number match',
                'match_type' => 'invoice_exact',
            ];
        }

        return null;
    }

    /**
     * Match by phone number
     */
    protected function matchByPhoneNumber(MpesaC2BTransaction $transaction): array
    {
        $matches = [];
        
        if (empty($transaction->msisdn)) {
            return $matches;
        }

        // Normalize phone number
        $phone = $transaction->msisdn;
        $normalizedPhone = $this->normalizePhone($phone);

        // Search in family records
        $students = Student::whereHas('family', function ($query) use ($phone, $normalizedPhone) {
            $query->where('phone', 'LIKE', '%' . $normalizedPhone . '%')
                ->orWhere('father_phone', 'LIKE', '%' . $normalizedPhone . '%')
                ->orWhere('mother_phone', 'LIKE', '%' . $normalizedPhone . '%');
        })->with(['family', 'classroom'])->get();

        foreach ($students as $student) {
            $phoneMatch = false;
            $matchedField = '';
            
            if ($this->phonesMatch($phone, $student->family->phone)) {
                $phoneMatch = true;
                $matchedField = 'Primary phone';
            } elseif ($this->phonesMatch($phone, $student->family->father_phone)) {
                $phoneMatch = true;
                $matchedField = 'Father phone';
            } elseif ($this->phonesMatch($phone, $student->family->mother_phone)) {
                $phoneMatch = true;
                $matchedField = 'Mother phone';
            }

            if ($phoneMatch) {
                $matches[] = [
                    'student_id' => $student->id,
                    'student_name' => $student->first_name . ' ' . $student->last_name,
                    'admission_number' => $student->admission_number,
                    'classroom_name' => $student->classroom ? $student->classroom->name : null,
                    'confidence' => 75,
                    'reason' => 'Phone number match (' . $matchedField . ')',
                    'match_type' => 'phone',
                ];
            }
        }

        return $matches;
    }

    /**
     * Match by parent name and reference (for sibling payments)
     * Payer name should match parent, reference should contain child names
     */
    protected function matchByParentAndReference(MpesaC2BTransaction $transaction): array
    {
        $matches = [];
        
        $payerName = trim($transaction->full_name);
        $reference = trim($transaction->bill_ref_number ?? '');
        
        // Skip if payer name is empty or "Unknown"
        if (empty($payerName) || $payerName === 'Unknown' || empty($reference)) {
            return $matches;
        }
        
        // Parse reference for multiple child names (e.g., "Nadia/Fadhili/Dawn" or "Nadia, Fadhili, Dawn")
        $childNames = $this->parseChildNamesFromReference($reference);
        
        if (empty($childNames)) {
            return $matches;
        }
        
        // Find parents matching payer name
        $parents = ParentInfo::where(function($q) use ($payerName) {
            $q->where('father_name', 'LIKE', "%{$payerName}%")
              ->orWhere('mother_name', 'LIKE', "%{$payerName}%")
              ->orWhere('guardian_name', 'LIKE', "%{$payerName}%");
        })->with('students')->get();
        
        foreach ($parents as $parent) {
            // Get all children of this parent
            $children = $parent->students()
                ->where('archive', 0)
                ->where('is_alumni', false)
                ->with('classroom')
                ->get();
            
            // Find children whose names match the reference
            $matchingChildren = [];
            foreach ($children as $child) {
                $childFullName = strtolower($child->first_name . ' ' . $child->last_name);
                $childFirstName = strtolower($child->first_name);
                $childLastName = strtolower($child->last_name);
                
                foreach ($childNames as $childName) {
                    $childNameLower = strtolower(trim($childName));
                    
                    // Check if child name matches student
                    if (stripos($childFullName, $childNameLower) !== false || 
                        stripos($childNameLower, $childFirstName) !== false ||
                        stripos($childNameLower, $childLastName) !== false ||
                        stripos($childFullName, str_replace(' ', '', $childNameLower)) !== false) {
                        
                        // Check if we already added this child
                        if (!in_array($child->id, array_column($matchingChildren, 'student_id'))) {
                            $matchingChildren[] = [
                                'student_id' => $child->id,
                                'student_name' => $child->first_name . ' ' . $child->last_name,
                                'admission_number' => $child->admission_number,
                                'classroom_name' => $child->classroom ? $child->classroom->name : null,
                            ];
                        }
                        break; // Found match for this child name
                    }
                }
            }
            
            // If we found matching children, add them as suggestions
            if (!empty($matchingChildren)) {
                // Higher confidence if multiple children match
                $confidence = count($matchingChildren) >= count($childNames) ? 85 : 75;
                
                foreach ($matchingChildren as $child) {
                    $matches[] = [
                        'student_id' => $child['student_id'],
                        'student_name' => $child['student_name'],
                        'admission_number' => $child['admission_number'],
                        'classroom_name' => $child['classroom_name'],
                        'confidence' => $confidence,
                        'reason' => 'Parent name match + child name in reference (sibling payment)',
                        'match_type' => 'parent_sibling',
                        'siblings' => array_map(function($c) {
                            return $c['student_id'];
                        }, $matchingChildren), // Include all sibling IDs
                    ];
                }
            }
        }
        
        return $matches;
    }
    
    /**
     * Match by reference as multiple sibling names (e.g. "Christie and Chrissy").
     * Reference-only; no parent name required.
     */
    protected function matchByReferenceAsSiblingNames(MpesaC2BTransaction $transaction): array
    {
        $ref = trim($transaction->bill_ref_number ?? '');
        if (strlen($ref) < 4) {
            return [];
        }
        if (preg_match('/RKS\s*\d+/i', $ref) || preg_match('/^[A-Z]*\d{3,}$/i', $ref)) {
            return [];
        }

        $childNames = $this->parseChildNamesFromReference($ref);
        if (count($childNames) < 2) {
            return [];
        }

        // Per family: which child names we matched (student_id per name)
        $familyMatches = [];
        foreach ($childNames as $childName) {
            $childNameLower = strtolower(trim($childName));
            if (strlen($childNameLower) < 2) {
                continue;
            }
            $students = Student::with('classroom')
                ->where('archive', 0)
                ->where('is_alumni', false)
                ->whereNotNull('family_id')
                ->where(function ($q) use ($childNameLower) {
                    $q->whereRaw('LOWER(TRIM(first_name)) = ?', [$childNameLower])
                      ->orWhereRaw('LOWER(TRIM(last_name)) = ?', [$childNameLower])
                      ->orWhereRaw('LOWER(TRIM(first_name)) LIKE ?', ['%' . $childNameLower . '%'])
                      ->orWhereRaw('LOWER(TRIM(last_name)) LIKE ?', ['%' . $childNameLower . '%'])
                      ->orWhereRaw('LOWER(REPLACE(CONCAT(TRIM(first_name), TRIM(last_name)), \' \', \'\')) = ?', [str_replace(' ', '', $childNameLower)]);
                })
                ->get();

            foreach ($students as $s) {
                $familyId = $s->family_id;
                if (!$familyId) continue;
                if (!isset($familyMatches[$familyId])) {
                    $familyMatches[$familyId] = [];
                }
                if (!isset($familyMatches[$familyId][$childName])) {
                    $familyMatches[$familyId][$childName] = [];
                }
                if (!in_array($s->id, $familyMatches[$familyId][$childName])) {
                    $familyMatches[$familyId][$childName][] = $s;
                }
            }
        }

        $matches = [];
        foreach ($familyMatches as $familyId => $nameToStudents) {
            if (count($nameToStudents) < 2) {
                continue;
            }
            $siblingIds = [];
            $children = [];
            foreach ($nameToStudents as $students) {
                $s = $students[0];
                if (!in_array($s->id, $siblingIds)) {
                    $siblingIds[] = $s->id;
                    $children[] = [
                        'student_id' => $s->id,
                        'student_name' => $s->first_name . ' ' . $s->last_name,
                        'admission_number' => $s->admission_number,
                        'classroom_name' => $s->classroom ? $s->classroom->name : null,
                    ];
                }
            }
            if (count($children) >= 2) {
                $confidence = count($children) >= count($childNames) ? 85 : 75;
                foreach ($children as $child) {
                    $matches[] = [
                        'student_id' => $child['student_id'],
                        'student_name' => $child['student_name'],
                        'admission_number' => $child['admission_number'],
                        'classroom_name' => $child['classroom_name'],
                        'confidence' => $confidence,
                        'reason' => 'Reference matches sibling names: ' . $ref,
                        'match_type' => 'reference_sibling',
                        'siblings' => $siblingIds,
                    ];
                }
            }
        }

        return $matches;
    }

    /**
     * Compute smart sibling allocations based on fee balances.
     * Rules: share equally; if one has balance < half, clear that one first, remainder to others;
     * overpayment only when all siblings have cleared their fee balance.
     */
    public function computeSmartSiblingAllocations(float $totalAmount, array $siblingIds): array
    {
        $siblingIds = array_values(array_unique(array_filter(array_map('intval', $siblingIds))));
        if (empty($siblingIds) || $totalAmount <= 0) {
            return [];
        }

        $balances = [];
        foreach ($siblingIds as $id) {
            $balances[$id] = max(0, StudentBalanceService::getTotalOutstandingBalance($id));
        }

        $n = count($siblingIds);
        $equalShare = $totalAmount / $n;
        $allocations = array_fill_keys($siblingIds, 0.0);
        $remainder = 0.0;

        foreach ($siblingIds as $id) {
            $balance = $balances[$id];
            if ($balance < $equalShare) {
                $allocations[$id] = round(min($balance, $equalShare), 2);
                $remainder += ($equalShare - $allocations[$id]);
            } else {
                $allocations[$id] = round($equalShare, 2);
            }
        }

        // Distribute remainder to siblings who still have room (balance > allocation)
        if ($remainder > 0.01) {
            $candidates = array_values(array_filter($siblingIds, function ($id) use ($balances, $allocations) {
                return ($balances[$id] ?? 0) > ($allocations[$id] ?? 0);
            }));
            if (!empty($candidates)) {
                foreach ($candidates as $id) {
                    if ($remainder <= 0.01) break;
                    $room = ($balances[$id] ?? 0) - ($allocations[$id] ?? 0);
                    $add = min($room, $remainder);
                    $allocations[$id] = round(($allocations[$id] ?? 0) + $add, 2);
                    $remainder = round($remainder - $add, 2);
                }
            }
            if ($remainder > 0.01) {
                $allocations[$siblingIds[0]] = round(($allocations[$siblingIds[0]] ?? 0) + $remainder, 2);
            }
        }

        return array_values(array_filter(array_map(function ($id) use ($allocations) {
            $amt = (float) ($allocations[$id] ?? 0);
            return $amt > 0 ? ['student_id' => $id, 'amount' => $amt] : null;
        }, $siblingIds)));
    }

    /**
     * Parse child names from reference field
     * Handles formats like: "Nadia/Fadhili/Dawn", "Nadia, Fadhili, Dawn", "Nadia and Chrissy"
     */
    protected function parseChildNamesFromReference(string $reference): array
    {
        if (empty($reference)) {
            return [];
        }
        
        // Try splitting by common delimiters
        $delimiters = ['/', ',', '|', '&', ' and ', ' AND '];
        
        foreach ($delimiters as $delimiter) {
            if (stripos($reference, $delimiter) !== false) {
                $names = array_map('trim', explode($delimiter, $reference));
                $names = array_filter($names, function($name) {
                    return strlen($name) >= 2; // At least 2 characters
                });
                if (count($names) > 1) {
                    return array_values($names);
                }
            }
        }
        
        // If no delimiter found, try splitting by spaces (but only if it looks like multiple names)
        // This is less reliable, so we'll be conservative
        $words = array_filter(explode(' ', $reference), function($word) {
            return strlen(trim($word)) >= 2;
        });
        
        // If we have 2-4 words, treat them as potential names
        if (count($words) >= 2 && count($words) <= 4) {
            return array_values(array_map('trim', $words));
        }
        
        // Single name
        return [trim($reference)];
    }

    /**
     * Parse reference into name part and optional class/grade hint (e.g. "peter mwangi grade 7" -> name "peter mwangi", class "grade 7").
     */
    protected function parseReferenceNameAndClass(string $ref): array
    {
        $ref = trim($ref);
        $classHint = null;
        // Strip common class/grade suffixes: "grade 7", "grade 6", "class 5", "form 1", etc.
        if (preg_match('/\b(grade|class|form|std)\s*(\d+)\b/i', $ref, $m)) {
            $classHint = strtoupper(trim($m[1] . ' ' . $m[2]));
            $ref = trim(preg_replace('/\b(grade|class|form|std)\s*\d+\b/i', '', $ref));
        }
        $ref = trim(preg_replace('/\s+/', ' ', $ref));
        return ['name' => $ref, 'class_hint' => $classHint];
    }

    /**
     * Match by reference/particulars as student name.
     * Handles "job", "peter mwangi", "peter mwangi grade 7" etc. Skips when reference looks like admission number.
     */
    protected function matchByReferenceAsStudentName(MpesaC2BTransaction $transaction): array
    {
        $ref = trim($transaction->bill_ref_number ?? '');
        if (strlen($ref) < 2) {
            return [];
        }

        // Skip if reference looks like admission number (RKS123, digits, etc.)
        if (preg_match('/RKS\s*\d+/i', $ref) || preg_match('/^[A-Z]*\d{3,}$/i', $ref)) {
            return [];
        }

        $parsed = $this->parseReferenceNameAndClass($ref);
        $namePart = $parsed['name'];
        $classHint = $parsed['class_hint'];
        if (strlen($namePart) < 2) {
            return [];
        }

        $refUpper = strtoupper($namePart);
        $refLower = strtolower($namePart);

        // 1) Exact match on single first_name or last_name (e.g. "job")
        $exactMatches = Student::with('classroom')
            ->where('archive', 0)
            ->where('is_alumni', false)
            ->where(function ($q) use ($refUpper, $refLower) {
                $q->whereRaw('UPPER(TRIM(first_name)) = ?', [$refUpper])
                  ->orWhereRaw('UPPER(TRIM(last_name)) = ?', [$refUpper])
                  ->orWhereRaw('LOWER(TRIM(first_name)) = ?', [$refLower])
                  ->orWhereRaw('LOWER(TRIM(last_name)) = ?', [$refLower]);
            })
            ->get();

        $matches = [];
        foreach ($exactMatches as $student) {
            $confidence = 95;
            $reason = 'Reference matches student name: ' . $ref;
            if ($classHint && $student->classroom && stripos($student->classroom->name, $classHint) !== false) {
                $confidence = 98;
                $reason = 'Reference matches student name + class: ' . $ref;
            }
            $matches[] = [
                'student_id' => $student->id,
                'student_name' => $student->first_name . ' ' . $student->last_name,
                'admission_number' => $student->admission_number,
                'classroom_name' => $student->classroom ? $student->classroom->name : null,
                'confidence' => $confidence,
                'reason' => $reason,
                'match_type' => 'reference_student_name',
            ];
        }

        if (!empty($matches)) {
            return $matches;
        }

        // 2a) Concatenated name match: "sandranjoki" -> Sandra Njoki (no space between first+last)
        if (strlen($namePart) >= 6 && strpos($namePart, ' ') === false) {
            $refNorm = strtolower(preg_replace('/\s+/', '', $namePart));
            $concatenatedMatches = Student::with('classroom')
                ->where('archive', 0)
                ->where('is_alumni', false)
                ->whereRaw("LOWER(REPLACE(CONCAT(TRIM(first_name), TRIM(last_name)), ' ', '')) = ?", [$refNorm])
                ->get();
            foreach ($concatenatedMatches as $student) {
                $matches[] = [
                    'student_id' => $student->id,
                    'student_name' => $student->first_name . ' ' . $student->last_name,
                    'admission_number' => $student->admission_number,
                    'classroom_name' => $student->classroom ? $student->classroom->name : null,
                    'confidence' => 95,
                    'reason' => 'Reference matches student name: ' . $ref,
                    'match_type' => 'reference_student_name',
                ];
            }
            if (!empty($matches)) {
                return $matches;
            }
        }

        // 2b) Full name match: "peter mwangi" -> CONCAT(first_name, ' ', last_name) or first_name + last_name
        $nameWords = array_values(array_filter(explode(' ', $namePart), function ($p) {
            return strlen(trim($p)) >= 2;
        }));
        if (count($nameWords) >= 2) {
            $first = $nameWords[0];
            $last = $nameWords[count($nameWords) - 1];
            $query = Student::with('classroom')
                ->where('archive', 0)
                ->where('is_alumni', false)
                ->where(function ($q) use ($first, $last) {
                    $q->where(function ($q2) use ($first, $last) {
                        $q2->whereRaw('UPPER(TRIM(first_name)) = ?', [strtoupper($first)])
                           ->whereRaw('UPPER(TRIM(last_name)) = ?', [strtoupper($last)]);
                    })->orWhere(function ($q2) use ($first, $last) {
                        $q2->whereRaw('UPPER(TRIM(first_name)) = ?', [strtoupper($last)])
                           ->whereRaw('UPPER(TRIM(last_name)) = ?', [strtoupper($first)]);
                    });
                });
            if ($classHint) {
                $query->whereHas('classroom', function ($q) use ($classHint) {
                    $q->whereRaw('UPPER(name) LIKE ?', ['%' . $classHint . '%']);
                });
            }
            $fullNameMatches = $query->get();
            foreach ($fullNameMatches as $student) {
                $confidence = $classHint && $student->classroom && stripos($student->classroom->name, $classHint) !== false ? 98 : 95;
                $matches[] = [
                    'student_id' => $student->id,
                    'student_name' => $student->first_name . ' ' . $student->last_name,
                    'admission_number' => $student->admission_number,
                    'classroom_name' => $student->classroom ? $student->classroom->name : null,
                    'confidence' => $confidence,
                    'reason' => 'Reference matches student name' . ($classHint ? ' + class' : '') . ': ' . $ref,
                    'match_type' => 'reference_student_name',
                ];
            }
            if (!empty($matches)) {
                return $matches;
            }
        }

        // 3) Name-parts + similarity (exclude numeric/class-like tokens); optionally filter by class hint
        $nameParts = array_filter($nameWords ?? array_values(array_filter(explode(' ', $namePart), function ($p) {
            return strlen(trim($p)) >= 2 && !preg_match('/^\d+$/', trim($p));
        })), function ($p) {
            $p = strtolower(trim($p));
            return $p !== 'grade' && $p !== 'class' && $p !== 'form' && $p !== 'std';
        });
        if (empty($nameParts)) {
            return [];
        }

        $students = Student::with('classroom')
            ->where('archive', 0)
            ->where('is_alumni', false)
            ->where(function ($query) use ($nameParts) {
                foreach ($nameParts as $part) {
                    if (strlen($part) >= 2) {
                        $query->orWhereRaw('UPPER(first_name) LIKE ?', ['%' . strtoupper($part) . '%'])
                              ->orWhereRaw('UPPER(last_name) LIKE ?', ['%' . strtoupper($part) . '%'])
                              ->orWhereRaw('UPPER(middle_name) LIKE ?', ['%' . strtoupper($part) . '%']);
                    }
                }
            });
        if ($classHint) {
            $students = $students->whereHas('classroom', function ($q) use ($classHint) {
                $q->whereRaw('UPPER(name) LIKE ?', ['%' . $classHint . '%']);
            });
        }
        $students = $students->get();

        $studentFullNameUpper = null;
        foreach ($students as $student) {
            $studentFullName = strtoupper($student->first_name . ' ' . $student->last_name);
            $similarity = 0;
            similar_text($refUpper, $studentFullName, $similarity);
            if ($similarity >= 65) {
                $confidence = (int) round($similarity);
                $confidence = min(92, max(80, $confidence));
                if ($classHint && $student->classroom && stripos($student->classroom->name, $classHint) !== false) {
                    $confidence = min(98, $confidence + 5);
                }
                $matches[] = [
                    'student_id' => $student->id,
                    'student_name' => $student->first_name . ' ' . $student->last_name,
                    'admission_number' => $student->admission_number,
                    'classroom_name' => $student->classroom ? $student->classroom->name : null,
                    'confidence' => $confidence,
                    'reason' => 'Matched: ' . $student->admission_number . ' ' . $confidence . '% confidence',
                    'match_type' => 'reference_student_name',
                ];
            }
        }

        return $matches;
    }

    /**
     * Deduplicate and sort suggestions
     */
    protected function deduplicateAndSort(array $suggestions): array
    {
        // Remove duplicates by student_id, keeping highest confidence
        $unique = [];
        foreach ($suggestions as $suggestion) {
            $studentId = $suggestion['student_id'];
            
            if (!isset($unique[$studentId]) || $unique[$studentId]['confidence'] < $suggestion['confidence']) {
                $unique[$studentId] = $suggestion;
            }
        }

        // Sort by confidence descending
        usort($unique, function ($a, $b) {
            return $b['confidence'] <=> $a['confidence'];
        });

        return array_values($unique);
    }

    /**
     * Normalize phone number
     */
    protected function normalizePhone(string $phone): string
    {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Convert to 07XXXXXXXX format
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
     * Check if two phone numbers match
     */
    protected function phonesMatch(?string $phone1, ?string $phone2): bool
    {
        if (empty($phone1) || empty($phone2)) {
            return false;
        }

        $normalized1 = $this->normalizePhone($phone1);
        $normalized2 = $this->normalizePhone($phone2);

        return $normalized1 === $normalized2;
    }
}

