<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Models\StaffProfileChange;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ProfileChangeController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->get('status'); // null|pending|approved|rejected

        $query = StaffProfileChange::with(['staff','submitter'])
            ->latest();

        if (in_array($status, ['pending','approved','rejected'], true)) {
            $query->where('status', $status);
        }

        $changes = $query->paginate(20);

        return view('hr.Profile_changes.index', compact('changes','status'));
    }

    public function show(StaffProfileChange $change)
    {
        $change->load(['staff','submitter','reviewer']);
        return view('hr.Profile_changes.show', compact('change'));
    }

    public function approve(Request $request, StaffProfileChange $change)
    {
        if ($change->status !== 'pending') {
            return back()->with('error', 'This request is not pending.');
        }

        $change->load('staff');
        $staff = $change->staff;

        // Extract proposed changes
        $proposed = $change->changes ?? [];

        // Build validation rules (only for fields being changed)
        $rules = $this->rulesForApproval($staff, array_keys($proposed));
        $validated = validator($this->pluckNewValues($proposed), $rules)->validate();

        try {
            DB::transaction(function () use ($change, $staff, $validated) {
                // If work_email changed -> sync to users.email too (unique checks handled in rules)
                if (array_key_exists('work_email', $validated)) {
                    /** @var User $user */
                    $user = $staff->user;
                    if ($user) {
                        $user->email = $validated['work_email'];
                        $user->save();
                    }
                }

                // Apply all validated fields to Staff
                foreach ($validated as $field => $value) {
                    $staff->{$field} = $value;
                }
                $staff->save();

                // Mark request approved
                $change->status = 'approved';
                $change->reviewed_by = Auth::id();
                $change->reviewed_at = now();
                $change->review_notes = request('review_notes');
                $change->save();
            });
        } catch (\Throwable $e) {
            return back()->with('error', 'Approve failed: '.$e->getMessage())->withInput();
        }

        return redirect()->route('hr.profile_requests.show', $change->id)
            ->with('success', 'Profile changes approved and applied.');
    }

    public function reject(Request $request, StaffProfileChange $change)
    {
        if ($change->status !== 'pending') {
            return back()->with('error', 'This request is not pending.');
        }

        $request->validate([
            'review_notes' => 'nullable|string|max:1000',
        ]);

        $change->status = 'rejected';
        $change->reviewed_by = Auth::id();
        $change->reviewed_at = now();
        $change->review_notes = $request->review_notes;
        $change->save();

        return redirect()->route('hr.profile_requests.show', $change->id)
            ->with('success', 'Profile changes rejected.');
    }

    /**
     * Build rules only for the fields included in the change request.
     */
    protected function rulesForApproval(Staff $staff, array $fields): array
    {
        $rules = [];

        $all = [
            'work_email'   => ['required','email', Rule::unique('staff','work_email')->ignore($staff->id), Rule::unique('users','email')->ignore($staff->user_id)],
            'personal_email' => ['nullable','email'],
            'phone_number' => ['required','string','max:20'],
            'id_number'    => ['required','string','max:255'],
            'date_of_birth'=> ['nullable','date'],
            'gender'       => ['nullable', Rule::in(['male','female','other'])],
            'marital_status' => ['nullable','string','max:255'],
            'residential_address' => ['nullable','string','max:255'],
            'emergency_contact_name' => ['nullable','string','max:255'],
            'emergency_contact_relationship' => ['nullable','string','max:255'],
            'emergency_contact_phone' => ['nullable','string','max:30'],
            'kra_pin' => ['nullable','string','max:255'],
            'nssf'    => ['nullable','string','max:255'],
            'nhif'    => ['nullable','string','max:255'],
            'bank_name'   => ['nullable','string','max:255'],
            'bank_branch' => ['nullable','string','max:255'],
            'bank_account'=> ['nullable','string','max:255'],
            'department_id'      => ['nullable','exists:departments,id'],
            'job_title_id'       => ['nullable','exists:job_titles,id'],
            'staff_category_id'  => ['nullable','exists:staff_categories,id'],
            'supervisor_id'      => ['nullable','exists:staff,id'],
        ];

        foreach ($fields as $f) {
            if (isset($all[$f])) $rules[$f] = $all[$f];
        }

        return $rules;
    }

    /**
     * Convert changes [{old,new}] → ['field' => newValue, ...]
     */
    protected function pluckNewValues(array $proposed): array
    {
        $out = [];
        foreach ($proposed as $field => $pair) {
            $out[$field] = $pair['new'] ?? null;
        }
        return $out;
    }
}
