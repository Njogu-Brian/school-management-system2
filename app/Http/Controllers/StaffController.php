<?php

namespace App\Http\Controllers;

use App\Models\Staff;
use App\Models\StaffMeta;
use App\Models\User;
use App\Models\SystemSetting;
use App\Models\EmailTemplate;
use App\Models\CommunicationTemplate;
use App\Models\Department;
use App\Models\JobTitle;
use App\Models\CustomField;
use App\Models\StaffCategory; // <- renamed model (former StaffRole)
use App\Services\CommunicationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Role;

class StaffController extends Controller
{
    protected $comm;

    public function __construct(CommunicationService $comm)
    {
        $this->comm = $comm;
    }

    public function index()
    {
        // Include HR category, department, jobTitle, supervisor, meta
        $staff = Staff::with(['supervisor', 'meta', 'category', 'department', 'jobTitle'])->get();
        return view('staff.index', compact('staff'));
    }

    public function create()
    {
        $supervisors   = Staff::all();
        $categories    = StaffCategory::all();
        $departments   = Department::all();
        $jobTitles     = JobTitle::all();
        $customFields  = CustomField::where('module', 'staff')->get();
        $spatieRoles   = Role::all();

        return view('staff.create', [
            'staff'        => null,
            'supervisors'  => $supervisors,
            'categories'   => $categories,
            'departments'  => $departments,
            'jobTitles'    => $jobTitles,
            'customFields' => $customFields,
            'spatieRoles'  => $spatieRoles,
        ]);
    }

    public function store(Request $request)
    {
        // Validate core, HR, and access role
        $request->validate([
            'first_name'   => 'required|string|max:255',
            'last_name'    => 'required|string|max:255',
            'email'        => 'required|email|unique:users,email|unique:staff,email',
            'id_number'    => 'required',
            'phone_number' => 'required',
            // HR lookups
            'department_id'     => 'nullable|exists:departments,id',
            'job_title_id'      => 'nullable|exists:job_titles,id',
            'staff_category_id' => 'nullable|exists:staff_categories,id',
            'supervisor_id'     => 'nullable|exists:staff,id',
            // Spatie role (access)
            'spatie_role_id'    => 'nullable|exists:roles,id',
        ]);

        DB::beginTransaction();
        try {
            $passwordPlain = $request->id_number;

            // 1) Create the user (login identity)
            $user = User::create([
                'name'                  => $request->first_name . ' ' . $request->last_name,
                'email'                 => $request->email,
                'password'              => Hash::make($passwordPlain),
                'must_change_password'  => true,
            ]);

            // 2) Assign Spatie access role (if provided)
            if ($request->filled('spatie_role_id')) {
                $spatieRole = Role::find($request->spatie_role_id);
                if ($spatieRole) {
                    $user->assignRole($spatieRole->name);
                }
            }

            // 3) Generate staff ID (prefix + counter from settings)
            $prefix = SystemSetting::getValue('staff_id_prefix', 'STAFF');
            $start  = (int) SystemSetting::getValue('staff_id_start', 1001);

            // 4) Build staff payload (HR + profile)
            $staffData = $request->only([
                'first_name','middle_name','last_name','email','phone_number','id_number',
                'date_of_birth','gender','marital_status','address',
                'emergency_contact_name','emergency_contact_phone',
                'kra_pin','nssf','nhif','bank_name','bank_branch','bank_account',
                'department_id','job_title_id','supervisor_id','staff_category_id'
            ]);

            $staffData['user_id']  = $user->id;
            $staffData['staff_id'] = $prefix . $start;

            if ($request->hasFile('photo')) {
                $staffData['photo'] = $request->file('photo')->store('staff_photos', 'public');
            }

            $staff = Staff::create($staffData);

            // 5) Increment counter
            SystemSetting::set('staff_id_start', $start + 1);

            // 6) Save custom fields metadata
            if ($request->has('custom_fields')) {
                foreach ($request->custom_fields as $key => $value) {
                    StaffMeta::updateOrCreate(
                        ['staff_id' => $staff->id, 'field_key' => $key],
                        ['field_value' => $value]
                    );
                }
            }

            // 7) Notifications (handled by CommunicationService)
            $msg = "Welcome {$user->name}, login with {$user->email}, password {$passwordPlain}";

            $subject = "Welcome to School";
            $body = "Account created. Email: {$user->email}, Password: {$passwordPlain}";

            $this->comm->sendEmail('staff', $user->id, $user->email, $subject, $body);
            $this->comm->sendSMS('staff', $staff->id, $staff->phone_number, $msg);

            DB::commit();
            return redirect()->route('staff.index')->with('success', 'Staff created successfully');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Staff creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'input' => $request->all(),
            ]);
            return back()->withInput()->with('error', 'Error creating staff: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        $staff        = Staff::with('meta', 'user.roles')->findOrFail($id);
        $supervisors  = Staff::where('id', '!=', $id)->get();
        $categories   = StaffCategory::all();
        $departments  = Department::all();
        $jobTitles    = JobTitle::all();
        $customFields = CustomField::where('module','staff')->get();
        $spatieRoles  = Role::all();

        return view('staff.edit', compact(
            'staff','supervisors','categories','departments','jobTitles','customFields','spatieRoles'
        ));
    }

