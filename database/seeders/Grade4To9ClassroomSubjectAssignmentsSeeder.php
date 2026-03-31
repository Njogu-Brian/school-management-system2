<?php

namespace Database\Seeders;

use App\Models\Academics\Classroom;
use App\Models\Academics\ClassroomSubject;
use App\Models\Academics\Stream;
use App\Models\Academics\Subject;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Applies per-class subject teacher assignments for grades 4–9 from
 * database/data/grade4_9_classroom_subject_assignments.json (classroom_subjects.staff_id).
 *
 * Regenerate the JSON after local changes:
 *   php database/scripts/export_grade4_9_assignments.php
 *
 * Note: The global subject↔user pivot `subject_teacher` is separate; this seeder only
 * updates `classroom_subjects`, which powers Academics → Subjects → Assign Teachers.
 */
class Grade4To9ClassroomSubjectAssignmentsSeeder extends Seeder
{
    /**
     * Alternate login emails to try if the JSON email is missing (e.g. typo fixes in prod).
     *
     * @var array<string, list<string>>
     */
    private array $emailAlternates = [
        'r.kemunto@royalkingsshools.sc.ke' => ['r.kemunto@royalkingsschools.sc.ke'],
    ];

    public function run(): void
    {
        $path = database_path('data/grade4_9_classroom_subject_assignments.json');
        if (! is_readable($path)) {
            $this->command?->warn("Grade4To9ClassroomSubjectAssignmentsSeeder: missing {$path}");

            return;
        }

        $rows = json_decode(file_get_contents($path), true);
        if (! is_array($rows)) {
            $this->command?->error('Invalid JSON in grade4_9_classroom_subject_assignments.json');

            return;
        }

        $classrooms = Classroom::query()->get(['id', 'name']);
        $classroomByNorm = [];
        foreach ($classrooms as $c) {
            $classroomByNorm[$this->normClassroomName((string) $c->name)] = $c->id;
        }

        $subjects = Subject::query()->get(['id', 'code']);
        $subjectIdByCode = [];
        foreach ($subjects as $s) {
            $subjectIdByCode[strtoupper(trim((string) $s->code))] = $s->id;
        }

        $applied = 0;
        $skipped = [];

        DB::transaction(function () use ($rows, $classroomByNorm, $subjectIdByCode, &$applied, &$skipped) {
            foreach ($rows as $i => $row) {
                $classroomName = (string) ($row['classroom_name'] ?? '');
                $subjectCode = strtoupper(trim((string) ($row['subject_code'] ?? '')));
                $streamName = $row['stream_name'] ?? null;
                $staffEmail = strtolower(trim((string) ($row['staff_email'] ?? '')));

                $cid = $classroomByNorm[$this->normClassroomName($classroomName)] ?? null;
                $sid = $subjectIdByCode[$subjectCode] ?? null;
                $staffId = $this->resolveStaffId($staffEmail);

                if ($cid === null) {
                    $skipped[] = "#{$i}: classroom not found [{$classroomName}]";

                    continue;
                }
                if ($sid === null) {
                    $skipped[] = "#{$i}: subject code not found [{$subjectCode}]";

                    continue;
                }
                if ($staffId === null) {
                    $skipped[] = "#{$i}: no staff for email [{$staffEmail}]";

                    continue;
                }

                $streamId = null;
                if ($streamName !== null && $streamName !== '') {
                    $streamId = Stream::query()
                        ->where('classroom_id', $cid)
                        ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower(trim((string) $streamName))])
                        ->value('id');
                    if ($streamId === null) {
                        $skipped[] = "#{$i}: stream [{$streamName}] not found for classroom id {$cid}";

                        continue;
                    }
                }

                $yearId = $row['academic_year_id'] ?? null;
                $termId = $row['term_id'] ?? null;
                if ($yearId === '') {
                    $yearId = null;
                }
                if ($termId === '') {
                    $termId = null;
                }

                $attrs = [
                    'staff_id' => $staffId,
                    'is_compulsory' => (bool) ($row['is_compulsory'] ?? true),
                ];
                if (array_key_exists('lessons_per_week', $row) && $row['lessons_per_week'] !== null) {
                    $attrs['lessons_per_week'] = (int) $row['lessons_per_week'];
                }

                ClassroomSubject::updateOrCreate(
                    [
                        'classroom_id' => $cid,
                        'stream_id' => $streamId,
                        'subject_id' => $sid,
                        'academic_year_id' => $yearId,
                        'term_id' => $termId,
                    ],
                    $attrs
                );
                $applied++;
            }
        });

        $this->command?->info("Grade4To9ClassroomSubjectAssignmentsSeeder: updated {$applied} classroom_subjects row(s).");
        foreach (array_slice($skipped, 0, 30) as $msg) {
            $this->command?->warn($msg);
        }
        if (count($skipped) > 30) {
            $this->command?->warn('… and '.(count($skipped) - 30).' more skipped row(s).');
        }
    }

    private function normClassroomName(string $name): string
    {
        return mb_strtoupper(preg_replace('/\s+/', ' ', trim($name)));
    }

    private function resolveStaffId(string $email): ?int
    {
        if ($email === '') {
            return null;
        }

        $uid = User::query()->where('email', $email)->value('id');
        if ($uid === null && isset($this->emailAlternates[$email])) {
            foreach ($this->emailAlternates[$email] as $alt) {
                $uid = User::query()->where('email', $alt)->value('id');
                if ($uid !== null) {
                    break;
                }
            }
        }
        if ($uid === null) {
            return null;
        }

        return Staff::query()->where('user_id', $uid)->value('id');
    }
}
