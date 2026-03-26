<?php

namespace App\Services;

use App\Models\Academics\Stream;
use App\Models\OnlineAdmission;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Keeps stream links consistent across classrooms, teachers, subjects, finance, and academics
 * when streams are edited or removed from a class, or when a stream is deleted entirely.
 */
class StreamLifecycleService
{
    /**
     * Primary classroom plus additional classrooms (pivot). Matches Assign Teachers / Student validation.
     */
    public function collectLinkedClassroomIds(Stream $stream): array
    {
        $stream->loadMissing(['classroom', 'classrooms']);

        $ids = [];
        if ($stream->classroom_id) {
            $ids[] = (int) $stream->classroom_id;
        }
        foreach ($stream->classrooms as $c) {
            $ids[] = (int) $c->id;
        }

        return array_values(array_unique(array_filter($ids)));
    }

    /**
     * After primary or pivot classrooms change: remove data tied to stream+classroom pairs that no longer exist.
     *
     * @param  array<int>  $removedClassroomIds  Classrooms that are no longer linked to this stream
     */
    public function propagateWhenClassroomsRemoved(Stream $stream, array $removedClassroomIds): void
    {
        $removedClassroomIds = array_values(array_unique(array_map('intval', array_filter($removedClassroomIds))));
        if ($removedClassroomIds === []) {
            return;
        }

        $sid = (int) $stream->id;

        DB::transaction(function () use ($sid, $removedClassroomIds) {
            DB::table('stream_teacher')
                ->where('stream_id', $sid)
                ->whereIn('classroom_id', $removedClassroomIds)
                ->delete();

            if (Schema::hasTable('classroom_subjects')) {
                DB::table('classroom_subjects')
                    ->where('stream_id', $sid)
                    ->whereIn('classroom_id', $removedClassroomIds)
                    ->delete();
            }

            if (Schema::hasTable('fee_structures')) {
                DB::table('fee_structures')
                    ->where('stream_id', $sid)
                    ->whereIn('classroom_id', $removedClassroomIds)
                    ->delete();
            }

            Student::withArchived()
                ->where('stream_id', $sid)
                ->whereIn('classroom_id', $removedClassroomIds)
                ->update(['stream_id' => null]);

            if (Schema::hasTable('online_admissions')) {
                DB::table('online_admissions')
                    ->where('stream_id', $sid)
                    ->whereIn('classroom_id', $removedClassroomIds)
                    ->update(['stream_id' => null]);
            }

            $this->nullifyStreamOnScopedRows('exams', $sid, $removedClassroomIds);
            $this->nullifyStreamOnScopedRows('report_cards', $sid, $removedClassroomIds);
            $this->nullifyStreamOnScopedRows('homework', $sid, $removedClassroomIds);
            $this->nullifyStreamOnScopedRows('diaries', $sid, $removedClassroomIds);
            $this->nullifyStreamOnScopedRows('communication_logs', $sid, $removedClassroomIds);

            if (Schema::hasTable('student_academic_history')) {
                DB::table('student_academic_history')
                    ->where('stream_id', $sid)
                    ->whereIn('classroom_id', $removedClassroomIds)
                    ->update(['stream_id' => null]);
            }
        });
    }

    /**
     * Delete a stream and clear dependent rows that are not handled by FK alone (subject slots, etc.).
     */
    public function deleteStreamWithCascade(Stream $stream): void
    {
        $id = (int) $stream->id;

        DB::transaction(function () use ($stream, $id) {
            Student::withArchived()->where('stream_id', $id)->update(['stream_id' => null]);

            if (Schema::hasTable('online_admissions')) {
                DB::table('online_admissions')->where('stream_id', $id)->update(['stream_id' => null]);
            }

            DB::table('stream_teacher')->where('stream_id', $id)->delete();

            if (Schema::hasTable('classroom_subjects')) {
                DB::table('classroom_subjects')->where('stream_id', $id)->delete();
            }

            $stream->classrooms()->detach();
            $stream->delete();
        });
    }

