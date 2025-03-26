<?php

namespace App\Http\Controllers;

use App\Models\Staff;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Services\SMSService;
use App\Mail\StaffWelcomeMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class StaffController extends Controller
{
    protected $smsService;

    public function __construct(SMSService $smsService)
    {
        $this->smsService = $smsService;
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

            // Create staff data
            $staffData = $request->only([
                'first_name', 'middle_name', 'last_name', 'email', 'phone_number',
                'id_number', 'date_of_birth', 'gender', 'marital_status', 'address',
                'emergency_contact_name', 'emergency_contact_phone'
            ]);
            $staffData['user_id'] = $user->id;
            $staffData['role'] = 'staff'; // Or whatever default you want
            $staffData['password'] = Hash::make($password); // Even if not used directly, required for DB
            $staffData['status'] = 'active'; // Optional fallback default
            
            Staff::create($staffData);
            

            // Send Email
            try {
                Mail::to($user->email)->send(new StaffWelcomeMail($user, $password));

            } catch (\Exception $e) {
                Log::error('Email send failed: ' . $e->getMessage());
            }

            // Send SMS
            if (!empty($request->phone_number)) {
                $message = "Welcome {$user->name}. Email: {$user->email}, Password: {$password}. Please log in and change it.";
                try {
                    $this->smsService->sendSMS($request->phone_number, $message);
                } catch (\Exception $e) {
                    Log::error('SMS send failed: ' . $e->getMessage());
                }
            }

            return redirect()->route('staff.index')->with('success', 'Staff created and credentials sent.');
        } catch (\Exception $e) {
            Log::error('Error creating staff: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Something went wrong while creating the staff.');
        }
    }

    public function create()
    {
        $roles = Role::all();
        return view('staff.create', compact('roles'));
    }

    public function index()
    {
        $staff = Staff::all();
        return view('staff.index', compact('staff'));
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
