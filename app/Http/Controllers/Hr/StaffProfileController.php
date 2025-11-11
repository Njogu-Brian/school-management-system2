<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Models\StaffProfileChange;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

        // Accept all editable profile fields (keep this list in sync with your Staff $fillable)
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
            // Optional HR lookups (user can request these too; admin will approve)
            'department_id'      => 'nullable|exists:departments,id',
            'job_title_id'       => 'nullable|exists:job_titles,id',
            'staff_category_id'  => 'nullable|exists:staff_categories,id',
            'supervisor_id'      => 'nullable|exists:staff,id',
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

        if (empty($changes)) {
            return back()->with('success', 'No changes detected.');
        }

        StaffProfileChange::create([
            'staff_id'     => $staff->id,
            'submitted_by' => $user->id,
            'changes'      => $changes,
            'status'       => 'pending',
        ]);

        return back()->with('success', 'Your changes were submitted and are pending admin approval.');
    }
}
