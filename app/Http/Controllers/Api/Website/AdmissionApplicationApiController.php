<?php

namespace App\Http\Controllers\Api\Website;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admissions\AdmissionApplicationResource;
use App\Models\Academics\Classroom;
use App\Models\Admissions\AdmissionApplication;
use App\Services\Admissions\AdmissionApplicationService;
use App\Services\Website\WebsiteAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdmissionApplicationApiController extends Controller
{
    public function __construct(
        private AdmissionApplicationService $applications,
        private WebsiteAnalyticsService $analytics,
    ) {
    }

    public function options(): JsonResponse
    {
        $classrooms = Classroom::query()->orderBy('name')->get();

        $classrooms = $classrooms->sortBy([
            function ($classroom) {
                $name = strtolower(trim($classroom->name));
                if (str_contains($name, 'creche')) {
                    return 1;
                }
                if (str_contains($name, 'foundation')) {
                    return 2;
                }
                if (preg_match('/^pp1/', $name)) {
                    return 3;
                }
                if (preg_match('/^pp2/', $name)) {
                    return 4;
                }
                if (preg_match('/^grade\s*1(?!\d)/', $name)) {
                    return 5;
                }
                if (preg_match('/^grade\s*2(?!\d)/', $name)) {
                    return 6;
                }
                if (preg_match('/^grade\s*3(?!\d)/', $name)) {
                    return 7;
                }
                if (preg_match('/^grade\s*4(?!\d)/', $name)) {
                    return 8;
                }
                if (preg_match('/^grade\s*5(?!\d)/', $name)) {
                    return 9;
                }
                if (preg_match('/^grade\s*6(?!\d)/', $name)) {
                    return 10;
                }
                if (preg_match('/^grade\s*7(?!\d)/', $name)) {
                    return 11;
                }
                if (preg_match('/^grade\s*8(?!\d)/', $name)) {
                    return 12;
                }
                if (preg_match('/^grade\s*9(?!\d)/', $name)) {
                    return 13;
                }

                return 1000;
            },
            fn ($classroom) => strtolower(trim($classroom->name)),
        ])->values();

        $currentYear = (int) (get_current_academic_year() ?? now()->year);
        $currentTerm = (int) (get_current_term_number() ?? 1);

        $terms = [
            ['year' => $currentYear, 'term' => $currentTerm, 'label' => "Term {$currentTerm} {$currentYear} (Current)"],
        ];
        for ($t = $currentTerm + 1; $t <= 3; $t++) {
            $terms[] = ['year' => $currentYear, 'term' => $t, 'label' => "Term {$t} {$currentYear}"];
        }
        $nextYear = $currentYear + 1;
        for ($t = 1; $t <= 3; $t++) {
            $terms[] = ['year' => $nextYear, 'term' => $t, 'label' => "Term {$t} {$nextYear}"];
        }

        return response()->json([
            'data' => [
                'classrooms' => $classrooms->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])->values(),
                'enrollment_terms' => $terms,
            ],
        ]);
    }

    public function start(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'source' => 'nullable|string|max:100',
        ]);

        $application = $this->applications->createDraft([
            'parent_name' => 'Draft Applicant',
            'phone' => '0000000000',
            'email' => 'draft-'.uniqid().'@pending.local',
            'child_name' => 'Pending',
            'source' => $validated['source'] ?? 'website',
        ]);

        $this->analytics->trackConversion('application_start', '/admissions/apply', [
            'application_no' => $application->application_no,
        ]);

        return response()->json([
            'data' => AdmissionApplicationResource::make($application),
        ], 201);
    }

    public function saveStep(Request $request, string $token): JsonResponse
    {
        $application = $this->resolveDraft($token);

        $validated = $request->validate([
            'step' => 'required|integer|min:1|max:3',
            'data' => 'required|array',
        ]);

        $application = $this->applications->saveProgress(
            $application,
            (int) $validated['step'],
            $validated['data']
        );

        return response()->json(['data' => AdmissionApplicationResource::make($application)]);
    }

    public function uploadDocument(Request $request, string $token): JsonResponse
    {
        $application = $this->resolveDraft($token);

        $validated = $request->validate([
            'document_type' => 'required|in:'.implode(',', \App\Models\Admissions\AdmissionDocument::types()),
            'file' => 'required|file|max:5120|mimes:jpg,jpeg,png,pdf',
        ]);

        $doc = $this->applications->storeDocument(
            $application,
            $request->file('file'),
            $validated['document_type']
        );

        return response()->json([
            'data' => [
                'id' => $doc->id,
                'document_type' => $doc->document_type,
                'url' => $doc->url(),
                'verified' => $doc->verified,
            ],
        ]);
    }

    public function submit(Request $request, string $token): JsonResponse
    {
        $application = $this->resolveDraft($token);

        $validated = $request->validate([
            'parent_name' => 'required|string|max:255',
            'phone' => 'required|string|max:50',
            'email' => 'required|email|max:255',
            'child_name' => 'required|string|max:255',
            'dob' => 'required|date',
            'gender' => 'nullable|string|max:20',
            'preferred_classroom_id' => 'required|exists:classrooms,id',
            'enrollment_year' => 'required|integer|min:2020|max:2035',
            'enrollment_term' => 'required|integer|in:1,2,3',
        ]);

        $classroom = Classroom::find($validated['preferred_classroom_id']);
        $validated['desired_class'] = $classroom?->name;

        $application = $this->applications->submit($application, $validated);

        $this->analytics->trackConversion('application_complete', '/admissions/apply', [
            'application_no' => $application->application_no,
        ]);

        return response()->json([
            'message' => 'Application submitted successfully. A confirmation has been sent to your email and phone.',
            'data' => AdmissionApplicationResource::make($application),
        ]);
    }

    public function track(string $applicationNo): JsonResponse
    {
        $application = AdmissionApplication::query()
            ->where('application_no', $applicationNo)
            ->with(['documents', 'preferredClassroom'])
            ->firstOrFail();

        return response()->json(['data' => AdmissionApplicationResource::make($application)]);
    }

    protected function resolveDraft(string $token): AdmissionApplication
    {
        return AdmissionApplication::query()
            ->where('draft_token', $token)
            ->whereNotIn('status', [
                AdmissionApplication::STATUS_ENROLLED,
                AdmissionApplication::STATUS_REJECTED,
            ])
            ->firstOrFail();
    }
}
