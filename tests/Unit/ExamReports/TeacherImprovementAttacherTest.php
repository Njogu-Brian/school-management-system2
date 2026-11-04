<?php

namespace Tests\Unit\ExamReports;

use App\Exports\TeacherPerformanceExport;
use App\Services\Academics\ExamReports\PreviousTermResolver;
use App\Services\Academics\ExamReports\TeacherImprovementAttacher;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class TeacherImprovementAttacherTest extends TestCase
{
    public function test_attach_computes_prev_mean_and_improvement(): void
    {
        $attacher = new TeacherImprovementAttacher();
        $rows = $attacher->attach(
            [
                ['teacher_id' => 1, 'teacher' => 'Ada', 'mean' => 70],
                ['teacher_id' => 2, 'teacher' => 'Ben', 'mean' => 55],
            ],
            [
                ['teacher_id' => 1, 'mean' => 60],
                ['teacher_id' => 3, 'mean' => 80],
            ],
            'teacher_id',
            'mean'
        );

        $this->assertSame(60.0, $rows[0]['prev_mean']);
        $this->assertSame(10.0, $rows[0]['improvement']);
        $this->assertNull($rows[1]['prev_mean']);
        $this->assertNull($rows[1]['improvement']);
    }

    public function test_top_improved_orders_descending(): void
    {
        $attacher = new TeacherImprovementAttacher();
        $top = $attacher->topImproved([
            ['teacher' => 'A', 'improvement' => 2],
            ['teacher' => 'B', 'improvement' => 9],
            ['teacher' => 'C', 'improvement' => null],
        ], 2);

        $this->assertCount(2, $top);
        $this->assertSame('B', $top[0]['teacher']);
        $this->assertSame('A', $top[1]['teacher']);
    }

    public function test_previous_term_sort_key_orders_named_terms(): void
    {
        $resolver = new PreviousTermResolver();
        $method = new ReflectionMethod(PreviousTermResolver::class, 'termSortKey');
        $method->setAccessible(true);

        $t1 = new \App\Models\Term(['name' => 'Term 1']);
        $t1->id = 10;
        $t2 = new \App\Models\Term(['name' => 'Term 2']);
        $t2->id = 11;
        $t3 = new \App\Models\Term(['name' => 'Third Term']);
        $t3->id = 12;

        $keys = [
            $method->invoke($resolver, $t2),
            $method->invoke($resolver, $t1),
            $method->invoke($resolver, $t3),
        ];
        sort($keys);

        $this->assertSame($method->invoke($resolver, $t1), $keys[0]);
        $this->assertSame($method->invoke($resolver, $t2), $keys[1]);
        $this->assertSame($method->invoke($resolver, $t3), $keys[2]);
    }

    public function test_export_includes_improvement_columns_for_categories(): void
    {
        $payload = [
            'meta' => [
                'scope' => 'school',
                'mode' => 'term',
                'previous_term' => ['academic_year' => 2025, 'term_name' => 'Term 2'],
            ],
            'by_category' => [[
                'label' => 'Junior High',
                'detail' => 'Grade 7–9',
                'per_teacher' => [[
                    'rank' => 1,
                    'teacher' => 'Ada',
                    'subjects_count' => 2,
                    'mean_of_subject_means' => 72,
                    'prev_mean' => 65,
                    'improvement' => 7,
                ]],
                'per_subject_teacher' => [],
            ]],
        ];

        $rows = (new TeacherPerformanceExport($payload, 'All Teachers', 'Tester', 'Acme'))->array();
        $flat = collect($rows)->flatten()->filter(fn ($v) => is_string($v))->implode(' | ');

        $this->assertStringContainsString('Junior High', $flat);
        $this->assertStringContainsString('Prev mean', $flat);
        $this->assertStringContainsString('Improvement', $flat);
        $this->assertStringContainsString('Term 2', $flat);
    }
}
