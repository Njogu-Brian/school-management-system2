<?php

namespace App\Http\Controllers;

use App\Models\Staff;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Services\CommunicationService;
use Illuminate\Support\Facades\Log;
use App\Models\SMSTemplate;
use App\Models\EmailTemplate;

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
        $roles = Role::all();
        return view('staff.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email|unique:staff,email',
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,id',
        ]);

        try {
            $password = Str::random(10);

            // Create user
            $user = User::create([
                'name' => $request->first_name . ' ' . $request->last_name,
                'email' => $request->email,
                'password' => Hash::make($password),
                'must_change_password' => true,
            ]);

            // Assign roles
            $user->roles()->sync($request->roles);

            // Create staff record
            $staffData = $request->only([
                'first_name', 'middle_name', 'last_name', 'email', 'phone_number',
                'id_number', 'date_of_birth', 'gender', 'marital_status', 'address',
                'emergency_contact_name', 'emergency_contact_phone'
            ]);
            $staffData['user_id'] = $user->id;
            $staffData['status'] = 'active';

            Staff::create($staffData);

            // ✅ Load templates
            $smsTemplate = SMSTemplate::where('code', 'welcome_staff')->first();
            $emailTemplate = EmailTemplate::where('code', 'welcome_staff')->first();

            // ✅ Format message
            $name = $user->name;
            $login = $user->email;
            $msg = $smsTemplate ? str_replace(['{name}', '{login}', '{password}'], [$name, $login, $password], $smsTemplate->message) : 
                   "Welcome $name! Your login: $login and password: $password";

            $subject = $emailTemplate ? $emailTemplate->title : "Welcome to Royal Kings School";
            $body = $emailTemplate ? str_replace(['{name}', '{login}', '{password}'], [$name, $login, $password], $emailTemplate->message) : 
                    "Dear $name,<br><br>Your account has been created.<br>Email: $login<br>Password: $password";

            // ✅ Send both email + SMS
            $this->CommunicationService->sendEmail(
                'staff',
                $user->id,
                $user->email,
                $subject,
                $body
            );
            

            $this->CommunicationService->sendSMS(
                recipientType: 'staff',
                recipientId: null,
                phone: $request->phone_number,
                message: $msg
            );

            return redirect()->route('staff.index')->with('success', 'Staff created and credentials sent.');
        } catch (\Exception $e) {
            Log::error('Error creating staff: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Something went wrong while creating the staff.');
        }
    }

    public function edit($id)
    {
        $staff = Staff::findOrFail($id);
        $roles = Role::all();
        $user = $staff->user;

        return view('staff.edit', compact('staff', 'roles', 'user'));
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $staff = $user->staff;

        $request->validate([
            'email' => 'required|email|unique:users,email,' . $id,
            'first_name' => 'required',
            'last_name' => 'required',
            'roles' => 'required|array',
        ]);

        // Update user info
        $user->email = $request->email;
        $user->save();

        // Update staff bio
        $staff->update([
            'first_name' => $request->first_name,
            'middle_name' => $request->middle_name,
            'last_name' => $request->last_name,
            'phone_number' => $request->phone_number,
            'id_number' => $request->id_number,
            'date_of_birth' => $request->date_of_birth,
            'gender' => $request->gender,
            'marital_status' => $request->marital_status,
            'address' => $request->address,
            'emergency_contact_name' => $request->emergency_contact_name,
            'emergency_contact_phone' => $request->emergency_contact_phone,
        ]);

        // Sync roles
        $user->roles()->sync($request->roles);

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
}
