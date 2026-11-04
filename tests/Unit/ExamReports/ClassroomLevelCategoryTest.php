<?php

namespace Tests\Unit\ExamReports;

use App\Models\Academics\Classroom;
use App\Services\Academics\ExamReports\ClassroomLevelCategory;
use PHPUnit\Framework\TestCase;

class ClassroomLevelCategoryTest extends TestCase
{
    public function test_uses_explicit_level_type(): void
    {
        $classroom = new Classroom([
            'name' => 'Mystery Class',
            'level_type' => 'junior_high',
        ]);

        $this->assertSame(
            ClassroomLevelCategory::JUNIOR_HIGH,
            ClassroomLevelCategory::resolve($classroom)
        );
    }

    public function test_infers_bands_from_class_names(): void
    {
        $cases = [
            'PP1 East' => ClassroomLevelCategory::PRESCHOOL,
            'Foundation Class A' => ClassroomLevelCategory::PRESCHOOL,
            'Grade 2 Blue' => ClassroomLevelCategory::LOWER_PRIMARY,
            'Grade 5' => ClassroomLevelCategory::UPPER_PRIMARY,
            'Grade 8 West' => ClassroomLevelCategory::JUNIOR_HIGH,
            '7 East' => ClassroomLevelCategory::JUNIOR_HIGH,
        ];

        foreach ($cases as $name => $expected) {
            $this->assertSame(
                $expected,
                ClassroomLevelCategory::inferFromName($name),
                $name
            );
        }
    }
}
