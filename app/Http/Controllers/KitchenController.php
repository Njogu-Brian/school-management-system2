<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SMSService; // Import the SMS service
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;

class KitchenController extends Controller
{
    protected $smsService;

    public function __construct(SMSService $smsService)
    {
        $this->smsService = $smsService; // Inject the SMS service
    }

    public function showForm()
    {
        // Fetch the number of present students per class
        $classCounts = \App\Models\Attendance::whereDate('date', today())
            ->where('is_present', true)
            ->join('students', 'attendance.student_id', '=', 'students.id')
            ->select('students.class', \Illuminate\Support\Facades\DB::raw('COUNT(*) as count'))
            ->groupBy('students.class')
            ->get();

        return view('notify-kitchen', compact('classCounts'));
    }

    public function notifyKitchen(Request $request)
    {
        // Fetch the number of present students per class
        $classCounts = \App\Models\Attendance::whereDate('date', today())
            ->where('is_present', true)
            ->join('students', 'attendance.student_id', '=', 'students.id')
            ->select('students.class', \Illuminate\Support\Facades\DB::raw('COUNT(*) as count'))
            ->groupBy('students.class')
            ->get();

        // Prepare the message for the kitchen team
        $message = "Daily Attendance Summary:\n";
        foreach ($classCounts as $classCount) {
            $message .= "Class {$classCount->class}: {$classCount->count} students present\n";
        }

        // Phone number of the kitchen team (replace with the actual phone number)
        $phoneNumber = '254708225397'; // Replace with the actual phone number

        // Send the SMS using the SMS service
        try {
            $response = $this->smsService->sendSMS($phoneNumber, $message);

            // Convert response to array if it's a JSON string
            if (is_string($response)) {
                $response = json_decode($response, true);
            }

            // Log the response for debugging purposes
            \Illuminate\Support\Facades\Log::info('SMS Response:', ['response' => $response]);

            // Redirect back with a success message
            return redirect()->route('dashboard')->with('success', 'Kitchen notified successfully.');
        } catch (\Exception $e) {
            // Log the error and redirect back with an error message
            \Illuminate\Support\Facades\Log::error('SMS Sending Failed:', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Failed to notify the kitchen: ' . $e->getMessage());
        }
    }
}