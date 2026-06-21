<?php

namespace App\Http\Controllers\Api\Website;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admissions\AdmissionApplicationResource;
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
            'step' => 'required|integer|min:1|max:4',
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
            'dob' => 'nullable|date',
            'gender' => 'nullable|string|max:20',
            'age' => 'nullable|integer|min:3|max:15',
            'desired_class' => 'nullable|string|max:100',
            'previous_school' => 'nullable|string|max:255',
            'medical_notes' => 'nullable|string|max:5000',
            'special_needs' => 'nullable|string|max:5000',
        ]);

        $application = $this->applications->submit($application, $validated);

        $this->analytics->trackConversion('application_complete', '/admissions/apply', [
            'application_no' => $application->application_no,
        ]);

        return response()->json([
            'message' => 'Application submitted successfully.',
            'data' => AdmissionApplicationResource::make($application),
        ]);
    }

    public function track(string $applicationNo): JsonResponse
    {
        $application = AdmissionApplication::query()
            ->where('application_no', $applicationNo)
            ->with('documents')
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
