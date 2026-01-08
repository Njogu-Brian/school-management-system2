<?php

namespace App\Http\Controllers\Students;

use App\Http\Controllers\Controller;
use App\Models\OnlineAdmission;
use App\Models\Student;
use App\Models\ParentInfo;
use App\Models\Academics\Classroom;
use App\Models\Academics\Stream;
use App\Models\StudentCategory;
use App\Models\Trip;
use App\Models\DropOffPoint;
use App\Services\TransportFeeService;
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
        $allClassrooms = Classroom::all();
        
        // Get IDs before sorting
        $crecheId = $allClassrooms->firstWhere('name', 'like', '%creche%')?->id;
        $foundationId = $allClassrooms->firstWhere('name', 'like', '%foundation%')?->id;
        
        // Sort classrooms: Creche, Foundation, PP1, PP2, Grade 1-9
        // Use multi-level sort: first by category weight, then alphabetically within category
        $classrooms = $allClassrooms->sortBy([
            function($classroom) {
                $name = strtolower(trim($classroom->name));
                
                // Define order weights - match at start of string
                if (strpos($name, 'creche') !== false) return 1;
                if (strpos($name, 'foundation') !== false) return 2;
                if (preg_match('/^pp1/', $name)) return 3;
                if (preg_match('/^pp2/', $name)) return 4;
                if (preg_match('/^grade\s*1(?!\d)/', $name)) return 5;
                if (preg_match('/^grade\s*2(?!\d)/', $name)) return 6;
                if (preg_match('/^grade\s*3(?!\d)/', $name)) return 7;
                if (preg_match('/^grade\s*4(?!\d)/', $name)) return 8;
                if (preg_match('/^grade\s*5(?!\d)/', $name)) return 9;
                if (preg_match('/^grade\s*6(?!\d)/', $name)) return 10;
                if (preg_match('/^grade\s*7(?!\d)/', $name)) return 11;
                if (preg_match('/^grade\s*8(?!\d)/', $name)) return 12;
                if (preg_match('/^grade\s*9(?!\d)/', $name)) return 13;
                
                // For other classrooms, sort alphabetically after the main sequence
                return 1000;
            },
            function($classroom) {
                // Secondary sort: alphabetically by name
                return strtolower(trim($classroom->name));
            }
        ])->values();
        
        $dropOffPoints = DropOffPoint::orderBy('name')->get();
        $countryCodes = $this->getCountryCodes();
        
        return view('online_admissions.public_form', compact('classrooms', 'dropOffPoints', 'countryCodes','crecheId','foundationId'));
    }

    /**
     * Store public admission application
     */
    public function storePublicApplication(Request $request)
    {
        if ($request->input('drop_off_point_id') === 'other') {
            $request->merge(['drop_off_point_id' => null]);
        }

        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'dob' => 'required|date',
            'gender' => 'required|in:Male,Female',
            'preferred_classroom_id' => 'nullable|exists:classrooms,id',
            'marital_status' => 'nullable|in:married,single_parent,co_parenting',
            'father_name' => 'nullable|string|max:255',
            'father_phone' => ['nullable','string','max:50','regex:/^[0-9]{4,15}$/'],
            'father_phone_country_code' => 'nullable|string|max:8',
            'father_email' => 'nullable|email|max:255',
            'mother_name' => 'nullable|string|max:255',
            'mother_phone' => ['nullable','string','max:50','regex:/^[0-9]{4,15}$/'],
            'mother_phone_country_code' => 'nullable|string|max:8',
            'mother_email' => 'nullable|email|max:255',
            'mother_whatsapp' => ['nullable','string','max:50','regex:/^[0-9]{4,15}$/'],
            'guardian_name' => 'nullable|string|max:255',
            'guardian_phone' => ['nullable','string','max:50','regex:/^[0-9]{4,15}$/'],
            'guardian_phone_country_code' => 'nullable|string|max:8',
            'guardian_relationship' => 'nullable|string|max:255',
            'passport_photo' => 'nullable|image|max:2048',
            'birth_certificate' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'father_id_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'mother_id_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'transport_needed' => 'nullable|boolean',
            'drop_off_point_id' => 'nullable|exists:drop_off_points,id',
            'drop_off_point_other' => 'nullable|string|max:255',
            'has_allergies' => 'nullable|boolean',
            'allergies_notes' => 'nullable|string',
            'is_fully_immunized' => 'nullable|boolean',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => ['nullable','string','max:50','regex:/^[0-9]{4,15}$/'],
            'residential_area' => 'required|string|max:255',
            'preferred_hospital' => 'nullable|string|max:255',
            'previous_school' => 'nullable|string|max:255',
            'transfer_reason' => 'nullable|string|max:255',
        ]);

        $data = $request->only([
            'first_name','middle_name','last_name','dob','gender',
            'preferred_classroom_id','transport_needed','drop_off_point_id','drop_off_point_other',
            'has_allergies','allergies_notes','is_fully_immunized',
            'emergency_contact_name','residential_area','preferred_hospital',
            'marital_status','guardian_relationship','previous_school','transfer_reason',
            'father_name','father_email','mother_name','mother_email','guardian_name','guardian_email',
            'father_phone_country_code','mother_phone_country_code','guardian_phone_country_code'
        ]);
        
        // Normalize gender to lowercase (form uses Male/Female, but we store as lowercase)
        if (isset($data['gender'])) {
            $data['gender'] = strtolower(trim($data['gender']));
        }
        
        // Normalize DOB - empty string to null
        if (isset($data['dob']) && empty($data['dob'])) {
            $data['dob'] = null;
        }
        // Default country codes to Kenya
        $data['father_phone_country_code'] = $data['father_phone_country_code'] ?? '+254';
        $data['mother_phone_country_code'] = $data['mother_phone_country_code'] ?? '+254';
        $data['guardian_phone_country_code'] = $data['guardian_phone_country_code'] ?? '+254';
        $data['application_status'] = 'pending';
        $data['application_date'] = now();
        $data['application_source'] = 'online';
        $data['classroom_id'] = null; // do not assign class from applicant
        $data['stream_id'] = null;

        // Normalize phones with country codes
        $data['father_phone'] = $this->formatPhoneWithCode($request->father_phone, $data['father_phone_country_code']);
        $data['father_whatsapp'] = $this->formatPhoneWithCode($request->father_whatsapp, $data['father_phone_country_code']);
        $data['mother_phone'] = $this->formatPhoneWithCode($request->mother_phone, $data['mother_phone_country_code']);
        $data['mother_whatsapp'] = $this->formatPhoneWithCode($request->mother_whatsapp, $data['mother_phone_country_code']);
        $data['guardian_phone'] = $this->formatPhoneWithCode($request->guardian_phone, $data['guardian_phone_country_code']);
        $data['guardian_whatsapp'] = $this->formatPhoneWithCode($request->guardian_whatsapp, $data['guardian_phone_country_code']);
        $data['emergency_contact_phone'] = $this->formatPhoneWithCode($request->emergency_contact_phone, '+254');

        // Require at least one parent/guardian name + phone
        $parentName = $request->father_name ?: $request->mother_name ?: $request->guardian_name;
        $parentPhone = $request->father_phone ?: $request->mother_phone ?: $request->guardian_phone;
        if (!$parentName || !$parentPhone) {
            return back()->withInput()->with('error', 'At least one parent/guardian name and phone is required.');
        }

        // Note: Stream selection is handled by admin during approval process, not during public submission

        // Handle file uploads
        if ($request->hasFile('passport_photo')) {
            $data['passport_photo'] = $request->file('passport_photo')->store('admissions/photos', 'public');
        }
        if ($request->hasFile('birth_certificate')) {
            $data['birth_certificate'] = $request->file('birth_certificate')->store('admissions/documents', 'private');
        }
        if ($request->hasFile('father_id_document')) {
            $data['father_id_document'] = $request->file('father_id_document')->store('admissions/documents', 'private');
        }
        if ($request->hasFile('mother_id_document')) {
            $data['mother_id_document'] = $request->file('mother_id_document')->store('admissions/documents', 'private');
        }

        OnlineAdmission::create($data);

        if ($request->filled('save_add_another')) {
            return redirect()->route('online-admissions.public-form')
                ->with('success', 'Application submitted. You can add another student now.');
        }

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
        $categories = StudentCategory::orderBy('name')->get();
        $trips = Trip::orderBy('trip_name')->get();
        $dropOffPoints = DropOffPoint::orderBy('name')->get();
        $countryCodes = $this->getCountryCodes();
        
        return view('online_admissions.show', compact('admission', 'classrooms', 'streams', 'categories', 'trips', 'dropOffPoints', 'countryCodes'));
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
    public function approve(Request $request, OnlineAdmission $admission)
    {
        if ($admission->enrolled) {
            return redirect()->back()->with('error', 'This application has already been processed.');
        }

        if ($request->input('drop_off_point_id') === 'other') {
            $request->merge(['drop_off_point_id' => null]);
        }

        $validated = $request->validate([
            'classroom_id' => 'required|exists:classrooms,id',
            'stream_id' => 'nullable|exists:streams,id',
            'category_id' => 'required|exists:student_categories,id',
            'trip_id' => 'nullable|exists:trips,id',
            'drop_off_point_id' => 'nullable|exists:drop_off_points,id',
            'drop_off_point_other' => 'nullable|string|max:255',
            'transport_fee_amount' => 'nullable|numeric|min:0',
            'has_allergies' => 'nullable|boolean',
            'allergies_notes' => 'nullable|string',
            'is_fully_immunized' => 'nullable|boolean',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => ['nullable','string','max:50','regex:/^[0-9]{4,15}$/'],
            'residential_area' => 'required|string|max:255',
            'preferred_hospital' => 'nullable|string|max:255',
            'marital_status' => 'nullable|in:married,single_parent,co_parenting',
        ]);

        DB::transaction(function () use ($admission, $validated) {
            // Require stream if classroom has streams
            $classroomHasStreams = Classroom::withCount('streams')->find($validated['classroom_id'])?->streams_count > 0;
            if ($classroomHasStreams && empty($validated['stream_id'])) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'stream_id' => 'Please select a stream for the chosen classroom.'
                ]);
            }

            // Create parent info
            $parent = ParentInfo::create([
                'father_name' => $admission->father_name,
                'father_phone' => $this->formatPhoneWithCode($admission->father_phone, $admission->father_phone_country_code ?? '+254'),
                'father_phone_country_code' => $admission->father_phone_country_code ?? '+254',
                'father_whatsapp' => $this->formatPhoneWithCode($admission->father_whatsapp, $admission->father_phone_country_code ?? '+254'),
                'father_email' => $admission->father_email,
                'father_id_number' => $admission->father_id_number,
                'father_id_document' => $admission->father_id_document,
                'mother_name' => $admission->mother_name,
                'mother_phone' => $this->formatPhoneWithCode($admission->mother_phone, $admission->mother_phone_country_code ?? '+254'),
                'mother_phone_country_code' => $admission->mother_phone_country_code ?? '+254',
                'mother_whatsapp' => $this->formatPhoneWithCode($admission->mother_whatsapp, $admission->mother_phone_country_code ?? '+254'),
                'mother_email' => $admission->mother_email,
                'mother_id_number' => $admission->mother_id_number,
                'mother_id_document' => $admission->mother_id_document,
                'guardian_name' => $admission->guardian_name,
                'guardian_phone' => $this->formatPhoneWithCode($admission->guardian_phone, $admission->guardian_phone_country_code ?? '+254'),
                'guardian_phone_country_code' => $admission->guardian_phone_country_code ?? '+254',
                'guardian_relationship' => $admission->guardian_relationship,
                'marital_status' => $admission->marital_status,
            ]);

            // Generate admission number
            $admissionNumber = $this->generateNextAdmissionNumber();

            $dropOffPointLabel = null;
            if (!empty($validated['drop_off_point_other'])) {
                $dropOffPointLabel = $validated['drop_off_point_other'];
            } elseif (!empty($validated['drop_off_point_id'])) {
                $dropOffPointLabel = optional(DropOffPoint::find($validated['drop_off_point_id']))->name;
            }

            // Copy passport photo from admissions to students/photos if it exists
            $photoPath = null;
            if ($admission->passport_photo && Storage::disk('public')->exists($admission->passport_photo)) {
                // Copy the file to students/photos directory
                $newPath = 'students/photos/' . basename($admission->passport_photo);
                if (Storage::disk('public')->copy($admission->passport_photo, $newPath)) {
                    $photoPath = $newPath;
                } else {
                    // If copy fails, try to move it
                    $photoPath = $admission->passport_photo;
                }
            }

            // Create student
            $student = Student::create([
                'admission_number' => $admissionNumber,
                'first_name' => $admission->first_name,
                'middle_name' => $admission->middle_name,
                'last_name' => $admission->last_name,
                'dob' => $admission->dob,
                'gender' => $admission->gender,
                'classroom_id' => $validated['classroom_id'],
                'stream_id' => $validated['stream_id'] ?? null,
                'category_id' => $validated['category_id'],
                'trip_id' => $validated['trip_id'] ?? null,
                'drop_off_point_id' => $validated['drop_off_point_id'] ?? null,
                'drop_off_point_other' => $validated['drop_off_point_other'] ?? null,
                'drop_off_point' => $dropOffPointLabel,
                'parent_id' => $parent->id,
                'nemis_number' => $admission->nemis_number,
                'knec_assessment_number' => $admission->knec_assessment_number,
                'marital_status' => $admission->marital_status,
                'photo_path' => $photoPath,
                // Medical & emergency
                'has_allergies' => isset($validated['has_allergies']) ? (bool)$validated['has_allergies'] : (bool)$admission->has_allergies,
                'allergies_notes' => $validated['allergies_notes'] ?? $admission->allergies_notes,
                'is_fully_immunized' => isset($validated['is_fully_immunized']) ? (bool)$validated['is_fully_immunized'] : (bool)$admission->is_fully_immunized,
                'emergency_contact_name' => $validated['emergency_contact_name'] ?? $admission->emergency_contact_name,
                'emergency_contact_phone' => $this->formatPhoneWithCode(
                    $validated['emergency_contact_phone'] ?? $admission->emergency_contact_phone,
                    '+254'
                ),
                'preferred_hospital' => $validated['preferred_hospital'] ?? $admission->preferred_hospital,
                'residential_area' => $validated['residential_area'] ?? $admission->residential_area,
                'status' => 'active',
                'admission_date' => now(),
            ]);

            if (!empty($validated['transport_fee_amount'])) {
                TransportFeeService::upsertFee([
                    'student_id' => $student->id,
                    'amount' => $validated['transport_fee_amount'],
                    'drop_off_point_id' => $validated['drop_off_point_id'] ?? null,
                    'drop_off_point_name' => $dropOffPointLabel,
                    'source' => 'online_admission',
                    'note' => 'Captured during online admission approval',
                ]);
            }

            // Update admission record
            $admission->update([
                'enrolled' => true,
                'application_status' => 'accepted',
                'reviewed_by' => auth()->id(),
                'review_date' => now(),
                'classroom_id' => $validated['classroom_id'],
                'stream_id' => $validated['stream_id'] ?? null,
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
            Storage::disk('private')->delete($admission->birth_certificate);
        }
        $admission->delete();

        return redirect()->route('online-admissions.index')
            ->with('success', 'Application deleted successfully.');
    }

    /**
     * Generate next admission number with RKS prefix
     */
    private function generateNextAdmissionNumber(): string
    {
        $lastNumber = Student::max('admission_number');
        
        if (!$lastNumber) {
            return 'RKS001';
        }
        
        // Extract numeric part from admission number (handles RKS724, 724, RKS 724, etc.)
        if (preg_match('/(\d+)/', $lastNumber, $matches)) {
            $numericPart = (int) $matches[1];
            $nextNumber = $numericPart + 1;
            return 'RKS' . $nextNumber;
        }
        
        // Fallback if no number found
        return 'RKS001';
    }

    /**
     * Full international country codes list (dialing) without flags, Kenya default appears first.
     */
    private function getCountryCodes(): array
    {
        $codes = [
            ['code' => '+254', 'label' => 'Kenya (+254)'],
            ['code' => '+1', 'label' => 'United States / Canada (+1)'],
            ['code' => '+44', 'label' => 'United Kingdom (+44)'],
            ['code' => '+27', 'label' => 'South Africa (+27)'],
            ['code' => '+234', 'label' => 'Nigeria (+234)'],
            ['code' => '+256', 'label' => 'Uganda (+256)'],
            ['code' => '+255', 'label' => 'Tanzania (+255)'],
            ['code' => '+91', 'label' => 'India (+91)'],
            ['code' => '+971', 'label' => 'United Arab Emirates (+971)'],
            ['code' => '+61', 'label' => 'Australia (+61)'],
            ['code' => '+64', 'label' => 'New Zealand (+64)'],
            ['code' => '+81', 'label' => 'Japan (+81)'],
            ['code' => '+86', 'label' => 'China (+86)'],
            ['code' => '+49', 'label' => 'Germany (+49)'],
            ['code' => '+33', 'label' => 'France (+33)'],
            ['code' => '+39', 'label' => 'Italy (+39)'],
            ['code' => '+34', 'label' => 'Spain (+34)'],
            ['code' => '+46', 'label' => 'Sweden (+46)'],
            ['code' => '+47', 'label' => 'Norway (+47)'],
            ['code' => '+45', 'label' => 'Denmark (+45)'],
            ['code' => '+31', 'label' => 'Netherlands (+31)'],
            ['code' => '+32', 'label' => 'Belgium (+32)'],
            ['code' => '+41', 'label' => 'Switzerland (+41)'],
            ['code' => '+52', 'label' => 'Mexico (+52)'],
            ['code' => '+55', 'label' => 'Brazil (+55)'],
            ['code' => '+54', 'label' => 'Argentina (+54)'],
            ['code' => '+51', 'label' => 'Peru (+51)'],
            ['code' => '+20', 'label' => 'Egypt (+20)'],
            ['code' => '+212', 'label' => 'Morocco (+212)'],
            ['code' => '+974', 'label' => 'Qatar (+974)'],
            ['code' => '+966', 'label' => 'Saudi Arabia (+966)'],
            ['code' => '+962', 'label' => 'Jordan (+962)'],
            ['code' => '+961', 'label' => 'Lebanon (+961)'],
            ['code' => '+90', 'label' => 'Turkey (+90)'],
            ['code' => '+94', 'label' => 'Sri Lanka (+94)'],
            ['code' => '+880', 'label' => 'Bangladesh (+880)'],
            ['code' => '+92', 'label' => 'Pakistan (+92)'],
            ['code' => '+60', 'label' => 'Malaysia (+60)'],
            ['code' => '+65', 'label' => 'Singapore (+65)'],
            ['code' => '+63', 'label' => 'Philippines (+63)'],
            ['code' => '+62', 'label' => 'Indonesia (+62)'],
            ['code' => '+82', 'label' => 'South Korea (+82)'],
            ['code' => '+853', 'label' => 'Macau (+853)'],
            ['code' => '+852', 'label' => 'Hong Kong (+852)'],
            ['code' => '+7', 'label' => 'Russia (+7)'],
            ['code' => '+380', 'label' => 'Ukraine (+380)'],
            ['code' => '+48', 'label' => 'Poland (+48)'],
            ['code' => '+420', 'label' => 'Czech Republic (+420)'],
            ['code' => '+421', 'label' => 'Slovakia (+421)'],
            ['code' => '+36', 'label' => 'Hungary (+36)'],
            ['code' => '+40', 'label' => 'Romania (+40)'],
            ['code' => '+30', 'label' => 'Greece (+30)'],
            ['code' => '+386', 'label' => 'Slovenia (+386)'],
            ['code' => '+385', 'label' => 'Croatia (+385)'],
            ['code' => '+43', 'label' => 'Austria (+43)'],
            ['code' => '+372', 'label' => 'Estonia (+372)'],
            ['code' => '+371', 'label' => 'Latvia (+371)'],
            ['code' => '+370', 'label' => 'Lithuania (+370)'],
            ['code' => '+56', 'label' => 'Chile (+56)'],
            ['code' => '+57', 'label' => 'Colombia (+57)'],
            ['code' => '+58', 'label' => 'Venezuela (+58)'],
            ['code' => '+507', 'label' => 'Panama (+507)'],
            ['code' => '+506', 'label' => 'Costa Rica (+506)'],
            ['code' => '+66', 'label' => 'Thailand (+66)'],
            ['code' => '+84', 'label' => 'Vietnam (+84)'],
        ];

        // Separate Kenya from the rest, then sort the rest alphabetically
        $kenya = collect($codes)->firstWhere('code', '+254');
        $others = collect($codes)->reject(fn($item) => $item['code'] === '+254')
            ->sortBy('label')
            ->values()
            ->all();

        return $kenya ? array_merge([$kenya], $others) : $others;
    }

    /**
     * Normalize country code (e.g., +ke, ke -> +254)
     */
    protected function normalizeCountryCode(?string $code): string
    {
        if (!$code) {
            return '+254';
        }
        $code = trim($code);
        // Handle +ke or ke
        if (strtolower($code) === '+ke' || strtolower($code) === 'ke') {
            return '+254';
        }
        // Ensure it starts with +
        if (!str_starts_with($code, '+')) {
            return '+' . ltrim($code, '+');
        }
        return $code;
    }

    /**
     * Normalize a phone number by combining country code and local digits.
     */
    protected function formatPhoneWithCode(?string $number, ?string $code = '+254'): ?string
    {
        if (!$number) {
            return null;
        }
        // Ensure code is properly formatted (handle +ke or ke to +254)
        $code = $this->normalizeCountryCode($code);
        $cleanCode = ltrim(trim($code ?? '+254'), '+');
        $cleanNumber = preg_replace('/\D+/', '', $number);
        $cleanNumber = ltrim($cleanNumber, '0');
        if ($cleanNumber === '') {
            return null;
        }
        return '+' . $cleanCode . $cleanNumber;
    }
}
