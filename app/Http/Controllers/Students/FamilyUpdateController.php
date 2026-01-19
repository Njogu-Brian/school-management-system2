<?php

namespace App\Http\Controllers\Students;

use App\Http\Controllers\Controller;
use App\Models\Family;
use App\Models\FamilyUpdateLink;
use App\Models\FamilyUpdateAudit;
use App\Models\Student;
use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FamilyUpdateController extends Controller
{
    /**
     * Admin: list all family update links.
     */
    public function adminIndex()
    {
        $families = Family::with(['updateLink', 'students'])
            ->withCount('students')
            ->orderByDesc('updated_at')
            ->paginate(25);

        $audits = FamilyUpdateAudit::with(['family', 'student', 'user'])
            ->orderByDesc('created_at')
            ->paginate(50);
        
        // Get statistics
        $hasClickCount = \Illuminate\Support\Facades\Schema::hasColumn('family_update_links', 'click_count');
        $hasUpdateCount = \Illuminate\Support\Facades\Schema::hasColumn('family_update_links', 'update_count');
        
        $stats = [
            'total_links' => FamilyUpdateLink::count(),
            'active_links' => FamilyUpdateLink::where('is_active', true)->count(),
            'total_clicks' => $hasClickCount ? FamilyUpdateLink::sum('click_count') : 0,
            'total_updates' => $hasUpdateCount ? FamilyUpdateLink::sum('update_count') : 0,
            'links_with_clicks' => $hasClickCount ? FamilyUpdateLink::where('click_count', '>', 0)->count() : 0,
            'links_with_updates' => $hasUpdateCount ? FamilyUpdateLink::where('update_count', '>', 0)->count() : 0,
        ];

        return view('family_update.admin.index', compact('families', 'audits', 'stats'));
    }

    /**
     * Admin: reset/generate links for all families.
     * Ensures all students have families and all families have active links.
     */
    public function resetAll()
    {
        DB::beginTransaction();
        try {
            // Step 1: Ensure all active students have families
            $studentsWithoutFamilies = Student::where('archive', 0)
                ->where('is_alumni', false)
                ->whereNull('family_id')
                ->get();
            
            $familiesCreated = 0;
            foreach ($studentsWithoutFamilies as $student) {
                // Create a family for this student
                $family = Family::create([
                    'guardian_name' => $student->parent 
                        ? ($student->parent->guardian_name ?? $student->parent->father_name ?? $student->parent->mother_name ?? 'Family ' . $student->admission_number)
                        : 'Family ' . $student->admission_number,
                    'phone' => $student->parent 
                        ? ($student->parent->guardian_phone ?? $student->parent->father_phone ?? $student->parent->mother_phone)
                        : null,
                    'email' => $student->parent 
                        ? ($student->parent->guardian_email ?? $student->parent->father_email ?? $student->parent->mother_email)
                        : null,
                ]);
                
                $student->update(['family_id' => $family->id]);
                $familiesCreated++;
            }
            
            // Step 2: Get all families (including newly created ones)
            $families = Family::with('updateLink')->get();
            $linksCreated = 0;
            $linksReset = 0;
            
            foreach ($families as $family) {
                if ($family->updateLink) {
                    // Reset existing link - generate new token and ensure it's active
                    $family->updateLink->update([
                        'token' => FamilyUpdateLink::generateToken(),
                        'is_active' => true,
                        'last_sent_at' => null,
                    ]);
                    $linksReset++;
                } else {
                    // Create new link for family
                    FamilyUpdateLink::create([
                        'family_id' => $family->id,
                        'token' => FamilyUpdateLink::generateToken(),
                        'is_active' => true,
                    ]);
                    $linksCreated++;
                }
            }
            
            DB::commit();
            
            $message = 'All profile update links have been regenerated.';
            if ($familiesCreated > 0) {
                $message .= " Created {$familiesCreated} new families for students without families.";
            }
            if ($linksCreated > 0) {
                $message .= " Created {$linksCreated} new links.";
            }
            if ($linksReset > 0) {
                $message .= " Reset {$linksReset} existing links.";
            }
            
            return back()->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error resetting profile update links: ' . $e->getMessage());
            return back()->with('error', 'An error occurred while resetting links: ' . $e->getMessage());
        }
    }

    /**
    * Admin: show or create link for a family.
    */
    public function showLink(Family $family)
    {
        $link = $family->updateLink ?: FamilyUpdateLink::create([
            'family_id' => $family->id,
        ]);

        return redirect()->back()->with('info', 'Family update link ready: ' . route('family-update.form', $link->token));
    }

    /**
     * Admin: reset/regenerate link for a family.
     */
    public function reset(Family $family)
    {
        $link = $family->updateLink;
        if ($link) {
            $link->update([
                'token' => FamilyUpdateLink::generateToken(),
                'is_active' => true,
                'last_sent_at' => null,
            ]);
        } else {
            $link = FamilyUpdateLink::create([
                'family_id' => $family->id,
            ]);
        }

        return redirect()->back()->with('success', 'Family update link has been reset.');
    }

    /**
     * Public: show family update form.
     */
    public function publicForm($token)
    {
        $link = FamilyUpdateLink::where('token', $token)->where('is_active', true)->first();
        
        if (!$link) {
            // Check if link exists but is inactive
            $inactiveLink = FamilyUpdateLink::where('token', $token)->first();
            if ($inactiveLink) {
                \Log::warning('FamilyUpdate: Inactive link accessed', [
                    'token' => $token,
                    'link_id' => $inactiveLink->id,
                    'is_active' => $inactiveLink->is_active,
                ]);
                abort(404, 'This profile update link has been deactivated. Please contact the school for a new link.');
            }
            
            \Log::warning('FamilyUpdate: Invalid link token accessed', [
                'token' => $token,
                'token_length' => strlen($token),
            ]);
            abort(404, 'This profile update link is invalid or has expired. Please contact the school for a new link.');
        }
        
        // Track link click/access (if columns exist)
        if (\Illuminate\Support\Facades\Schema::hasColumn('family_update_links', 'click_count')) {
            $isFirstClick = ($link->click_count ?? 0) === 0;
            $link->increment('click_count');
            if ($isFirstClick && \Illuminate\Support\Facades\Schema::hasColumn('family_update_links', 'first_clicked_at')) {
                $link->first_clicked_at = now();
            }
            if (\Illuminate\Support\Facades\Schema::hasColumn('family_update_links', 'last_clicked_at')) {
                $link->last_clicked_at = now();
            }
            $link->save();
        }
        
        $family = $link->family()->with(['students' => function ($q) {
            $q->where('archive', 0)->with(['classroom', 'documents', 'parent']);
        }, 'students.parent.documents'])->firstOrFail();

        $students = $family->students;
        if ($students->isEmpty()) {
            abort(404);
        }

        $countryCodes = $this->getCountryCodes();

        return view('family_update.public_form', compact('link', 'family', 'students', 'countryCodes'));
    }

    /**
     * Public: process submission.
     */
    public function submit(Request $request, $token)
    {
        \Log::info('FamilyUpdate Submit: Method called', [
            'token' => $token,
            'method' => $request->method(),
            'has_csrf' => $request->has('_token'),
            'request_data_keys' => array_keys($request->all()),
            'ip' => $request->ip(),
        ]);

        try {
            $link = FamilyUpdateLink::where('token', $token)->where('is_active', true)->firstOrFail();
            \Log::info('FamilyUpdate Submit: Link found', [
                'link_id' => $link->id,
                'family_id' => $link->family_id,
                'is_active' => $link->is_active,
            ]);

            $family = $link->family()->with(['students' => function ($q) {
                $q->where('archive', 0)->with('parent');
            }])->firstOrFail();
            \Log::info('FamilyUpdate Submit: Family found', ['family_id' => $family->id]);

            $students = $family->students;
            if ($students->isEmpty()) {
                \Log::warning('FamilyUpdate Submit: No students found for family', ['family_id' => $family->id]);
                abort(404);
            }

            \Log::info('FamilyUpdate Submit: Students found', [
                'students_count' => $students->count(),
                'student_ids' => $students->pluck('id')->toArray(),
            ]);

            $studentIds = $students->pluck('id')->toArray();

            \Log::info('FamilyUpdate Submit: Starting validation');
            $validated = $request->validate([
            'residential_area' => 'nullable|string|max:255',
            'father_name' => 'nullable|string|max:255',
            'father_id_number' => 'nullable|string|max:255',
            'father_phone' => ['nullable','string','max:50','regex:/^[0-9]{4,15}$/'],
            'father_phone_country_code' => 'nullable|string|max:8',
            'father_whatsapp' => ['nullable','string','max:50','regex:/^[0-9]{4,15}$/'],
            'father_whatsapp_country_code' => 'nullable|string|max:8',
            'father_email' => 'nullable|email|max:255',
            'mother_name' => 'nullable|string|max:255',
            'mother_id_number' => 'nullable|string|max:255',
            'mother_phone' => ['nullable','string','max:50','regex:/^[0-9]{4,15}$/'],
            'mother_phone_country_code' => 'nullable|string|max:8',
            'mother_whatsapp' => ['nullable','string','max:50','regex:/^[0-9]{4,15}$/'],
            'mother_whatsapp_country_code' => 'nullable|string|max:8',
            'mother_email' => 'nullable|email|max:255',
            'guardian_name' => 'nullable|string|max:255',
            'guardian_phone' => ['nullable','string','max:50','regex:/^[0-9]{4,15}$/'],
            'guardian_phone_country_code' => 'nullable|string|max:8',
            'guardian_relationship' => 'nullable|string|max:255',
            'marital_status' => 'nullable|in:married,single_parent,co_parenting',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => ['nullable','string','max:50','regex:/^[0-9]{4,15}$/'],
            'emergency_phone_country_code' => 'nullable|string|max:8',
            'preferred_hospital' => 'nullable|string|max:255',
            'residential_area' => 'nullable|string|max:255',
            'students' => 'required|array|min:1',
            'students.*.id' => 'required|integer|in:' . implode(',', $studentIds),
            'students.*.first_name' => 'required|string|max:255',
            'students.*.middle_name' => 'nullable|string|max:255',
            'students.*.last_name' => 'required|string|max:255',
            'students.*.gender' => 'required|in:Male,Female',
            'students.*.dob' => 'nullable|date',
            'students.*.has_allergies' => 'nullable|boolean',
            'students.*.allergies_notes' => 'nullable|string',
            'students.*.is_fully_immunized' => 'nullable|boolean',
            'students.*.passport_photo' => 'nullable|image|max:4096',
            'students.*.birth_certificate' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'father_id_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'mother_id_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);
            \Log::info('FamilyUpdate Submit: Validation passed', [
                'validated_keys' => array_keys($validated),
                'students_count' => count($validated['students'] ?? []),
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('FamilyUpdate Submit: Validation failed', [
                'token' => $token,
                'errors' => $e->errors(),
                'input' => $request->except(['_token', '_method']),
            ]);
            throw $e;
        } catch (\Exception $e) {
            \Log::error('FamilyUpdate Submit: Exception during validation', [
                'token' => $token,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }

        try {
            $result = DB::transaction(function () use ($validated, $family, $students, $link) {
                $audits = [];
                $now = now();
                $source = auth()->check() ? 'admin' : 'public';
                $userId = auth()->id();
                
                \Log::info('FamilyUpdate: Starting transaction', [
                    'family_id' => $family->id,
                    'students_count' => count($validated['students'] ?? []),
                    'user_id' => $userId,
                ]);

            // Update parent info for each student's parent (shared data)
            foreach ($students as $stu) {
                \Log::info('FamilyUpdate: Checking parent', [
                    'student_id' => $stu->id,
                    'has_parent' => $stu->parent ? 'yes' : 'no',
                    'parent_id' => $stu->parent ? $stu->parent->id : null,
                ]);
                
                // Get or create parent
                if ($stu->parent) {
                    $parent = $stu->parent;
                } else {
                    // Create parent if doesn't exist
                    \Log::info('FamilyUpdate: Creating parent for student', ['student_id' => $stu->id]);
                    $parent = \App\Models\ParentInfo::create([]);
                    $stu->parent_id = $parent->id;
                    $stu->save();
                    \Log::info('FamilyUpdate: Parent created', ['parent_id' => $parent->id, 'student_id' => $stu->id]);
                }
                
                if ($parent) {
                    // Normalize country codes before using them
                    $fatherCountryCode = $this->normalizeCountryCode($validated['father_phone_country_code'] ?? $parent->father_phone_country_code ?? '+254');
                    $motherCountryCode = $this->normalizeCountryCode($validated['mother_phone_country_code'] ?? $parent->mother_phone_country_code ?? '+254');
                    $guardianCountryCode = $this->normalizeCountryCode($validated['guardian_phone_country_code'] ?? $parent->guardian_phone_country_code ?? '+254');
                    $fatherWhatsappCountryCode = $this->normalizeCountryCode($validated['father_whatsapp_country_code'] ?? $parent->father_whatsapp_country_code ?? $fatherCountryCode);
                    $motherWhatsappCountryCode = $this->normalizeCountryCode($validated['mother_whatsapp_country_code'] ?? $parent->mother_whatsapp_country_code ?? $motherCountryCode);
                    
                    // Build parent update data - only include fields that are in validated array
                    $parentData = [
                        'father_phone_country_code' => $fatherCountryCode,
                        'mother_phone_country_code' => $motherCountryCode,
                        'guardian_phone_country_code' => $guardianCountryCode,
                        'father_whatsapp_country_code' => $fatherWhatsappCountryCode,
                        'mother_whatsapp_country_code' => $motherWhatsappCountryCode,
                    ];
                    
                    // Father fields
                    if (array_key_exists('father_name', $validated)) {
                        $parentData['father_name'] = $validated['father_name'] ?: null;
                    }
                    if (array_key_exists('father_id_number', $validated)) {
                        $parentData['father_id_number'] = $validated['father_id_number'] ?: null;
                    }
                    if (array_key_exists('father_phone', $validated)) {
                        $parentData['father_phone'] = !empty($validated['father_phone']) 
                            ? $this->formatPhoneWithCode($validated['father_phone'], $fatherCountryCode) 
                            : null;
                    }
                    if (array_key_exists('father_whatsapp', $validated)) {
                        $parentData['father_whatsapp'] = !empty($validated['father_whatsapp']) 
                            ? $this->formatPhoneWithCode($validated['father_whatsapp'], $fatherWhatsappCountryCode) 
                            : null;
                    }
                    if (array_key_exists('father_email', $validated)) {
                        $parentData['father_email'] = $validated['father_email'] ?: null;
                    }
                    
                    // Mother fields
                    if (array_key_exists('mother_name', $validated)) {
                        $parentData['mother_name'] = $validated['mother_name'] ?: null;
                    }
                    if (array_key_exists('mother_id_number', $validated)) {
                        $parentData['mother_id_number'] = $validated['mother_id_number'] ?: null;
                    }
                    if (array_key_exists('mother_phone', $validated)) {
                        $parentData['mother_phone'] = !empty($validated['mother_phone']) 
                            ? $this->formatPhoneWithCode($validated['mother_phone'], $motherCountryCode) 
                            : null;
                    }
                    if (array_key_exists('mother_whatsapp', $validated)) {
                        $parentData['mother_whatsapp'] = !empty($validated['mother_whatsapp']) 
                            ? $this->formatPhoneWithCode($validated['mother_whatsapp'], $motherWhatsappCountryCode) 
                            : null;
                    }
                    if (array_key_exists('mother_email', $validated)) {
                        $parentData['mother_email'] = $validated['mother_email'] ?: null;
                    }
                    
                    // Guardian fields
                    if (array_key_exists('guardian_name', $validated)) {
                        $parentData['guardian_name'] = $validated['guardian_name'] ?: null;
                    }
                    if (array_key_exists('guardian_phone', $validated)) {
                        $parentData['guardian_phone'] = !empty($validated['guardian_phone']) 
                            ? $this->formatPhoneWithCode($validated['guardian_phone'], $guardianCountryCode) 
                            : null;
                    }
                    if (array_key_exists('guardian_relationship', $validated)) {
                        $parentData['guardian_relationship'] = $validated['guardian_relationship'] ?: null;
                    }
                    if (array_key_exists('marital_status', $validated)) {
                        $parentData['marital_status'] = $validated['marital_status'] ?: null;
                    }

                    // Get before snapshot for parent
                    $parentBeforeSnapshot = [];
                    foreach ($parentData as $field => $value) {
                        $oldValue = $parent->{$field};
                        // Convert dates/Carbon to string for comparison
                        if ($oldValue instanceof \Carbon\Carbon) {
                            $parentBeforeSnapshot[$field] = $oldValue->toDateString();
                        } elseif ($oldValue instanceof \DateTime) {
                            $parentBeforeSnapshot[$field] = $oldValue->format('Y-m-d');
                        } else {
                            $parentBeforeSnapshot[$field] = $oldValue;
                        }
                    }

                    \Log::info('FamilyUpdate: Updating parent', [
                        'parent_id' => $parent->id,
                        'update_data' => $parentData,
                    ]);
                    
                    $result = $parent->update($parentData);
                    $saveResult = $parent->save(); // Ensure save happens
                    $parent->refresh(); // Refresh to get updated values
                    
                    \Log::info('FamilyUpdate: Parent updated', [
                        'parent_id' => $parent->id,
                        'update_result' => $result,
                        'save_result' => $saveResult,
                    ]);
                    
                    // Compare after update for audit
                    foreach ($parentData as $field => $value) {
                        $afterValue = $parent->{$field};
                        // Convert dates/Carbon to string for comparison
                        if ($afterValue instanceof \Carbon\Carbon) {
                            $afterValue = $afterValue->toDateString();
                        } elseif ($afterValue instanceof \DateTime) {
                            $afterValue = $afterValue->format('Y-m-d');
                        }
                        
                        $beforeValue = $parentBeforeSnapshot[$field] ?? null;
                        
                        // Compare as strings to handle type differences
                        if ((string)$beforeValue !== (string)$afterValue) {
                            $audits[] = [
                                'family_id' => $family->id,
                                'student_id' => $stu->id,
                                'changed_by_user_id' => $userId,
                                'source' => $source,
                                'field' => 'parent.' . $field,
                                'before' => $parentBeforeSnapshot[$field],
                                'after' => $parent->{$field},
                                'created_at' => $now,
                                'updated_at' => $now,
                            ];
                        }
                    }

                    // Handle parent ID uploads - save to Document model
                    if (request()->hasFile('father_id_document')) {
                        $file = request()->file('father_id_document');
                        $path = $file->store('documents', 'public');
                        
                        // Create document record
                        Document::create([
                            'title' => "Father ID Document - " . ($parent->father_name ?: 'Father'),
                            'description' => "Father ID document uploaded via profile update form",
                            'file_path' => $path,
                            'file_name' => $file->getClientOriginalName(),
                            'file_type' => $file->getClientMimeType(),
                            'file_size' => $file->getSize(),
                            'category' => 'parent_id_card',
                            'document_type' => 'id_card',
                            'documentable_type' => \App\Models\ParentInfo::class,
                            'documentable_id' => $parent->id,
                            'version' => 1,
                            'is_active' => true,
                            'uploaded_by' => $userId,
                        ]);
                        
                        // Also update legacy father_id_document for backward compatibility
                        if ($parent->father_id_document) {
                            Storage::disk('private')->delete($parent->father_id_document);
                        }
                        $parent->father_id_document = $path;
                    }
                    
                    if (request()->hasFile('mother_id_document')) {
                        $file = request()->file('mother_id_document');
                        $path = $file->store('documents', 'public');
                        
                        // Create document record
                        Document::create([
                            'title' => "Mother ID Document - " . ($parent->mother_name ?: 'Mother'),
                            'description' => "Mother ID document uploaded via profile update form",
                            'file_path' => $path,
                            'file_name' => $file->getClientOriginalName(),
                            'file_type' => $file->getClientMimeType(),
                            'file_size' => $file->getSize(),
                            'category' => 'parent_id_card',
                            'document_type' => 'id_card',
                            'documentable_type' => \App\Models\ParentInfo::class,
                            'documentable_id' => $parent->id,
                            'version' => 1,
                            'is_active' => true,
                            'uploaded_by' => $userId,
                        ]);
                        
                        // Also update legacy mother_id_document for backward compatibility
                        if ($parent->mother_id_document) {
                            Storage::disk('private')->delete($parent->mother_id_document);
                        }
                        $parent->mother_id_document = $path;
                    }
                    
                    $parent->save();
                }
            }

            // Update each student - reload from database to ensure fresh data
            foreach ($validated['students'] as $stuData) {
                // Reload student from database to ensure we have latest data
                // Use withArchived to bypass global scope in case student is archived
                $student = Student::withArchived()->find($stuData['id']);
                if (!$student) {
                    \Log::warning('FamilyUpdate: Student not found', [
                        'student_id' => $stuData['id'],
                    ]);
                    continue;
                }
                
                // Prepare update data - always use validated values when they exist
                $updateData = [
                    'first_name' => $stuData['first_name'],
                    'last_name' => $stuData['last_name'],
                ];
                
                // Middle name
                if (array_key_exists('middle_name', $stuData)) {
                    $updateData['middle_name'] = $stuData['middle_name'] ?: null;
                }
                
                // Gender - normalize to lowercase (form uses Male/Female, but we store as lowercase)
                if (isset($stuData['gender'])) {
                    $updateData['gender'] = strtolower(trim($stuData['gender']));
                }
                
                // DOB - normalize empty string to null, but always update if key exists
                if (array_key_exists('dob', $stuData)) {
                    if (!empty($stuData['dob'])) {
                        $updateData['dob'] = $stuData['dob'];
                    } else {
                        $updateData['dob'] = null;
                    }
                }
                
                // Checkboxes - only update if present in validated data
                if (array_key_exists('has_allergies', $stuData)) {
                    $updateData['has_allergies'] = (bool)$stuData['has_allergies'];
                }
                
                if (array_key_exists('is_fully_immunized', $stuData)) {
                    $updateData['is_fully_immunized'] = (bool)$stuData['is_fully_immunized'];
                }
                
                // Allergies notes
                if (array_key_exists('allergies_notes', $stuData)) {
                    $updateData['allergies_notes'] = $stuData['allergies_notes'] ?: null;
                }
                
                // Fields from top-level validated array
                if (array_key_exists('residential_area', $validated)) {
                    $updateData['residential_area'] = $validated['residential_area'] ?: null;
                }
                
                if (array_key_exists('preferred_hospital', $validated)) {
                    $updateData['preferred_hospital'] = $validated['preferred_hospital'] ?: null;
                }
                
                if (array_key_exists('emergency_contact_name', $validated)) {
                    $updateData['emergency_contact_name'] = $validated['emergency_contact_name'] ?: null;
                }
                
                if (array_key_exists('emergency_contact_phone', $validated)) {
                    if (!empty($validated['emergency_contact_phone'])) {
                        $updateData['emergency_contact_phone'] = $this->formatPhoneWithCode(
                            $validated['emergency_contact_phone'],
                            $this->normalizeCountryCode($validated['emergency_phone_country_code'] ?? '+254')
                        );
                    } else {
                        $updateData['emergency_contact_phone'] = null;
                    }
                }

                // Ensure DOB is properly formatted if it's a string (before snapshot)
                if (isset($updateData['dob']) && is_string($updateData['dob']) && !empty($updateData['dob'])) {
                    try {
                        $updateData['dob'] = \Carbon\Carbon::parse($updateData['dob'])->toDateString();
                    } catch (\Exception $e) {
                        // If parsing fails, keep original value
                    }
                }
                
                $fieldsToCheck = array_keys($updateData);
                // Get before snapshot - convert dates to strings for comparison
                $beforeSnapshot = [];
                foreach ($fieldsToCheck as $field) {
                    $value = $student->{$field};
                    // Convert dates/Carbon to string for comparison
                    if ($value instanceof \Carbon\Carbon) {
                        $beforeSnapshot[$field] = $value->toDateString();
                    } elseif ($value instanceof \DateTime) {
                        $beforeSnapshot[$field] = $value->format('Y-m-d');
                    } else {
                        $beforeSnapshot[$field] = $value;
                    }
                }
                
                \Log::info('FamilyUpdate: Updating student', [
                    'student_id' => $student->id,
                    'update_data' => $updateData,
                ]);
                
                // Perform the update - ensure it actually saves
                $result = $student->update($updateData);
                
                if (!$result) {
                    \Log::error('FamilyUpdate: Student update returned false', [
                        'student_id' => $student->id,
                        'update_data' => $updateData,
                    ]);
                }
                
                // Explicitly save to ensure changes are persisted
                $saveResult = $student->save();
                
                if (!$saveResult) {
                    \Log::error('FamilyUpdate: Student save returned false', [
                        'student_id' => $student->id,
                    ]);
                }
                
                // Refresh student to get updated values from database
                $student->refresh();
                
                \Log::info('FamilyUpdate: Student updated', [
                    'student_id' => $student->id,
                    'first_name' => $student->first_name,
                    'last_name' => $student->last_name,
                    'gender' => $student->gender,
                    'dob' => $student->dob,
                ]);
                
                // Compare before and after, convert dates for comparison
                foreach ($fieldsToCheck as $field) {
                    $afterValue = $student->{$field};
                    // Convert dates/Carbon to string for comparison
                    if ($afterValue instanceof \Carbon\Carbon) {
                        $afterValue = $afterValue->toDateString();
                    } elseif ($afterValue instanceof \DateTime) {
                        $afterValue = $afterValue->format('Y-m-d');
                    }
                    
                    $beforeValue = $beforeSnapshot[$field] ?? null;
                    
                    // Compare as strings to handle date differences
                    if ((string)$beforeValue !== (string)$afterValue) {
                        $audits[] = [
                            'family_id' => $family->id,
                            'student_id' => $student->id,
                            'changed_by_user_id' => $userId,
                            'source' => $source,
                            'field' => 'student.' . $field,
                            'before' => $beforeSnapshot[$field],
                            'after' => $student->{$field},
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                }

                // File uploads for student - save to Document model
                $fileKeyPhoto = "students.{$student->id}.passport_photo";
                $fileKeyCert = "students.{$student->id}.birth_certificate";
                
                if (request()->hasFile($fileKeyPhoto)) {
                    $file = request()->file($fileKeyPhoto);
                    $path = $file->store('documents', 'public');
                    
                    // Create document record
                    Document::create([
                        'title' => "Passport Photo - {$student->first_name} {$student->last_name}",
                        'description' => "Passport photo uploaded via profile update form",
                        'file_path' => $path,
                        'file_name' => $file->getClientOriginalName(),
                        'file_type' => $file->getClientMimeType(),
                        'file_size' => $file->getSize(),
                        'category' => 'student_profile_photo',
                        'document_type' => 'photo',
                        'documentable_type' => Student::class,
                        'documentable_id' => $student->id,
                        'version' => 1,
                        'is_active' => true,
                        'uploaded_by' => $userId,
                    ]);
                    
                    // Also update legacy photo_path for backward compatibility
                    if ($student->photo_path) {
                        Storage::disk('public')->delete($student->photo_path);
                    }
                    $student->photo_path = $path;
                }
                
                if (request()->hasFile($fileKeyCert)) {
                    $file = request()->file($fileKeyCert);
                    $path = $file->store('documents', 'public');
                    
                    // Create document record
                    Document::create([
                        'title' => "Birth Certificate - {$student->first_name} {$student->last_name}",
                        'description' => "Birth certificate uploaded via profile update form",
                        'file_path' => $path,
                        'file_name' => $file->getClientOriginalName(),
                        'file_type' => $file->getClientMimeType(),
                        'file_size' => $file->getSize(),
                        'category' => 'student_birth_certificate',
                        'document_type' => 'birth_certificate',
                        'documentable_type' => Student::class,
                        'documentable_id' => $student->id,
                        'version' => 1,
                        'is_active' => true,
                        'uploaded_by' => $userId,
                    ]);
                    
                    // Also update legacy birth_certificate_path for backward compatibility
                    if ($student->birth_certificate_path) {
                        Storage::disk('private')->delete($student->birth_certificate_path);
                    }
                    $student->birth_certificate_path = $path;
                }
                
                $student->save();
                
                // Ensure changes are persisted immediately
                $student->refresh();
            }

            // Always insert audits if we have any, even if empty
            if (!empty($audits)) {
                try {
                    FamilyUpdateAudit::insert($audits);
                } catch (\Exception $e) {
                    // Log error but don't fail the transaction
                    \Log::error('Failed to insert family update audits: ' . $e->getMessage());
                }
            }
            
            // Track update count on the link if we have any student updates
            if (!empty($validated['students'])) {
                $link->increment('update_count');
                $link->last_updated_at = now();
                $link->save();
            }
            
            \Log::info('FamilyUpdate: Transaction completed', [
                'audits_count' => count($audits),
                'family_id' => $family->id,
            ]);
        });
        
        } catch (\Exception $e) {
            \Log::error('FamilyUpdate: Exception during update', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return redirect()->route('family-update.form', $token)
                ->with('error', 'An error occurred while updating. Please try again.');
        }

        // Clear cached relationships - the redirect will reload fresh data from database
        $family->unsetRelation('students');
        
        \Log::info('FamilyUpdate Submit: Update completed successfully', [
            'token' => $token,
            'family_id' => $family->id,
            'students_updated' => $students->count(),
        ]);

        // Track update count (if column exists)
        if (\Illuminate\Support\Facades\Schema::hasColumn('family_update_links', 'update_count')) {
            $link->increment('update_count');
            if (\Illuminate\Support\Facades\Schema::hasColumn('family_update_links', 'last_updated_at')) {
                $link->last_updated_at = now();
            }
            $link->save();
        }
        
        return redirect()->route('family-update.form', $token)
            ->with('success', 'Details updated successfully. You can revisit this link anytime to update again.');
    }

    private function getCountryCodes(): array
    {
        $codes = include resource_path('data/country_codes.php');
        return is_array($codes) ? $codes : [];
    }

    /**
     * Normalize country code (e.g., +ke, ke, KE -> +254)
     */
    private function normalizeCountryCode(?string $code): string
    {
        if (!$code) {
            return '+254';
        }
        $code = trim($code);
        // Handle +ke, ke, KE, +KE
        $codeLower = strtolower($code);
        if ($codeLower === '+ke' || $codeLower === 'ke') {
            return '+254';
        }
        // Ensure it starts with +
        if (!str_starts_with($code, '+')) {
            return '+' . ltrim($code, '+');
        }
        return $code;
    }

    private function formatPhoneWithCode(?string $phone, ?string $code = '+254'): ?string
    {
        if (!$phone) {
            return null;
        }

        // Normalize country code first (convert +KE to +254)
        $code = $this->normalizeCountryCode($code);
        
        // Check if phone already contains +KE and replace it
        if (stripos($phone, '+KE') !== false) {
            $phone = str_ireplace('+KE', '', $phone);
        }
        
        $cleanPhone = preg_replace('/\D+/', '', $phone);
        $cleanCode = ltrim($code, '+');

        // If phone already starts with country code (with or without plus), keep as is
        if (str_starts_with($phone, '+') && str_starts_with($phone, '+' . $cleanCode)) {
            return $phone;
        }
        
        if (str_starts_with($cleanPhone, $cleanCode)) {
            return '+' . $cleanPhone;
        }

        // If starts with 0, drop it then prepend code
        if (str_starts_with($cleanPhone, '0')) {
            $cleanPhone = ltrim($cleanPhone, '0');
        }

        return $code . $cleanPhone;
    }
}

