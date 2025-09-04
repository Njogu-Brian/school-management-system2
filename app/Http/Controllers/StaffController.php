<?php

namespace App\Http\Controllers;

use App\Models\Staff;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Services\CommunicationService;
use Illuminate\Support\Facades\Log;
use App\Models\EmailTemplate;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use App\Models\SystemSetting;
use App\Models\CommunicationTemplate;

class StaffController extends Controller
{
    protected $CommunicationService;

    public function __construct(CommunicationService $CommunicationService)
    {
        $this->CommunicationService = $CommunicationService;
    }

    public function index()
    {
        $staff = Staff::all();
        return view('staff.index', compact('staff'));
    }

    public function create()
    {
        return view('staff.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email|unique:staff,email',
        ]);

        DB::beginTransaction();
        try {
            $password = Str::random(10);

            // ✅ Create user
            $user = User::create([
                'name' => $request->first_name . ' ' . $request->last_name,
                'email' => $request->email,
                'password' => Hash::make($password),
                'must_change_password' => true,
            ]);

            // ✅ Prepare staff data
            $staffData = $request->only([
                'first_name', 'middle_name', 'last_name', 'email', 'phone_number',
                'id_number', 'gender', 'marital_status', 'address',
                'emergency_contact_name', 'emergency_contact_phone'
            ]);

            // ✅ Parse date of birth
            if (!empty($request->date_of_birth)) {
                $staffData['date_of_birth'] = \Carbon\Carbon::parse($request->date_of_birth);
            }

            // ✅ Generate staff ID
            $staffData['user_id'] = $user->id;
            $prefix = SystemSetting::getValue('staff_id_prefix', 'STAFF');
            $start = (int) SystemSetting::getValue('staff_id_start', 1001);
            $staffData['staff_id'] = $prefix . $start;

            Staff::create($staffData);

            // ✅ Increment counter
            SystemSetting::set('staff_id_start', $start + 1);

            // ✅ Notify staff
            $smsTemplate = CommunicationTemplate::where('type', 'sms')->where('code', 'welcome_staff')->first();
            $emailTemplate = EmailTemplate::where('code', 'welcome_staff')->first();

            $name = $user->name;
            $login = $user->email;

            $msg = $smsTemplate
                ? str_replace(['{name}', '{login}', '{password}'], [$name, $login, $password], $smsTemplate->content)
                : "Welcome $name! Your login: $login and password: $password";

            $subject = $emailTemplate ? $emailTemplate->title : "Welcome to Royal Kings School";
            $body = $emailTemplate
                ? str_replace(['{name}', '{login}', '{password}'], [$name, $login, $password], $emailTemplate->message)
                : "Dear $name,<br><br>Your account has been created.<br>Email: $login<br>Password: $password";

            $this->CommunicationService->sendEmail('staff', $user->id, $user->email, $subject, $body);
            $this->CommunicationService->sendSMS('staff', null, $request->phone_number, $msg);

            DB::commit();

            return redirect()->route('staff.index')->with('success', 'Staff created and credentials sent.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating staff: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        $staff = Staff::findOrFail($id);
        $user = $staff->user;
        return view('staff.edit', compact('staff', 'user'));
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $staff = $user->staff;

        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone_number' => ['required', 'regex:/^\+254\d{9}$/'],
            'id_number' => 'required',
            'date_of_birth' => 'required|date',
        ]);

        $user->email = $request->email;
        $user->save();

        $staff->update($request->only([
            'first_name', 'middle_name', 'last_name',
            'phone_number', 'id_number', 'date_of_birth',
            'gender', 'marital_status', 'address',
            'emergency_contact_name', 'emergency_contact_phone'
        ]));

        return redirect()->route('staff.index')->with('success', 'Staff updated successfully!');
    }

    public function archive($id)
    {
        Staff::where('id', $id)->update(['status' => 'archived']);
        return redirect()->route('staff.index')->with('success', 'Staff archived.');
    }

    public function restore($id)
    {
        Staff::where('id', $id)->update(['status' => 'active']);
        return redirect()->route('staff.index')->with('success', 'Staff restored.');
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
