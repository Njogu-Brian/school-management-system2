<?php

namespace Tests\Unit\ExamReports;

use App\Services\Academics\ExamReports\ExamScopeResolver;
use PHPUnit\Framework\TestCase;

class ExamScopeResolverTest extends TestCase
{
    public function test_exam_type_sort_order_recognizes_opener_mid_and_end(): void
    {
        $this->assertSame(1, ExamScopeResolver::examTypeSortOrder('Opener', 'OPN'));
        $this->assertSame(2, ExamScopeResolver::examTypeSortOrder('Mid Term', 'MID'));
        $this->assertSame(3, ExamScopeResolver::examTypeSortOrder('End Term', 'END'));
    }

    public function test_exam_type_sort_order_unknown_types_sort_last(): void
    {
        $this->assertSame(50, ExamScopeResolver::examTypeSortOrder('Mock Exam', 'MOCK'));
        $this->assertSame(0, ExamScopeResolver::examTypeSortOrder(null, null));
    }
}
