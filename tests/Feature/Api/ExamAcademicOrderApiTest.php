<?php

namespace Tests\Feature\Api;

use App\Models\Academics\Exam;
use App\Models\Academics\ExamSession;
use App\Models\Academics\ExamType;
use App\Models\Academics\Subject;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ExamAcademicOrderApiTest extends TestCase
{
    protected function setUp(): void
    {
        $driver = getenv('DB_CONNECTION') ?: 'mysql';
        if ($driver === 'mysql') {
            $this->markTestSkipped('Skipping on mysql: RefreshDatabase migration graph fails locally. Use sqlite for API tests.');
        }

        parent::setUp();
    }

    public function test_api_lists_term1_exams_before_term2_even_when_term1_was_created_later(): void
    {
        $admin = $this->createAdmin();
        $year = $this->createAcademicYear(['year' => 2026, 'is_active' => true]);
        $classroom = $this->createClassroom();
        $subject = Subject::create(['name' => 'English', 'code' => 'ENG', 'is_active' => true]);

        $term2 = $this->createTerm([
            'academic_year_id' => $year->id,
            'name' => 'Term 2',
            'opening_date' => '2026-05-04',
            'closing_date' => '2026-08-07',
            'is_current' => false,
        ]);
        $term1 = $this->createTerm([
            'academic_year_id' => $year->id,
            'name' => 'Term 1',
            'opening_date' => '2026-01-06',
            'closing_date' => '2026-04-10',
            'is_current' => true,
        ]);

        $term2Exam = Exam::create([
            'name' => 'English — FOUNDATION — END TERM 2',
            'modality' => 'physical',
            'academic_year_id' => $year->id,
            'term_id' => $term2->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'starts_on' => '2026-07-22',
            'ends_on' => '2026-07-25',
            'max_marks' => 100,
            'weight' => 100,
            'status' => 'published',
        ]);
        $term1Exam = Exam::create([
            'name' => 'English — GRADE 1 — TERM 1 ENDTERM',
            'modality' => 'physical',
            'academic_year_id' => $year->id,
            'term_id' => $term1->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'starts_on' => '2026-03-17',
            'ends_on' => '2026-03-31',
            'max_marks' => 100,
            'weight' => 100,
            'status' => 'published',
        ]);

        $this->assertGreaterThan($term2Exam->id, $term1Exam->id);

        $orderedIds = Exam::query()->inAcademicOrder()->pluck('id')->all();
        $this->assertSame([$term1Exam->id, $term2Exam->id], $orderedIds);

        Sanctum::actingAs($admin);
        $response = $this->getJson('/api/exams');
        $response->assertOk()->assertJsonPath('success', true);

        $ids = collect($response->json('data.data'))->pluck('id')->all();
        $this->assertSame([$term1Exam->id, $term2Exam->id], $ids);
    }

    public function test_exam_sessions_list_term1_before_term2_even_when_term1_was_created_later(): void
    {
        $admin = $this->createAdmin();
        $year = $this->createAcademicYear(['year' => 2026, 'is_active' => true]);
        $classroom = $this->createClassroom();
        $examType = ExamType::query()->first() ?? ExamType::create([
            'name' => 'End Term',
            'code' => 'endterm',
        ]);

        $term2 = $this->createTerm([
            'academic_year_id' => $year->id,
            'name' => 'Term 2',
            'opening_date' => '2026-05-04',
            'closing_date' => '2026-08-07',
        ]);
        $term1 = $this->createTerm([
            'academic_year_id' => $year->id,
            'name' => 'Term 1',
            'opening_date' => '2026-01-06',
            'closing_date' => '2026-04-10',
        ]);

        $term2Session = ExamSession::create([
            'exam_type_id' => $examType->id,
            'academic_year_id' => $year->id,
            'term_id' => $term2->id,
            'classroom_id' => $classroom->id,
            'name' => 'End Term 2',
            'starts_on' => '2026-07-22',
            'ends_on' => '2026-07-25',
            'status' => 'published',
        ]);
        $term1Session = ExamSession::create([
            'exam_type_id' => $examType->id,
            'academic_year_id' => $year->id,
            'term_id' => $term1->id,
            'classroom_id' => $classroom->id,
            'name' => 'End Term 1',
            'starts_on' => '2026-03-17',
            'ends_on' => '2026-03-31',
            'status' => 'published',
        ]);

        $this->assertGreaterThan($term2Session->id, $term1Session->id);

        Sanctum::actingAs($admin);
        $response = $this->getJson('/api/exam-sessions');
        $response->assertOk()->assertJsonPath('success', true);

        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertSame([$term1Session->id, $term2Session->id], $ids);
    }
}
