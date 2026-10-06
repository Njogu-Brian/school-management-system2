<?php

namespace App\Services;

use App\Models\Student;
use App\Models\Invoice;
use App\Models\MpesaC2BTransaction;
use App\Models\ParentInfo;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MpesaSmartMatchingService
{
    /**
     * Attempt to match a C2B transaction to a student
     */
    public function matchTransaction(MpesaC2BTransaction $transaction): array
    {
        // Start with learned suggestions from past manual assignments (system improves over time).
        // Prefer bill_ref_number (what parents type) over trans_id (unique per payment, never reuses).
        $suggestions = \App\Models\ManualMatchLearning::findSuggestions(
            'c2b',
            $transaction->bill_ref_number ?? null,
            $transaction->bill_ref_number
                ?? trim(implode(' ', array_filter([
                    $transaction->first_name ?? null,
                    $transaction->middle_name ?? null,
                    $transaction->last_name ?? null,
                ])))
                ?: null
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

        // Store initial match candidates on the transaction for audit / UI
        $transaction->storeSuggestions(array_slice($suggestions, 0, 5)); // Store top 5

        // Auto-match only when the top suggestion is strong enough AND not ambiguous
        // (e.g. do not auto-assign "Israel Wainaina" for reference "shalin wainaina").
        if (!empty($suggestions) && $this->shouldAutoAssign($suggestions)) {
            $top = $suggestions[0];
            $siblingIds = $top['siblings'] ?? [];
            $amount = (float) $transaction->trans_amount;
            $familySplit = null;

            if (count($siblingIds) < 2 && !empty($top['student_id'])) {
                $familySplit = \App\Services\Finance\FamilyPaymentSplitter::allocationsForStudent((int) $top['student_id'], $amount);
                if ($familySplit) {
                    $siblingIds = array_column($familySplit, 'student_id');
                    $top['match_type'] = 'family_balance_split';
                    $top['reason'] = ($top['reason'] ?? 'Matched student') . ' — amount covers sibling balances, split across family';
                }
            }

            $isSiblingMatch = count($siblingIds) >= 2 && in_array($top['match_type'] ?? '', ['parent_sibling', 'reference_sibling', 'family_balance_split'], true);
            if ($isSiblingMatch) {
                $smartAllocations = $familySplit ?: $this->computeSmartSiblingAllocations($amount, $siblingIds);
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
     * Decide whether the top suggestion is safe to auto-assign.
     *
     * Learned from production corrections (top suggestion ≠ final student):
     * - "shalin wainaina" → Israel (surname-only fuzzy)
     * - "Keisha"/"Israel"/"Gabriella" → wrong child among same first name
     * - "Everlyn Wanjiku G3" → Elsa Wanjiku (class hint ignored)
     * - "Grace Kemunto" → Peace Kemunto (second token surname collision)
     */
    protected function shouldAutoAssign(array $suggestions): bool
    {
        if (empty($suggestions)) {
            return false;
        }

        $top = $suggestions[0];
        $confidence = (float) ($top['confidence'] ?? 0);
        $matchType = (string) ($top['match_type'] ?? '');
        $reason = (string) ($top['reason'] ?? '');
        $second = $suggestions[1] ?? null;
        $secondConfidence = (float) ($second['confidence'] ?? 0);
        $gap = $confidence - $secondConfidence;

        if (!empty($top['ambiguous'])) {
            return false;
        }

        // Two near-tied candidates → staff must choose (Keisha×2, Israel×3, Gabriella×2)
        if ($second && $gap < 5 && in_array($matchType, ['reference_student_name', 'learned'], true)) {
            return false;
        }

        // Exact / strong match types
        $strongTypes = [
            'admission_number',
            'invoice_number',
            'learned',
            'phone',
            'parent_sibling',
            'reference_sibling',
            'family_balance_split',
        ];

        // Fuzzy "Matched: RKSxxx N% confidence" from similarity — never auto below 92,
        // and never when another suggestion is within 10 points (ambiguous surname family).
        $isFuzzyName = $matchType === 'reference_student_name'
            && (str_starts_with($reason, 'Matched:') || str_contains($reason, 'fuzzy'));

        if ($isFuzzyName) {
            if ($confidence < 92) {
                return false;
            }
            if ($second && $gap < 10) {
                return false;
            }
            if (array_key_exists('given_name_matched', $top) && !$top['given_name_matched']) {
                return false;
            }
            return true;
        }

        // Strong name matches (exact first+last / first+middle) auto at 90+
        // Single-token first-name matches only auto when unique, or uniquely resolved by class (G3/FND).
        if ($matchType === 'reference_student_name') {
            $uniqueClass = str_contains($reason, 'unique class');
            if (!empty($top['single_token_name']) && $second && !$uniqueClass) {
                return false;
            }
            return $confidence >= 90;
        }

        if (in_array($matchType, $strongTypes, true)) {
            // Learned corrections auto only when clearly ahead of alternatives
            if ($matchType === 'learned') {
                return $confidence >= 90 && (!$second || $gap >= 5);
            }
            return $confidence >= 80;
        }

        return $confidence >= 90;
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
     *
     * Important: "Phillip Njenga" / "First Last" is ONE person — never treat space-separated
     * first+last as sibling tokens. A shared last name alone must not create a sibling match.
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

        // Each token must identify a distinct child primarily by first/given name.
        // Matching only on shared last name (e.g. "Njenga") falsely groups whole families.
        $familyHits = []; // family_id => [student_id => ['student' => Student, 'tokens' => []]]
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
                      ->orWhereRaw('SOUNDEX(first_name) = SOUNDEX(?)', [$childNameLower])
                      ->orWhereRaw('LOWER(TRIM(middle_name)) = ?', [$childNameLower])
                      ->orWhereRaw(
                          'LOWER(REPLACE(CONCAT(TRIM(first_name), TRIM(middle_name)), \' \', \'\')) = ?',
                          [str_replace(' ', '', $childNameLower)]
                      );
                })
                ->get();

            foreach ($students as $s) {
                $familyId = $s->family_id;
                if (!$familyId) {
                    continue;
                }
                if (!isset($familyHits[$familyId][$s->id])) {
                    $familyHits[$familyId][$s->id] = [
                        'student' => $s,
                        'tokens' => [],
                    ];
                }
                $familyHits[$familyId][$s->id]['tokens'][$childNameLower] = true;
            }
        }

        $matches = [];
        foreach ($familyHits as $familyId => $byId) {
            // Require at least two siblings, each matched by a different given-name token.
            $matchedTokens = [];
            foreach ($byId as $hit) {
                foreach (array_keys($hit['tokens']) as $token) {
                    $matchedTokens[$token] = true;
                }
            }
            if (count($byId) < 2 || count($matchedTokens) < 2) {
                continue;
            }

            $siblingIds = array_map('intval', array_keys($byId));
            $children = [];
            foreach ($byId as $hit) {
                $s = $hit['student'];
                $children[] = [
                    'student_id' => $s->id,
                    'student_name' => $s->first_name . ' ' . $s->last_name,
                    'admission_number' => $s->admission_number,
                    'classroom_name' => $s->classroom ? $s->classroom->name : null,
                ];
            }
            $confidence = count($matchedTokens) >= count($childNames) ? 85 : 75;
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

        return $matches;
    }

    /**
     * Compute smart sibling allocations based on fee balances.
     * Rules: share equally; if one has balance < half, clear that one first, remainder to others;
     * overpayment only when all siblings have cleared their fee balance.
     */
    public function computeSmartSiblingAllocations(float $totalAmount, array $siblingIds): array
    {
        return \App\Services\Finance\FamilyPaymentSplitter::allocationsForSiblings($siblingIds, $totalAmount);
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
        
        // Explicit multi-child delimiters only. Never split on spaces — "Philip Njenga"
        // is one student's name, not two siblings (Phillip + Njenga).
        $delimiters = ['/', ',', '|', '&', ' and ', ' AND '];

        foreach ($delimiters as $delimiter) {
            if (stripos($reference, $delimiter) !== false) {
                $names = array_map('trim', explode($delimiter, $reference));
                $names = array_filter($names, function ($name) {
                    return strlen($name) >= 2;
                });
                if (count($names) > 1) {
                    return array_values($names);
                }
            }
        }

        // Single child name (may include spaces: "Philip Njenga").
        return [trim($reference)];
    }

    /**
     * Parse reference into name part and optional class/grade hint.
     * Supports: "grade 7", "G3", "G 8", "FND", "FOUNDATION", "PP1", "class 5".
     */
    protected function parseReferenceNameAndClass(string $ref): array
    {
        $ref = trim($ref);
        $classHint = null;

        // "grade 7", "class 5", "form 1", "std 3"
        if (preg_match('/\b(grade|class|form|std)\s*(\d+)\b/i', $ref, $m)) {
            $classHint = 'GRADE ' . $m[2];
            $ref = trim(preg_replace('/\b(grade|class|form|std)\s*\d+\b/i', '', $ref));
        }

        // Compact "G3" / "G 8" (common parent shorthand — Everlyn Wanjiku G3)
        if (!$classHint && preg_match('/\bG\s*(\d+)\b/i', $ref, $m)) {
            $classHint = 'GRADE ' . $m[1];
            $ref = trim(preg_replace('/\bG\s*\d+\b/i', '', $ref));
        }

        // Foundation / PP
        if (!$classHint && preg_match('/\b(FND|FOUNDATION)\b/i', $ref, $m)) {
            $classHint = 'FOUNDATION';
            $ref = trim(preg_replace('/\b(FND|FOUNDATION)\b/i', '', $ref));
        }
        if (!$classHint && preg_match('/\bPP\s*([12])\b/i', $ref, $m)) {
            $classHint = 'PP' . $m[1];
            $ref = trim(preg_replace('/\bPP\s*[12]\b/i', '', $ref));
        }

        $ref = trim(preg_replace('/\s+/', ' ', $ref));
        return ['name' => $ref, 'class_hint' => $classHint];
    }

    /**
     * Whether a classroom name matches a parsed class hint.
     */
    protected function classroomMatchesHint(?string $classroomName, ?string $classHint): bool
    {
        if (!$classroomName || !$classHint) {
            return false;
        }
        $room = strtoupper(trim($classroomName));
        $hint = strtoupper(trim($classHint));

        if (str_contains($room, $hint)) {
            return true;
        }
        // GRADE 3 ↔ "GRADE 3" / "G3" already normalized to GRADE N
        if (preg_match('/GRADE\s*(\d+)/', $hint, $hm) && preg_match('/(?:GRADE|G|CLASS|STD)\s*' . $hm[1] . '\b/', $room)) {
            return true;
        }
        if ($hint === 'FOUNDATION' && (str_contains($room, 'FOUNDATION') || str_contains($room, 'FND'))) {
            return true;
        }
        if (preg_match('/^PP([12])$/', $hint, $hm) && preg_match('/PP\s*' . $hm[1] . '\b/', $room)) {
            return true;
        }

        return false;
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

        // 1) Exact match on single first_name or last_name (e.g. "job", "Keisha", "Israel")
        // When several students share the name, do NOT auto-pick — production corrections show
        // Keisha/Israel/Gabriella repeatedly assigned to the wrong child at 95%.
        $isSingleToken = !str_contains(trim($namePart), ' ');
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

        // Near-spelling first names (Keisha/Keysha, Talia/Tallia) as extra suggestions only
        if ($isSingleToken && strlen($refLower) >= 4) {
            $near = Student::with('classroom')
                ->where('archive', 0)
                ->where('is_alumni', false)
                ->whereRaw('SOUNDEX(first_name) = SOUNDEX(?)', [$refLower])
                ->whereRaw('UPPER(TRIM(first_name)) != ?', [$refUpper])
                ->limit(10)
                ->get()
                ->filter(function ($s) use ($refLower) {
                    $fn = strtolower(trim((string) $s->first_name));
                    similar_text($refLower, $fn, $pct);
                    return $pct >= 80 || levenshtein($refLower, $fn) <= 1;
                });
            $exactMatches = $exactMatches->merge($near)->unique('id');
        }

        $matches = [];
        foreach ($exactMatches as $student) {
            $isExactFirst = strtoupper(trim((string) $student->first_name)) === $refUpper
                || strtolower(trim((string) $student->first_name)) === $refLower;
            $confidence = $isExactFirst ? 95 : 88;
            $reason = 'Reference matches student name: ' . $ref;
            if ($this->classroomMatchesHint($student->classroom->name ?? null, $classHint)) {
                $confidence = min(98, $confidence + 8);
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
                'single_token_name' => $isSingleToken,
            ];
        }

        if (!empty($matches)) {
            // Unique class-hint winner can stay auto-safe; otherwise mark ambiguous.
            if (count($matches) > 1) {
                $classWinners = array_values(array_filter($matches, function ($m) use ($classHint) {
                    return $this->classroomMatchesHint($m['classroom_name'] ?? null, $classHint);
                }));
                if ($classHint && count($classWinners) === 1) {
                    $winner = $classWinners[0];
                    $winner['confidence'] = 96;
                    $winner['ambiguous'] = false;
                    $winner['single_token_name'] = $isSingleToken;
                    $winner['reason'] = 'Reference matches student name + unique class: ' . $ref;
                    // Keep others as lower-confidence suggestions
                    $others = array_map(function ($m) use ($winner) {
                        if ($m['student_id'] === $winner['student_id']) {
                            return $winner;
                        }
                        $m['confidence'] = min(85, (int) $m['confidence']);
                        $m['ambiguous'] = true;
                        return $m;
                    }, $matches);
                    usort($others, fn ($a, $b) => $b['confidence'] <=> $a['confidence']);
                    return $others;
                }

                foreach ($matches as &$m) {
                    $m['confidence'] = min(85, (int) $m['confidence']);
                    $m['ambiguous'] = true;
                }
                unset($m);
                usort($matches, fn ($a, $b) => $b['confidence'] <=> $a['confidence']);
            }

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

        // 2b) Full name match: "peter mwangi" -> first+last, first+middle, or soundex first + middle/last
        // Covers "Phillip Njenga" -> Philip Njenga Karanja (middle name Njenga).
        $nameWords = array_values(array_filter(explode(' ', $namePart), function ($p) {
            return strlen(trim($p)) >= 2;
        }));
        if (count($nameWords) >= 2) {
            $first = $nameWords[0];
            $second = $nameWords[1];
            $last = $nameWords[count($nameWords) - 1];
            $query = Student::with('classroom')
                ->where('archive', 0)
                ->where('is_alumni', false)
                ->where(function ($q) use ($first, $second, $last) {
                    $q->where(function ($q2) use ($first, $last) {
                        $q2->whereRaw('UPPER(TRIM(first_name)) = ?', [strtoupper($first)])
                           ->whereRaw('UPPER(TRIM(last_name)) = ?', [strtoupper($last)]);
                    })->orWhere(function ($q2) use ($first, $last) {
                        $q2->whereRaw('UPPER(TRIM(first_name)) = ?', [strtoupper($last)])
                           ->whereRaw('UPPER(TRIM(last_name)) = ?', [strtoupper($first)]);
                    })->orWhere(function ($q2) use ($first, $second) {
                        // first + middle (exact or soundex on first for Philip/Phillip)
                        $q2->where(function ($q3) use ($first) {
                            $q3->whereRaw('UPPER(TRIM(first_name)) = ?', [strtoupper($first)])
                               ->orWhereRaw('SOUNDEX(first_name) = SOUNDEX(?)', [$first]);
                        })->where(function ($q3) use ($second) {
                            $q3->whereRaw('UPPER(TRIM(middle_name)) = ?', [strtoupper($second)])
                               ->orWhereRaw('UPPER(TRIM(middle_name)) LIKE ?', ['%' . strtoupper($second) . '%'])
                               ->orWhereRaw('UPPER(TRIM(last_name)) = ?', [strtoupper($second)]);
                        });
                    });
                });
            if ($classHint) {
                $query->whereHas('classroom', function ($q) use ($classHint) {
                    $q->whereRaw('UPPER(name) LIKE ?', ['%' . $classHint . '%']);
                });
            }
            $fullNameMatches = $query->get();
            foreach ($fullNameMatches as $student) {
                $classOk = $this->classroomMatchesHint($student->classroom->name ?? null, $classHint);
                $confidence = $classOk ? 98 : 95;
                $matches[] = [
                    'student_id' => $student->id,
                    'student_name' => $student->first_name . ' ' . $student->last_name,
                    'admission_number' => $student->admission_number,
                    'classroom_name' => $student->classroom ? $student->classroom->name : null,
                    'confidence' => $confidence,
                    'reason' => 'Reference matches student name' . ($classOk ? ' + class' : '') . ': ' . $ref,
                    'match_type' => 'reference_student_name',
                    'given_name_matched' => true,
                ];
            }
            if (!empty($matches)) {
                // If class hint uniquely identifies one of several full-name hits, prefer it.
                if ($classHint && count($matches) > 1) {
                    $classWinners = array_values(array_filter($matches, fn ($m) => $this->classroomMatchesHint($m['classroom_name'] ?? null, $classHint)));
                    if (count($classWinners) === 1) {
                        usort($matches, function ($a, $b) use ($classWinners) {
                            $aw = $a['student_id'] === $classWinners[0]['student_id'] ? 1 : 0;
                            $bw = $b['student_id'] === $classWinners[0]['student_id'] ? 1 : 0;
                            return $bw <=> $aw ?: ($b['confidence'] <=> $a['confidence']);
                        });
                    }
                }
                return $matches;
            }
        }

        // 3) Safer fuzzy match: require the given-name token to match first/middle.
        // Plain similar_text("SHALIN WAINAINA", "ISRAEL WAINAINA") = 80% and used to auto-assign wrongly.
        $nameParts = array_values(array_filter($nameWords ?? array_values(array_filter(explode(' ', $namePart), function ($p) {
            return strlen(trim($p)) >= 2 && !preg_match('/^\d+$/', trim($p));
        })), function ($p) {
            $p = strtolower(trim($p));
            return $p !== 'grade' && $p !== 'class' && $p !== 'form' && $p !== 'std';
        }));
        if (empty($nameParts)) {
            return [];
        }

        $givenToken = $nameParts[0];
        $otherTokens = array_slice($nameParts, 1);

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

        foreach ($students as $student) {
            $givenMatched = $this->tokenMatchesGivenName($givenToken, $student);
            $surnameMatched = empty($otherTokens)
                ? true
                : $this->tokensMatchSurnameParts($otherTokens, $student);

            // Multi-word refs (e.g. "shalin wainaina") MUST match the given name.
            // Surname-only collisions are suggestions at best, never strong auto-matches.
            if (count($nameParts) >= 2 && !$givenMatched) {
                continue;
            }

            $bestSimilarity = $this->bestNameSimilarity($refUpper, $student);
            if ($bestSimilarity < 70) {
                continue;
            }

            // Cap confidence: given+surname strong → up to 88 (suggest/manual); exact paths already returned above at 95.
            // Without given-name match (single-token refs only), keep low so shouldAutoAssign refuses.
            if ($givenMatched && $surnameMatched) {
                $confidence = min(88, max(78, (int) round($bestSimilarity)));
            } elseif ($givenMatched) {
                $confidence = min(75, max(60, (int) round($bestSimilarity)));
            } else {
                $confidence = min(70, max(50, (int) round($bestSimilarity)));
            }

            if ($classHint && $student->classroom && stripos($student->classroom->name, $classHint) !== false) {
                $confidence = min(90, $confidence + 3);
            }

            $matches[] = [
                'student_id' => $student->id,
                'student_name' => trim($student->first_name . ' ' . ($student->middle_name ? $student->middle_name . ' ' : '') . $student->last_name),
                'admission_number' => $student->admission_number,
                'classroom_name' => $student->classroom ? $student->classroom->name : null,
                'confidence' => $confidence,
                'reason' => 'Matched: ' . $student->admission_number . ' ' . $confidence . '% confidence (fuzzy)',
                'match_type' => 'reference_student_name',
                'given_name_matched' => $givenMatched,
            ];
        }

        return $matches;
    }

    /**
     * Given-name token must align with first or middle name (exact, soundex, or high similarity).
     */
    protected function tokenMatchesGivenName(string $token, Student $student): bool
    {
        $token = strtolower(trim($token));
        if ($token === '') {
            return false;
        }

        foreach ([$student->first_name, $student->middle_name] as $part) {
            $part = strtolower(trim((string) $part));
            if ($part === '') {
                continue;
            }
            // Middle may be multi-word ("Wainaina Ngige") — check each piece
            foreach (preg_split('/\s+/', $part) as $piece) {
                if ($piece === '') {
                    continue;
                }
                if ($piece === $token) {
                    return true;
                }
                if (strlen($token) >= 3 && strlen($piece) >= 3 && soundex($token) === soundex($piece)) {
                    // soundex alone is weak (Philip/Phillip ok; reject distant collisions via similarity)
                    similar_text($token, $piece, $pct);
                    if ($pct >= 80 || levenshtein($token, $piece) <= 1) {
                        return true;
                    }
                }
                similar_text($token, $piece, $pct);
                if ($pct >= 90 || (strlen($token) >= 4 && levenshtein($token, $piece) <= 1)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * At least one non-given token must match middle or last name.
     */
    protected function tokensMatchSurnameParts(array $tokens, Student $student): bool
    {
        $haystack = strtolower(trim(
            preg_replace('/\s+/', ' ', trim(($student->middle_name ?? '') . ' ' . ($student->last_name ?? '')))
        ));
        if ($haystack === '') {
            return false;
        }

        foreach ($tokens as $token) {
            $token = strtolower(trim((string) $token));
            if (strlen($token) < 2) {
                continue;
            }
            if (str_contains($haystack, $token)) {
                return true;
            }
            foreach (preg_split('/\s+/', $haystack) as $piece) {
                if ($piece === '') {
                    continue;
                }
                similar_text($token, $piece, $pct);
                if ($pct >= 90 || (strlen($token) >= 4 && levenshtein($token, $piece) <= 1)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Best similar_text across common student name shapes (includes middle name).
     */
    protected function bestNameSimilarity(string $refUpper, Student $student): float
    {
        $first = strtoupper(trim((string) $student->first_name));
        $middle = strtoupper(trim((string) $student->middle_name));
        $last = strtoupper(trim((string) $student->last_name));

        $candidates = array_filter([
            trim($first . ' ' . $last),
            $middle !== '' ? trim($first . ' ' . $middle) : null,
            $middle !== '' ? trim($first . ' ' . $middle . ' ' . $last) : null,
            $middle !== '' ? trim($first . ' ' . preg_replace('/\s+.*/', '', $middle) . ' ' . $last) : null,
        ]);

        $best = 0.0;
        foreach ($candidates as $candidate) {
            similar_text($refUpper, $candidate, $pct);
            $best = max($best, (float) $pct);
        }

        return $best;
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

