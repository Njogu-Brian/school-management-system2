<?php

namespace Tests\Feature\Console;

use App\Models\Attendance;
use App\Models\ClassTeacherAssignment;
use App\Models\Staff;
use App\Models\Student;
use App\Models\Term;
use App\Models\User;
use App\Notifications\CustomAppMessageNotification;
use App\Services\TeacherReminderAudienceService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TeacherAttendanceReminderCommandsTest extends TestCase
{
    protected function setUp(): void
    {
        $driver = getenv('DB_CONNECTION') ?: 'mysql';
        if ($driver === 'mysql') {
            $this->markTestSkipped('Skipping on mysql: RefreshDatabase migration graph fails locally. Use sqlite for API tests.');
        }

        parent::setUp();
        Notification::fake();
        Http::fake([
            'https://exp.host/--/api/v2/push/send' => Http::response(['data' => []], 200),
        ]);
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Teacher']);
    }

    protected function seedHomeroomWithStudents(string $date): array
    {
        Carbon::setTestNow(Carbon::parse($date.' 10:00:00', 'Africa/Nairobi'));

        Term::factory()->create([
            'opening_date' => Carbon::parse($date)->subMonth()->toDateString(),
            'closing_date' => Carbon::parse($date)->addMonth()->toDateString(),
        ]);

        $classroom = $this->createClassroom();
        $teacher = $this->createTeacher();
        $staff = Staff::where('user_id', $teacher->id)->first();
        ClassTeacherAssignment::create([
            'classroom_id' => $classroom->id,
            'stream_id' => null,
            'staff_id' => $staff->id,
        ]);

        $studentA = $this->createStudent([
            'classroom_id' => $classroom->id,
            'archive' => 0,
            'is_alumni' => false,
        ]);
        $studentB = $this->createStudent([
            'classroom_id' => $classroom->id,
            'archive' => 0,
            'is_alumni' => false,
        ]);

        return compact('teacher', 'staff', 'classroom', 'studentA', 'studentB', 'date');
    }

    public function test_audience_includes_class_teacher_with_unmarked_students(): void
    {
        $ctx = $this->seedHomeroomWithStudents('2026-09-09'); // Wednesday
        $rows = app(TeacherReminderAudienceService::class)
            ->classTeachersWithUnmarkedStudents($ctx['date']);

        $this->assertTrue($rows->contains(fn ($row) => $row->user->id === $ctx['teacher']->id));
        $this->assertSame(2, (int) $rows->firstWhere(fn ($r) => $r->user->id === $ctx['teacher']->id)->unmarked_count);
    }

    public function test_audience_excludes_when_all_students_marked(): void
    {
        $ctx = $this->seedHomeroomWithStudents('2026-09-09');
        foreach ([$ctx['studentA'], $ctx['studentB']] as $student) {
            Attendance::create([
                'student_id' => $student->id,
                'date' => $ctx['date'],
                'status' => 'present',
                'marked_by' => $ctx['teacher']->id,
                'marked_at' => now(),
            ]);
        }

        $rows = app(TeacherReminderAudienceService::class)
            ->classTeachersWithUnmarkedStudents($ctx['date']);

        $this->assertFalse($rows->contains(fn ($row) => $row->user->id === $ctx['teacher']->id));
    }

    public function test_nine_am_command_notifies_class_teacher_with_unmarked(): void
    {
        $ctx = $this->seedHomeroomWithStudents('2026-09-09');

        Artisan::call('reminders:class-teacher-attendance');

        Notification::assertSentTo($ctx['teacher'], CustomAppMessageNotification::class);
    }

    public function test_two_pm_command_notifies_remaining_unmarked(): void
    {
        $ctx = $this->seedHomeroomWithStudents('2026-09-09');
        Attendance::create([
            'student_id' => $ctx['studentA']->id,
            'date' => $ctx['date'],
            'status' => 'present',
            'marked_by' => $ctx['teacher']->id,
            'marked_at' => now(),
        ]);

        Artisan::call('reminders:class-teacher-unmarked-attendance');

        Notification::assertSentTo($ctx['teacher'], CustomAppMessageNotification::class);
    }

    public function test_unrelated_teacher_is_not_in_audience(): void
    {
        $ctx = $this->seedHomeroomWithStudents('2026-09-09');
        $other = $this->createTeacher();

        $rows = app(TeacherReminderAudienceService::class)
            ->classTeachersWithUnmarkedStudents($ctx['date']);
        $ids = $rows->map(fn ($r) => $r->user->id)->all();

        $this->assertContains($ctx['teacher']->id, $ids);
        $this->assertNotContains($other->id, $ids);
    }
}
