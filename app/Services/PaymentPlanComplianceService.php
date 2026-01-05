<?php

namespace App\Services;

use App\Models\{
    Student, Term, PaymentThreshold, FeePaymentPlan, Invoice, StudentCategory
};
use App\Services\StudentBalanceService;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Payment Plan Compliance Service
 * Handles classification of students based on payment thresholds on term opening day
 */
class PaymentPlanComplianceService
{
    /**
     * Classify student payment compliance for a term
     * Returns: 'above_threshold', 'below_threshold', or 'no_threshold'
     */
    public function classifyStudentCompliance(Student $student, Term $term): string
    {
        // Get threshold for this term and student category
        $threshold = PaymentThreshold::where('term_id', $term->id)
            ->where('student_category_id', $student->category_id)
            ->where('is_active', true)
            ->first();

        // If no threshold configured, return 'no_threshold'
        if (!$threshold) {
            return 'no_threshold';
        }

        // Get total fees for the term (from invoices)
        $termInvoices = Invoice::where('student_id', $student->id)
            ->where('term_id', $term->id)
            ->where('status', '!=', 'reversed')
            ->get();

        $totalFees = $termInvoices->sum('total');
        
        if ($totalFees <= 0) {
            return 'no_threshold'; // No fees to check
        }

        // Get total paid amount for term invoices
        $totalPaid = $termInvoices->sum('paid_amount');
        
        // Calculate percentage paid
        $percentagePaid = ($totalPaid / $totalFees) * 100;

        // Check against threshold
        if ($percentagePaid >= $threshold->minimum_percentage) {
            return 'above_threshold';
        }

        return 'below_threshold';
    }

    /**
     * Check if student should be allowed in school (above threshold)
     */
    public function isAllowedInSchool(Student $student, Term $term): bool
    {
        $classification = $this->classifyStudentCompliance($student, $term);
        return $classification === 'above_threshold' || $classification === 'no_threshold';
    }

    /**
     * Check if student must create a payment plan (below threshold)
     */
    public function mustCreatePaymentPlan(Student $student, Term $term): bool
    {
        return $this->classifyStudentCompliance($student, $term) === 'below_threshold';
    }

    /**
     * Classify all students for term opening day
     * Can be run as a command or job
     */
    public function classifyStudentsForTerm(Term $term): array
    {
        $results = [
            'above_threshold' => [],
            'below_threshold' => [],
            'no_threshold' => [],
        ];

        $students = Student::where('archive', 0)
            ->where('is_alumni', false)
            ->with('category')
            ->get();

        foreach ($students as $student) {
            $classification = $this->classifyStudentCompliance($student, $term);
            $results[$classification][] = [
                'student_id' => $student->id,
                'student_name' => $student->first_name . ' ' . $student->last_name,
                'category' => $student->category->name ?? 'N/A',
            ];
        }

        return $results;
    }

    /**
     * Get payment percentage for student in term
     */
    public function getPaymentPercentage(Student $student, Term $term): float
    {
        $termInvoices = Invoice::where('student_id', $student->id)
            ->where('term_id', $term->id)
            ->where('status', '!=', 'reversed')
            ->get();

        $totalFees = $termInvoices->sum('total');
        if ($totalFees <= 0) {
            return 0;
        }

        $totalPaid = $termInvoices->sum('paid_amount');
        return ($totalPaid / $totalFees) * 100;
    }

    /**
     * Calculate final clearance deadline for a term based on threshold
     */
    public function getFinalDeadlineDate(Term $term, ?StudentCategory $category = null): ?Carbon
    {
        if (!$category) {
            return null;
        }

        $threshold = PaymentThreshold::where('term_id', $term->id)
            ->where('student_category_id', $category->id)
            ->where('is_active', true)
            ->first();

        if (!$threshold || !$term->opening_date) {
            return null;
        }

        return $threshold->calculateFinalDeadlineDate($term->opening_date);
    }
}
