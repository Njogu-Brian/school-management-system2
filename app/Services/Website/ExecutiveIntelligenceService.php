<?php

namespace App\Services\Website;

use App\Http\Controllers\Api\ApiAnalyticsController;
use App\Models\Admissions\AdmissionApplication;
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

            $attendanceRate = $this->attendanceAbsenteeRate();

            $websiteTraffic = Schema::hasTable('page_views')
                ? PageView::where('created_at', '>=', now()->subDays(30))->count()
                : 0;

            $conversions = Schema::hasTable('conversion_events')
                ? ConversionEvent::where('created_at', '>=', now()->subDays(30))->count()
                : 0;

            return [
                'admissions' => [
                    'total_leads' => $totalLeads,
                    'admitted' => $admitted,
                    'conversion_rate' => $conversionRate,
                ],
                'finance' => [
                    'collections' => round($collections, 2),
                    'outstanding_balances' => round($outstanding, 2),
                ],
                'attendance' => [
                    'absentee_rate' => $attendanceRate,
                ],
                'academics' => [
                    'active_learners' => Student::where('archive', 0)->where('is_alumni', false)->count(),
                ],
                'website' => [
                    'traffic_30d' => $websiteTraffic,
                    'conversions_30d' => $conversions,
                ],
                'marketing' => [
                    'campaign_logs' => CampaignLog::where('created_at', '>=', now()->subDays(30))->sum('sent_count'),
                ],
                'parent_engagement' => [
                    'portal_usage_proxy' => ConversionEvent::where('event_type', 'parent_login')
                        ->where('created_at', '>=', now()->subDays(30))->count(),
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

        return $created;
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
