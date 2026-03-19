<?php

namespace App\Console\Commands;

use App\Models\Student;
use App\Models\OnlineAdmission;
use App\Models\Academics\Classroom;
use Illuminate\Console\Command;

class UnassignStreamsFromClassesWithoutStreams extends Command
{
    protected $signature = 'students:unassign-streams-from-classes-without-streams {--dry-run : Show what would be changed without making changes}';

    protected $description = 'Unassign stream_id from students and online admissions in classrooms that have no streams assigned';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        // Get classrooms that have NO streams (primary + pivot)
        $byCount = Classroom::withCount(['primaryStreams', 'streams'])
            ->get()
            ->filter(fn ($c) => ($c->primary_streams_count ?? 0) + ($c->streams_count ?? 0) === 0);

        // Also explicitly include Foundation, Creche, Grade 3-9 (case-insensitive) in case of data inconsistency
        $forced = Classroom::where(function ($q) {
            $q->orWhereRaw('UPPER(name) LIKE ?', ['%FOUNDATION%'])
                ->orWhereRaw('UPPER(name) LIKE ?', ['%CRECHE%']);
            foreach (range(3, 9) as $n) {
                $q->orWhereRaw('(UPPER(name) LIKE ? OR UPPER(name) LIKE ?)', ["%GRADE {$n} %", "%GRADE {$n}"]);
            }
        })->get();

        $classroomsWithoutStreams = $byCount->merge($forced)->unique('id');
        $classroomIds = $classroomsWithoutStreams->pluck('id')->toArray();

        if (empty($classroomIds)) {
            $this->info('All classrooms have streams assigned. Nothing to do.');
            return 0;
        }

        $this->info('Classrooms without streams: ' . $classroomsWithoutStreams->pluck('name')->join(', '));

        // Students in these classrooms with stream_id set
        $students = Student::whereIn('classroom_id', $classroomIds)
            ->whereNotNull('stream_id')
            ->with(['classroom', 'stream'])
            ->get();

        // Online admissions in these classrooms with stream_id set
        $admissions = OnlineAdmission::whereIn('classroom_id', $classroomIds)
            ->whereNotNull('stream_id')
            ->with(['classroom', 'stream'])
            ->get();

        $studentCount = $students->count();
        $admissionCount = $admissions->count();

        if ($studentCount === 0 && $admissionCount === 0) {
            $this->info('No students or admissions with streams in classes without streams. Nothing to do.');
            return 0;
        }

        if ($studentCount > 0) {
            $this->table(
                ['ID', 'Name', 'Classroom', 'Current Stream'],
                $students->map(fn ($s) => [$s->id, $s->full_name, $s->classroom?->name ?? '—', $s->stream?->name ?? '—'])
            );
            if ($dryRun) {
                $this->warn("[DRY RUN] Would unassign stream from {$studentCount} student(s).");
            } else {
                Student::whereIn('id', $students->pluck('id'))->update(['stream_id' => null]);
                $this->info("Unassigned stream from {$studentCount} student(s).");
            }
        }

        if ($admissionCount > 0) {
            $this->table(
                ['ID', 'Name', 'Classroom', 'Current Stream'],
                $admissions->map(fn ($a) => [$a->id, trim($a->first_name . ' ' . $a->last_name), $a->classroom?->name ?? '—', $a->stream?->name ?? '—'])
            );
            if ($dryRun) {
                $this->warn("[DRY RUN] Would unassign stream from {$admissionCount} online admission(s).");
            } else {
                OnlineAdmission::whereIn('id', $admissions->pluck('id'))->update(['stream_id' => null]);
                $this->info("Unassigned stream from {$admissionCount} online admission(s).");
            }
        }

        return 0;
    }
}
