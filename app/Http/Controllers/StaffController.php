<?php

namespace App\Http\Controllers;

use App\Models\Staff;
use App\Models\StaffMeta;
use App\Models\User;
use App\Models\SystemSetting;
use App\Models\EmailTemplate;
use App\Models\CommunicationTemplate;
use App\Models\StaffRole;
use App\Models\Department;
use App\Models\JobTitle;
use App\Models\CustomField;
use App\Services\CommunicationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class StaffController extends Controller
{
    protected $comm;

    public function __construct(CommunicationService $comm)
    {
        $this->comm = $comm;
    }

    public function index()
    {
        $staff = Staff::with(['supervisor', 'meta','role','department','jobTitle'])->get();
        return view('staff.index', compact('staff'));
    }

    public function create()
    {
        $supervisors = Staff::all();
        $roles = StaffRole::all();
        $departments = Department::all();
        $jobTitles = JobTitle::all();
        $customFields = CustomField::where('module','staff')->get();

        return view('staff.create', compact('supervisors','roles','departments','jobTitles','customFields'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'first_name'   => 'required|string|max:255',
            'last_name'    => 'required|string|max:255',
            'email'        => 'required|email|unique:users,email|unique:staff,email',
            'id_number'    => 'required',
            'phone_number' => 'required',
        ]);

        DB::beginTransaction();
        try {
            $passwordPlain = $request->id_number;

            $user = User::create([
                'name' => $request->first_name . ' ' . $request->last_name,
                'email' => $request->email,
                'password' => Hash::make($passwordPlain),
                'must_change_password' => true,
            ]);

            $prefix = SystemSetting::getValue('staff_id_prefix','STAFF');
            $start  = (int) SystemSetting::getValue('staff_id_start',1001);

            $staffData = $request->only([
                'first_name','middle_name','last_name','email','phone_number','id_number',
                'date_of_birth','gender','marital_status','address',
                'emergency_contact_name','emergency_contact_phone',
                'kra_pin','nssf','nhif','bank_name','bank_branch','bank_account',
                'department_id','job_title_id','supervisor_id','role_id'
            ]);

            $staffData['user_id'] = $user->id;
            $staffData['staff_id'] = $prefix.$start;

            if ($request->hasFile('photo')) {
                $staffData['photo'] = $request->file('photo')->store('staff_photos','public');
            }

            $staff = Staff::create($staffData);

            SystemSetting::set('staff_id_start', $start+1);

            if ($request->has('custom_fields')) {
                foreach ($request->custom_fields as $key => $value) {
                    StaffMeta::updateOrCreate(
                        ['staff_id'=>$staff->id,'field_key'=>$key],
                        ['field_value'=>$value]
                    );
                }
            }

            $smsTemplate = CommunicationTemplate::where('type','sms')->where('code','welcome_staff')->first();
            $emailTemplate = EmailTemplate::where('code','welcome_staff')->first();

            $msg = $smsTemplate
                ? str_replace(['{name}','{login}','{password}'], [$user->name,$user->email,$passwordPlain], $smsTemplate->content)
                : "Welcome {$user->name}, login with {$user->email}, password {$passwordPlain}";

            $subject = $emailTemplate ? $emailTemplate->title : "Welcome to School";
            $body = $emailTemplate
                ? str_replace(['{name}','{login}','{password}'], [$user->name,$user->email,$passwordPlain], $emailTemplate->message)
                : "Account created. Email: {$user->email}, Password: {$passwordPlain}";

            $this->comm->sendEmail('staff',$user->id,$user->email,$subject,$body);
            $this->comm->sendSMS('staff',$staff->id,$staff->phone_number,$msg);

            DB::commit();
            return redirect()->route('staff.index')->with('success','Staff created successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Staff creation failed: '.$e->getMessage());
            return back()->with('error','Error: '.$e->getMessage());
        }
    }

    public function edit($id)
    {
        $staff = Staff::with('meta')->findOrFail($id);
        $supervisors = Staff::where('id','!=',$id)->get();
        $roles = StaffRole::all();
        $departments = Department::all();
        $jobTitles = JobTitle::all();
        $customFields = CustomField::where('module','staff')->get();

        return view('staff.edit', compact('staff','supervisors','roles','departments','jobTitles','customFields'));
    }

   public function update(Request $request, $id)
    {
        $staff = Staff::with('user')->findOrFail($id);
        $user = $staff->user;

        $request->validate([
            'first_name'   => 'required|string|max:255',
            'last_name'    => 'required|string|max:255',
            'email'        => 'required|email|unique:users,email,'.$user->id,
            'phone_number' => 'required|string|max:20',
        ]);

        // update user email
        $user->update(['email' => $request->email]);

        // collect staff data (everything except email & custom_fields)
        $staffData = $request->only([
            'first_name','middle_name','last_name','email','phone_number','id_number',
            'date_of_birth','gender','marital_status','address',
            'emergency_contact_name','emergency_contact_phone',
            'kra_pin','nssf','nhif','bank_name','bank_branch','bank_account',
            'department_id','job_title_id','supervisor_id','role_id'
        ]);

        if ($request->hasFile('photo')) {
            $staffData['photo'] = $request->file('photo')->store('staff_photos','public');
        }

        $staff->update($staffData);

        // handle custom fields
        if ($request->has('custom_fields')) {
            foreach ($request->custom_fields as $key=>$value) {
                $staff->meta()->updateOrCreate(
                    ['field_key'=>$key],
                    ['field_value'=>$value]
                );
            }
        }

        return redirect()->route('staff.index')->with('success','Staff updated successfully.');
    }

    public function archive($id)
    {
        Staff::where('id',$id)->update(['status'=>'archived']);
        return back()->with('success','Staff archived');
    }

    public function restore($id)
    {
        Staff::where('id',$id)->update(['status'=>'active']);
        return back()->with('success','Staff restored');
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
