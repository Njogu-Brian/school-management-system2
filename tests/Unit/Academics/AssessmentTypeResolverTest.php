<?php

namespace Tests\Unit\Academics;

use App\Models\Academics\Assessment;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamMark;
use App\Services\Academics\AssessmentTypeResolver;
use PHPUnit\Framework\TestCase;

class AssessmentTypeResolverTest extends TestCase
{
    public function test_resolves_cat_from_exam_is_cat_flag(): void
    {
        $resolver = new AssessmentTypeResolver();
        $mark = new ExamMark(['assessment_method' => 'written']);
        $mark->setRelation('exam', new Exam(['is_cat' => true, 'name' => 'CAT 1']));

        $result = $resolver->resolveForExamMark($mark);

        $this->assertSame(AssessmentTypeResolver::TYPE_CAT, $result['type']);
    }

    public function test_resolves_oral_from_mark_assessment_method(): void
    {
        $resolver = new AssessmentTypeResolver();
        $mark = new ExamMark(['assessment_method' => 'oral']);
        $mark->setRelation('exam', new Exam(['is_cat' => false, 'name' => 'Oral Exam']));

        $result = $resolver->resolveForExamMark($mark);

        $this->assertSame(AssessmentTypeResolver::TYPE_ORAL, $result['type']);
    }

    public function test_resolves_weekly_assignment_from_type_string(): void
    {
        $resolver = new AssessmentTypeResolver();
        $row = new Assessment(['assessment_type' => 'Homework Assignment']);

        $result = $resolver->resolveForWeeklyAssessment($row);

        $this->assertSame(AssessmentTypeResolver::TYPE_ASSIGNMENT, $result['type']);
    }
}
