<?php

namespace App\Http\Controllers\Hr;

use App\Http\Controllers\Controller;
use Illuminate\Validation\Rule;
use App\Models\Staff;
use App\Models\StaffMeta;
use App\Models\User;
use App\Models\Setting;
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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Role;
use App\Exports\StaffTemplateExport;
use App\Imports\StaffImportPreviewOnly;
use Illuminate\Support\Arr;

class StaffController extends Controller
{
    protected $comm;
    protected array $statutoryDeductionCodes = ['nssf', 'nhif', 'paye'];

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
            'basic_salary' => 'nullable|numeric|min:0',
            'max_lessons_per_week' => 'nullable|integer|min:0',
            'statutory_exemptions' => 'nullable|array',
            'statutory_exemptions.*' => 'in:nssf,nhif,paye',
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
        $prefix = Setting::get('staff_id_prefix', 'STAFF');
        $start  = Setting::getInt('staff_id_start', 1001);
        $staffId = $prefix . $start;

        // 4) Build staff payload (HR + profile)
        $staffData = $request->only([
            'first_name','middle_name','last_name',
            'work_email','personal_email','phone_number','id_number',
            'date_of_birth','gender','marital_status',
            'residential_address',
            'emergency_contact_name','emergency_contact_relationship','emergency_contact_phone',
            'kra_pin','nssf','nhif','bank_name','bank_branch','bank_account',
            'department_id','job_title_id','supervisor_id','staff_category_id',
            'basic_salary','max_lessons_per_week'
        ]);
        $staffData['user_id']  = $user->id;
        $staffData['staff_id'] = $staffId;

        if ($request->hasFile('photo')) {
            $staffData['photo'] = $request->file('photo')->store('staff_photos', 'public');
        }

        $staff = Staff::create($staffData);

        // 5) Increment counter
        Setting::setInt('staff_id_start', $start + 1);

        // 6) Save statutory exemptions
        $this->syncStatutoryExemptions($staff, $request->input('statutory_exemptions', []));

        // 6) Save custom fields metadata
        if ($request->has('custom_fields')) {
            foreach ($request->custom_fields as $key => $value) {
                StaffMeta::updateOrCreate(
                    ['staff_id' => $staff->id, 'field_key' => $key],
                    ['field_value' => $value]
                );
            }
        }

        // 6.5) Create salary structure if basic_salary is provided
        if ($request->filled('basic_salary')) {
            \App\Models\SalaryStructure::updateOrCreate(
                [
                    'staff_id' => $staff->id,
                    'is_active' => true,
                ],
                [
                    'basic_salary' => $request->basic_salary,
                    'housing_allowance' => 0,
                    'transport_allowance' => 0,
                    'medical_allowance' => 0,
                    'other_allowances' => 0,
                    'effective_from' => now()->startOfMonth(),
                    'is_active' => true,
                    'created_by' => auth()->id(),
                ]
            )->calculateTotals()->save();
        }

        // 7) Notifications via templates - Send welcome email and SMS
        $vars = [
            'name'     => $user->name,
            'login'    => $user->email,
            'password' => $passwordPlain,
            'staff_id' => $staff->staff_id,
        ];

        $emailTpl = CommunicationTemplate::where('code','welcome_staff')->where('type','email')->first();
        $smsTpl   = CommunicationTemplate::where('code','welcome_staff')->where('type','sms')->first();

        // Send email notification
        if ($emailTpl && $user->email) {
            try {
                $subject = $this->fillTemplate($emailTpl->subject ?? 'Welcome to ' . config('app.name'), $vars);
                $body    = $this->fillTemplate($emailTpl->content, $vars);
                // Pass relative path - GenericMail will handle the full path
                $attachmentPath = $emailTpl->attachment ?? null;

                $this->comm->sendEmail(
                    'staff',
                    $staff->id, // Use staff->id, not user->id
                    $user->email,
                    $subject,
                    $body,
                    $attachmentPath
                );
            } catch (\Exception $e) {
                Log::warning('Failed to send welcome email to staff', [
                    'staff_id' => $staff->id,
                    'email' => $user->email,
                    'error' => $e->getMessage()
                ]);
                // Don't fail the entire transaction if email fails
            }
        }

