<?php

namespace App\Services;

use App\Models\Student;
use App\Models\Invoice;
use App\Models\MpesaC2BTransaction;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MpesaSmartMatchingService
{
    /**
     * Attempt to match a C2B transaction to a student
     */
    public function matchTransaction(MpesaC2BTransaction $transaction): array
    {
        $suggestions = [];
        
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

        // Method 4: Match by name similarity
        $nameMatches = $this->matchByName($transaction);
        if (!empty($nameMatches)) {
            $suggestions = array_merge($suggestions, $nameMatches);
        }

        // Remove duplicates and sort by confidence
        $suggestions = $this->deduplicateAndSort($suggestions);

        // Store suggestions in transaction
        $transaction->storeSuggestions(array_slice($suggestions, 0, 5)); // Store top 5

        // Auto-match if confidence is high enough
        if (!empty($suggestions) && $suggestions[0]['confidence'] >= 80) {
            $student = Student::find($suggestions[0]['student_id']);
            if ($student) {
                $transaction->autoMatch($student, $suggestions[0]['confidence'], $suggestions[0]['reason']);
                
                Log::info('Auto-matched C2B transaction', [
                    'transaction_id' => $transaction->id,
                    'trans_id' => $transaction->trans_id,
                    'student_id' => $student->id,
                    'confidence' => $suggestions[0]['confidence'],
                    'reason' => $suggestions[0]['reason'],
                ]);
            }
        }

        return $suggestions;
    }

    /**
     * Match by admission number in bill reference
     */
    protected function matchByAdmissionNumber(MpesaC2BTransaction $transaction): ?array
    {
        if (empty($transaction->bill_ref_number)) {
            return null;
        }

        $ref = strtoupper(trim($transaction->bill_ref_number));
        
        // Try exact match first
        $student = Student::whereRaw('UPPER(admission_number) = ?', [$ref])->first();
        
        if ($student) {
            return [
                'student_id' => $student->id,
                'student_name' => $student->first_name . ' ' . $student->last_name,
                'admission_number' => $student->admission_number,
                'confidence' => 100,
                'reason' => 'Exact admission number match in reference',
                'match_type' => 'admission_exact',
            ];
        }

        // Try to extract admission number from reference
        // Pattern: ADM123, SCH123, 123, etc.
        preg_match('/([A-Z]*\d+)/', $ref, $matches);
        if (!empty($matches[1])) {
            $extracted = $matches[1];
            $student = Student::whereRaw('UPPER(admission_number) = ?', [$extracted])
                ->orWhereRaw('UPPER(admission_number) LIKE ?', ['%' . $extracted . '%'])
                ->first();
            
            if ($student) {
                return [
                    'student_id' => $student->id,
                    'student_name' => $student->first_name . ' ' . $student->last_name,
                    'admission_number' => $student->admission_number,
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
            return [
                'student_id' => $invoice->student->id,
                'student_name' => $invoice->student->first_name . ' ' . $invoice->student->last_name,
                'admission_number' => $invoice->student->admission_number,
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
        })->with('family')->get();

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
                    'confidence' => 75,
                    'reason' => 'Phone number match (' . $matchedField . ')',
                    'match_type' => 'phone',
                ];
            }
        }

        return $matches;
    }

    /**
     * Match by name similarity
     */
    protected function matchByName(MpesaC2BTransaction $transaction): array
    {
        $matches = [];
        
        $fullName = trim($transaction->full_name);
        if (empty($fullName) || $fullName === 'Unknown') {
            return $matches;
        }

        // Split name into parts
        $nameParts = explode(' ', strtoupper($fullName));
        
        // Search students by name parts
        $students = Student::where(function ($query) use ($nameParts) {
            foreach ($nameParts as $part) {
                if (strlen($part) >= 3) { // Only search meaningful parts
                    $query->orWhereRaw('UPPER(first_name) LIKE ?', ['%' . $part . '%'])
                          ->orWhereRaw('UPPER(last_name) LIKE ?', ['%' . $part . '%'])
                          ->orWhereRaw('UPPER(middle_name) LIKE ?', ['%' . $part . '%']);
                }
            }
        })->get();

        foreach ($students as $student) {
            $studentFullName = strtoupper($student->first_name . ' ' . $student->last_name);
            $similarity = 0;
            
            // Calculate similarity percentage
            similar_text(strtoupper($fullName), $studentFullName, $similarity);
            
            if ($similarity >= 60) {
                $confidence = min(70, round($similarity)); // Cap at 70 for name matches
                
                $matches[] = [
                    'student_id' => $student->id,
                    'student_name' => $student->first_name . ' ' . $student->last_name,
                    'admission_number' => $student->admission_number,
                    'confidence' => $confidence,
                    'reason' => 'Name similarity (' . round($similarity) . '%)',
                    'match_type' => 'name_similarity',
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

