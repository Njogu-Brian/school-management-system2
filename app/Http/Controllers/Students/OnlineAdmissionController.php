<?php

namespace App\Http\Controllers\Students;

use App\Http\Controllers\Controller;
use App\Models\OnlineAdmission;
use App\Models\Student;
use App\Models\ParentInfo;
use App\Models\Academics\Classroom;
use App\Models\Academics\Stream;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class OnlineAdmissionController extends Controller
{
    /**
     * Display list of online admissions (admin)
     */
    public function index(Request $request)
    {
        $query = OnlineAdmission::with(['reviewedBy', 'classroom', 'stream'])
            ->orderByDesc('application_date');

        // Filter by status
        if ($request->filled('status')) {
            $query->where('application_status', $request->status);
        }

        // Filter by waiting list
        if ($request->boolean('waitlist_only')) {
            $query->where('application_status', 'waitlisted')
                  ->orderBy('waitlist_position');
        }

        $admissions = $query->paginate(20);
        $statuses = ['pending', 'under_review', 'accepted', 'rejected', 'waitlisted'];

        return view('online_admissions.index', compact('admissions', 'statuses'));
    }

    /**
     * Show public admission form (no auth required)
     */
    public function showPublicForm()
    {
        $classrooms = Classroom::orderBy('name')->get();
        $streams = Stream::orderBy('name')->get();
        
        return view('online_admissions.public_form', compact('classrooms', 'streams'));
    }

    /**
     * Store public admission application
     */
    public function storePublicApplication(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'dob' => 'required|date',
            'gender' => 'required|in:Male,Female',
            'classroom_id' => 'nullable|exists:classrooms,id',
            'stream_id' => 'nullable|exists:streams,id',
            'father_name' => 'nullable|string|max:255',
            'father_phone' => 'nullable|string|max:255',
            'father_email' => 'nullable|email|max:255',
            'mother_name' => 'nullable|string|max:255',
            'mother_phone' => 'nullable|string|max:255',
            'mother_email' => 'nullable|email|max:255',
            'guardian_name' => 'nullable|string|max:255',
            'guardian_phone' => 'nullable|string|max:255',
            'guardian_email' => 'nullable|email|max:255',
            'passport_photo' => 'nullable|image|max:2048',
            'birth_certificate' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'parent_id_card' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $data = $request->all();
        $data['application_status'] = 'pending';
        $data['application_date'] = now();
        $data['application_source'] = 'online';

        // Handle file uploads
        if ($request->hasFile('passport_photo')) {
            $data['passport_photo'] = $request->file('passport_photo')->store('admissions/photos', 'public');
        }
        if ($request->hasFile('birth_certificate')) {
            $data['birth_certificate'] = $request->file('birth_certificate')->store('admissions/documents', 'public');
        }
        if ($request->hasFile('parent_id_card')) {
            $data['parent_id_card'] = $request->file('parent_id_card')->store('admissions/documents', 'public');
        }

        OnlineAdmission::create($data);

        return redirect()->route('online-admissions.public-form')
            ->with('success', 'Your application has been submitted successfully! We will review it and contact you soon.');
    }

    /**
     * Show admission details for review
     */
    public function show(OnlineAdmission $admission)
    {
        $admission->load(['reviewedBy', 'classroom', 'stream']);
        $classrooms = Classroom::orderBy('name')->get();
        $streams = Stream::orderBy('name')->get();
        
        return view('online_admissions.show', compact('admission', 'classrooms', 'streams'));
    }

    /**
     * Update admission status (review, accept, reject, waitlist)
     */
    public function updateStatus(Request $request, OnlineAdmission $admission)
    {
        $request->validate([
            'application_status' => 'required|in:pending,under_review,accepted,rejected,waitlisted',
            'review_notes' => 'nullable|string',
            'classroom_id' => 'nullable|exists:classrooms,id',
            'stream_id' => 'nullable|exists:streams,id',
        ]);

        $admission->update([
            'application_status' => $request->application_status,
            'review_notes' => $request->review_notes,
            'reviewed_by' => auth()->id(),
            'review_date' => now(),
            'classroom_id' => $request->classroom_id,
            'stream_id' => $request->stream_id,
        ]);

        // If waitlisted, assign position
        if ($request->application_status === 'waitlisted') {
            $maxPosition = OnlineAdmission::where('application_status', 'waitlisted')
                ->max('waitlist_position') ?? 0;
            $admission->update(['waitlist_position' => $maxPosition + 1]);
        }

        return redirect()->back()->with('success', 'Application status updated successfully.');
    }

    /**
     * Add to waiting list
     */
    public function addToWaitlist(Request $request, OnlineAdmission $admission)
    {
        $maxPosition = OnlineAdmission::where('application_status', 'waitlisted')
            ->max('waitlist_position') ?? 0;

        $admission->update([
            'application_status' => 'waitlisted',
            'waitlist_position' => $maxPosition + 1,
            'reviewed_by' => auth()->id(),
            'review_date' => now(),
            'review_notes' => $request->review_notes ?? 'Added to waiting list',
        ]);

        return redirect()->back()->with('success', 'Application added to waiting list.');
    }

    /**
     * Approve and create student from admission
     */
    public function approve(OnlineAdmission $admission)
    {
        if ($admission->enrolled) {
            return redirect()->back()->with('error', 'This application has already been processed.');
        }

        DB::transaction(function () use ($admission) {
            // Create parent info
            $parent = ParentInfo::create([
                'father_name' => $admission->father_name,
                'father_phone' => $admission->father_phone,
                'father_email' => $admission->father_email,
                'father_id_number' => $admission->father_id_number,
                'mother_name' => $admission->mother_name,
                'mother_phone' => $admission->mother_phone,
                'mother_email' => $admission->mother_email,
                'mother_id_number' => $admission->mother_id_number,
                'guardian_name' => $admission->guardian_name,
                'guardian_phone' => $admission->guardian_phone,
                'guardian_email' => $admission->guardian_email,
                'guardian_id_number' => $admission->guardian_id_number,
            ]);

            // Generate admission number
            $admissionNumber = $this->generateNextAdmissionNumber();

            // Create student
            $student = Student::create([
                'admission_number' => $admissionNumber,
                'first_name' => $admission->first_name,
                'middle_name' => $admission->middle_name,
                'last_name' => $admission->last_name,
                'dob' => $admission->dob,
                'gender' => $admission->gender,
                'classroom_id' => $admission->classroom_id,
                'stream_id' => $admission->stream_id,
                'parent_id' => $parent->id,
                'nemis_number' => $admission->nemis_number,
                'knec_assessment_number' => $admission->knec_assessment_number,
                'status' => 'active',
                'admission_date' => now(),
            ]);

            // Update admission record
            $admission->update([
                'enrolled' => true,
                'application_status' => 'accepted',
                'reviewed_by' => auth()->id(),
                'review_date' => now(),
            ]);
            
            // Charge fees for newly admitted student
            try {
                \App\Services\FeePostingService::chargeFeesForNewStudent($student);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning('Failed to charge fees for new student from online admission: ' . $e->getMessage(), [
                    'student_id' => $student->id,
                    'admission_id' => $admission->id,
                ]);
            }
        });

        return redirect()->route('online-admissions.index')
            ->with('success', 'Student approved and enrolled successfully.');
    }

    /**
     * Transfer from waiting list to admitted
     */
    public function transferFromWaitlist(OnlineAdmission $admission)
    {
        if ($admission->application_status !== 'waitlisted') {
            return redirect()->back()->with('error', 'This application is not on the waiting list.');
        }

        if ($admission->enrolled) {
            return redirect()->back()->with('error', 'This application has already been processed.');
        }

        // Approve the admission (same as approve method)
        $this->approve($admission);

        // Reorder remaining waitlist
        OnlineAdmission::where('application_status', 'waitlisted')
            ->where('waitlist_position', '>', $admission->waitlist_position)
            ->decrement('waitlist_position');

        return redirect()->route('online-admissions.index')
            ->with('success', 'Student transferred from waiting list and enrolled successfully.');
    }

    /**
     * Reject admission
     */
    public function reject(OnlineAdmission $admission)
    {
        $admission->update([
            'application_status' => 'rejected',
            'reviewed_by' => auth()->id(),
            'review_date' => now(),
        ]);

        return redirect()->back()->with('success', 'Application rejected.');
    }

    /**
     * Delete admission
     */
    public function destroy(OnlineAdmission $admission)
    {
        // Delete uploaded files
        if ($admission->passport_photo) {
            Storage::disk('public')->delete($admission->passport_photo);
        }
        if ($admission->birth_certificate) {
            Storage::disk('public')->delete($admission->birth_certificate);
        }
        if ($admission->parent_id_card) {
            Storage::disk('public')->delete($admission->parent_id_card);
        }

        $admission->delete();

        return redirect()->route('online-admissions.index')
            ->with('success', 'Application deleted successfully.');
    }

    /**
     * Generate next admission number
     */
    private function generateNextAdmissionNumber(): string
    {
        $lastNumber = Student::max('admission_number');
        if (!$lastNumber) {
            return '001';
        }
        
        $numericPart = (int) $lastNumber;
        return str_pad((string)($numericPart + 1), 3, '0', STR_PAD_LEFT);
    }
}
