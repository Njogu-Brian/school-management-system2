<?php

namespace Tests\Unit\ExamReports;

use App\Services\Academics\ExamReports\TrendsService;
use PHPUnit\Framework\TestCase;

class TrendsInsightsTest extends TestCase
{
    public function test_insights_from_series_mentions_deltas(): void
    {
        $service = new TrendsService();

        $payload = [
            'meta' => ['academic_year_id' => 1, 'term_id' => 1],
            'series' => [
                [
                    'exam_id' => 10,
                    'exam' => 'CAT 1',
                    'count' => 30,
                    'mean' => 55.0,
                    'pass_rate' => 40.0,
                    'delta_mean' => null,
                    'delta_pass_rate' => null,
                ],
                [
                    'exam_id' => 11,
                    'exam' => 'CAT 2',
                    'count' => 30,
                    'mean' => 60.0,
                    'pass_rate' => 50.0,
                    'delta_mean' => 5.0,
                    'delta_pass_rate' => 10.0,
                ],
            ],
        ];

        $out = $service->insightsFromSeries($payload);
        $txt = implode("\n", $out['insights'] ?? []);
        $this->assertStringContainsString('Average', $txt);
        $this->assertStringContainsString('Pass rate', $txt);
    }
}

