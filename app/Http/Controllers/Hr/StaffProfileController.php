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

        // Accept only editable profile fields (HR lookups are admin-only)
        $rules = [
            'work_email'   => 'required|email|unique:staff,work_email,' . $staff->id,
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

        // Compute diff (old vs new)
        $interesting = array_keys($rules);
        $changes = [];
        foreach ($interesting as $field) {
            $old = $staff->{$field};
            $new = $data[$field] ?? null;
            // normalize empties
            if (($old ?? null) != ($new ?? null)) {
                $changes[$field] = ['old' => $old, 'new' => $new];
            }
        }

        // Handle photo upload separately (apply immediately, no approval needed)
        if ($request->hasFile('photo')) {
            // Delete old photo if exists
            if ($staff->photo && Storage::disk('public')->exists($staff->photo)) {
                Storage::disk('public')->delete($staff->photo);
            }
            
            // Store new photo
            $photoPath = $request->file('photo')->store('staff_photos', 'public');
            $staff->photo = $photoPath;
            $staff->save();
        }

        if (empty($changes)) {
            if ($request->hasFile('photo')) {
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
}
