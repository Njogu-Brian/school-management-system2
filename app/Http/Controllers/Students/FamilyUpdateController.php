<?php

namespace App\Http\Controllers\Students;

use App\Http\Controllers\Controller;
use App\Models\Family;
use App\Models\FamilyUpdateLink;
use App\Models\FamilyUpdateAudit;
use App\Models\Student;
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

        return view('family_update.admin.index', compact('families', 'audits'));
    }

    /**
     * Admin: reset/generate links for all families.
     */
    public function resetAll()
    {
        $families = Family::with('updateLink')->get();
        foreach ($families as $family) {
            if ($family->updateLink) {
                $family->updateLink->update([
                    'token' => FamilyUpdateLink::generateToken(),
                    'is_active' => true,
                    'last_sent_at' => null,
                ]);
            } else {
                FamilyUpdateLink::create([
                    'family_id' => $family->id,
                    'token' => FamilyUpdateLink::generateToken(),
                    'is_active' => true,
                ]);
            }
        }

        return back()->with('success', 'All profile update links have been regenerated.');
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
        $link = FamilyUpdateLink::where('token', $token)->where('is_active', true)->firstOrFail();
        $family = $link->family()->with(['students' => function ($q) {
            $q->where('archive', 0)->with('classroom');
        }, 'students.parent'])->firstOrFail();

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
        $link = FamilyUpdateLink::where('token', $token)->where('is_active', true)->firstOrFail();
        $family = $link->family()->with(['students' => function ($q) {
            $q->where('archive', 0)->with('parent');
        }])->firstOrFail();
        $students = $family->students;
        if ($students->isEmpty()) {
            abort(404);
        }

        $studentIds = $students->pluck('id')->toArray();

        $validated = $request->validate([
            'residential_area' => 'nullable|string|max:255',
            'father_name' => 'nullable|string|max:255',
            'father_phone' => ['nullable','string','max:50','regex:/^[0-9]{4,15}$/'],
            'father_phone_country_code' => 'nullable|string|max:8',
            'father_whatsapp' => ['nullable','string','max:50','regex:/^[0-9]{4,15}$/'],
            'father_email' => 'nullable|email|max:255',
            'mother_name' => 'nullable|string|max:255',
            'mother_phone' => ['nullable','string','max:50','regex:/^[0-9]{4,15}$/'],
            'mother_phone_country_code' => 'nullable|string|max:8',
            'mother_whatsapp' => ['nullable','string','max:50','regex:/^[0-9]{4,15}$/'],
            'mother_email' => 'nullable|email|max:255',
            'guardian_name' => 'nullable|string|max:255',
            'guardian_phone' => ['nullable','string','max:50','regex:/^[0-9]{4,15}$/'],
            'guardian_phone_country_code' => 'nullable|string|max:8',
            'guardian_relationship' => 'nullable|string|max:255',
            'marital_status' => 'nullable|in:married,single_parent,co_parenting',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => ['nullable','string','max:50','regex:/^[0-9]{4,15}$/'],
            'preferred_hospital' => 'nullable|string|max:255',
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

        DB::transaction(function () use ($validated, $family, $students) {
            $audits = [];
            $now = now();
            $source = auth()->check() ? 'admin' : 'public';
            $userId = auth()->id();

            // Update parent info for each student's parent (shared data)
            foreach ($students as $stu) {
                if ($stu->parent) {
                    $parent = $stu->parent;
                    $parentData = [
                        'father_name' => $validated['father_name'] ?? $parent->father_name,
                        'father_phone' => $this->formatPhoneWithCode($validated['father_phone'] ?? $parent->father_phone, $validated['father_phone_country_code'] ?? $parent->father_phone_country_code ?? '+254'),
                        'father_phone_country_code' => $validated['father_phone_country_code'] ?? $parent->father_phone_country_code ?? '+254',
                        'father_whatsapp' => $this->formatPhoneWithCode($validated['father_whatsapp'] ?? $parent->father_whatsapp, $validated['father_phone_country_code'] ?? $parent->father_phone_country_code ?? '+254'),
                        'father_email' => $validated['father_email'] ?? $parent->father_email,
                        'mother_name' => $validated['mother_name'] ?? $parent->mother_name,
                        'mother_phone' => $this->formatPhoneWithCode($validated['mother_phone'] ?? $parent->mother_phone, $validated['mother_phone_country_code'] ?? $parent->mother_phone_country_code ?? '+254'),
                        'mother_phone_country_code' => $validated['mother_phone_country_code'] ?? $parent->mother_phone_country_code ?? '+254',
                        'mother_whatsapp' => $this->formatPhoneWithCode($validated['mother_whatsapp'] ?? $parent->mother_whatsapp, $validated['mother_phone_country_code'] ?? $parent->mother_phone_country_code ?? '+254'),
                        'mother_email' => $validated['mother_email'] ?? $parent->mother_email,
                        'guardian_name' => $validated['guardian_name'] ?? $parent->guardian_name,
                        'guardian_phone' => $this->formatPhoneWithCode($validated['guardian_phone'] ?? $parent->guardian_phone, $validated['guardian_phone_country_code'] ?? $parent->guardian_phone_country_code ?? '+254'),
                        'guardian_phone_country_code' => $validated['guardian_phone_country_code'] ?? $parent->guardian_phone_country_code ?? '+254',
                        'guardian_relationship' => $validated['guardian_relationship'] ?? $parent->guardian_relationship,
                        'marital_status' => $validated['marital_status'] ?? $parent->marital_status,
                    ];

                    foreach ($parentData as $field => $value) {
                        if ($parent->{$field} != $value) {
                            $audits[] = [
                                'family_id' => $family->id,
                                'student_id' => $stu->id,
                                'changed_by_user_id' => $userId,
                                'source' => $source,
                                'field' => 'parent.' . $field,
                                'before' => $parent->{$field},
                                'after' => $value,
                                'created_at' => $now,
                                'updated_at' => $now,
                            ];
                        }
                    }

                    $parent->update($parentData);

                    // Handle parent ID uploads
                    if (request()->hasFile('father_id_document')) {
                        $parent->father_id_document = request()->file('father_id_document')->store('parents/id', 'private');
                    }
                    if (request()->hasFile('mother_id_document')) {
                        $parent->mother_id_document = request()->file('mother_id_document')->store('parents/id', 'private');
                    }
                    $parent->save();
                }
            }

            // Update each student
            foreach ($validated['students'] as $stuData) {
                $student = $students->firstWhere('id', $stuData['id']);
                if (!$student) {
                    continue;
                }
                
                // Normalize gender to lowercase (form uses Male/Female, but we store as lowercase)
                if (isset($stuData['gender'])) {
                    $stuData['gender'] = strtolower(trim($stuData['gender']));
                }
                
                // Normalize DOB - empty string to null
                if (isset($stuData['dob']) && empty($stuData['dob'])) {
                    $stuData['dob'] = null;
                }

                $fieldsToCheck = [
                    'first_name',
                    'middle_name',
                    'last_name',
                    'gender',
                    'dob',
                    'has_allergies',
                    'allergies_notes',
                    'is_fully_immunized',
                    'residential_area',
                    'preferred_hospital',
                    'emergency_contact_name',
                    'emergency_contact_phone',
                ];

                $beforeSnapshot = $student->only($fieldsToCheck);

                $student->update([
                    'first_name' => $stuData['first_name'],
                    'middle_name' => $stuData['middle_name'] ?? null,
                    'last_name' => $stuData['last_name'],
                    'gender' => $stuData['gender'],
                    'dob' => $stuData['dob'] ?? null,
                    'has_allergies' => $stuData['has_allergies'] ?? false,
                    'allergies_notes' => $stuData['allergies_notes'] ?? null,
                    'is_fully_immunized' => $stuData['is_fully_immunized'] ?? false,
                    'residential_area' => $validated['residential_area'] ?? $student->residential_area,
                    'preferred_hospital' => $validated['preferred_hospital'] ?? $student->preferred_hospital,
                    'emergency_contact_name' => $validated['emergency_contact_name'] ?? $student->emergency_contact_name,
                    'emergency_contact_phone' => $this->formatPhoneWithCode(
                        $validated['emergency_contact_phone'] ?? $student->emergency_contact_phone,
                        '+254'
                    ),
                ]);

                $student->refresh();
                foreach ($fieldsToCheck as $field) {
                    if ($beforeSnapshot[$field] != $student->{$field}) {
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

                // File uploads for student
                $fileKeyPhoto = "students.{$student->id}.passport_photo";
                $fileKeyCert = "students.{$student->id}.birth_certificate";
                if (request()->hasFile($fileKeyPhoto)) {
                    if ($student->photo_path) {
                        Storage::disk('private')->delete($student->photo_path);
                    }
                    $student->photo_path = request()->file($fileKeyPhoto)->store('students/photos', 'private');
                }
                if (request()->hasFile($fileKeyCert)) {
                    if ($student->birth_certificate_path) {
                        Storage::disk('private')->delete($student->birth_certificate_path);
                    }
                    $student->birth_certificate_path = request()->file($fileKeyCert)->store('students/documents', 'private');
                }
                $student->save();
            }

            if (!empty($audits)) {
                FamilyUpdateAudit::insert($audits);
            }
        });

        return redirect()->route('family-update.form', $token)
            ->with('success', 'Details updated successfully. You can revisit this link anytime to update again.');
    }

    private function getCountryCodes(): array
    {
        $codes = include resource_path('data/country_codes.php');
        return is_array($codes) ? $codes : [];
    }

    private function formatPhoneWithCode(?string $phone, ?string $code = '+254'): ?string
    {
        if (!$phone) {
            return null;
        }

        $cleanPhone = preg_replace('/\D+/', '', $phone);
        $code = $code && str_starts_with($code, '+') ? $code : '+' . ltrim((string) $code, '+');

        // If phone already starts with country code (with or without plus), keep as is
        if (str_starts_with($phone, '+') || str_starts_with($cleanPhone, ltrim($code, '+'))) {
            return str_starts_with($phone, '+') ? $phone : '+' . $cleanPhone;
        }

        // If starts with 0, drop it then prepend code
        if (str_starts_with($cleanPhone, '0')) {
            $cleanPhone = ltrim($cleanPhone, '0');
        }

        return $code . $cleanPhone;
    }
}

