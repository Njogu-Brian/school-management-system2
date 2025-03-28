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
    // Fixing the query using the relationship with classrooms
    $classCounts = \App\Models\Attendance::whereDate('date', today())
        ->where('is_present', true)
        ->join('students', 'attendance.student_id', '=', 'students.id')
        ->join('classrooms', 'students.classroom_id', '=', 'classrooms.id')
        ->select('classrooms.name as class', DB::raw('COUNT(*) as count'))
        ->groupBy('classrooms.name')
        ->get();

    return view('notify-kitchen', compact('classCounts'));
}

public function notifyKitchen(Request $request)
{
    // Fixing the query using the relationship with classrooms
    $classCounts = \App\Models\Attendance::whereDate('date', today())
        ->where('is_present', true)
        ->join('students', 'attendance.student_id', '=', 'students.id')
        ->join('classrooms', 'students.classroom_id', '=', 'classrooms.id')
        ->select('classrooms.name as class', DB::raw('COUNT(*) as count'))
        ->groupBy('classrooms.name')
        ->get();

    // Prepare the message for the kitchen team
    $message = "Daily Attendance Summary:\n";
    foreach ($classCounts as $classCount) {
        $message .= "Class {$classCount->class}: {$classCount->count} students present\n";
    }

    // Phone number of the kitchen team (replace with actual number)
    $phoneNumber = '254708225397';

    // Send the SMS using the SMS service
    try {
        $response = $this->smsService->sendSMS($phoneNumber, $message);
        Log::info('SMS Response:', ['response' => $response]);

        return redirect()->route('dashboard')->with('success', 'Kitchen notified successfully.');
    } catch (\Exception $e) {
        Log::error('SMS Sending Failed:', ['error' => $e->getMessage()]);
        return redirect()->back()->with('error', 'Failed to notify the kitchen: ' . $e->getMessage());
    }
}

}