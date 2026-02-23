<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Models\StaffProfileChange;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class StaffProfileController extends Controller
{
    public function show()
    {
        $user  = Auth::user();
        $staff = Staff::with(['department','jobTitle','category','supervisor'])->where('user_id', $user->id)->firstOrFail();

        $pending = StaffProfileChange::where('staff_id', $staff->id)
            ->where('status', 'pending')
            ->latest()
            ->get();

        return view('staff.profile', compact('staff','pending'));
    }

    public function update(Request $request)
    {
        $user  = Auth::user();
        $staff = Staff::where('user_id', $user->id)->firstOrFail();

        // Accept only editable profile fields (HR lookups and work_email are admin-only)
        $rules = [
            'personal_email' => 'nullable|email',
            'phone_number' => 'required|string|max:20',
            'id_number'    => 'required|string|max:255',
            'date_of_birth'=> 'nullable|date',
            'gender'       => 'nullable|in:male,female,other',
            'marital_status' => 'nullable|string|max:255',
            'residential_address' => 'nullable|string|max:255',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_relationship' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:30',
            'kra_pin' => 'nullable|string|max:255',
            'nssf'    => 'nullable|string|max:255',
            'nhif'    => 'nullable|string|max:255',
            'bank_name'   => 'nullable|string|max:255',
            'bank_branch' => 'nullable|string|max:255',
            'bank_account'=> 'nullable|string|max:255',
            'photo' => 'nullable|image|mimes:jpeg,jpg,png|max:2048',
        ];
        $data = $request->validate($rules);

        // Normalize empty strings to null for nullable fields
        $data['date_of_birth'] = !empty($data['date_of_birth']) ? $data['date_of_birth'] : null;
        $data['gender'] = !empty($data['gender']) ? strtolower(trim($data['gender'])) : null;
        $data['marital_status'] = !empty($data['marital_status']) ? $data['marital_status'] : null;
        $data['personal_email'] = !empty($data['personal_email']) ? $data['personal_email'] : null;
        $data['residential_address'] = !empty($data['residential_address']) ? $data['residential_address'] : null;
        $data['emergency_contact_name'] = !empty($data['emergency_contact_name']) ? $data['emergency_contact_name'] : null;
        $data['emergency_contact_relationship'] = !empty($data['emergency_contact_relationship']) ? $data['emergency_contact_relationship'] : null;
        $data['emergency_contact_phone'] = !empty($data['emergency_contact_phone']) ? $data['emergency_contact_phone'] : null;
        $data['kra_pin'] = !empty($data['kra_pin']) ? $data['kra_pin'] : null;
        $data['nssf'] = !empty($data['nssf']) ? $data['nssf'] : null;
        $data['nhif'] = !empty($data['nhif']) ? $data['nhif'] : null;
        $data['bank_name'] = !empty($data['bank_name']) ? $data['bank_name'] : null;
        $data['bank_branch'] = !empty($data['bank_branch']) ? $data['bank_branch'] : null;
        $data['bank_account'] = !empty($data['bank_account']) ? $data['bank_account'] : null;

        $phoneService = app(\App\Services\PhoneNumberService::class);
        $data['phone_number'] = $phoneService->formatWithCountryCode($data['phone_number'] ?? null, '+254');
        $data['emergency_contact_phone'] = $phoneService->formatWithCountryCode($data['emergency_contact_phone'] ?? null, '+254');
        $userId = auth()->id();
        $this->logPhoneNormalization(Staff::class, $staff->id, 'phone_number', $staff->phone_number, $data['phone_number'] ?? null, '+254', 'staff_profile_update', $userId);
        $this->logPhoneNormalization(Staff::class, $staff->id, 'emergency_contact_phone', $staff->emergency_contact_phone, $data['emergency_contact_phone'] ?? null, '+254', 'staff_profile_update', $userId);

        // Handle photo upload separately (apply immediately, no approval needed)
        $photoUpdated = false;
        if ($request->hasFile('photo')) {
            // Delete old photo if exists
            if ($staff->photo && storage_public()->exists($staff->photo)) {
                storage_public()->delete($staff->photo);
            }
            
            // Store new photo
            $photoPath = $request->file('photo')->store('staff_photos', config('filesystems.public_disk', 'public'));
            $staff->photo = $photoPath;
            $staff->save();
            $photoUpdated = true;
        }

        // Compute diff (old vs new) - exclude 'photo' since it's handled separately
        $interesting = array_filter(array_keys($rules), fn($key) => $key !== 'photo');
        $changes = [];
        foreach ($interesting as $field) {
            $old = $staff->{$field};
            $new = $data[$field] ?? null;
            
            // Normalize dates for comparison
            if ($field === 'date_of_birth') {
                $old = $old ? ($old instanceof \Carbon\Carbon ? $old->format('Y-m-d') : $old) : null;
                $new = $new ? (is_string($new) ? $new : ($new instanceof \Carbon\Carbon ? $new->format('Y-m-d') : $new)) : null;
            }
            
            // Normalize gender for comparison (lowercase)
            if ($field === 'gender') {
                $old = $old ? strtolower(trim($old)) : null;
                $new = $new ? strtolower(trim($new)) : null;
            }
            
            // Compare normalized values
            if (($old ?? null) != ($new ?? null)) {
                $changes[$field] = ['old' => $old, 'new' => $new];
            }
        }

        if (empty($changes)) {
            if ($photoUpdated) {
                return back()->with('success', 'Profile photo updated successfully.');
            }
            return back()->with('success', 'No changes detected.');
        }

        StaffProfileChange::create([
            'staff_id'     => $staff->id,
            'submitted_by' => $user->id,
            'changes'      => $changes,
            'status'       => 'pending',
        ]);

        $message = 'Your changes were submitted and are pending admin approval.';
        if ($request->hasFile('photo')) {
            $message .= ' Profile photo has been updated.';
        }

        return back()->with('success', $message);
    }

    private function logPhoneNormalization(
        string $modelType,
        ?int $modelId,
        string $field,
        ?string $oldValue,
        ?string $newValue,
        ?string $countryCode,
        string $source,
        ?int $userId
    ): void {
        app(\App\Services\PhoneNumberNormalizationLogger::class)
            ->logIfChanged($modelType, $modelId, $field, $oldValue, $newValue, $countryCode, $source, $userId);
    }
}
