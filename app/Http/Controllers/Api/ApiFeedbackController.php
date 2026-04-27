<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AcademicReports\AcademicReportAnswer;
use App\Models\AcademicReports\AcademicReportQuestion;
use App\Models\AcademicReports\AcademicReportSubmission;
use App\Models\AcademicReports\AcademicReportTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ApiFeedbackController extends Controller
{
    protected function assertStaffOnly($user): void
    {
        if (! $user) {
            abort(401);
        }
        if ($user->hasAnyRole(['Parent', 'Guardian', 'Student'])) {
            abort(403, 'Feedback is for staff only.');
        }
    }

    protected function getOrCreateFeedbackTemplate(): AcademicReportTemplate
    {
        $tpl = AcademicReportTemplate::query()->where('slug', 'staff_feedback')->first();
        if ($tpl) {
            return $tpl;
        }

        return DB::transaction(function () {
            $tpl = AcademicReportTemplate::create([
                'slug' => 'staff_feedback',
                'title' => 'Staff Feedback',
                'description' => 'Share feedback with the academic office. You may choose to submit anonymously.',
                'status' => 'published',
                'created_by_user_id' => null,
            ]);

            AcademicReportQuestion::create([
                'template_id' => $tpl->id,
                'type' => 'single_select',
                'label' => 'Feedback category',
                'help_text' => null,
                'is_required' => true,
                'options' => [
                    'options' => [
                        ['label' => 'Teaching & Learning', 'value' => 'teaching_learning'],
                        ['label' => 'Student behaviour', 'value' => 'student_behaviour'],
                        ['label' => 'Assessments & exams', 'value' => 'assessments_exams'],
                        ['label' => 'Resources', 'value' => 'resources'],
                        ['label' => 'Timetable', 'value' => 'timetable'],
                        ['label' => 'Other', 'value' => 'other'],
                    ],
                ],
                'display_order' => 0,
            ]);

            AcademicReportQuestion::create([
                'template_id' => $tpl->id,
                'type' => 'long_text',
                'label' => 'Your feedback',
                'help_text' => 'Be specific. If reporting an issue, include class/subject/date where relevant.',
                'is_required' => true,
                'options' => null,
                'display_order' => 1,
            ]);

            AcademicReportQuestion::create([
                'template_id' => $tpl->id,
                'type' => 'file_upload',
                'label' => 'Attachment (optional)',
                'help_text' => 'Upload a file if it helps explain your feedback.',
                'is_required' => false,
                'options' => null,
                'display_order' => 2,
            ]);

            return $tpl;
        });
    }

    public function template(Request $request)
    {
        $user = $request->user();
        $this->assertStaffOnly($user);

        $tpl = $this->getOrCreateFeedbackTemplate();
        $tpl->load('questions');
        return response()->json(['success' => true, 'data' => $tpl]);
    }

    public function submit(Request $request)
    {
        $user = $request->user();
        $this->assertStaffOnly($user);

        $tpl = $this->getOrCreateFeedbackTemplate();
        $tpl->load('questions');

        $v = $request->validate([
            'is_anonymous' => 'nullable|boolean',
            'answers' => 'required|array|min:1',
            'answers.*.question_id' => [
                'required',
                'integer',
                Rule::exists('academic_report_questions', 'id')->where('template_id', $tpl->id),
            ],
            'answers.*.value_text' => 'nullable|string|max:10000',
            'answers.*.value_json' => 'nullable|array',
        ]);

        $isAnonymous = (bool) ($v['is_anonymous'] ?? false);
        $answers = $v['answers'] ?? [];
        $submittedQids = collect($answers)->pluck('question_id')->map(fn ($id) => (int) $id)->unique()->values()->all();

        foreach ($tpl->questions as $q) {
            if (! $q->is_required) {
                continue;
            }
            if ($q->type === 'file_upload') {
                continue;
            }
            if (! in_array((int) $q->id, $submittedQids, true)) {
                return response()->json(['success' => false, 'message' => 'Please answer all required questions.'], 422);
            }
        }

        $questionsById = $tpl->questions->keyBy('id');

        $submission = null;
        DB::transaction(function () use ($tpl, $user, $isAnonymous, $answers, $questionsById, &$submission) {
            $submission = AcademicReportSubmission::create([
                'template_id' => $tpl->id,
                'submitted_by_user_id' => $isAnonymous ? null : $user->id,
                'is_anonymous' => $isAnonymous,
                'submitted_for' => ['type' => 'feedback'],
            ]);

            foreach ($answers as $a) {
                $qid = (int) $a['question_id'];
                $q = $questionsById->get($qid);
                if (! $q || $q->type === 'file_upload') {
                    continue;
                }
                AcademicReportAnswer::create([
                    'submission_id' => $submission->id,
                    'question_id' => $qid,
                    'value_text' => $a['value_text'] ?? null,
                    'value_json' => $a['value_json'] ?? null,
                ]);
            }
        });

        return response()->json(['success' => true, 'data' => ['id' => $submission->id]], 201);
    }

    public function uploadFile(Request $request, AcademicReportSubmission $submission, AcademicReportQuestion $question)
    {
        $user = $request->user();
        $this->assertStaffOnly($user);

        $tpl = $this->getOrCreateFeedbackTemplate();
        if ((int) $submission->template_id !== (int) $tpl->id || (int) $question->template_id !== (int) $tpl->id) {
            abort(404);
        }
        if ($question->type !== 'file_upload') {
            return response()->json(['success' => false, 'message' => 'This question does not accept files.'], 422);
        }

        $v = $request->validate([
            'file' => 'required|file|max:10240',
        ]);

        $path = $v['file']->store("feedback/submissions/{$submission->id}", ['disk' => config('filesystems.default', 'local')]);

        $answer = AcademicReportAnswer::updateOrCreate(
            ['submission_id' => $submission->id, 'question_id' => $question->id],
            ['file_path' => $path]
        );

        return response()->json(['success' => true, 'data' => ['id' => $answer->id, 'file_path' => $answer->file_path]]);
    }
}

