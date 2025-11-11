<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use Illuminate\Validation\Rule;
use App\Models\Staff;
use App\Models\StaffMeta;
use App\Models\User;
use App\Models\SystemSetting;
use App\Models\EmailTemplate;
use App\Models\CommunicationTemplate;
use App\Models\Department;
use App\Models\JobTitle;
use App\Models\CustomField;
use App\Models\StaffCategory;
use App\Services\CommunicationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Role;
use App\Exports\StaffTemplateExport;
use App\Imports\StaffImportPreviewOnly;
use Illuminate\Support\Arr;

class StaffController extends Controller
{
    protected $comm;

    public function __construct(CommunicationService $comm)
    {
        $this->comm = $comm;
    }

    public function index()
    {
        $query = Staff::with(['supervisor', 'meta', 'category', 'department', 'jobTitle']);

        // Search functionality
        if (request()->filled('q')) {
            $search = request('q');
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('middle_name', 'like', "%{$search}%")
                  ->orWhere('work_email', 'like', "%{$search}%")
                  ->orWhere('personal_email', 'like', "%{$search}%")
                  ->orWhere('phone_number', 'like', "%{$search}%")
                  ->orWhere('staff_id', 'like', "%{$search}%")
                  ->orWhere('id_number', 'like', "%{$search}%");
            });
        }

        // Department filter
        if (request()->filled('department_id')) {
            $query->where('department_id', request('department_id'));
        }

        // Status filter (only active and archived exist in model)
        if (request()->filled('status')) {
            $status = request('status');
            if (in_array($status, ['active', 'archived'])) {
                $query->where('status', $status);
            }
        } else {
            // Default: show only active staff
            $query->where('status', 'active');
        }

        // Pagination
        $staff = $query->orderBy('first_name')->orderBy('last_name')->paginate(20)->withQueryString();

        // Summary statistics
        $totalStaff = Staff::count();
        $activeStaff = Staff::where('status', 'active')->count();
        $archivedStaff = Staff::where('status', 'archived')->count();
        $departments = Department::withCount('staff')->get();

        return view('staff.index', compact('staff', 'totalStaff', 'activeStaff', 'archivedStaff', 'departments'));
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
        'work_email'   => [
            'required','email',
            Rule::unique('users','email'),
            Rule::unique('staff','work_email'),
        ],
        'personal_email' => 'nullable|email',
        'id_number'    => 'required',
        'phone_number' => 'required',
        'department_id'     => 'nullable|exists:departments,id',
        'job_title_id'      => 'nullable|exists:job_titles,id',
        'staff_category_id' => 'nullable|exists:staff_categories,id',
        'supervisor_id'     => 'nullable|exists:staff,id',
        'spatie_role_id'    => 'nullable|exists:roles,id',
        'emergency_contact_relationship' => 'nullable|string|max:100',
        'residential_address' => 'nullable|string|max:255',
        'photo' => 'nullable|image|max:2048',
    ]);

    DB::beginTransaction();
    try {
        $passwordPlain = $request->id_number;

        // 1) Create user (login with work email)
        $user = User::create([
            'name'                 => $request->first_name . ' ' . $request->last_name,
            'email'                => $request->work_email,
            'password'             => \Hash::make($passwordPlain),
            'must_change_password' => true,
        ]);

        // 2) Assign Spatie role (guard 'web')
        if ($request->filled('spatie_role_id')) {
            $role = \Spatie\Permission\Models\Role::find($request->spatie_role_id);
            if ($role && $role->guard_name === 'web') {
                $user->assignRole($role->name);
            }
        } else {
            // Auto-role by category if none selected (optional defaults)
            if ((int)$request->staff_category_id === 1) {
                $user->assignRole('Teacher');
            } elseif ((int)$request->staff_category_id === 2) {
                $user->assignRole('Administrator');
            } else {
                $user->assignRole('Staff');
            }
        }

        // 3) Generate staff ID
        $prefix = SystemSetting::getValue('staff_id_prefix', 'STAFF');
        $start  = (int) SystemSetting::getValue('staff_id_start', 1001);

        // 4) Build staff payload (HR + profile)
        $staffData = $request->only([
            'first_name','middle_name','last_name',
            'work_email','personal_email','phone_number','id_number',
            'date_of_birth','gender','marital_status',
            'residential_address',
            'emergency_contact_name','emergency_contact_relationship','emergency_contact_phone',
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

        // 7) Notifications via templates
        $vars = [
            'name'     => $user->name,
            'login'    => $user->email,
            'password' => $passwordPlain,
        ];

        $emailTpl = CommunicationTemplate::where('code','welcome_staff')->where('type','email')->first();
        $smsTpl   = CommunicationTemplate::where('code','welcome_staff')->where('type','sms')->first();

        if ($emailTpl) {
            $subject = $this->fillTemplate($emailTpl->subject ?? 'Welcome', $vars);
            $body    = $this->fillTemplate($emailTpl->content, $vars);

            $this->comm->sendEmail(
                'staff',
                $user->id,
                $user->email,
                $subject,
                $body,
                $emailTpl->attachment ? storage_path('app/public/'.$emailTpl->attachment) : null
            );
        }

        if ($smsTpl && $staff->phone_number) {
            $smsBody = $this->fillTemplate($smsTpl->content, $vars);
            $this->comm->sendSMS('staff', $staff->id, $staff->phone_number, $smsBody);
        }

        DB::commit();
        return redirect()->route('staff.index')->with('success', 'Staff created successfully.');
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

            // ignore the current User and Staff during unique check
            'work_email'   => 'required|email|unique:users,email,'.$user->id.'|unique:staff,work_email,'.$staff->id,

            'personal_email' => 'nullable|email',
            'id_number'    => 'required|unique:staff,id_number,'.$staff->id,
            'phone_number' => 'required',
            'department_id'     => 'nullable|exists:departments,id',
            'job_title_id'      => 'nullable|exists:job_titles,id',
            'staff_category_id' => 'nullable|exists:staff_categories,id',
            'supervisor_id'     => 'nullable|exists:staff,id',
            'spatie_role_id'    => 'nullable|exists:roles,id',
            'emergency_contact_relationship' => 'nullable|string|max:100',
            'residential_address' => 'nullable|string|max:255',
            'hire_date' => 'nullable|date',
            'termination_date' => 'nullable|date|after_or_equal:hire_date',
            'employment_status' => 'nullable|in:active,on_leave,terminated,suspended',
            'employment_type' => 'nullable|in:full_time,part_time,contract,intern',
            'contract_start_date' => 'nullable|date',
            'contract_end_date' => 'nullable|date|after_or_equal:contract_start_date',
        ]);

        // Validate supervisor hierarchy (prevent circular references and self-supervision)
        if ($request->filled('supervisor_id')) {
            $supervisorId = $request->supervisor_id;
            if ($supervisorId == $staff->id) {
                return back()->withInput()->with('error', 'A staff member cannot be their own supervisor.');
            }
            // Check for circular reference (if supervisor's supervisor chain includes this staff)
            $supervisor = Staff::find($supervisorId);
            if ($supervisor && $supervisor->supervisor_id == $staff->id) {
                return back()->withInput()->with('error', 'Circular supervisor relationship detected. Please choose a different supervisor.');
            }
        }

        DB::beginTransaction();
        try {
            // sync email on users
            $user->update(['email' => $request->work_email]);

        // 2) sync role if provided
        if ($request->filled('spatie_role_id')) {
            $role = \Spatie\Permission\Models\Role::find($request->spatie_role_id);
            if ($role && $role->guard_name === 'web') {
                $user->syncRoles([$role->name]); // replace existing
            }
        }

            // update staff profile
            $staffData = $request->only([
                'first_name','middle_name','last_name',
                'work_email','personal_email','phone_number','id_number',
                'date_of_birth','gender','marital_status',
                'residential_address',
                'emergency_contact_name','emergency_contact_relationship','emergency_contact_phone',
                'kra_pin','nssf','nhif','bank_name','bank_branch','bank_account',
                'department_id','job_title_id','supervisor_id','staff_category_id',
                'hire_date','termination_date','employment_status','employment_type',
                'contract_start_date','contract_end_date'
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
        public function template()
    {
        return Excel::download(new StaffTemplateExport, 'staff_template.xlsx');
    }

    public function uploadParse(Request $request)
    {
        $request->validate(['file' => 'required|mimes:xlsx,xls,csv']);

        $sheets = \Maatwebsite\Excel\Facades\Excel::toCollection(new StaffImportPreviewOnly, $request->file('file'));
        $rows = $sheets->first();
        if (!$rows || $rows->count() < 2) {
            return back()->with('error','The file appears empty.');
        }

        // Drop header
        $rows->shift();

        // Prepare compact preview rows
        $preview = [];
        foreach ($rows as $r) {
            $preview[] = [
                'first_name'  => trim((string)($r[1] ?? '')),
                'middle_name' => trim((string)($r[2] ?? '')),
                'last_name'   => trim((string)($r[3] ?? '')),
                'work_email'  => trim((string)($r[4] ?? '')),
                'personal_email' => trim((string)($r[5] ?? '')),
                'phone_number'=> trim((string)($r[6] ?? '')),
                'id_number'   => trim((string)($r[7] ?? '')),
                'department_guess' => trim((string)($r[21] ?? '')),
                'job_title_guess'  => trim((string)($r[22] ?? '')),
                'category_guess'   => trim((string)($r[23] ?? '')),
                'supervisor_staff_id_guess' => trim((string)($r[24] ?? '')),
                'spatie_role_guess'         => trim((string)($r[25] ?? '')),
                'raw' => Arr::toArray($r), // keep if you want to show more later
            ];
        }

        session([
            'staff_upload_rows' => $preview,
            'staff_upload_time' => now()->toISOString(),
        ]);

        return view('staff.upload_verify', [
            'rows'        => $preview,
            'departments' => Department::orderBy('name')->get(),
            'jobTitles'   => JobTitle::orderBy('name')->get(),
            'categories'  => StaffCategory::orderBy('name')->get(),
            'supervisors' => \App\Models\Staff::orderBy('first_name')->get(['id','staff_id','first_name','last_name']),
            'roles'       => Role::orderBy('name')->get(),
        ]);
    }

    public function uploadCommit(Request $request)
    {
        $rows = session('staff_upload_rows', []);
        if (empty($rows)) {
            return redirect()->route('staff.upload.form')->with('error','Upload session expired. Please upload again.');
        }

        $deptIds   = $request->input('department_id', []);
        $jobIds    = $request->input('job_title_id', []);
        $catIds    = $request->input('staff_category_id', []);
        $supIds    = $request->input('supervisor_id', []);
        $roleNames = $request->input('spatie_role_name', []);

        $success = 0;
        $errors  = [];

        foreach ($rows as $i => $r) {
            try {
                // Build a single "sheet row" compatible with your StaffImport
                $row = collect([
                    '', // 0 staff_id (let generator create)
                    $r['first_name'],          // 1
                    $r['middle_name'],         // 2
                    $r['last_name'],           // 3
                    $r['work_email'],          // 4
                    $r['personal_email'],      // 5
                    $r['phone_number'],        // 6
                    $r['id_number'],           // 7
                    '', '', '', '', '', '', '', '', '', '', '', '', '', // up to 20
                ]);

                $row[21] = optional(Department::find($deptIds[$i] ?? null))->name;
                $row[22] = optional(JobTitle::find($jobIds[$i] ?? null))->name;
                $row[23] = optional(StaffCategory::find($catIds[$i] ?? null))->name;
                $row[24] = optional(\App\Models\Staff::find($supIds[$i] ?? null))->staff_id;
                $row[25] = $roleNames[$i] ?? '';

                // Call your existing StaffImport on just this row
                $import = new \App\Imports\StaffImport;
                $import->collection(collect([$row]));

                if (!empty($import->errorMessages)) {
                    throw new \RuntimeException(implode('; ', $import->errorMessages));
                }
                $success += $import->successCount;
            } catch (\Throwable $e) {
                $errors[] = "Row ".($i+2).": ".$e->getMessage();
            }
        }

        session()->forget(['staff_upload_rows','staff_upload_time']);

        return redirect()->route('staff.index')
            ->with('success', "$success staff imported.")
            ->with('errors', $errors);
    }
    private function fillTemplate(string $content, array $vars): string
    {
        // supports {key} placeholders
        $search  = array_map(fn($k)=>'{'.$k.'}', array_keys($vars));
        $replace = array_values($vars);
        return str_replace($search, $replace, $content);
    }

}
