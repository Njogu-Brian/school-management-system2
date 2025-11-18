<?php

namespace Tests\Feature\Academics;

use Tests\TestCase;
use App\Models\Academics\Exam;
use App\Models\Academics\Classroom;
use App\Models\Academics\Subject;
use App\Models\AcademicYear;
use App\Models\Term;
use Illuminate\Support\Facades\DB;

class ExamControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->admin = $this->createAdmin();
        $this->teacher = $this->createTeacher();
        $this->classroom = $this->createClassroom();
        $this->subject = \App\Models\Academics\Subject::factory()->create();
        $this->year = $this->createAcademicYear();
        $this->term = $this->createTerm();
    }

    /** @test */
    public function admin_can_view_exams_index()
    {
        Exam::factory()->count(3)->create();

        $response = $this->actingAs($this->admin)
            ->get(route('academics.exams.index'));

        $response->assertStatus(200);
        $response->assertViewIs('academics.exams.index');
    }

    /** @test */
    public function teacher_can_only_view_assigned_classroom_exams()
    {
        // Assign teacher to classroom
        DB::table('classroom_subjects')->insert([
            'classroom_id' => $this->classroom->id,
            'subject_id' => $this->subject->id,
            'staff_id' => $this->teacher->staff->id,
            'academic_year_id' => $this->year->id,
            'term_id' => $this->term->id,
        ]);

        // Create exam for assigned classroom
        $assignedExam = Exam::factory()->create([
            'classroom_id' => $this->classroom->id,
            'subject_id' => $this->subject->id,
        ]);

        // Create exam for different classroom
        $otherClassroom = $this->createClassroom();
        $unassignedExam = Exam::factory()->create([
            'classroom_id' => $otherClassroom->id,
        ]);

        $response = $this->actingAs($this->teacher)
            ->get(route('academics.exams.index'));

        $response->assertStatus(200);
        $response->assertViewHas('exams', function ($exams) use ($assignedExam, $unassignedExam) {
            return $exams->contains('id', $assignedExam->id) 
                && !$exams->contains('id', $unassignedExam->id);
        });
    }

    /** @test */
    public function admin_can_create_exam()
    {
        $examData = [
            'name' => 'Midterm Exam',
            'type' => 'midterm',
            'academic_year_id' => $this->year->id,
            'term_id' => $this->term->id,
            'classroom_id' => $this->classroom->id,
            'subject_id' => $this->subject->id,
            'max_marks' => 100,
            'status' => 'open'
        ];

        $response = $this->actingAs($this->admin)
            ->post(route('academics.exams.store'), $examData);

        $response->assertRedirect();
        $this->assertDatabaseHas('exams', [
            'name' => 'Midterm Exam',
            'classroom_id' => $this->classroom->id,
        ]);
    }

    /** @test */
    public function teacher_cannot_create_exam_for_unassigned_classroom()
    {
        $otherClassroom = $this->createClassroom();
        
        $examData = [
            'name' => 'Unauthorized Exam',
            'type' => 'midterm',
            'academic_year_id' => $this->year->id,
            'term_id' => $this->term->id,
            'classroom_id' => $otherClassroom->id,
            'subject_id' => $this->subject->id,
            'max_marks' => 100,
        ];

        $response = $this->actingAs($this->teacher)
            ->post(route('academics.exams.store'), $examData);

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_update_exam()
    {
        $exam = Exam::factory()->create([
            'name' => 'Original Name',
            'classroom_id' => $this->classroom->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->put(route('academics.exams.update', $exam), [
                'name' => 'Updated Name',
                'type' => $exam->type,
                'academic_year_id' => $exam->academic_year_id,
                'term_id' => $exam->term_id,
                'classroom_id' => $exam->classroom_id,
                'subject_id' => $exam->subject_id,
                'max_marks' => $exam->max_marks,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('exams', [
            'id' => $exam->id,
            'name' => 'Updated Name',
        ]);
    }

    /** @test */
    public function admin_can_delete_exam()
    {
        $exam = Exam::factory()->create();

        $response = $this->actingAs($this->admin)
            ->delete(route('academics.exams.destroy', $exam));

        $response->assertRedirect();
        $this->assertDatabaseMissing('exams', ['id' => $exam->id]);
    }

    /** @test */
    public function exam_cannot_be_deleted_if_published()
    {
        $exam = Exam::factory()->create(['status' => 'published']);

        $response = $this->actingAs($this->admin)
            ->delete(route('academics.exams.destroy', $exam));

        $response->assertSessionHasErrors();
        $this->assertDatabaseHas('exams', ['id' => $exam->id]);
    }
}

