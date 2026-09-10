<?php

namespace Tests\Feature\Api;

use App\Models\Academics\Assessment;
use App\Models\Academics\Subject;
use App\Models\Student;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SpeedTestApiTest extends TestCase
{
    protected function setUp(): void
    {
        $driver = getenv('DB_CONNECTION') ?: 'mysql';
        if ($driver === 'mysql') {
            $this->markTestSkipped('Skipping on mysql: RefreshDatabase migration graph fails locally. Use sqlite for API tests.');
        }

        parent::setUp();
    }

    public function test_admin_can_create_speed_test_enter_marks_and_list_results(): void
    {
        $admin = $this->createAdmin();
        $classroom = $this->createClassroom(['name' => 'Grade 4']);
        $subject = Subject::create(['name' => 'CRE', 'code' => 'CRE', 'is_active' => true]);
        $student = Student::factory()->create([
            'classroom_id' => $classroom->id,
            'archive' => 0,
            'is_alumni' => false,
        ]);

        Sanctum::actingAs($admin);

        $create = $this->postJson('/api/speed-tests', [
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'question_count' => 30,
            'max_marks' => 30,
            'title' => 'CRE speed test',
        ]);

        $create->assertCreated()->assertJsonPath('success', true);
        $batchKey = (string) $create->json('data.batch_key');
        $this->assertNotSame('', $batchKey);
        $this->assertSame(30, (int) $create->json('data.question_count'));
        $this->assertSame(30, (int) $create->json('data.max_marks'));

        $this->assertDatabaseHas('assessments', [
            'batch_key' => $batchKey,
            'student_id' => $student->id,
            'assessment_type' => 'Speed Test',
            'question_count' => 30,
        ]);

        $save = $this->putJson("/api/speed-tests/{$batchKey}/marks", [
            'entries' => [
                ['student_id' => $student->id, 'score' => 24],
            ],
        ]);
        $save->assertOk()->assertJsonPath('success', true);

        $row = Assessment::query()->where('batch_key', $batchKey)->where('student_id', $student->id)->first();
        $this->assertNotNull($row);
        $this->assertEquals(24, (float) $row->score);

        $list = $this->getJson('/api/speed-tests');
        $list->assertOk();
        $titles = collect($list->json('data') ?? [])->pluck('title')->all();
        $this->assertContains('CRE speed test', $titles);

        $show = $this->getJson("/api/speed-tests/{$batchKey}");
        $show->assertOk();
        $entries = $show->json('data.entries') ?? [];
        $this->assertNotEmpty($entries);
        $this->assertEquals(24, (float) ($entries[0]['score'] ?? 0));
    }
}
