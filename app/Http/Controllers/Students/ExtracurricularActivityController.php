<?php

namespace App\Http\Controllers\Students;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\StudentExtracurricularActivity;
use App\Models\Votehead;
use App\Models\Term;
use App\Models\AcademicYear;
use App\Http\Requests\StoreExtracurricularActivityRequest;
use App\Services\ActivityBillingService;
use Illuminate\Http\Request;

class ExtracurricularActivityController extends Controller
{
    /**
     * Display a listing of extracurricular activities for a student
     */
    public function index(Request $request, Student $student)
    {
        $activities = $student->extracurricularActivities()
            ->with('supervisor')
            ->orderByDesc('start_date')
            ->paginate(20);

        return view('students.records.activities.index', compact('student', 'activities'));
    }

    /**
     * Show the form for creating a new extracurricular activity
     */
    public function create(Student $student)
    {
        $voteheads = Votehead::where('is_mandatory', false)->orderBy('name')->get();
        $currentTerm = Term::where('is_current', true)->first();
        $currentYear = AcademicYear::where('is_active', true)->first();
        
        return view('students.records.activities.create', compact('student', 'voteheads', 'currentTerm', 'currentYear'));
    }

    /**
     * Store a newly created extracurricular activity
     */
    public function store(StoreExtracurricularActivityRequest $request, Student $student)
    {
        $data = $request->validated();
        $data['student_id'] = $student->id;

        // Set billing term/year if not provided
        if (!isset($data['billing_term'])) {
            $currentTerm = Term::where('is_current', true)->first();
            if ($currentTerm && preg_match('/\d+/', $currentTerm->name, $matches)) {
                $data['billing_term'] = (int) $matches[0];
            }
        }
        if (!isset($data['billing_year'])) {
            $currentYear = AcademicYear::where('is_active', true)->first();
            if ($currentYear) {
                $data['billing_year'] = (int) $currentYear->year;
            }
        }

        $activity = StudentExtracurricularActivity::create($data);

        // Auto-bill if enabled
        if ($activity->auto_bill && $activity->votehead_id) {
            $billingService = new ActivityBillingService();
            $billingService->billActivity($activity);
        }

        return redirect()->route('students.activities.index', $student)
            ->with('success', 'Extracurricular activity created successfully.' . ($activity->auto_bill && $activity->votehead_id ? ' Fee has been billed.' : ''));
    }

    /**
     * Display the specified extracurricular activity
     */
    public function show(Student $student, StudentExtracurricularActivity $activity)
    {
        $activity->load('supervisor');
        return view('students.records.activities.show', compact('student', 'activity'));
    }

    /**
     * Show the form for editing the specified extracurricular activity
     */
    public function edit(Student $student, StudentExtracurricularActivity $activity)
    {
        $voteheads = Votehead::where('is_mandatory', false)->orderBy('name')->get();
        $currentTerm = Term::where('is_current', true)->first();
        $currentYear = AcademicYear::where('is_active', true)->first();
        
        return view('students.records.activities.edit', compact('student', 'activity', 'voteheads', 'currentTerm', 'currentYear'));
    }

    /**
     * Update the specified extracurricular activity
     */
    public function update(StoreExtracurricularActivityRequest $request, Student $student, StudentExtracurricularActivity $activity)
    {
        $oldAutoBill = $activity->auto_bill;
        $oldVoteheadId = $activity->votehead_id;
        
        $activity->update($request->validated());
        $activity->refresh();

        $billingService = new ActivityBillingService();

        // If auto_bill was disabled or votehead removed, unbill
        if (($oldAutoBill && $oldVoteheadId) && (!$activity->auto_bill || !$activity->votehead_id)) {
            $billingService->unbillActivity($activity);
        }
        // If auto_bill was enabled or votehead added, bill
        elseif ($activity->auto_bill && $activity->votehead_id) {
            $billingService->billActivity($activity);
        }
        // If votehead or amount changed, update billing
        elseif ($activity->auto_bill && $activity->votehead_id && ($oldVoteheadId != $activity->votehead_id)) {
            // Unbill old
            if ($oldVoteheadId) {
                $tempActivity = clone $activity;
                $tempActivity->votehead_id = $oldVoteheadId;
                $billingService->unbillActivity($tempActivity);
            }
            // Bill new
            $billingService->billActivity($activity);
        }

        return redirect()->route('students.activities.index', $student)
            ->with('success', 'Extracurricular activity updated successfully.');
    }

    /**
     * Remove the specified extracurricular activity
     */
    public function destroy(Student $student, StudentExtracurricularActivity $activity)
    {
        // Unbill before deleting
        if ($activity->auto_bill && $activity->votehead_id) {
            $billingService = new ActivityBillingService();
            $billingService->unbillActivity($activity);
        }

        $activity->delete();

        return redirect()->route('students.activities.index', $student)
            ->with('success', 'Extracurricular activity deleted successfully.');
    }
}
