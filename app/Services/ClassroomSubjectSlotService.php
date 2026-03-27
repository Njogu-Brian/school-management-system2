<?php

namespace App\Services;

use App\Models\Academics\Classroom;
use App\Models\Academics\ClassroomSubject;

/**
 * Ensures subject assignments follow class structure: if a classroom has streams,
 * each core subject gets one slot per stream (different teachers per stream).
 * If there are no streams, a single slot with stream_id null is used.
 */
class ClassroomSubjectSlotService
{
    /**
     * @param  array{is_compulsory?: bool, lessons_per_week?: int|null}  $attrs
     * @return int Number of slots created or updated
     */
    public function ensureSlotsForClassroomAndSubject(int $classroomId, int $subjectId, array $attrs): int
    {
        $classroom = Classroom::with(['primaryStreams', 'streams'])->find($classroomId);
        if (! $classroom) {
            return 0;
        }

        $streams = $classroom->allStreams();

        if ($streams->isEmpty()) {
            $this->upsertSlot($classroomId, $subjectId, null, null, null, null, $attrs);

            return 1;
        }

        foreach ($streams as $stream) {
            $this->upsertSlot($classroomId, $subjectId, $stream->id, null, null, null, $attrs);
        }

        return $streams->count();
    }

    /**
     * Upsert a single slot: unique key is (classroom, stream, subject, year, term) — not staff_id.
     * Staff is updated when $staffId is non-null; passing null preserves an existing teacher on update.
     *
     * @param  array{is_compulsory?: bool, lessons_per_week?: int|null}  $attrs
     */
    public function upsertSlot(
        int $classroomId,
        int $subjectId,
        ?int $streamId,
        ?int $staffId,
        ?int $academicYearId,
        ?int $termId,
        array $attrs,
    ): void {
        $q = ClassroomSubject::query()
            ->where('classroom_id', $classroomId)
            ->where('subject_id', $subjectId);

        if ($streamId === null) {
            $q->whereNull('stream_id');
        } else {
            $q->where('stream_id', $streamId);
        }

        if ($academicYearId === null) {
            $q->whereNull('academic_year_id');
        } else {
            $q->where('academic_year_id', $academicYearId);
        }

        if ($termId === null) {
            $q->whereNull('term_id');
        } else {
            $q->where('term_id', $termId);
        }

        $row = $q->first();

        if ($row) {
            $payload = $attrs;
            if ($staffId !== null) {
                $payload['staff_id'] = $staffId;
            }
            $row->update($payload);
        } else {
            ClassroomSubject::create(array_merge([
                'classroom_id' => $classroomId,
                'subject_id' => $subjectId,
                'stream_id' => $streamId,
                'staff_id' => $staffId,
                'academic_year_id' => $academicYearId,
                'term_id' => $termId,
            ], $attrs));
        }
    }

    /**
     * Bulk assign: one slot per stream (or one whole-class slot) with optional shared teacher/year/term.
     *
     * @param  array{is_compulsory?: bool}  $baseAttrs
     */
    public function ensureSlotsWithStaff(
        int $classroomId,
        int $subjectId,
        ?int $staffId,
        ?int $academicYearId,
        ?int $termId,
        array $baseAttrs,
    ): int {
        $classroom = Classroom::with(['primaryStreams', 'streams'])->find($classroomId);
        if (! $classroom) {
            return 0;
        }

        $streams = $classroom->allStreams();

        if ($streams->isEmpty()) {
            $this->upsertSlot($classroomId, $subjectId, null, $staffId, $academicYearId, $termId, $baseAttrs);

            return 1;
        }

        foreach ($streams as $stream) {
            $this->upsertSlot($classroomId, $subjectId, $stream->id, $staffId, $academicYearId, $termId, $baseAttrs);
        }

        return $streams->count();
    }
}
