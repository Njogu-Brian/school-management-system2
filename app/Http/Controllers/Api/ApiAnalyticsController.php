<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\FixedAsset;
use App\Models\InventoryItem;
use App\Models\Invoice;
use App\Models\OnlineAdmission;
use App\Models\Payment;
use App\Models\Staff;
use App\Models\Student;
use App\Models\Term;
use App\Models\VisitorLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

/**
 * Executive analytics with time-series data for mobile charts.
 */
class ApiAnalyticsController extends Controller
{
    public function executive(Request $request)
    {
        $request->validate([
            'period' => 'nullable|string|in:week,month,term,year',
        ]);

        $period = $request->input('period', 'month');
        [$start, $end, $labels] = $this->resolvePeriod($period);

        $paymentBase = Payment::query()->where(function ($q) {
            $q->whereNull('reversed')->orWhere('reversed', false);
        });

        $dailyCollections = [];
        $weeklyCollections = [];
        foreach ($labels as $label) {
            $dailyCollections[] = 0;
            $weeklyCollections[] = 0;
        }

        $cursor = $start->copy();
        $idx = 0;
        while ($cursor->lte($end) && $idx < count($labels)) {
            $dailyCollections[$idx] = round((float) (clone $paymentBase)
                ->whereDate('payment_date', $cursor->toDateString())
                ->sum('amount'), 2);
            $weekEnd = $cursor->copy()->endOfWeek();
            if ($weekEnd->gt($end)) {
                $weekEnd = $end->copy();
            }
            $weeklyCollections[$idx] = round((float) (clone $paymentBase)
                ->whereBetween('payment_date', [$cursor->copy()->startOfWeek(), $weekEnd])
                ->sum('amount'), 2);
            $cursor->addDay();
            $idx++;
        }

        $monthlyCollected = round((float) (clone $paymentBase)
            ->whereBetween('payment_date', [$start, $end])
            ->sum('amount'), 2);

        // Align with web portal / admin dashboard (current-term due outstanding).
        // Raw sum of all invoice balances includes archived/alumni and other terms.
        $termKpis = app(\App\Services\FinanceTermKpiService::class)->forCurrentTerm();
        $outstanding = (float) ($termKpis['fees_outstanding'] ?? 0);

        $enrollmentTrend = $this->countByBucket(
            Student::query()->where('archive', 0)->where('is_alumni', false),
            'created_at',
            $start,
            $end,
            $labels,
            $period
        );

        $admissionTrend = $this->countByBucket(
            OnlineAdmission::query(),
            'application_date',
            $start,
            $end,
            $labels,
            $period
        );

        $attendanceTrend = $this->attendanceTrend($start, $end, $labels, $period);

        $staffGrowth = $this->countByBucket(Staff::query(), 'created_at', $start, $end, $labels, $period);

        $visitorsTrend = Schema::hasTable('visitor_logs')
            ? $this->countByBucket(VisitorLog::query(), 'checked_in_at', $start, $end, $labels, $period)
            : array_fill(0, count($labels), 0);

        $lowStock = InventoryItem::query()
            ->whereColumn('quantity', '<=', 'min_stock_level')
            ->count();

        $assetCount = Schema::hasTable('fixed_assets')
            ? FixedAsset::query()->count()
            : 0;

        $enrollmentByStatus = [
            ['name' => 'Enrolled', 'value' => Student::where('archive', 0)->where('is_alumni', false)->count(), 'color' => '#2563eb'],
            ['name' => 'Pending', 'value' => OnlineAdmission::where('application_status', 'pending')->count(), 'color' => '#f59e0b'],
            ['name' => 'Waitlisted', 'value' => OnlineAdmission::where('application_status', 'waitlisted')->count(), 'color' => '#8b5cf6'],
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'period' => $period,
                'as_of' => now()->toIso8601String(),
                'finance' => [
                    'daily_collections' => ['labels' => $labels, 'values' => $dailyCollections],
                    'weekly_collections' => ['labels' => $labels, 'values' => $weeklyCollections],
                    'monthly_collections' => $monthlyCollected,
                    'outstanding_balances' => round($outstanding, 2),
                ],
                'admissions' => [
                    'enrollment_trends' => ['labels' => $labels, 'values' => $enrollmentTrend],
                    'admission_trends' => ['labels' => $labels, 'values' => $admissionTrend],
                    'enrollment_pie' => $enrollmentByStatus,
                ],
                'academics' => [
                    'attendance_trends' => ['labels' => $labels, 'values' => $attendanceTrend],
                    'exam_trends' => ['labels' => $labels, 'values' => array_fill(0, count($labels), 0)],
                ],
                'hr' => [
                    'staff_growth' => ['labels' => $labels, 'values' => $staffGrowth],
                    'attendance_trends' => ['labels' => $labels, 'values' => $attendanceTrend],
                ],
                'operations' => [
                    'visitors' => ['labels' => $labels, 'values' => $visitorsTrend],
                    'assets' => $assetCount,
                    'inventory_alerts' => $lowStock,
                ],
            ],
        ]);
    }

    private function resolvePeriod(string $period): array
    {
        $end = Carbon::today()->endOfDay();

        if ($period === 'week') {
            $start = Carbon::today()->subDays(6)->startOfDay();
            $labels = [];
            for ($i = 0; $i < 7; $i++) {
                $labels[] = Carbon::today()->subDays(6 - $i)->format('D');
            }

            return [$start, $end, $labels];
        }

        if ($period === 'term') {
            $term = Term::where('is_current', true)->first()
                ?? Term::orderByDesc('opening_date')->first();
            if ($term?->opening_date && $term?->closing_date) {
                $start = $term->opening_date->copy()->startOfDay();
                $termEnd = $term->closing_date->copy()->endOfDay();
                $end = $termEnd->gt(Carbon::today()) ? Carbon::today()->endOfDay() : $termEnd;
            } else {
                $start = Carbon::today()->subMonths(3)->startOfDay();
            }
            $labels = $this->monthLabels($start, $end);

            return [$start, $end, $labels];
        }

        if ($period === 'year') {
            $year = AcademicYear::where('is_active', true)->first();
            $start = $year?->created_at?->copy()->startOfDay()
                ?? Carbon::today()->subMonths(11)->startOfDay();
            $labels = $this->monthLabels($start, $end);

            return [$start, $end, $labels];
        }

        // month (default)
        $start = Carbon::today()->subDays(29)->startOfDay();
        $labels = [];
        for ($i = 0; $i < 30; $i += 5) {
            $labels[] = Carbon::today()->subDays(29 - $i)->format('M j');
        }

        return [$start, $end, $labels];
    }

    private function monthLabels(Carbon $start, Carbon $end): array
    {
        $labels = [];
        $cursor = $start->copy()->startOfMonth();
        while ($cursor->lte($end)) {
            $labels[] = $cursor->format('M');
            $cursor->addMonth();
        }

        return $labels ?: [Carbon::today()->format('M')];
    }

    private function countByBucket($query, string $column, Carbon $start, Carbon $end, array $labels, string $period): array
    {
        $values = array_fill(0, count($labels), 0);
        $bucketCount = count($labels);
        $days = max(1, $start->diffInDays($end) + 1);
        $bucketSize = max(1, (int) ceil($days / $bucketCount));

        $records = (clone $query)
            ->whereBetween($column, [$start, $end])
            ->get([$column]);

        foreach ($records as $row) {
            $date = Carbon::parse($row->{$column});
            $dayOffset = $start->diffInDays($date);
            $bucket = min($bucketCount - 1, (int) floor($dayOffset / $bucketSize));
            $values[$bucket]++;
        }

        return $values;
    }

    private function attendanceTrend(Carbon $start, Carbon $end, array $labels, string $period): array
    {
        $values = array_fill(0, count($labels), 0);
        $bucketCount = count($labels);
        $days = max(1, $start->diffInDays($end) + 1);
        $bucketSize = max(1, (int) ceil($days / $bucketCount));

        $records = Attendance::query()
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get(['date', 'status']);

        $presentByDay = [];
        $totalByDay = [];
        foreach ($records as $row) {
            $day = (string) $row->date;
            $totalByDay[$day] = ($totalByDay[$day] ?? 0) + 1;
            if ($row->status === 'present') {
                $presentByDay[$day] = ($presentByDay[$day] ?? 0) + 1;
            }
        }

        foreach ($presentByDay as $day => $present) {
            $date = Carbon::parse($day);
            $dayOffset = $start->diffInDays($date);
            $bucket = min($bucketCount - 1, (int) floor($dayOffset / $bucketSize));
            $total = $totalByDay[$day] ?? 1;
            $values[$bucket] = round((($values[$bucket] * 0) + ($present / $total)) * 100);
        }

        return $values;
    }
}