        // Send SMS notification
        if ($smsTpl && $staff->phone_number) {
            try {
                $smsBody = $this->fillTemplate($smsTpl->content, $vars);
                $smsTitle = $smsTpl->title ? $this->fillTemplate($smsTpl->title, $vars) : 'Welcome to ' . config('app.name');
                $this->comm->sendSMS('staff', $staff->id, $staff->phone_number, $smsBody, $smsTitle);
            } catch (\Exception $e) {
                Log::warning('Failed to send welcome SMS to staff', [
                    'staff_id' => $staff->id,
                    'phone' => $staff->phone_number,
                    'error' => $e->getMessage()
                ]);
                // Don't fail the entire transaction if SMS fails
            }
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

    
    public function show($id)
    {
        $staff = Staff::with(['meta', 'user.roles', 'supervisor', 'category', 'department', 'jobTitle'])->findOrFail($id);
        return view('staff.show', compact('staff'));
    }

    public function edit($id)
    {
        $staff        = Staff::with('meta', 'user.roles', 'statutoryExemptions')->findOrFail($id);
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
            'basic_salary' => 'nullable|numeric|min:0',
            'max_lessons_per_week' => 'nullable|integer|min:0',
            'statutory_exemptions' => 'nullable|array',
            'statutory_exemptions.*' => 'in:nssf,nhif,paye',
            'photo' => 'nullable|image|mimes:jpeg,jpg,png|max:2048',
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
                'basic_salary','max_lessons_per_week',
                'residential_address',
                'emergency_contact_name','emergency_contact_relationship','emergency_contact_phone',
                'kra_pin','nssf','nhif','bank_name','bank_branch','bank_account',
                'department_id','job_title_id','supervisor_id','staff_category_id',
                'hire_date','termination_date','employment_status','employment_type',
                'contract_start_date','contract_end_date'
            ]);

            if ($request->hasFile('photo')) {
                // Delete old photo if exists
                if ($staff->photo && Storage::disk('public')->exists($staff->photo)) {
                    Storage::disk('public')->delete($staff->photo);
                }
                
                // Store new photo
                $staffData['photo'] = $request->file('photo')->store('staff_photos', 'public');
            }

            $staff->update($staffData);

            $this->syncStatutoryExemptions($staff, $request->input('statutory_exemptions', []));

            // handle custom fields
            if ($request->has('custom_fields')) {
                foreach ($request->custom_fields as $key => $value) {
                    $staff->meta()->updateOrCreate(
                        ['field_key' => $key],
                        ['field_value' => $value]
                    );
                }
            }

