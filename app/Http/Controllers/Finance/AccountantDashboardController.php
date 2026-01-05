<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;
use App\Models\FeePaymentPlan;
use App\Models\FeePaymentPlanInstallment;
use App\Models\Student;
use App\Models\Setting;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AccountantDashboardController extends Controller
{
    /**
     * Accountant dashboard with payment plan reporting
     */
    public function index(Request $request)
    {
        // Get configurable thresholds from settings
        $daysAheadDefault = Setting::getInt('accountant_dashboard_days_ahead', 14);
        $highRiskDaysUntilTermEnd = Setting::getInt('accountant_dashboard_high_risk_days', 30);
        $highRiskPercentageThreshold = Setting::getInt('accountant_dashboard_high_risk_percentage', 70);
        $highRiskMinimumBalance = Setting::getInt('accountant_dashboard_high_risk_min_balance', 10000);

        $filters = [
            'status' => $request->get('status'),
            'days_ahead' => $request->get('days_ahead', $daysAheadDefault),
        ];

        // Overdue payment plans
        $overduePlans = FeePaymentPlan::whereIn('status', ['overdue', 'broken'])
            ->with(['student.classroom', 'term', 'academicYear', 'installments'])
            ->orderBy('final_clearance_deadline', 'asc')
            ->get()
            ->map(function ($plan) {
                $totalPaid = $plan->installments->sum('paid_amount');
                $plan->total_paid = $totalPaid;
                $plan->remaining_balance = $plan->total_amount - $totalPaid;
                $plan->overdue_installments_count = $plan->installments()->where('status', 'overdue')->count();
                return $plan;
            });

        // Upcoming installments (within X days)
        $daysAhead = (int) $filters['days_ahead'];
        $upcomingDate = now()->addDays($daysAhead)->format('Y-m-d');
        
        $upcomingInstallments = FeePaymentPlanInstallment::where('due_date', '<=', $upcomingDate)
            ->where('due_date', '>=', now()->format('Y-m-d'))
            ->whereIn('status', ['pending', 'partial'])
            ->with(['paymentPlan.student.classroom', 'paymentPlan.term', 'paymentPlan.academicYear'])
            ->orderBy('due_date', 'asc')
            ->get()
            ->map(function ($installment) {
                $installment->outstanding = $installment->amount - $installment->paid_amount;
                $installment->days_until_due = Carbon::parse($installment->due_date)->diffInDays(now());
                return $installment;
            });

        // High-risk balances (near term closing)
        $currentTerm = \App\Models\Term::where('is_current', true)->first();
        $highRiskPlans = collect();
        
        if ($currentTerm && $currentTerm->end_date) {
            $daysUntilTermEnd = Carbon::parse($currentTerm->end_date)->diffInDays(now());
            
            // Plans with significant balance and less than configured days to term end
            if ($daysUntilTermEnd <= $highRiskDaysUntilTermEnd) {
                $highRiskPlans = FeePaymentPlan::where('term_id', $currentTerm->id)
                    ->whereIn('status', ['active', 'compliant', 'overdue'])
                    ->with(['student.classroom', 'installments'])
                    ->get()
                    ->map(function ($plan) use ($currentTerm) {
                        $totalPaid = $plan->installments->sum('paid_amount');
                        $remainingBalance = $plan->total_amount - $totalPaid;
                        $percentagePaid = $plan->total_amount > 0 
                            ? ($totalPaid / $plan->total_amount) * 100 
                            : 0;
                        
                        $plan->total_paid = $totalPaid;
                        $plan->remaining_balance = $remainingBalance;
                        $plan->percentage_paid = $percentagePaid;
                        $plan->days_until_term_end = Carbon::parse($currentTerm->end_date)->diffInDays(now());
                        return $plan;
                    })
                    ->filter(function ($plan) use ($highRiskPercentageThreshold, $highRiskMinimumBalance) {
                        // High risk: less than configured percentage paid and more than configured minimum balance
                        return $plan->percentage_paid < $highRiskPercentageThreshold && $plan->remaining_balance > $highRiskMinimumBalance;
                    })
                    ->sortByDesc('remaining_balance');
            }
        }

        // Statistics
        $stats = [
            'total_overdue_plans' => FeePaymentPlan::whereIn('status', ['overdue', 'broken'])->count(),
            'total_overdue_amount' => FeePaymentPlan::whereIn('status', ['overdue', 'broken'])
                ->with('installments')
                ->get()
                ->sum(function ($plan) {
                    return $plan->total_amount - $plan->installments->sum('paid_amount');
                }),
            'upcoming_installments_count' => $upcomingInstallments->count(),
            'upcoming_installments_amount' => $upcomingInstallments->sum('outstanding'),
            'high_risk_plans_count' => $highRiskPlans->count(),
            'high_risk_amount' => $highRiskPlans->sum('remaining_balance'),
        ];

        return view('finance.accountant_dashboard.index', compact(
            'overduePlans',
            'upcomingInstallments',
            'highRiskPlans',
            'stats',
            'filters',
            'daysAhead'
        ))->with([
            'thresholds' => [
                'days_ahead_default' => $daysAheadDefault,
                'high_risk_days' => $highRiskDaysUntilTermEnd,
                'high_risk_percentage' => $highRiskPercentageThreshold,
                'high_risk_min_balance' => $highRiskMinimumBalance,
            ]
        ]);
    }

    /**
     * Show settings page for dashboard thresholds
     */
    public function settings()
    {
        $settings = [
            'days_ahead_default' => Setting::getInt('accountant_dashboard_days_ahead', 14),
            'high_risk_days' => Setting::getInt('accountant_dashboard_high_risk_days', 30),
            'high_risk_percentage' => Setting::getInt('accountant_dashboard_high_risk_percentage', 70),
            'high_risk_min_balance' => Setting::getInt('accountant_dashboard_high_risk_min_balance', 10000),
        ];
        
        return view('finance.accountant_dashboard.settings', compact('settings'));
    }

    /**
     * Update dashboard threshold settings
     */
    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'days_ahead_default' => 'required|integer|min:1|max:365',
            'high_risk_days' => 'required|integer|min:1|max:365',
            'high_risk_percentage' => 'required|integer|min:0|max:100',
            'high_risk_min_balance' => 'required|integer|min:0',
        ]);

        Setting::setInt('accountant_dashboard_days_ahead', $validated['days_ahead_default']);
        Setting::setInt('accountant_dashboard_high_risk_days', $validated['high_risk_days']);
        Setting::setInt('accountant_dashboard_high_risk_percentage', $validated['high_risk_percentage']);
        Setting::setInt('accountant_dashboard_high_risk_min_balance', $validated['high_risk_min_balance']);

        return redirect()
            ->route('finance.accountant-dashboard.settings')
            ->with('success', 'Dashboard thresholds updated successfully.');
    }

    /**
     * Student-level payment plan history
     */
    public function studentHistory(Student $student)
    {
        $plans = FeePaymentPlan::where('student_id', $student->id)
            ->with(['term', 'academicYear', 'installments'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($plan) {
                $totalPaid = $plan->installments->sum('paid_amount');
                $plan->total_paid = $totalPaid;
                $plan->remaining_balance = $plan->total_amount - $totalPaid;
                $plan->percentage_paid = $plan->total_amount > 0 
                    ? ($totalPaid / $plan->total_amount) * 100 
                    : 0;
                $plan->is_compliant = $plan->status === 'compliant' || $plan->status === 'completed';
                return $plan;
            });

        // Calculate compliance score (percentage of completed plans)
        $totalPlans = $plans->count();
        $completedPlans = $plans->where('status', 'completed')->count();
        $complianceScore = $totalPlans > 0 ? ($completedPlans / $totalPlans) * 100 : 100;

        return view('finance.accountant_dashboard.student_history', compact(
            'student',
            'plans',
            'complianceScore'
        ));
    }
}

