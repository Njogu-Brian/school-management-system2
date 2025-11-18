<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Student;
use App\Models\ParentInfo;
use App\Models\Academics\Classroom;
use App\Models\Academics\Stream;
use App\Models\StudentCategory;

class StudentModelTest extends TestCase
{
    /** @test */
    public function student_can_be_created()
    {
        $student = Student::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'admission_number' => 'ST001'
        ]);

        $this->assertDatabaseHas('students', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'admission_number' => 'ST001'
        ]);
    }

    /** @test */
    public function student_belongs_to_parent()
    {
        $parent = ParentInfo::factory()->create();
        $student = Student::factory()->create(['parent_id' => $parent->id]);

        $this->assertInstanceOf(ParentInfo::class, $student->parent);
        $this->assertEquals($parent->id, $student->parent->id);
    }

    /** @test */
    public function student_belongs_to_classroom()
    {
        $classroom = $this->createClassroom();
        $student = Student::factory()->create(['classroom_id' => $classroom->id]);

        $this->assertInstanceOf(Classroom::class, $student->classroom);
        $this->assertEquals($classroom->id, $student->classroom->id);
    }

    /** @test */
    public function student_belongs_to_stream()
    {
        $stream = \App\Models\Academics\Stream::factory()->create();
        $student = Student::factory()->create(['stream_id' => $stream->id]);

        $this->assertInstanceOf(Stream::class, $student->stream);
        $this->assertEquals($stream->id, $student->stream->id);
    }

    /** @test */
    public function student_belongs_to_category()
    {
        $category = StudentCategory::factory()->create();
        $student = Student::factory()->create(['category_id' => $category->id]);

        $this->assertInstanceOf(StudentCategory::class, $student->category);
        $this->assertEquals($category->id, $student->category->id);
    }

    /** @test */
    public function student_name_accessor_works()
    {
        $student = Student::factory()->create([
            'first_name' => 'John',
            'middle_name' => 'Middle',
            'last_name' => 'Doe'
        ]);

        $this->assertStringContainsString('John', $student->name);
        $this->assertStringContainsString('Doe', $student->name);
    }

    /** @test */
    public function student_can_be_archived()
    {
        $student = Student::factory()->create(['archive' => 0]);
        
        $student->update(['archive' => 1]);
        
        $this->assertEquals(1, $student->fresh()->archive);
    }
}