    /**
     * Remove orphan rows for every stream (stream_teacher, classroom_subjects, fee_structures) and fix students/admissions.
     *
     * @return array<string, int> Counts of fixes applied
     */
    public function repairOrphanStreamData(bool $dryRun = false): array
    {
        $counts = [
            'stream_teacher_deleted' => 0,
            'classroom_subjects_deleted' => 0,
            'fee_structures_deleted' => 0,
            'students_cleared' => 0,
            'online_admissions_cleared' => 0,
        ];

        $streams = Stream::with('classrooms')->get();

        foreach ($streams as $stream) {
            $allowed = $this->collectLinkedClassroomIds($stream);
            if ($allowed === []) {
                continue;
            }

            if (!$dryRun) {
                $counts['stream_teacher_deleted'] += DB::table('stream_teacher')
                    ->where('stream_id', $stream->id)
                    ->whereNotIn('classroom_id', $allowed)
                    ->delete();

                if (Schema::hasTable('classroom_subjects')) {
                    $counts['classroom_subjects_deleted'] += DB::table('classroom_subjects')
                        ->where('stream_id', $stream->id)
                        ->whereNotIn('classroom_id', $allowed)
                        ->delete();
                }

                if (Schema::hasTable('fee_structures')) {
                    $counts['fee_structures_deleted'] += DB::table('fee_structures')
                        ->where('stream_id', $stream->id)
                        ->whereNotIn('classroom_id', $allowed)
                        ->delete();
                }
            } else {
                $counts['stream_teacher_deleted'] += DB::table('stream_teacher')
                    ->where('stream_id', $stream->id)
                    ->whereNotIn('classroom_id', $allowed)
                    ->count();

                if (Schema::hasTable('classroom_subjects')) {
                    $counts['classroom_subjects_deleted'] += DB::table('classroom_subjects')
                        ->where('stream_id', $stream->id)
                        ->whereNotIn('classroom_id', $allowed)
                        ->count();
                }

                if (Schema::hasTable('fee_structures')) {
                    $counts['fee_structures_deleted'] += DB::table('fee_structures')
                        ->where('stream_id', $stream->id)
                        ->whereNotIn('classroom_id', $allowed)
                        ->count();
                }
            }
        }

        // Students: stream no longer applies to their current classroom
        $query = Student::withArchived()
            ->whereNotNull('stream_id')
            ->whereNotNull('classroom_id');

        $evaluateStudent = function (Student $student) use ($streams): bool {
            $stream = $streams->firstWhere('id', $student->stream_id);
            if (!$stream) {
                return true;
            }
            $stream->loadMissing(['classroom', 'classrooms']);
            $allowed = $this->collectLinkedClassroomIds($stream);
            if ($allowed === []) {
                return true;
            }

            return !in_array((int) $student->classroom_id, $allowed, true);
        };

        if ($dryRun) {
            $counts['students_cleared'] = $query->get()->filter($evaluateStudent)->count();
        } else {
            foreach ($query->cursor() as $student) {
                if ($evaluateStudent($student)) {
                    $student->update(['stream_id' => null]);
                    $counts['students_cleared']++;
                }
            }
        }

        if (Schema::hasTable('online_admissions')) {
            $admissions = OnlineAdmission::whereNotNull('stream_id')
                ->whereNotNull('classroom_id')
                ->get();

            foreach ($admissions as $admission) {
                $stream = $streams->firstWhere('id', $admission->stream_id);
                if (!$stream) {
                    $invalid = true;
                } else {
                    $stream->loadMissing(['classroom', 'classrooms']);
                    $allowed = $this->collectLinkedClassroomIds($stream);
                    $invalid = $allowed === [] || !in_array((int) $admission->classroom_id, $allowed, true);
                }
                if ($invalid) {
                    if ($dryRun) {
                        $counts['online_admissions_cleared']++;
                    } else {
                        $admission->update(['stream_id' => null]);
                        $counts['online_admissions_cleared']++;
                    }
                }
            }
        }

        return $counts;
    }

    private function nullifyStreamOnScopedRows(string $table, int $streamId, array $classroomIds): void
    {
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'stream_id')) {
            return;
        }
        if (!Schema::hasColumn($table, 'classroom_id')) {
            return;
        }

        DB::table($table)
            ->where('stream_id', $streamId)
            ->whereIn('classroom_id', $classroomIds)
            ->update(['stream_id' => null]);
    }
}