            // Update salary structure if basic_salary is provided
            if ($request->filled('basic_salary')) {
                \App\Models\SalaryStructure::updateOrCreate(
                    [
                        'staff_id' => $staff->id,
                        'is_active' => true,
                    ],
                    [
                        'basic_salary' => $request->basic_salary,
                        'housing_allowance' => 0,
                        'transport_allowance' => 0,
                        'medical_allowance' => 0,
                        'other_allowances' => 0,
                        'effective_from' => now()->startOfMonth(),
                        'is_active' => true,
                        'created_by' => auth()->id(),
                    ]
                )->calculateTotals()->save();
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
        // Detect format: Check if first column looks like staff_id (empty or numeric/alphanumeric) vs first_name (text)
        // New template: 0=staff_id, 1=first_name, 2=middle_name, 3=last_name, 4=work_email, 5=personal_email, 6=phone_number, 7=id_number
        // Old template: 0=first_name, 1=middle_name, 2=last_name, 3=work_email, 4=personal_email, 5=phone_number, 6=id_number
        $preview = [];
        $hasStaffIdColumn = false;
        
        // Check first row to detect format
        if (!empty($rows)) {
            $firstRow = $rows->first();
            // If index 0 is empty or looks like an ID, and index 1 looks like a name, assume staff_id column exists
            $col0 = trim((string)($firstRow[0] ?? ''));
            $col1 = trim((string)($firstRow[1] ?? ''));
            if (empty($col0) || preg_match('/^(STAFF|RKS|EMP|\d+)$/i', $col0)) {
                if (!empty($col1) && !filter_var($col1, FILTER_VALIDATE_EMAIL)) {
                    $hasStaffIdColumn = true;
                }
            }
        }
        
        foreach ($rows as $r) {
            if ($hasStaffIdColumn) {
                // New format with staff_id at index 0
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
                    'raw' => (array) $r,
                ];
            } else {
                // Old format without staff_id (starts with first_name at index 0)
                $preview[] = [
                    'first_name'  => trim((string)($r[0] ?? '')),
                    'middle_name' => trim((string)($r[1] ?? '')),
                    'last_name'   => trim((string)($r[2] ?? '')),
                    'work_email'  => trim((string)($r[3] ?? '')),
                    'personal_email' => trim((string)($r[4] ?? '')),
                    'phone_number'=> trim((string)($r[5] ?? '')),
                    'id_number'   => trim((string)($r[6] ?? '')),
                    'department_guess' => trim((string)($r[20] ?? '')),
                    'job_title_guess'  => trim((string)($r[21] ?? '')),
                    'category_guess'   => trim((string)($r[22] ?? '')),
                    'supervisor_staff_id_guess' => trim((string)($r[23] ?? '')),
                    'spatie_role_guess'         => trim((string)($r[24] ?? '')),
                    'raw' => (array) $r,
                ];
            }
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

        // Bulk assignment options (apply to all rows if set)
        $bulkDepartmentId = $request->input('bulk_department_id');
        $bulkJobTitleId = $request->input('bulk_job_title_id');
        $bulkCategoryId = $request->input('bulk_staff_category_id');
        $bulkRoleName = $request->input('bulk_spatie_role_name');

        $deptIds   = $request->input('department_id', []);
        $jobIds    = $request->input('job_title_id', []);
        $catIds    = $request->input('staff_category_id', []);
        $supIds    = $request->input('supervisor_id', []);
        $roleNames = $request->input('spatie_role_name', []);

        $success = 0;
        $errors  = [];
        $errorDetails = [];

        foreach ($rows as $i => $r) {
            try {
                // Use bulk assignment if provided, otherwise use row-specific
                $deptId = $bulkDepartmentId ?: ($deptIds[$i] ?? null);
                $jobId = $bulkJobTitleId ?: ($jobIds[$i] ?? null);
                $catId = $bulkCategoryId ?: ($catIds[$i] ?? null);
                $roleName = $bulkRoleName ?: ($roleNames[$i] ?? '');

                // Build a single "sheet row" compatible with your StaffImport
                // Create array with all 26 indices (0-25) to match Excel columns
                $rowArray = array_fill(0, 26, '');
                $rowArray[0] = ''; // staff_id (let generator create)
                $rowArray[1] = $r['first_name'] ?? '';
                $rowArray[2] = $r['middle_name'] ?? '';
                $rowArray[3] = $r['last_name'] ?? '';
                $rowArray[4] = $r['work_email'] ?? '';
                $rowArray[5] = $r['personal_email'] ?? '';
                $rowArray[6] = $r['phone_number'] ?? '';
                $rowArray[7] = $r['id_number'] ?? '';
                // 8-20 are empty (other fields)
                $rowArray[21] = $deptId ? optional(Department::find($deptId))->name : '';
                $rowArray[22] = $jobId ? optional(JobTitle::find($jobId))->name : '';
                $rowArray[23] = $catId ? optional(StaffCategory::find($catId))->name : '';
                $rowArray[24] = isset($supIds[$i]) && $supIds[$i] ? optional(\App\Models\Staff::find($supIds[$i]))->staff_id : '';
                $rowArray[25] = $roleName;
                
                $row = collect($rowArray);

                // Validate required fields before import
                if (empty($rowArray[1]) || empty($rowArray[3]) || empty($rowArray[4]) || empty($rowArray[6]) || empty($rowArray[7])) {
                    throw new \RuntimeException("Missing required fields: first_name, last_name, work_email, phone_number, or id_number");
                }

                // Call your existing StaffImport on just this row (without header)
                $import = new \App\Imports\StaffImport;
                // Create a collection with a dummy header and the actual row
                $import->collection(collect([
                    collect([]), // dummy header
                    $row
                ]));

                if (!empty($import->errorMessages)) {
                    throw new \RuntimeException(implode('; ', $import->errorMessages));
                }
                $success += $import->successCount;
            } catch (\Throwable $e) {
                $rowNum = $i + 2; // +2 because Excel rows start at 1 and we skip header
                $errorMsg = "Row {$rowNum} ({$r['first_name']} {$r['last_name']}): " . $e->getMessage();
                $errors[] = $errorMsg;
                $errorDetails[] = [
                    'row' => $rowNum,
                    'name' => ($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? ''),
                    'email' => $r['work_email'] ?? '',
                    'error' => $e->getMessage(),
                    'trace' => config('app.debug') ? $e->getTraceAsString() : null,
                ];
                Log::error('Staff import error', [
                    'row' => $rowNum,
                    'data' => $r,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        session()->forget(['staff_upload_rows','staff_upload_time']);

        $message = "$success staff imported successfully.";
        if (!empty($errors)) {
            $message .= " " . count($errors) . " row(s) failed. Check errors below.";
        }

        return redirect()->route('staff.index')
            ->with('success', $message)
            ->with('errors', $errors)
            ->with('error_details', $errorDetails);
    }
    private function syncStatutoryExemptions(Staff $staff, array $requestedCodes = []): void
    {
        $codes = collect($requestedCodes)
            ->map(fn ($code) => strtolower($code))
            ->filter(fn ($code) => in_array($code, $this->statutoryDeductionCodes, true))
            ->unique()
            ->values();

        if ($codes->isEmpty()) {
            $staff->statutoryExemptions()->delete();
            return;
        }

        $staff->statutoryExemptions()
            ->whereNotIn('deduction_code', $codes)
            ->delete();

        foreach ($codes as $code) {
            $staff->statutoryExemptions()->firstOrCreate([
                'deduction_code' => $code,
            ]);
        }
    }

    /**
     * Resend login credentials to staff member
     */
    public function resendCredentials($id)
    {
        $staff = Staff::with('user')->findOrFail($id);
        $user = $staff->user;

        if (!$user) {
            return back()->with('error', 'Staff member does not have a user account.');
        }

        try {
            // Password is the staff's ID number
            $passwordPlain = $staff->id_number;

            // Prepare template variables
            $vars = [
                'name'     => $user->name,
                'login'    => $user->email,
                'password' => $passwordPlain,
                'staff_id' => $staff->staff_id,
            ];

            // Get welcome templates
            $emailTpl = CommunicationTemplate::where('code', 'welcome_staff')->where('type', 'email')->first();
            $smsTpl   = CommunicationTemplate::where('code', 'welcome_staff')->where('type', 'sms')->first();

            $sent = false;
            $errors = [];
            $warnings = [];

            // Check if templates exist
            if (!$emailTpl && !$smsTpl) {
                return back()->with('error', 'Welcome staff templates not found. Please create "welcome_staff" email and/or SMS templates first.');
            }

            // Send email notification
            if ($emailTpl) {
                if (!$user->email) {
                    $warnings[] = 'Email not available for this staff member.';
                } else {
                    try {
                        $subject = $this->fillTemplate($emailTpl->subject ?? 'Welcome to ' . config('app.name'), $vars);
                        $body    = $this->fillTemplate($emailTpl->content, $vars);
                        $attachmentPath = $emailTpl->attachment ?? null;

                        $this->comm->sendEmail(
                            'staff',
                            $staff->id,
                            $user->email,
                            $subject,
                            $body,
                            $attachmentPath
                        );
                        $sent = true;
                    } catch (\Exception $e) {
                        $errors[] = 'Email: ' . $e->getMessage();
                        Log::warning('Failed to resend welcome email to staff', [
                            'staff_id' => $staff->id,
                            'email' => $user->email,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            } else {
                $warnings[] = 'Email template not found.';
            }

            // Send SMS notification
            if ($smsTpl) {
                if (!$staff->phone_number) {
                    $warnings[] = 'Phone number not available for this staff member.';
                } else {
                    try {
                        $smsBody = $this->fillTemplate($smsTpl->content, $vars);
                        $smsTitle = $smsTpl->title ? $this->fillTemplate($smsTpl->title, $vars) : 'Welcome to ' . config('app.name');
                        $this->comm->sendSMS('staff', $staff->id, $staff->phone_number, $smsBody, $smsTitle);
                        $sent = true;
                    } catch (\Exception $e) {
                        $errors[] = 'SMS: ' . $e->getMessage();
                        Log::warning('Failed to resend welcome SMS to staff', [
                            'staff_id' => $staff->id,
                            'phone' => $staff->phone_number,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            } else {
                $warnings[] = 'SMS template not found.';
            }

            // Build response message
            if ($sent) {
                $message = 'Login credentials have been resent successfully.';
                if (!empty($warnings)) {
                    $message .= ' Note: ' . implode(' ', $warnings);
                }
                if (!empty($errors)) {
                    $message .= ' However, some notifications failed: ' . implode(', ', $errors);
                }
                return back()->with('success', $message);
            } else {
                $errorMsg = 'Failed to resend credentials.';
                if (!empty($errors)) {
                    $errorMsg .= ' ' . implode(', ', $errors);
                }
                if (!empty($warnings)) {
                    $errorMsg .= ' ' . implode(' ', $warnings);
                }
                return back()->with('error', $errorMsg);
            }
        } catch (\Throwable $e) {
            Log::error('Failed to resend credentials to staff', [
                'staff_id' => $staff->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->with('error', 'Error resending credentials: ' . $e->getMessage());
        }
    }

    /**
     * Reset staff password (admin only)
     */
    public function resetPassword(Request $request, $id)
    {
        $staff = Staff::with('user')->findOrFail($id);
        $user = $staff->user;

        if (!$user) {
            return back()->with('error', 'Staff member does not have a user account. Cannot reset password.');
        }

        $request->validate([
            'new_password' => 'nullable|string|min:6',
            'password_option' => 'required|in:id_number,random,custom',
        ]);

        try {
            // Generate new password based on selected option
            $passwordOption = $request->input('password_option');
            
            if ($passwordOption === 'custom' && $request->filled('new_password')) {
                $newPassword = $request->new_password;
            } elseif ($passwordOption === 'id_number' && $staff->id_number) {
                $newPassword = $staff->id_number;
            } elseif ($passwordOption === 'random') {
                // Generate a random password
                $newPassword = \Str::random(8);
            } else {
                return back()->with('error', 'Invalid password option or missing required data.');
            }

            // Update user password
            $user->update([
                'password' => \Hash::make($newPassword),
                'must_change_password' => true, // Force password change on next login
            ]);

            // Prepare template variables
            $vars = [
                'name'     => $user->name,
                'login'    => $user->email,
                'password' => $newPassword,
                'staff_id' => $staff->staff_id,
            ];

            // Get password reset templates (or use welcome_staff if reset template doesn't exist)
            $emailTpl = CommunicationTemplate::where('code', 'password_reset_staff')
                ->where('type', 'email')
                ->first() ?? CommunicationTemplate::where('code', 'welcome_staff')
                ->where('type', 'email')
                ->first();

            $smsTpl = CommunicationTemplate::where('code', 'password_reset_staff')
                ->where('type', 'sms')
                ->first() ?? CommunicationTemplate::where('code', 'welcome_staff')
                ->where('type', 'sms')
                ->first();

            $sent = false;
            $errors = [];
            $warnings = [];

            // Send email notification
            if ($emailTpl && $user->email) {
                try {
                    $subject = $this->fillTemplate($emailTpl->subject ?? 'Password Reset - ' . config('app.name'), $vars);
                    $body    = $this->fillTemplate($emailTpl->content, $vars);
                    $attachmentPath = $emailTpl->attachment ?? null;

                    $this->comm->sendEmail(
                        'staff',
                        $staff->id,
                        $user->email,
                        $subject,
                        $body,
                        $attachmentPath
                    );
                    $sent = true;
                } catch (\Exception $e) {
                    $errors[] = 'Email: ' . $e->getMessage();
                    Log::warning('Failed to send password reset email to staff', [
                        'staff_id' => $staff->id,
                        'email' => $user->email,
                        'error' => $e->getMessage()
                    ]);
                }
            } else {
                if (!$user->email) {
                    $warnings[] = 'Email not available for this staff member.';
                } else {
                    $warnings[] = 'Email template not found.';
                }
            }

            // Send SMS notification
            if ($smsTpl && $staff->phone_number) {
                try {
                    $smsBody = $this->fillTemplate($smsTpl->content, $vars);
                    $smsTitle = $smsTpl->title ? $this->fillTemplate($smsTpl->title, $vars) : 'Password Reset - ' . config('app.name');
                    $this->comm->sendSMS('staff', $staff->id, $staff->phone_number, $smsBody, $smsTitle);
                    $sent = true;
                } catch (\Exception $e) {
                    $errors[] = 'SMS: ' . $e->getMessage();
                    Log::warning('Failed to send password reset SMS to staff', [
                        'staff_id' => $staff->id,
                        'phone' => $staff->phone_number,
                        'error' => $e->getMessage()
                    ]);
                }
            } else {
                if (!$staff->phone_number) {
                    $warnings[] = 'Phone number not available for this staff member.';
                } else {
                    $warnings[] = 'SMS template not found.';
                }
            }

            // Build response message
            $message = 'Password has been reset successfully.';
            if ($sent) {
                $message .= ' The new password has been sent to the staff member.';
            }
            if (!empty($warnings)) {
                $message .= ' Note: ' . implode(' ', $warnings);
            }
            if (!empty($errors)) {
                $message .= ' However, some notifications failed: ' . implode(', ', $errors);
            }

            Log::info('Admin reset password for staff', [
                'admin_id' => auth()->id(),
                'staff_id' => $staff->id,
                'staff_email' => $user->email,
            ]);

            return back()->with('success', $message);
        } catch (\Throwable $e) {
            Log::error('Failed to reset password for staff', [
                'staff_id' => $staff->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return back()->with('error', 'Error resetting password: ' . $e->getMessage());
        }
    }

    private function fillTemplate(string $content, array $vars): string
    {
        // supports {key} placeholders
        $search  = array_map(fn($k)=>'{'.$k.'}', array_keys($vars));
        $replace = array_values($vars);
        return str_replace($search, $replace, $content);
    }

}
