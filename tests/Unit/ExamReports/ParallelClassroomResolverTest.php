<?php

namespace Tests\Unit\ExamReports;

use App\Models\Academics\Classroom;
use App\Services\Academics\ExamReports\ParallelClassroomResolver;
use PHPUnit\Framework\TestCase;

class ParallelClassroomResolverTest extends TestCase
{
    public function test_grade_key_from_name_groups_parallel_sections(): void
    {
        $resolver = new ParallelClassroomResolver();

        $this->assertSame('grade 7', $resolver->gradeKeyFromName('Grade 7 East'));
        $this->assertSame('grade 7', $resolver->gradeKeyFromName('Grade 7 West'));
        $this->assertSame('grade 4', $resolver->gradeKeyFromName('Grade 4A'));
        $this->assertSame('pp 1', $resolver->gradeKeyFromName('PP1 Blue'));
    }

    public function test_grade_key_prefers_classroom_level_field(): void
    {
        $resolver = new ParallelClassroomResolver();
        $classroom = new Classroom([
            'name' => 'Something Odd',
            'level' => 'Grade 8',
        ]);

        $this->assertSame('grade 8', $resolver->gradeKey($classroom));
        $this->assertSame('Grade 8', $resolver->gradeLabel($classroom));
    }
}
