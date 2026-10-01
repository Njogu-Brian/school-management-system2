<?php

namespace App\Http\Controllers\Operator;

use App\Http\Controllers\Controller;
use App\Models\SchoolRegistry;
use App\Models\SchoolSubscription;
use Illuminate\View\View;

class OperatorDashboardController extends Controller
{
    public function index(): View
    {
        $schools = SchoolRegistry::query()->orderBy('name')->get();
        $active = $schools->where('status', SchoolRegistry::STATUS_ACTIVE)->count();
        $suspended = $schools->where('status', SchoolRegistry::STATUS_SUSPENDED)->count();
        $overdue = $schools->where('billing_status', SchoolRegistry::BILLING_OVERDUE)->count();
        $students = (int) $schools->sum('student_count_cached');
        $period = now()->format('Y-m');
        $subs = SchoolSubscription::query()->where('period', $period)->get();
        $expected = (float) $subs->sum('amount_due');
        $collected = (float) $subs->sum('amount_paid');

        return view('operator.dashboard', compact(
            'schools', 'active', 'suspended', 'overdue', 'students', 'expected', 'collected', 'period'
        ));
    }
}
