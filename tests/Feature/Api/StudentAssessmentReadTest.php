<?php

namespace Tests\Feature\Api;

use App\Models\Academics\Assessment;
use App\Models\Academics\Classroom;
use App\Models\Academics\Exam;
use App\Models\Academics\ExamMark;
use App\Models\Academics\ReportCard;
use App\Models\Academics\Subject;
use App\Models\AcademicYear;
use App\Models\Student;
use App\Models\Term;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StudentAssessmentReadTest extends TestCase
{
    protected function setUp(): void
    {
        if (config('database.default') === 'mysql') {
            $this->markTestSkipped('Skipping on mysql: RefreshDatabase migration graph fails in CI.');
        }

        parent::setUp();
    }

    public function test_admin_can_fetch_assessment_history_and_summary(): void
    {

        $admin = $this->createAdmin();
        $year = $this->createAcademicYear();
        $term = $this->createTerm(['is_current' => true]);
        $classroom = $this->createClassroom();
        $subject = Subject::create(['name' => 'Mathematics', 'code' => 'MATH', 'is_active' => true]);
        $student = $this->createStudent(['classroom_id' => $classroom->id]);

        $exam = Exam::create([
            'name' => 'CAT 1 Mathematics',
            'modality' => 'physical',
            'academic_year_id' => $year->id,
            'term_id' => $term->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'max_marks' => 30,
            'weight' => 10,
            'status' => 'published',
            'is_cat' => true,
            'cat_number' => 1,
            'publish_result' => true,
        ]);

        ExamMark::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'subject_id' => $subject->id,
            'score_raw' => 24,
            'grade_label' => 'A',
            'status' => 'approved',
        ]);

        Assessment::create([
            'assessment_date' => now()->toDateString(),
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'student_id' => $student->id,
            'assessment_type' => 'Weekly Test',
            'score' => 18,
            'out_of' => 20,
        ]);

        ReportCard::create([
            'student_id' => $student->id,
            'academic_year_id' => $year->id,
            'term_id' => $term->id,
            'classroom_id' => $classroom->id,
            'summary' => json_encode(['average' => 78.5, 'grade' => 'B+']),
            'published_at' => now(),
        ]);

        Sanctum::actingAs($admin);

        $history = $this->getJson("/api/students/{$student->id}/assessment-history");
        $history->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => ['data', 'current_page', 'total'],
                'meta' => ['student_id', 'current_term_id'],
            ]);

        $types = collect($history->json('data.data'))->pluck('type')->all();
        $this->assertContains('cat', $types);
        $this->assertContains('weekly_assessment', $types);
        $this->assertContains('report_card_term', $types);

        $summary = $this->getJson("/api/students/{$student->id}/academic-summary");
        $summary->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.student_id', $student->id)
            ->assertJsonPath('data.marks_recorded_count', 1)
            ->assertJsonPath('data.report_cards_count', 1);

        $this->assertNotNull($summary->json('data.exam_average'));
    }

    public function test_type_filter_limits_history_rows(): void
    {
        $admin = $this->createAdmin();
        $year = $this->createAcademicYear();
        $term = $this->createTerm();
        $classroom = $this->createClassroom();
        $subject = Subject::create(['name' => 'English', 'code' => 'ENG', 'is_active' => true]);
        $student = $this->createStudent(['classroom_id' => $classroom->id]);

        $exam = Exam::create([
            'name' => 'End Term',
            'modality' => 'physical',
            'academic_year_id' => $year->id,
            'term_id' => $term->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'max_marks' => 100,
            'weight' => 100,
            'status' => 'published',
            'is_cat' => false,
            'publish_result' => true,
        ]);

        ExamMark::create([
            'exam_id' => $exam->id,
            'student_id' => $student->id,
            'subject_id' => $subject->id,
            'score_raw' => 70,
            'status' => 'approved',
        ]);

        Assessment::create([
            'assessment_date' => now()->toDateString(),
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'student_id' => $student->id,
            'assessment_type' => 'Quiz',
            'score' => 8,
            'out_of' => 10,
        ]);

        Sanctum::actingAs($admin);

        $response = $this->getJson("/api/students/{$student->id}/assessment-history?type=cat");
        $response->assertOk();
        $this->assertCount(0, $response->json('data.data'));

        $response = $this->getJson("/api/students/{$student->id}/assessment-history?type=traditional_exam");
        $response->assertOk();
        $this->assertCount(1, $response->json('data.data'));
    }
}
