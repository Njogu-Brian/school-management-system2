<?php

namespace App\Http\Controllers\Students;

use App\Http\Controllers\Controller;
use App\Services\Students\StudentDuplicateDetector;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentDuplicateReportController extends Controller
{
    public function __construct(private StudentDuplicateDetector $detector)
    {
    }

    public function index(): View
    {
        $studentGroups = $this->detector->findExistingStudentDuplicateGroups();
        $applicationMatches = $this->detector->findApplicationsMatchingStudents();
        $duplicateApplications = $this->detector->findDuplicateOpenApplications();

        return view('students.duplicate_report', [
            'studentGroups' => $studentGroups,
            'applicationMatches' => $applicationMatches,
            'duplicateApplications' => $duplicateApplications,
        ]);
    }

    public function check(Request $request): JsonResponse
    {
        $candidate = $request->only([
            'first_name',
            'middle_name',
            'last_name',
            'dob',
            'gender',
            'nemis_number',
            'knec_assessment_number',
            'admission_number',
        ]);

        $matches = $this->detector->findAllMatches($candidate);

        return response()->json([
            'matches' => $matches->map->toArray()->values(),
            'has_high_confidence' => $matches->contains(fn ($m) => $m->isHighConfidence()),
            'message' => $matches->isEmpty() ? null : $this->detector->blockingMessage($matches),
        ]);
    }
}
