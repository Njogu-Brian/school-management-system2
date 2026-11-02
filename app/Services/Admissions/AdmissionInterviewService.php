<?php

namespace App\Services\Admissions;

use App\Models\Academics\Classroom;
use App\Models\Academics\Subject;
use App\Models\Admissions\AdmissionInterviewScore;
use App\Models\OnlineAdmission;
use App\Services\CbcRationalizedSubjectSyncService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdmissionInterviewService
{
    public function __construct(
        private AdmissionInterviewPolicy $policy,
    ) {
    }

    public function subjectsForAdmission(OnlineAdmission $admission): Collection
    {
        $classroomId = $admission->preferred_classroom_id ?: $admission->classroom_id;
        $classroom = $classroomId
            ? Classroom::withoutGlobalScope('offered')->find($classroomId)
            : null;

        if ($classroom) {
            $ids = DB::table('classroom_subjects')
                ->where('classroom_id', $classroom->id)
                ->pluck('subject_id')
                ->unique()
                ->values()
                ->all();

            if ($ids !== []) {
                return Subject::whereIn('id', $ids)
                    ->orderBy('name')
                    ->get(['id', 'name', 'code']);
            }

            $level = $this->resolveLevelLabel($classroom->name);
            if ($level) {
                try {
                    $rows = collect(app(CbcRationalizedSubjectSyncService::class)->getCBCSubjectsForLevel($level));
                    if ($rows->isNotEmpty()) {
                        $codes = $rows->pluck('code')->filter()->all();
                        $byCode = Subject::whereIn('code', $codes)->get()->keyBy('code');

                        return $rows->map(function ($r) use ($byCode) {
                            $code = $r['code'] ?? null;
                            $subject = $code ? $byCode->get($code) : null;

                            return (object) [
                                'id' => $subject?->id,
                                'name' => $r['name'] ?? $subject?->name ?? 'Subject',
                                'code' => $code,
                            ];
                        })->values();
                    }
                } catch (\Throwable) {
                    // fall through
                }
            }
        }

        return collect();
    }

    public function recordInterview(OnlineAdmission $admission, array $data, int $userId): OnlineAdmission
    {
        if ($admission->enrolled) {
            throw ValidationException::withMessages([
                'application' => 'This application has already been enrolled.',
            ]);
        }

        $classroom = $admission->preferredClassroom ?: $admission->classroom;
        $policy = $admission->interview_required
            ?: $this->policy->forClassroom($classroom);

        if ($policy === AdmissionInterviewPolicy::NOT_APPLICABLE
            && ($data['outcome'] ?? null) !== 'skipped') {
            throw ValidationException::withMessages([
                'interview' => 'Foundation / beginner classes do not require interviews. Mark as skipped or proceed to enrollment form.',
            ]);
        }

        return DB::transaction(function () use ($admission, $data, $userId, $policy) {
            $scores = $data['scores'] ?? [];
            AdmissionInterviewScore::where('online_admission_id', $admission->id)->delete();

            foreach ($scores as $row) {
                if (! isset($row['subject_name']) && empty($row['subject_id'])) {
                    continue;
                }
                $subjectName = $row['subject_name'] ?? null;
                $subjectCode = $row['subject_code'] ?? null;
                if (! empty($row['subject_id'])) {
                    $subject = Subject::find($row['subject_id']);
                    if ($subject) {
                        $subjectName = $subject->name;
                        $subjectCode = $subject->code;
                    }
                }

                AdmissionInterviewScore::create([
                    'online_admission_id' => $admission->id,
                    'subject_id' => $row['subject_id'] ?? null,
                    'subject_name' => $subjectName ?: 'Subject',
                    'subject_code' => $subjectCode,
                    'score' => $row['score'] ?? null,
                    'out_of' => $row['out_of'] ?? 100,
                    'notes' => $row['notes'] ?? null,
                ]);
            }

            $outcome = $data['outcome'] ?? 'passed';
            $status = $data['application_status']
                ?? ($outcome === 'failed' ? 'under_review' : 'interviewed');

            $admission->update([
                'interview_required' => $policy,
                'interview_date' => $data['interview_date'] ?? now()->toDateString(),
                'interview_notes' => $data['notes'] ?? $admission->interview_notes,
                'interview_outcome' => $outcome,
                'interviewed_by' => $userId,
                'application_status' => $status,
                'pipeline_stage' => 'interview',
                'reviewed_by' => $userId,
                'review_date' => now(),
                'review_notes' => $data['review_notes'] ?? $admission->review_notes,
            ]);

            return $admission->fresh(['interviewScores', 'preferredClassroom', 'classroom']);
        });
    }

    public function assertCanEnroll(OnlineAdmission $admission): void
    {
        $classroom = $admission->preferredClassroom ?: $admission->classroom;
        $policy = $admission->interview_required
            ?: $this->policy->forClassroom($classroom);

        if ($policy === AdmissionInterviewPolicy::REQUIRED
            && ! in_array($admission->interview_outcome, ['passed', 'skipped'], true)
            && $admission->application_status !== 'interviewed'
            && $admission->application_status !== 'ready_to_enroll') {
            throw ValidationException::withMessages([
                'interview' => 'Interview is compulsory for this class. Record the interview before enrolling.',
            ]);
        }

        if ($policy === AdmissionInterviewPolicy::REQUIRED
            && $admission->interview_outcome === 'failed') {
            throw ValidationException::withMessages([
                'interview' => 'Interview outcome is failed. Update the outcome before enrolling.',
            ]);
        }
    }

    protected function resolveLevelLabel(string $name): ?string
    {
        $n = strtolower(trim($name));
        if (str_contains($n, 'foundation')) {
            return 'Foundation';
        }
        if (preg_match('/\bpp\s*1\b|^pp1/', $n)) {
            return 'PP1';
        }
        if (preg_match('/\bpp\s*2\b|^pp2/', $n)) {
            return 'PP2';
        }
        if (preg_match('/\bgrade\s*([1-9])\b/', $n, $m)) {
            return 'Grade '.$m[1];
        }

        return null;
    }
}