    public function update(Request $request, $id)
    {
        $staff = Staff::with('user')->findOrFail($id);
        $user  = $staff->user;

        $request->validate([
            'first_name'   => 'required|string|max:255',
            'last_name'    => 'required|string|max:255',
            'email'        => 'required|email|unique:users,email,' . $user->id,
            'phone_number' => 'required|string|max:20',
            // HR lookups
            'department_id'     => 'nullable|exists:departments,id',
            'job_title_id'      => 'nullable|exists:job_titles,id',
            'staff_category_id' => 'nullable|exists:staff_categories,id',
            'supervisor_id'     => 'nullable|exists:staff,id',
            // Access
            'spatie_role_id'    => 'nullable|exists:roles,id',
        ]);

        DB::beginTransaction();
        try {
            // sync email on users
            $user->update(['email' => $request->email]);

            // (optional) change access role
            if ($request->filled('spatie_role_id')) {
                $spatieRole = Role::find($request->spatie_role_id);
                if ($spatieRole) {
                    $user->syncRoles([$spatieRole->name]); // replace existing role(s)
                }
            }

            // update staff profile
            $staffData = $request->only([
                'first_name','middle_name','last_name','email','phone_number','id_number',
                'date_of_birth','gender','marital_status','address',
                'emergency_contact_name','emergency_contact_phone',
                'kra_pin','nssf','nhif','bank_name','bank_branch','bank_account',
                'department_id','job_title_id','supervisor_id','staff_category_id'
            ]);

            if ($request->hasFile('photo')) {
                $staffData['photo'] = $request->file('photo')->store('staff_photos', 'public');
            }

            $staff->update($staffData);

            // handle custom fields
            if ($request->has('custom_fields')) {
                foreach ($request->custom_fields as $key => $value) {
                    $staff->meta()->updateOrCreate(
                        ['field_key' => $key],
                        ['field_value' => $value]
                    );
                }
            }

            DB::commit();
            return redirect()->route('staff.index')->with('success', 'Staff updated successfully.');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Staff update failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'input' => $request->all(),
                'staff_id' => $id,
            ]);
            return back()->withInput()->with('error', 'Error updating staff: ' . $e->getMessage());
        }
    }

    public function archive($id)
    {
        Staff::where('id', $id)->update(['status' => 'archived']);
        return back()->with('success', 'Staff archived');
    }

    public function restore($id)
    {
        Staff::where('id', $id)->update(['status' => 'active']);
        return back()->with('success', 'Staff restored');
    }

    public function showUploadForm()
    {
        return view('staff.upload');
    }

    public function handleUpload(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv',
        ]);

        $import = new \App\Imports\StaffImport;
        Excel::import($import, $request->file('file'));

        return redirect()->route('staff.index')
            ->with('success', "{$import->successCount} staff imported.")
            ->with('errors', $import->errorMessages);
    }
}
