<?php

namespace Tests\Feature\Finance;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\{User, Student, Votehead, FeeStructure, AcademicYear, Term, FeePostingRun};
use Illuminate\Support\Facades\DB;

class FeePostingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create(['role' => 'Admin']));
    }

    /** @test */
    public function admin_can_access_posting_page()
    {
        $response = $this->get(route('finance.posting.index'));

        $response->assertStatus(200);
        $response->assertViewIs('finance.posting.index');
    }

    /** @test */
    public function admin_can_preview_posting_diffs()
    {
        $student = Student::factory()->create();
        $votehead = Votehead::factory()->create(['is_mandatory' => true]);
        $academicYear = AcademicYear::factory()->create(['year' => 2025]);
        $term = Term::factory()->create(['academic_year_id' => $academicYear->id, 'name' => 'Term 1']);

        $response = $this->post(route('finance.posting.preview'), [
            'year' => 2025,
            'term' => 1,
            'student_id' => $student->id,
        ]);

        $response->assertStatus(200);
        $response->assertViewIs('finance.posting.preview');
        $response->assertViewHas('diffs');
        $response->assertViewHas('summary');
    }

    /** @test */
    public function admin_can_commit_posting()
    {
        $student = Student::factory()->create();
        $votehead = Votehead::factory()->create(['is_mandatory' => true]);
        $academicYear = AcademicYear::factory()->create(['year' => 2025]);
        $term = Term::factory()->create(['academic_year_id' => $academicYear->id, 'name' => 'Term 1']);

        $diffs = [
            [
                'student_id' => $student->id,
                'votehead_id' => $votehead->id,
                'old_amount' => null,
                'new_amount' => 5000.00,
                'action' => 'added',
                'origin' => 'structure',
            ],
        ];

        $response = $this->post(route('finance.posting.commit'), [
            'year' => 2025,
            'term' => 1,
            'activate_now' => true,
            'diffs' => $diffs,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('fee_posting_runs', [
            'status' => 'completed',
        ]);
    }

    /** @test */
    public function admin_can_view_posting_run_details()
    {
        $academicYear = AcademicYear::factory()->create(['year' => 2025]);
        $term = Term::factory()->create(['academic_year_id' => $academicYear->id, 'name' => 'Term 1']);
        $run = FeePostingRun::factory()->create([
            'academic_year_id' => $academicYear->id,
            'term_id' => $term->id,
            'status' => 'completed',
        ]);

        $response = $this->get(route('finance.posting.show', $run));

        $response->assertStatus(200);
        $response->assertViewIs('finance.posting.show');
        $response->assertViewHas('run');
    }
}

