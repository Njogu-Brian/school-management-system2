<?php

namespace App\Services\Website;

use App\Http\Controllers\Api\ApiAnalyticsController;
use App\Models\Academics\ExamMark;
use App\Models\Academics\Classroom;
use App\Models\Admissions\AdmissionApplication;
use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\Invoice;
use App\Models\Student;
use App\Models\Website\CampaignLog;
use App\Models\Website\ConversionEvent;
use App\Models\Website\ExecutiveAlert;
use App\Models\Website\PageView;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

class ExecutiveIntelligenceService
{
    public function kpis(): array
    {
        return Cache::remember('website.executive.kpis', 300, function () {
            $totalLeads = AdmissionApplication::count();
            $admitted = AdmissionApplication::where('status', AdmissionApplication::STATUS_ENROLLED)->count();
            $conversionRate = $totalLeads > 0 ? round(($admitted / $totalLeads) * 100, 1) : 0;

            $collections = (float) Invoice::query()
                ->whereNull('reversed_at')
                ->sum('paid_amount');

            $outstanding = (float) Invoice::query()
                ->whereNull('reversed_at')
                ->sum('balance');

            $websiteTraffic = Schema::hasTable('page_views')
                ? PageView::where('viewed_at', '>=', now()->subDays(30))->count()
                : 0;

            $conversions = Schema::hasTable('conversion_events')
                ? ConversionEvent::where('occurred_at', '>=', now()->subDays(30))->count()
                : 0;

            return [
                'admissions' => [
                    'total_leads' => $totalLeads,
                    'admitted' => $admitted,
                    'conversion_rate' => $conversionRate,
                    'pending' => AdmissionApplication::where('status', AdmissionApplication::STATUS_PENDING)->count(),
                ],
                'finance' => [
                    'collections' => round($collections, 2),
                    'outstanding_balances' => round($outstanding, 2),
                ],
                'attendance' => [
                    'absentee_rate' => $this->attendanceAbsenteeRate(),
                ],
                'academics' => [
                    'active_learners' => Student::where('archive', 0)->where('is_alumni', false)->count(),
                    'class_performance' => $this->classPerformanceSummary(),
                ],
                'website' => [
                    'traffic_30d' => $websiteTraffic,
                    'conversions_30d' => $conversions,
                    'conversion_funnel' => $this->conversionFunnel(),
                ],
                'marketing' => [
                    'campaign_sends_30d' => CampaignLog::where('created_at', '>=', now()->subDays(30))->sum('sent_count'),
                    'campaigns_30d' => CampaignLog::where('created_at', '>=', now()->subDays(30))->count(),
                ],
                'parent_engagement' => [
                    'portal_logins_30d' => ConversionEvent::where('event_type', 'parent_login')
                        ->where('occurred_at', '>=', now()->subDays(30))->count(),
                    'active_announcements' => Announcement::where('active', true)->count(),
                ],
                'as_of' => now()->toIso8601String(),
            ];
        });
    }

    public function erpExecutiveTrends(Request $request): array
    {
        $response = app(ApiAnalyticsController::class)->executive($request);

        return $response->getData(true)['data'] ?? [];
    }

    public function computePredictiveAlerts(): int
    {
        $created = 0;
        $kpis = $this->kpis();

        if ($kpis['admissions']['total_leads'] > 5 && $kpis['admissions']['conversion_rate'] < 15) {
            $this->upsertAlert('admissions_dropping', 'warning', 'Admissions conversion below 15%', 'Review follow-up on leads and abandoned applications.');
            $created++;
        }

        if ($kpis['finance']['outstanding_balances'] > 500000) {
            $this->upsertAlert('fee_default_risk', 'warning', 'High outstanding fee balance', 'Outstanding balances exceed KES 500,000. Consider targeted reminders.');
            $created++;
        }

        if ($kpis['attendance']['absentee_rate'] > 12) {
            $this->upsertAlert('low_attendance', 'warning', 'Elevated absentee rate', 'Absentee rate is above 12%. Review class-level patterns.');
            $created++;
        }

        if ($kpis['website']['traffic_30d'] < 50) {
            $this->upsertAlert('declining_engagement', 'info', 'Low website traffic', 'Website traffic in the last 30 days is low. Review SEO and campaigns.');
            $created++;
        }

        $funnel = $kpis['website']['conversion_funnel'] ?? [];
        $views = (int) ($funnel['page_views'] ?? 0);
        $enquiries = (int) ($funnel['enquiries'] ?? 0);
        if ($views > 100 && $enquiries < 3) {
            $this->upsertAlert('conversion_funnel_weak', 'warning', 'Weak enquiry conversion', 'High traffic but few enquiries — review admissions CTA and forms.');
            $created++;
        }

        return $created;
    }

    protected function conversionFunnel(): array
    {
        if (! Schema::hasTable('conversion_events') || ! Schema::hasTable('page_views')) {
            return ['page_views' => 0, 'enquiries' => 0, 'applications' => 0, 'enrolled' => 0];
        }

        $since = now()->subDays(30);

        return [
            'page_views' => PageView::where('viewed_at', '>=', $since)->count(),
            'enquiries' => ConversionEvent::where('event_type', 'enquiry')->where('occurred_at', '>=', $since)->count(),
            'applications' => ConversionEvent::where('event_type', 'admission_start')->where('occurred_at', '>=', $since)->count(),
            'enrolled' => AdmissionApplication::where('status', AdmissionApplication::STATUS_ENROLLED)
                ->where('updated_at', '>=', $since)->count(),
        ];
    }

    protected function classPerformanceSummary(): array
    {
        if (! Schema::hasTable('exam_marks')) {
            return [];
        }

        return Classroom::query()
            ->where('is_alumni', false)
            ->withCount('students')
            ->limit(12)
            ->get()
            ->map(function ($class) {
                $avg = ExamMark::query()
                    ->whereHas('student', fn ($q) => $q->where('classroom_id', $class->id))
                    ->where('created_at', '>=', now()->subMonths(6))
                    ->avg('score_raw');

                return [
                    'classroom' => $class->name,
                    'learners' => $class->students_count,
                    'avg_score_6m' => $avg ? round((float) $avg, 1) : null,
                ];
            })
            ->filter(fn ($r) => $r['avg_score_6m'] !== null)
            ->values()
            ->all();
    }

    protected function upsertAlert(string $type, string $severity, string $title, string $message): void
    {
        $existing = ExecutiveAlert::where('alert_type', $type)
            ->where('acknowledged', false)
            ->where('created_at', '>=', now()->subDays(7))
            ->first();

        if (! $existing) {
            ExecutiveAlert::create([
                'alert_type' => $type,
                'severity' => $severity,
                'title' => $title,
                'message' => $message,
            ]);
        }
    }

    protected function attendanceAbsenteeRate(): float
    {
        if (! Schema::hasTable('attendance')) {
            return 0;
        }

        $recent = Attendance::whereDate('date', '>=', now()->subDays(14))->get();
        if ($recent->isEmpty()) {
            return 0;
        }

        $absent = $recent->where('status', 'absent')->count();

        return round(($absent / $recent->count()) * 100, 1);
    }
}
