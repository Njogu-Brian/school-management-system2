<?php

namespace App\Services\Website;

use App\Models\Academics\HomeworkDiary;
use App\Models\Student;

class ParentHomeworkService
{
    public function forStudent(int $studentId, int $limit = 30): array
    {
        abort_unless(auth()->user()?->canAccessStudent($studentId), 403);

        return HomeworkDiary::query()
            ->where('student_id', $studentId)
            ->with(['homework.subject:id,name', 'homework.classroom:id,name', 'homework:id,title,instructions,due_date,subject_id,classroom_id'])
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(fn ($d) => [
                'id' => $d->id,
                'title' => $d->homework?->title,
                'instructions' => $d->homework?->instructions,
                'subject' => $d->homework?->subject?->name,
                'classroom' => $d->homework?->classroom?->name,
                'due_date' => $d->homework?->due_date?->toDateString(),
                'status' => $d->status,
                'score' => $d->score,
                'max_score' => $d->max_score,
                'submitted_at' => $d->submitted_at?->toIso8601String(),
                'teacher_feedback' => $d->teacher_feedback,
            ])
            ->all();
    }

    public function classroomHomeworkTeaser(int $classroomId, int $limit = 6): array
    {
        return HomeworkDiary::query()
            ->whereHas('student', fn ($q) => $q->where('classroom_id', $classroomId))
            ->with(['homework.subject:id,name', 'student:id,first_name,last_name'])
            ->orderByDesc('id')
            ->limit($limit)
            ->get()
            ->map(fn ($d) => [
                'student' => trim(($d->student->first_name ?? '').' '.($d->student->last_name ?? '')),
                'title' => $d->homework?->title,
                'subject' => $d->homework?->subject?->name,
                'due_date' => $d->homework?->due_date?->toDateString(),
                'status' => $d->status,
            ])
            ->all();
    }
}
